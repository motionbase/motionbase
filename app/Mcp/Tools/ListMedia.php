<?php

namespace App\Mcp\Tools;

use App\Mcp\Annotations\IsReadOnly;
use App\Models\Media;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[IsReadOnly]
#[Description('List files in the media library so they can be placed with add_image_block, add_lottie_block or add_interactive_block, or - for type model - passed to create_interactive in models. A model is loaded in the graphic by the name shown here. For an interactive graphic, files names the models and textures it carries. Files are uploaded in the web app; this server does not accept uploads.')]
class ListMedia extends Tool
{
    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'type' => ['string', 'in:image,lottie,interactive,model'],
            'search' => ['string', 'max:100'],
            'limit' => ['integer', 'min:1', 'max:100'],
        ]);

        if (! $request->user()) {
            return Response::error('Not authenticated.');
        }

        $media = Media::query()
            ->with('assets')
            ->when($validated['type'] ?? null, fn ($query, $type) => $query->where('type', $type))
            ->when($validated['search'] ?? null, fn ($query, $search) => $query
                ->where(fn ($q) => $q->where('original_filename', 'like', "%{$search}%")
                    ->orWhere('alt', 'like', "%{$search}%")))
            ->latest()
            ->limit($validated['limit'] ?? 30)
            ->get()
            ->map(function (Media $file) {
                $entry = [
                    'id' => $file->id,
                    'name' => $file->original_filename,
                    'type' => $file->type,
                    'url' => $file->url,
                    'alt' => $file->alt,
                    'size' => $file->human_size,
                ];

                // Only graphics carry files; on anything else the key would just be noise
                if ($file->type === 'interactive') {
                    $entry['files'] = $file->assets->pluck('name')->all();
                }

                return $entry;
            });

        return Response::json(['media' => $media]);
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'type' => $schema->string()->description('Filter by image, lottie, interactive or model (3D models for create_interactive).'),
            'search' => $schema->string()->description('Match against filename or alt text.'),
            'limit' => $schema->integer()->description('Defaults to 30, at most 100.'),
        ];
    }
}
