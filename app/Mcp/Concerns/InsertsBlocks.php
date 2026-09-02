<?php

namespace App\Mcp\Concerns;

use App\Models\Section;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Response;

/**
 * Shared plumbing for the tools that place a single block.
 *
 * Every one of them needs the same three things: an optional position that is
 * clamped to the block count, a rewritten content envelope, and the same shape
 * of answer. Repeating that per tool is how the versions drift apart.
 */
trait InsertsBlocks
{
    /**
     * @param  array<string, mixed>  $block
     */
    protected function insertBlock(Section $section, array $block, ?int $position): Response
    {
        $content = $section->content ?? [];
        $blocks = $content['blocks'] ?? [];

        $position = min($position ?? count($blocks), count($blocks));
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
            'type' => $block['type'],
            'blocks' => count($blocks),
        ]);
    }

    protected function positionSchema(JsonSchema $schema): Type
    {
        return $schema->integer()
            ->description('Block index to insert at, as reported by get_section. Omit to append at the end.');
    }
}
