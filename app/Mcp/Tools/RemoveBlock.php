<?php

namespace App\Mcp\Tools;

use App\Mcp\Concerns\ResolvesOwnedContent;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Remove a single block from a section by its index. Works on every block type, including the ones that cannot be written - this is the way to delete an alert, image or lottie without overwriting the whole body.')]
class RemoveBlock extends Tool
{
    use ResolvesOwnedContent;

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'section_id' => ['required', 'integer'],
            'index' => ['required', 'integer', 'min:0'],
        ]);

        $section = $this->findSection($request->user(), $validated['section_id']);

        if (! $section) {
            return Response::error("No section with id {$validated['section_id']} owned by you.");
        }

        $content = $section->content ?? [];
        $blocks = $content['blocks'] ?? [];
        $index = $validated['index'];

        // Indexes come from a get_section that may be stale. Silently removing
        // the wrong block would be worse than refusing.
        if (! array_key_exists($index, $blocks)) {
            return Response::error(
                "Section {$section->id} has ".count($blocks)." blocks, so index {$index} does not exist."
            );
        }

        $removed = array_splice($blocks, $index, 1)[0];

        $section->content = [
            'time' => now()->getTimestampMs(),
            'blocks' => $blocks,
            'version' => $content['version'] ?? '2.31.0',
        ];
        $section->save();

        return Response::json([
            'section_id' => $section->id,
            'removed' => ['index' => $index] + $removed,
            'blocks' => count($blocks),
        ]);
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'section_id' => $schema->integer()->description('Section to remove from.')->required(),
            'index' => $schema->integer()->description('Zero based block index, as reported by get_section.')->required(),
        ];
    }
}
