<?php

namespace App\Mcp\Tools;

use App\Models\Media;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Store a self-contained interactive HTML graphic and return the URL to embed it with add_interactive_block. The graphic runs on an opaque origin (CSP sandbox), so it must not use cookies, localStorage or requests to the app - canvas, SVG, WebGL, CSS animation and inline JS are fine, and a library too large to inline, such as three.js, may come from a pinned CDN version through an importmap. This tool stores the HTML and nothing else. A graphic that needs a 3D model or textures cannot be finished here: write it to load them by relative path (loader.load("model.glb")), give the user the HTML, and tell them to upload it together with those files in the web editor interactive block - glb, gltf, bin, png, jpg or webp, flat file names without subfolders, and a model may only reference files uploaded with it. The upload creates the block. To make the embed size itself, post {type:"motionbase:resize", height} to window.parent; see /design.')]
class CreateInteractive extends Tool
{
    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'html' => ['required', 'string', 'max:5242880'],
        ]);

        if (! $request->user()) {
            return Response::error('Not authenticated.');
        }

        $filename = Str::uuid().'.html';
        $path = 'interactive/'.$filename;

        Storage::disk('local')->put($path, $validated['html']);

        $media = Media::create([
            'filename' => $filename,
            'original_filename' => Str::finish(Str::slug($validated['name']), '').'.html',
            'path' => $path,
            'url' => '',
            'mime_type' => 'text/html',
            'type' => 'interactive',
            'size' => strlen($validated['html']),
        ]);

        $url = route('interactive.show', $media, absolute: false);
        $media->update(['url' => $url]);

        return Response::json([
            'media_id' => $media->id,
            'url' => $url,
            'preview' => url($url),
            'size' => $media->size,
        ]);
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'name' => $schema->string()->description('Human readable name, used as the filename in the media library.')->required(),
            'html' => $schema->string()->description('The complete HTML document. CSS and JS inline. No files can be attached here - see the tool description for graphics that need a model or textures.')->required(),
        ];
    }
}
