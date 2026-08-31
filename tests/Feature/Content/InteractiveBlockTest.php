<?php

use App\Models\Media;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\post;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('local');
    Storage::fake('public');
});

function uploadGraphic(string $name = 'graphic.html', string $body = '<!DOCTYPE html><p>hi</p>')
{
    $path = tempnam(sys_get_temp_dir(), 'interactive');
    file_put_contents($path, $body);

    return actingAs(User::factory()->create())
        ->post('/admin/upload/interactive', [
            'interactive' => new UploadedFile($path, $name, null, null, true),
        ]);
}

it('keeps uploaded graphics off the web served public disk', function () {
    $response = uploadGraphic();

    $response->assertOk()->assertJsonPath('success', 1);

    $media = Media::firstOrFail();

    expect($media->type)->toBe('interactive')
        ->and(Storage::disk('local')->exists($media->path))->toBeTrue()
        // The public disk is served directly by the web server, which would
        // hand out the graphic on our own origin without any CSP.
        ->and(Storage::disk('public')->exists($media->path))->toBeFalse();

    expect($response->json('file.url'))->toBe("/interactive/{$media->id}");
});

it('serves graphics on an opaque origin', function () {
    uploadGraphic();
    $media = Media::firstOrFail();

    $response = get("/interactive/{$media->id}");

    $response->assertOk();

    // Without allow-same-origin the document cannot reach the app's cookies,
    // localStorage or same-origin requests - not even on direct navigation.
    expect($response->headers->get('Content-Security-Policy'))->toBe('sandbox allow-scripts');
    expect($response->headers->get('X-Content-Type-Options'))->toBe('nosniff');
    expect($response->headers->get('Content-Type'))->toStartWith('text/html');
});

it('revalidates instead of caching blind, so a replaced graphic shows up', function () {
    uploadGraphic();
    $media = Media::firstOrFail();

    $first = get("/interactive/{$media->id}")->assertOk();
    $etag = $first->headers->get('ETag');

    expect($etag)->not->toBeNull()
        ->and($first->headers->get('Cache-Control'))->toContain('must-revalidate')
        ->and($first->headers->get('Cache-Control'))->toContain('max-age=0');

    // Unchanged: cheap 304 instead of shipping the file again.
    get("/interactive/{$media->id}", ['If-None-Match' => $etag])->assertStatus(304);

    // Replaced in place: the author must see it on the next load, not in an hour.
    Storage::disk('local')->put($media->path, '<!DOCTYPE html><p>v2</p>');

    $second = get("/interactive/{$media->id}", ['If-None-Match' => $etag]);
    $second->assertOk();
    expect($second->headers->get('ETag'))->not->toBe($etag);
});

it('serves graphics to guests so embeds keep working', function () {
    uploadGraphic();
    $media = Media::firstOrFail();

    get("/interactive/{$media->id}")->assertOk();
});

it('refuses non-html uploads', function () {
    uploadGraphic('graphic.svg')
        ->assertStatus(422)
        ->assertJsonPath('success', 0);

    expect(Media::count())->toBe(0);
});

it('requires authentication to upload', function () {
    $path = tempnam(sys_get_temp_dir(), 'interactive');
    file_put_contents($path, '<!DOCTYPE html><p>hi</p>');

    post('/admin/upload/interactive', [
        'interactive' => new UploadedFile($path, 'graphic.html', null, null, true),
    ])->assertRedirect('/login');

    expect(Media::count())->toBe(0);
});

it('does not serve media of other types through the interactive route', function () {
    $media = Media::create([
        'filename' => 'x.png',
        'original_filename' => 'x.png',
        'path' => 'editor-images/x.png',
        'url' => '/storage/editor-images/x.png',
        'mime_type' => 'image/png',
        'type' => 'image',
        'size' => 10,
    ]);

    get("/interactive/{$media->id}")->assertNotFound();
});

it('renders a sandboxed iframe in embed and lti views', function () {
    $html = view('lti.partials.content-blocks', ['blocks' => [
        ['type' => 'interactive', 'data' => [
            'url' => '/interactive/7',
            'caption' => 'Easing Simulator',
            'height' => 620,
        ]],
    ]])->render();

    expect($html)
        ->toContain('src="/interactive/7"')
        ->toContain('sandbox="allow-scripts"')
        ->toContain('height: 620px')
        ->toContain('class="interactive-frame')
        ->toContain('Easing Simulator')
        // allow-same-origin would defeat the whole point of the sandbox
        ->not->toContain('allow-same-origin');
});

it('clamps absurd heights and drops non-http urls', function () {
    $render = fn (array $data) => view('lti.partials.content-blocks', [
        'blocks' => [['type' => 'interactive', 'data' => $data]],
    ])->render();

    expect($render(['url' => '/interactive/7', 'height' => 99999]))->toContain('height: 5000px');
    expect($render(['url' => '/interactive/7', 'height' => 0]))->toContain('height: 480px');

    foreach (['javascript:alert(1)', 'data:text/html,<script>alert(1)</script>', ''] as $url) {
        expect($render(['url' => $url]))->not->toContain('<iframe');
    }
});
