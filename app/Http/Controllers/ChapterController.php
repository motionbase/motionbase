<?php

namespace App\Http\Controllers;

use App\Models\Chapter;
use App\Models\Topic;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ChapterController extends Controller
{
    public function store(Request $request, Topic $topic): RedirectResponse
    {
        $this->authorize('update', $topic);

        $validated = $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
        ]);

        $maxSortOrder = $topic->chapters()->max('sort_order') ?? -1;

        $chapter = $topic->chapters()->create([
            'title' => $validated['title'] ?? 'Neues Kapitel',
            'sort_order' => $maxSortOrder + 1,
        ]);

        // Create an initial section for the chapter
        $section = $chapter->sections()->create([
            'title' => 'Einführung',
            'content' => [
                'time' => now()->getTimestampMs(),
                'blocks' => [
                    [
                        'type' => 'paragraph',
                        'data' => ['text' => 'Beginne hier mit deinem Inhalt...'],
                    ],
                ],
                'version' => '2.31.0',
            ],
            'sort_order' => 0,
        ]);

        return redirect()
            ->route('topics.edit', ['topic' => $topic->id, 'section' => $section->id])
            ->with('flash', ['status' => 'success', 'message' => 'Kapitel erstellt.']);
    }

    public function update(Request $request, Chapter $chapter): RedirectResponse
    {
        $this->authorize('update', $chapter->topic);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
        ]);

        $chapter->update($validated);

        return redirect()
            ->back()
            ->with('flash', ['status' => 'success', 'message' => 'Kapitel aktualisiert.']);
    }

    public function destroy(Chapter $chapter): RedirectResponse
    {
        $this->authorize('update', $chapter->topic);

        $topic = $chapter->topic;

        // Prevent deleting the last chapter
        if ($topic->chapters()->count() <= 1) {
            return redirect()
                ->back()
                ->with('flash', ['status' => 'error', 'message' => 'Ein Thema muss mindestens ein Kapitel haben.']);
        }

        $chapter->delete();

        // Get the first section of the first remaining chapter
        $nextChapter = $topic->chapters()->orderBy('sort_order')->first();
        $nextSection = $nextChapter?->sections()->orderBy('sort_order')->first();

        return redirect()
            ->route('topics.edit', ['topic' => $topic->id, 'section' => $nextSection?->id])
            ->with('flash', ['status' => 'success', 'message' => 'Kapitel gelöscht.']);
    }

    public function reorder(Request $request, Topic $topic): RedirectResponse
    {
        $this->authorize('update', $topic);

        $validated = $request->validate([
            'order' => ['required', 'array'],
            'order.*' => ['integer', 'exists:chapters,id'],
        ]);

        foreach ($validated['order'] as $index => $chapterId) {
            $topic->chapters()
                ->whereKey($chapterId)
                ->update(['sort_order' => $index]);
        }

        return redirect()
            ->back()
            ->with('flash', ['status' => 'success', 'message' => 'Reihenfolge gespeichert.']);
    }
}


