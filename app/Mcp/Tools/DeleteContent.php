<?php

namespace App\Mcp\Tools;

use App\Mcp\Concerns\ResolvesOwnedContent;
use App\Models\Chapter;
use App\Models\Section;
use App\Models\Topic;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Illuminate\Support\Facades\DB;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Permanently delete a section, a chapter with all its sections, or a topic with everything in it. The exact title must be passed as confirmation, so the thing has to be looked at first. Deleting a whole course is not undone by a single call - prefer removing single blocks or unpublishing.')]
class DeleteContent extends Tool
{
    use ResolvesOwnedContent;

    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'kind' => ['required', 'string', 'in:section,chapter,topic'],
            'id' => ['required', 'integer'],
            'confirm_title' => ['required', 'string'],
        ]);

        $target = match ($validated['kind']) {
            'section' => $this->findSection($request->user(), $validated['id']),
            'chapter' => $this->findChapter($request->user(), $validated['id']),
            'topic' => $this->findTopic($request->user(), $validated['id']),
        };

        if (! $target) {
            return Response::error("No {$validated['kind']} with id {$validated['id']} owned by you.");
        }

        // Guards against a stale or mistyped id. An id alone is easy to get
        // wrong; the title is not something you hold by accident.
        if (trim($validated['confirm_title']) !== $target->title) {
            return Response::error(
                "confirm_title does not match. The {$validated['kind']} with id {$validated['id']} is called \"{$target->title}\"."
            );
        }

        $removed = DB::transaction(function () use ($target, $validated) {
            // Deleted child by child on purpose. The foreign keys cascade in
            // the database, which bypasses Eloquent entirely - the sections of
            // a deleted chapter would vanish without ever firing the event that
            // writes their revision.
            $counts = ['sections' => 0, 'chapters' => 0];

            if ($validated['kind'] === 'topic') {
                foreach ($target->chapters as $chapter) {
                    $counts['sections'] += $this->deleteSectionsOf($chapter);
                    $chapter->delete();
                    $counts['chapters']++;
                }
            }

            if ($validated['kind'] === 'chapter') {
                $counts['sections'] += $this->deleteSectionsOf($target);
            }

            $target->delete();

            return $counts;
        });

        return Response::json([
            'deleted' => $validated['kind'],
            'id' => $validated['id'],
            'title' => $target->title,
            'also_deleted' => $removed,
        ]);
    }

    private function deleteSectionsOf(Chapter $chapter): int
    {
        $count = 0;

        foreach ($chapter->sections as $section) {
            $section->delete();
            $count++;
        }

        return $count;
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'kind' => $schema->string()->description('section, chapter or topic.')->required(),
            'id' => $schema->integer()->description('Id of the thing to delete.')->required(),
            'confirm_title' => $schema->string()
                ->description('Its exact current title. The call is refused if it does not match.')
                ->required(),
        ];
    }
}
