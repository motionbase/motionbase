<?php

namespace App\Mcp\Tools;

use App\Mcp\Concerns\ResolvesOwnedContent;
use App\Models\Chapter;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Create a new chapter at the end of a course.')]
class CreateChapter extends Tool
{
    use ResolvesOwnedContent;

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'topic_id' => ['required', 'integer'],
            'title' => ['required', 'string', 'max:255'],
            'is_published' => ['boolean'],
        ]);

        $topic = $this->findTopic($request->user(), $validated['topic_id']);

        if (! $topic) {
            return Response::error("No course with id {$validated['topic_id']} owned by you.");
        }

        $chapter = new Chapter([
            'topic_id' => $topic->id,
            'title' => $validated['title'],
            'is_published' => $validated['is_published'] ?? true,
            'sort_order' => (int) $topic->chapters()->max('sort_order') + 1,
        ]);

        $chapter->slug = $chapter->uniqueSlug($validated['title']);
        $chapter->save();

        return Response::json([
            'id' => $chapter->id,
            'title' => $chapter->title,
            'slug' => $chapter->slug,
            'sort_order' => $chapter->sort_order,
        ]);
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'topic_id' => $schema->integer()->description('Course to add the chapter to.')->required(),
            'title' => $schema->string()->description('Chapter title.')->required(),
            'is_published' => $schema->boolean()->description('Defaults to true.'),
        ];
    }
}
