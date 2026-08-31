<?php

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\actingAs;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('public');
});

it('stores uploads under an extension derived from the file contents', function () {
    // A genuine PNG carrying an .html name. Laravel blocks .php uploads itself,
    // but not this: the public disk is web served, so storing the file under the
    // client-supplied extension would serve attacker HTML from our own origin.
    $path = tempnam(sys_get_temp_dir(), 'upload');
    file_put_contents($path, base64_decode(
        'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg=='
    ));

    $response = actingAs(User::factory()->create())
        ->post('/admin/upload/image', [
            'image' => new UploadedFile($path, 'harmless.html', null, null, true),
        ]);

    $response->assertOk()->assertJsonPath('success', 1);

    expect($response->json('file.url'))->toEndWith('.png');
});

it('refuses to fetch images from private network addresses', function () {
    Http::fake();

    foreach (['http://127.0.0.1/logo.png', 'http://localhost/logo.png', 'http://169.254.169.254/latest/meta-data'] as $url) {
        actingAs(User::factory()->create())
            ->postJson('/admin/upload/image-by-url', ['url' => $url])
            ->assertStatus(422)
            ->assertJsonPath('success', 0);
    }

    Http::assertNothingSent();
});

it('refuses non-http schemes', function () {
    actingAs(User::factory()->create())
        ->postJson('/admin/upload/image-by-url', ['url' => 'file:///etc/passwd'])
        ->assertStatus(422);
});

it('rejects a remote response that is not an image', function () {
    Http::fake(['*' => Http::response('<?php echo "hi";', 200, ['Content-Type' => 'image/png'])]);

    actingAs(User::factory()->create())
        ->postJson('/admin/upload/image-by-url', ['url' => 'https://example.com/evil.png'])
        ->assertStatus(422)
        ->assertJsonPath('success', 0);

    expect(Storage::disk('public')->allFiles())->toBeEmpty();
});

it('requires authentication for uploads', function () {
    $this->post('/admin/upload/image', [
        'image' => UploadedFile::fake()->image('a.png'),
    ])->assertRedirect('/login');
});
