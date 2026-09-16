<?php

namespace App\Mcp\Tools;

use App\Models\Media;
use App\Services\InteractiveAssets;
use App\Services\InteractiveGraphics;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Illuminate\Support\Str;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Store an interactive HTML graphic and return the URL to embed it with add_interactive_block. The graphic runs on an opaque origin (CSP sandbox), so it must not use cookies, localStorage or requests to the app - canvas, SVG, WebGL, CSS animation and inline JS are fine, and a library too large to inline, such as three.js, may come from a pinned CDN version through an importmap. For a 3D graphic, pass the ids of .glb models from the media library in models (list_media with type model finds them): each is copied in beside the graphic under its name, and the HTML loads it by that name as a relative path, e.g. loader.load("wuerfel.glb"). The user uploads models in the web app under Medien, "Medien hochladen"; this server takes no uploads. To make the embed size itself, post {type:"motionbase:resize", height} to window.parent; see /design.')]
class CreateInteractive extends Tool
{
    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'html' => ['required', 'string', 'max:5242880'],
            'models' => ['array', 'max:'.InteractiveAssets::MAX_FILES],
            'models.*' => ['integer'],
        ]);

        if (! $request->user()) {
            return Response::error('Not authenticated.');
        }

        $ids = array_values(array_unique($validated['models'] ?? []));
        $models = Media::whereIn('id', $ids)->where('type', 'model')->get()->keyBy('id');

        // An id that is not a model - or not there at all - would otherwise go
        // live as a graphic whose model 404s.
        if ($missing = array_diff($ids, $models->keys()->all())) {
            return Response::error('No 3D model with id '.implode(', ', $missing).' in the media library. list_media with type model shows the ones there are.');
        }

        $names = $models->pluck('original_filename')->map(fn ($name) => strtolower($name));

        if ($names->duplicates()->isNotEmpty()) {
            return Response::error('Two of the models share the name '.$names->duplicates()->first().', so the graphic could not tell them apart. Rename one in the web app and upload it again.');
        }

        if ($models->sum('size') > InteractiveAssets::MAX_TOTAL_BYTES) {
            return Response::error('The models add up to more than 60 MB.');
        }

        $media = app(InteractiveGraphics::class)->store(
            $validated['html'],
            Str::slug($validated['name']).'.html',
            $models->values()->map(fn (Media $model) => [
                'name' => $model->original_filename,
                'extension' => 'glb',
                'mime' => 'model/gltf-binary',
                'size' => $model->size,
                'source' => $model->path,
            ])->all(),
        );

        $url = $media->url;

        return Response::json([
            'media_id' => $media->id,
            'url' => $url,
            'files' => $models->pluck('original_filename')->values()->all(),
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
            'html' => $schema->string()->description('The complete HTML document. CSS and JS inline; models passed in models are loaded by their name as a relative path.')->required(),
            'models' => $schema->array()
                ->description('Optional ids of .glb models from the media library (list_media type model). Each is copied in beside the graphic under the name list_media shows.')
                ->items($schema->integer()),
        ];
    }
}
