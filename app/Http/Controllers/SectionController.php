<?php

namespace App\Http\Controllers;

use App\Models\Section;
use App\Models\Topic;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SectionController extends Controller
{
    public function store(Request $request, Topic $topic): RedirectResponse
    {
        $this->authorize('update', $topic);

        $validated = $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
        ]);

        $maxSortOrder = $topic->sections()->max('sort_order') ?? -1;

        $section = $topic->sections()->create([
            'title' => $validated['title'] ?? 'Neuer Abschnitt',
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
            'sort_order' => $maxSortOrder + 1,
        ]);

        return redirect()
            ->route('topics.edit', ['topic' => $topic->id, 'section' => $section->id])
            ->with('flash', ['status' => 'success', 'message' => 'Abschnitt erstellt.']);
    }

    public function update(Request $request, Section $section): RedirectResponse
    {
        $this->authorize('update', $section->topic);

        $validated = $request->validate([
            'title' => ['sometimes', 'string', 'max:255'],
            'content' => ['sometimes', 'array'],
        ]);

        $section->update($validated);

        return redirect()
            ->route('topics.edit', ['topic' => $section->topic_id, 'section' => $section->id])
            ->with('flash', ['status' => 'success', 'message' => 'Abschnitt gespeichert.']);
    }

    public function destroy(Section $section): RedirectResponse
    {
        $this->authorize('update', $section->topic);

        // Prevent deleting the last section if needed, or handle empty state in UI
        if ($section->topic->sections()->count() <= 1) {
            return redirect()->back()->with('flash', ['status' => 'error', 'message' => 'Ein Thema muss mindestens einen Abschnitt haben.']);
        }

        $section->delete();

        $nextSection = $section->topic->sections()->orderBy('sort_order')->first();

        return redirect()
            ->route('topics.edit', ['topic' => $section->topic_id, 'section' => optional($nextSection)->id])
            ->with('flash', ['status' => 'success', 'message' => 'Abschnitt gelöscht.']);
    }

    public function reorder(Request $request, Topic $topic): RedirectResponse
    {
        $this->authorize('update', $topic);

        $validated = $request->validate([
            'order' => ['required', 'array'],
            'order.*' => ['integer', 'exists:sections,id'],
        ]);

        foreach ($validated['order'] as $index => $sectionId) {
            $topic->sections()
                ->whereKey($sectionId)
                ->update(['sort_order' => $index]);
        }

        return redirect()
            ->route('topics.edit', ['topic' => $topic->id])
            ->with('flash', ['status' => 'success', 'message' => 'Reihenfolge gespeichert.']);
    }
}
