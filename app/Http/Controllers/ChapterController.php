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
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
        ]);

        $maxSortOrder = $topic->chapters()->max('sort_order') ?? -1;

        // Generate slug from title if not provided
        $slug = $validated['slug'] ?? \Illuminate\Support\Str::slug($validated['title']);

        // Ensure slug is unique within the topic
        $originalSlug = $slug;
        $counter = 1;
        while ($topic->chapters()->where('slug', $slug)->exists()) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }

        $chapter = $topic->chapters()->create([
            'title' => $validated['title'],
            'slug' => $slug,
            'sort_order' => $maxSortOrder + 1,
        ]);

        // Don't create a default section - leave chapter empty

        return redirect()
            ->route('topics.edit', ['topic' => $topic])
            ->with('flash', ['status' => 'success', 'message' => 'Kapitel erstellt.']);
    }

    public function update(Request $request, Chapter $chapter): RedirectResponse
    {
        $this->authorize('update', $chapter->topic);

        $validated = $request->validate([
            'title' => ['sometimes', 'string', 'max:255'],
            'slug' => ['sometimes', 'string', 'max:255'],
            'is_published' => ['sometimes', 'boolean'],
        ]);

        // If slug is provided, ensure uniqueness within the topic
        if (isset($validated['slug'])) {
            $slug = $validated['slug'];
            $originalSlug = $slug;
            $counter = 1;
            while ($chapter->topic->chapters()->where('slug', $slug)->where('id', '!=', $chapter->id)->exists()) {
                $slug = $originalSlug . '-' . $counter;
                $counter++;
            }
            $validated['slug'] = $slug;
        }

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
            ->route('topics.edit', ['topic' => $topic, 'section' => $nextSection?->id])
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


