<?php

use App\Models\Media;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\post;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('local');
    Storage::fake('public');
});

function libraryGlb(array $json = ['asset' => ['version' => '2.0']]): string
{
    $chunk = json_encode($json, JSON_UNESCAPED_SLASHES);
    $chunk .= str_repeat(' ', (4 - strlen($chunk) % 4) % 4);
    $body = pack('V', strlen($chunk)).'JSON'.$chunk;

    return 'glTF'.pack('V', 2).pack('V', 12 + strlen($body)).$body;
}

function uploadModel(string $name, string $contents)
{
    $path = tempnam(sys_get_temp_dir(), 'model');
    file_put_contents($path, $contents);

    return actingAs(User::factory()->create())->post('/admin/upload/model', [
        'model' => new UploadedFile($path, $name, null, null, true),
    ]);
}

it('puts a model in the media library, off the public disk and under a name a graphic can load', function () {
    $response = uploadModel('Mein Würfel (final).glb', libraryGlb());

    $response->assertOk()->assertJsonPath('success', 1)->assertJsonPath('file.name', 'Mein-Wuerfel-final.glb');

    $media = Media::firstOrFail();

    expect($media->type)->toBe('model')
        ->and($media->original_filename)->toBe('Mein-Wuerfel-final.glb')
        // Nothing serves it directly - it reaches learners only inside a graphic.
        ->and($media->url)->toBe('')
        ->and(Storage::disk('local')->exists($media->path))->toBeTrue()
        ->and(Storage::disk('public')->allFiles())->toBeEmpty();
});

it('refuses anything that is not a self-contained glb', function () {
    uploadModel('model.glb', '<!DOCTYPE html><script>alert(1)</script>')->assertStatus(422);
    uploadModel('scene.gltf', json_encode(['asset' => ['version' => '2.0']]))->assertStatus(422);

    // Nothing is uploaded beside it, so a texture loaded as its own file would 404.
    $loose = uploadModel('model.glb', libraryGlb(['asset' => ['version' => '2.0'], 'images' => [['uri' => 'wood.png']]]));
    $loose->assertStatus(422);
    expect($loose->json('message'))->toContain('einbetten');

    uploadModel('model.glb', libraryGlb(['asset' => ['version' => '2.0'], 'images' => [['uri' => 'https://tracker.example/p.png']]]))
        ->assertStatus(422);

    expect(Media::count())->toBe(0);
});

it('requires authentication to upload a model', function () {
    $path = tempnam(sys_get_temp_dir(), 'model');
    file_put_contents($path, libraryGlb());

    post('/admin/upload/model', ['model' => new UploadedFile($path, 'model.glb', null, null, true)])
        ->assertRedirect('/login');

    expect(Media::count())->toBe(0);
});

it('removes a model from disk when it is deleted', function () {
    uploadModel('wuerfel.glb', libraryGlb())->assertOk();
    $media = Media::firstOrFail();

    actingAs(User::factory()->create(['is_admin' => true]))
        ->deleteJson("/admin/media/{$media->id}")
        ->assertOk();

    expect(Storage::disk('local')->exists($media->path))->toBeFalse()
        ->and(Media::count())->toBe(0);
});
