<?php

namespace App\Mcp\Tools;

use App\Mcp\Concerns\ResolvesOwnedContent;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Move a block within a section. Works on every block type, so an interactive graphic or a quiz can be reordered without being rebuilt.')]
class MoveBlock extends Tool
{
    use ResolvesOwnedContent;

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'section_id' => ['required', 'integer'],
            'from' => ['required', 'integer', 'min:0'],
            'to' => ['required', 'integer', 'min:0'],
        ]);

        $section = $this->findSection($request->user(), $validated['section_id']);

        if (! $section) {
            return Response::error("No section with id {$validated['section_id']} owned by you.");
        }

        $content = $section->content ?? [];
        $blocks = $content['blocks'] ?? [];
        ['from' => $from, 'to' => $to] = $validated;

        if (! array_key_exists($from, $blocks)) {
            return Response::error(
                "Section {$section->id} has ".count($blocks)." blocks, so index {$from} does not exist."
            );
        }

        $to = min($to, count($blocks) - 1);
        $moved = array_splice($blocks, $from, 1)[0];
        array_splice($blocks, $to, 0, [$moved]);

        $section->content = [
            'time' => now()->getTimestampMs(),
            'blocks' => $blocks,
            'version' => $content['version'] ?? '2.31.0',
        ];
        $section->save();

        return Response::json([
            'section_id' => $section->id,
            'moved' => $moved['type'] ?? '?',
            'from' => $from,
            'to' => $to,
            'order' => collect($blocks)->pluck('type'),
        ]);
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'section_id' => $schema->integer()->description('Section to reorder.')->required(),
            'from' => $schema->integer()->description('Current index of the block.')->required(),
            'to' => $schema->integer()->description('Index it should end up at. Clamped to the last position.')->required(),
        ];
    }
}
