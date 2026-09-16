<?php

use App\Models\InteractiveAsset;
use App\Models\Media;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('local');
    Storage::fake('public');
});

/** A minimal but well-formed binary glTF. */
function glbBytes(array $json = ['asset' => ['version' => '2.0']], string $bin = ''): string
{
    $chunk = json_encode($json, JSON_UNESCAPED_SLASHES);
    $chunk .= str_repeat(' ', (4 - strlen($chunk) % 4) % 4);
    $body = pack('V', strlen($chunk)).'JSON'.$chunk;

    if ($bin !== '') {
        $bin .= str_repeat("\0", (4 - strlen($bin) % 4) % 4);
        $body .= pack('V', strlen($bin))."BIN\0".$bin;
    }

    return 'glTF'.pack('V', 2).pack('V', 12 + strlen($body)).$body;
}

function assetFile(string $name, string $contents): UploadedFile
{
    $path = tempnam(sys_get_temp_dir(), 'asset');
    file_put_contents($path, $contents);

    return new UploadedFile($path, $name, null, null, true);
}

function pngBytes(): string
{
    return base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==');
}

function uploadWithAssets(array $assets, ?int $announced = null)
{
    return actingAs(User::factory()->create())->post('/admin/upload/interactive', [
        'interactive' => assetFile('index.html', '<!DOCTYPE html><p>3D</p>'),
        'assets' => $assets,
        'asset_count' => $announced ?? count($assets),
    ]);
}

function servedBytes($response): string
{
    return file_get_contents($response->baseResponse->getFile()->getPathname());
}

it('serves a graphic and the model uploaded with it side by side', function () {
    $model = glbBytes();

    $response = uploadWithAssets([assetFile('model.glb', $model)]);

    $response->assertOk()->assertJsonPath('success', 1)->assertJsonPath('file.assets', ['model.glb']);

    $media = Media::firstOrFail();

    // One level down, so a relative "model.glb" in the document lands here.
    expect($response->json('file.url'))->toBe("/interactive/{$media->id}/index.html");

    get("/interactive/{$media->id}/index.html")
        ->assertOk()
        ->assertHeader('Content-Security-Policy', 'sandbox allow-scripts');

    $file = get("/interactive/{$media->id}/model.glb")->assertOk();

    expect($file->headers->get('Content-Type'))->toBe('model/gltf-binary')
        ->and($file->headers->get('X-Content-Type-Options'))->toBe('nosniff')
        // The graphic runs on an opaque origin: without this it cannot load the file.
        ->and($file->headers->get('Access-Control-Allow-Origin'))->toBe('*')
        ->and(servedBytes($file))->toBe($model);

    // Opened directly, nothing in it may run on our origin.
    expect($file->headers->get('Content-Security-Policy'))
        ->toContain('sandbox')
        ->not->toContain('allow-scripts');

    $asset = InteractiveAsset::firstOrFail();

    expect(Storage::disk('local')->exists($asset->path))->toBeTrue()
        ->and(Storage::disk('public')->allFiles())->toBeEmpty()
        // The author's name is a lookup key, never part of the path on disk.
        ->and($asset->path)->not->toContain('model.glb');
});

it('refuses a page dressed up as a model', function () {
    uploadWithAssets([assetFile('model.glb', '<!DOCTYPE html><script>alert(document.cookie)</script>')])
        ->assertStatus(422)
        ->assertJsonPath('success', 0);

    expect(Media::count())->toBe(0)
        ->and(Storage::disk('local')->allFiles())->toBeEmpty();
});

it('refuses a model with bytes riding along past its declared length', function () {
    uploadWithAssets([assetFile('model.glb', glbBytes().'<!DOCTYPE html><script>alert(1)</script>')])
        ->assertStatus(422);

    expect(Media::count())->toBe(0);
});

it('refuses a model that loads textures from another server', function () {
    $model = glbBytes([
        'asset' => ['version' => '2.0'],
        'images' => [['uri' => 'https://tracker.example/pixel.png']],
    ]);

    $response = uploadWithAssets([assetFile('model.glb', $model)]);

    $response->assertStatus(422);
    expect($response->json('message'))->toContain('von außerhalb');
});

it('accepts a gltf with its buffer and texture, and refuses one with a piece missing', function () {
    $gltf = json_encode([
        'asset' => ['version' => '2.0'],
        'buffers' => [['uri' => 'scene.bin', 'byteLength' => 4]],
        'images' => [['uri' => 'wood.png']],
    ]);

    uploadWithAssets([
        assetFile('scene.gltf', $gltf),
        assetFile('scene.bin', "\0\0\0\0"),
        assetFile('wood.png', pngBytes()),
    ])->assertOk();

    $media = Media::firstOrFail();
    get("/interactive/{$media->id}/scene.gltf")->assertOk()->assertHeader('Content-Type', 'model/gltf+json');
    get("/interactive/{$media->id}/wood.png")->assertOk()->assertHeader('Content-Type', 'image/png');

    $missing = uploadWithAssets([
        assetFile('scene.gltf', $gltf),
        assetFile('scene.bin', "\0\0\0\0"),
    ]);

    $missing->assertStatus(422);
    expect($missing->json('message'))->toContain('wood.png');
});

it('refuses file types that could run, and names that are not plain', function () {
    foreach ([
        assetFile('drawing.svg', '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>'),
        assetFile('app.js', 'alert(1)'),
        assetFile('second.html', '<!DOCTYPE html>'),
        assetFile('shell.php', '<?php echo 1;'),
        assetFile('mein modell.glb', glbBytes()),
        assetFile('.htaccess', 'Options +Indexes'),
    ] as $file) {
        uploadWithAssets([$file])->assertStatus(422);
    }

    expect(Media::count())->toBe(0);
});

it('refuses an image that is not one', function () {
    uploadWithAssets([assetFile('wood.png', '<!DOCTYPE html><script>alert(1)</script>')])
        ->assertStatus(422);
});

it('refuses the same file twice under different case', function () {
    uploadWithAssets([assetFile('Model.glb', glbBytes()), assetFile('model.glb', glbBytes())])
        ->assertStatus(422);
});

it('stores nothing when one file of the set is rejected', function () {
    uploadWithAssets([
        assetFile('model.glb', glbBytes()),
        assetFile('wood.png', 'not a png'),
    ])->assertStatus(422);

    expect(Media::count())->toBe(0)
        ->and(InteractiveAsset::count())->toBe(0)
        ->and(Storage::disk('local')->allFiles())->toBeEmpty();
});

it('refuses a set that arrived incomplete', function () {
    // PHP drops files past max_file_uploads silently; the editor announces how
    // many it sent so the shortfall is caught instead of going live.
    uploadWithAssets([assetFile('model.glb', glbBytes())], announced: 5)
        ->assertStatus(422);

    expect(Media::count())->toBe(0);
});

it('enforces the size limits', function () {
    uploadWithAssets([UploadedFile::fake()->create('huge.bin', 26 * 1024)])->assertStatus(422);

    uploadWithAssets([
        UploadedFile::fake()->create('a.bin', 21 * 1024),
        UploadedFile::fake()->create('b.bin', 21 * 1024),
        UploadedFile::fake()->create('c.bin', 21 * 1024),
    ])->assertStatus(422);

    expect(Media::count())->toBe(0);
});

it('does not hand out a file under any graphic but its own', function () {
    uploadWithAssets([assetFile('model.glb', glbBytes())])->assertOk();
    $owner = Media::firstOrFail();

    uploadWithAssets([assetFile('other.glb', glbBytes())])->assertOk();
    $other = Media::latest('id')->firstOrFail();

    get("/interactive/{$other->id}/model.glb")->assertNotFound();
    get("/interactive/{$owner->id}/missing.glb")->assertNotFound();

    $image = Media::create([
        'filename' => 'x.png', 'original_filename' => 'x.png', 'path' => 'editor-images/x.png',
        'url' => '/storage/editor-images/x.png', 'mime_type' => 'image/png', 'type' => 'image', 'size' => 10,
    ]);

    get("/interactive/{$image->id}/model.glb")->assertNotFound();
    get("/interactive/{$image->id}/index.html")->assertNotFound();
});

it('sends the short address of a graphic with files to where it finds them', function () {
    uploadWithAssets([assetFile('model.glb', glbBytes())])->assertOk();
    $media = Media::firstOrFail();

    // A block placed with the id pieced together - by hand or through the MCP
    // server - would otherwise load the page and 404 on the model without a trace.
    get("/interactive/{$media->id}")->assertRedirect("/interactive/{$media->id}/index.html");

    // And no loop from there.
    get("/interactive/{$media->id}/index.html")->assertOk();
});

it('keeps the files out of reach of the raw storage route', function () {
    uploadWithAssets([assetFile('model.glb', glbBytes())])->assertOk();

    $status = get('/storage/'.InteractiveAsset::firstOrFail()->path)->status();

    expect($status)->not->toBe(200);
});

it('serves files without a session cookie', function () {
    uploadWithAssets([assetFile('model.glb', glbBytes())])->assertOk();
    $media = Media::firstOrFail();

    $file = get("/interactive/{$media->id}/model.glb")->assertOk();

    expect($file->headers->getCookies())->toBeEmpty();
});

it('revalidates files so a replaced model shows up', function () {
    uploadWithAssets([assetFile('model.glb', glbBytes())])->assertOk();
    $media = Media::firstOrFail();

    $first = get("/interactive/{$media->id}/model.glb")->assertOk();

    expect($first->headers->get('Cache-Control'))->toContain('must-revalidate')
        ->and($first->headers->get('Last-Modified'))->not->toBeNull();

    get("/interactive/{$media->id}/model.glb", ['If-Modified-Since' => $first->headers->get('Last-Modified')])
        ->assertStatus(304);
});

it('removes the graphic and its files from disk when it is deleted', function () {
    uploadWithAssets([assetFile('model.glb', glbBytes()), assetFile('wood.png', pngBytes())])->assertOk();
    $media = Media::firstOrFail();

    // Another graphic in the same folder tree must survive.
    uploadWithAssets([assetFile('keep.glb', glbBytes())])->assertOk();
    $kept = Media::latest('id')->firstOrFail();

    actingAs(User::factory()->create(['is_admin' => true]))
        ->deleteJson("/admin/media/{$media->id}")
        ->assertOk();

    expect(Media::find($media->id))->toBeNull()
        ->and(InteractiveAsset::where('media_id', $media->id)->count())->toBe(0)
        ->and(Storage::disk('local')->exists($media->path))->toBeFalse();

    foreach (Storage::disk('local')->allFiles() as $path) {
        expect($path)->not->toContain(pathinfo($media->filename, PATHINFO_FILENAME));
    }

    expect(Storage::disk('local')->exists($kept->path))->toBeTrue()
        ->and(Storage::disk('local')->exists($kept->assets()->firstOrFail()->path))->toBeTrue();
});
