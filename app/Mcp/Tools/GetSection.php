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

#[Description('Read one section as Markdown. Rich blocks (interactive graphics, quizzes, images) appear as "> [...]" placeholders and are listed separately under rich_blocks; they cannot be edited through Markdown.')]
class GetSection extends Tool
{
    use ResolvesOwnedContent;

    public function handle(Request $request): Response
    {
        ['section_id' => $sectionId] = $request->validate([
            'section_id' => ['required', 'integer'],
        ]);

        $section = $this->findSection($request->user(), $sectionId);

        if (! $section) {
            return Response::error("No section with id {$sectionId} owned by you.");
        }

        $blocks = $section->content['blocks'] ?? [];
        $editable = ['header', 'paragraph', 'list', 'code'];

        return Response::json([
            'id' => $section->id,
            'title' => $section->title,
            'slug' => $section->slug,
            'is_published' => $section->is_published,
            'chapter' => ['id' => $section->chapter->id, 'title' => $section->chapter->title],
            'markdown' => MarkdownBlocks::toMarkdown($blocks),
            'rich_blocks' => collect($blocks)
                ->reject(fn (array $block) => in_array($block['type'] ?? '', $editable, true))
                ->map(fn (array $block, int $index) => ['index' => $index, 'type' => $block['type'] ?? '?'])
                ->values(),
        ]);
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'section_id' => $schema->integer()
                ->description('Id of the section, as returned by get_topic.')
                ->required(),
        ];
    }
}
