<?php

namespace App\Mcp\Tools;

use App\Mcp\Concerns\ResolvesOwnedContent;
use App\Mcp\Support\MarkdownBlocks;
use App\Mcp\Annotations\IsReadOnly;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[IsReadOnly]
#[Description('Read one section as Markdown. Blocks that Markdown cannot express - interactive, quiz, alert, image, youtube, lottie - appear in the body as "> [...]" placeholders and are listed in full under rich_blocks, data included, so they can be recreated after an overwrite.')]
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
        $editable = MarkdownBlocks::MARKDOWN_TYPES;

        return Response::json([
            'id' => $section->id,
            'title' => $section->title,
            'slug' => $section->slug,
            'is_published' => $section->is_published,
            'chapter' => ['id' => $section->chapter->id, 'title' => $section->chapter->title],
            'markdown' => MarkdownBlocks::toMarkdown($blocks),
            // The data comes along on purpose: these blocks are destroyed by an
            // update_section that passes markdown, and without their content
            // here there would be no way to put them back.
            'rich_blocks' => collect($blocks)
                ->map(fn (array $block, int $index) => ['index' => $index] + $block)
                ->reject(fn (array $block) => in_array($block['type'] ?? '', $editable, true))
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
