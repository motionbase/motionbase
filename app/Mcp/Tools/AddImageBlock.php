<?php

namespace App\Mcp\Tools;

use App\Mcp\Concerns\InsertsBlocks;
use App\Mcp\Concerns\ResolvesOwnedContent;
use App\Models\Media;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Place an image from the media library in a section. Find its id with list_media; uploading is done in the web editor.')]
class AddImageBlock extends Tool
{
    use InsertsBlocks, ResolvesOwnedContent;

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'section_id' => ['required', 'integer'],
            'media_id' => ['required', 'integer'],
            'caption' => ['string', 'max:500'],
            'with_border' => ['boolean'],
            'with_background' => ['boolean'],
            'stretched' => ['boolean'],
            'position' => ['integer', 'min:0'],
        ]);

        $section = $this->findSection($request->user(), $validated['section_id']);

        if (! $section) {
            return Response::error("No section with id {$validated['section_id']} owned by you.");
        }

        $media = Media::where('type', 'image')->find($validated['media_id']);

        if (! $media) {
            return Response::error("No image with id {$validated['media_id']} in the media library.");
        }

        return $this->insertBlock($section, ['type' => 'image', 'data' => [
            'url' => $media->url,
            'caption' => $validated['caption'] ?? $media->alt ?? '',
            'withBorder' => $validated['with_border'] ?? false,
            'withBackground' => $validated['with_background'] ?? false,
            'stretched' => $validated['stretched'] ?? false,
        ]], $validated['position'] ?? null);
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'section_id' => $schema->integer()->description('Section to add the image to.')->required(),
            'media_id' => $schema->integer()->description('Id from list_media, type image.')->required(),
            'caption' => $schema->string()->description('Caption below the image. Defaults to the file\'s alt text.'),
            'with_border' => $schema->boolean()->description('Draw a border around the image.'),
            'with_background' => $schema->boolean()->description('Place the image on a tinted panel.'),
            'stretched' => $schema->boolean()->description('Let the image run past the text column.'),
            'position' => $this->positionSchema($schema),
        ];
    }
}
