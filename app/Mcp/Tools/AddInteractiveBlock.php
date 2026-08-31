<?php

namespace App\Mcp\Tools;

use App\Mcp\Concerns\ResolvesOwnedContent;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Append an interactive graphic to a section, or insert it at a given block position. Use the url returned by create_interactive, or any https URL.')]
class AddInteractiveBlock extends Tool
{
    use ResolvesOwnedContent;

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'section_id' => ['required', 'integer'],
            'url' => ['required', 'string', 'max:2048'],
            'caption' => ['string', 'max:500'],
            'height' => ['integer', 'min:120', 'max:5000'],
            'position' => ['integer', 'min:0'],
        ]);

        // Same rule the renderers enforce: only app paths and http(s) become an iframe src.
        if (! str_starts_with($validated['url'], '/') && ! preg_match('#^https?://#i', $validated['url'])) {
            return Response::error('url must be an app-relative path (/interactive/7) or an http(s) URL.');
        }

        $section = $this->findSection($request->user(), $validated['section_id']);

        if (! $section) {
            return Response::error("No section with id {$validated['section_id']} owned by you.");
        }

        $content = $section->content ?? [];
        $blocks = $content['blocks'] ?? [];

        $block = ['type' => 'interactive', 'data' => [
            'url' => $validated['url'],
            'caption' => $validated['caption'] ?? '',
            'height' => $validated['height'] ?? 480,
        ]];

        $position = min($validated['position'] ?? count($blocks), count($blocks));
        array_splice($blocks, $position, 0, [$block]);

        $section->content = [
            'time' => now()->getTimestampMs(),
            'blocks' => $blocks,
            'version' => $content['version'] ?? '2.31.0',
        ];
        $section->save();

        return Response::json([
            'section_id' => $section->id,
            'inserted_at' => $position,
            'blocks' => count($blocks),
        ]);
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'section_id' => $schema->integer()->description('Section to add the graphic to.')->required(),
            'url' => $schema->string()->description('Graphic URL, e.g. /interactive/7 from create_interactive.')->required(),
            'caption' => $schema->string()->description('Optional caption below the graphic. Leave empty if the graphic has its own heading.'),
            'height' => $schema->integer()->description('Fallback height in px (120-5000, default 480). Overridden once the graphic reports its own height.'),
            'position' => $schema->integer()->description('Block index to insert at. Omit to append at the end.'),
        ];
    }
}
