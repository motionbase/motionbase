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

#[Description('Store a self-contained interactive HTML graphic and return the URL to embed it with add_interactive_block. The graphic runs on an opaque origin (CSP sandbox), so it must not use cookies, localStorage or requests to the app - canvas, SVG, CSS animation and inline JS are fine. To make the embed size itself, post {type:"motionbase:resize", height} to window.parent; see resources/interactives/README.md.')]
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
            'html' => $schema->string()->description('The complete HTML document. All CSS and JS must be inline - external files are not served.')->required(),
        ];
    }
}
