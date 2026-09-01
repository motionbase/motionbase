<?php

namespace App\Mcp\Tools;

use App\Mcp\Concerns\ResolvesOwnedContent;
use App\Mcp\Support\MarkdownBlocks;
use App\Models\Section;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Create a new section (page) at the end of a chapter. Content is written as Markdown: ##/###/#### headings, paragraphs, - and 1. lists, ``` code fences, | pipe | tables | with a header row, **bold**, *italic*, `code`. Do not repeat the section title as a heading - it is already rendered above the content.')]
class CreateSection extends Tool
{
    use ResolvesOwnedContent;

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'chapter_id' => ['required', 'integer'],
            'title' => ['required', 'string', 'max:255'],
            'markdown' => ['string'],
            'is_published' => ['boolean'],
        ]);

        $chapter = $this->findChapter($request->user(), $validated['chapter_id']);

        if (! $chapter) {
            return Response::error("No chapter with id {$validated['chapter_id']} owned by you.");
        }

        $section = new Section([
            'chapter_id' => $chapter->id,
            'title' => $validated['title'],
            'is_published' => $validated['is_published'] ?? true,
            'sort_order' => (int) $chapter->sections()->max('sort_order') + 1,
            'content' => [
                'time' => now()->getTimestampMs(),
                'blocks' => MarkdownBlocks::toBlocks($validated['markdown'] ?? ''),
                'version' => '2.31.0',
            ],
        ]);

        $section->slug = $section->uniqueSlug($validated['title']);
        $section->save();

        return Response::json([
            'id' => $section->id,
            'title' => $section->title,
            'slug' => $section->slug,
            'blocks' => count($section->content['blocks']),
            'url' => url('/themen/'.$chapter->topic->slug.'/'.$chapter->slug.'/'.$section->slug),
        ]);
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'chapter_id' => $schema->integer()->description('Chapter to add the section to.')->required(),
            'title' => $schema->string()->description('Section title, shown as the page heading.')->required(),
            'markdown' => $schema->string()->description('Section body as Markdown.'),
            'is_published' => $schema->boolean()->description('Defaults to true.'),
        ];
    }
}
