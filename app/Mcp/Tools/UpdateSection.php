<?php

namespace App\Mcp\Tools;

use App\Mcp\Concerns\ResolvesOwnedContent;
use App\Mcp\Support\MarkdownBlocks;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Update a section\'s title, publish state or body. Passing markdown REPLACES the whole body. Headings, paragraphs, lists, code and tables survive; interactive, alert, quiz, image, youtube and lottie blocks are dropped - read the section first and re-add rich blocks with their own tools afterwards. Omit markdown to leave the body untouched.')]
class UpdateSection extends Tool
{
    use ResolvesOwnedContent;

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'section_id' => ['required', 'integer'],
            'title' => ['string', 'max:255'],
            'markdown' => ['string'],
            'is_published' => ['boolean'],
        ]);

        $section = $this->findSection($request->user(), $validated['section_id']);

        if (! $section) {
            return Response::error("No section with id {$validated['section_id']} owned by you.");
        }

        $droppedRichBlocks = [];

        if (array_key_exists('title', $validated)) {
            $section->title = $validated['title'];
        }

        if (array_key_exists('is_published', $validated)) {
            $section->is_published = $validated['is_published'];
        }

        if (array_key_exists('markdown', $validated)) {
            $editable = MarkdownBlocks::MARKDOWN_TYPES;

            $droppedRichBlocks = collect($section->content['blocks'] ?? [])
                ->reject(fn (array $block) => in_array($block['type'] ?? '', $editable, true))
                ->map(fn (array $block) => $block['type'] ?? '?')
                ->values()
                ->all();

            $section->content = [
                'time' => now()->getTimestampMs(),
                'blocks' => MarkdownBlocks::toBlocks($validated['markdown']),
                'version' => '2.31.0',
            ];
        }

        $section->save();

        return Response::json([
            'id' => $section->id,
            'title' => $section->title,
            'is_published' => $section->is_published,
            'blocks' => count($section->content['blocks'] ?? []),
            'dropped_rich_blocks' => $droppedRichBlocks,
        ]);
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'section_id' => $schema->integer()->description('Section to update.')->required(),
            'title' => $schema->string()->description('New title. Omit to keep.'),
            'markdown' => $schema->string()->description('New body, replacing the current one entirely. Omit to keep.'),
            'is_published' => $schema->boolean()->description('New publish state. Omit to keep.'),
        ];
    }
}
