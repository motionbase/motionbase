<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Chapter;
use App\Models\Section;
use App\Models\Topic;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class TopicController extends Controller
{
    public function index(Request $request): Response
    {
        $topics = Topic::query()
            ->with([
                'category:id,name',
                'chapters' => fn ($query) => $query->orderBy('sort_order')->with([
                    'sections' => fn ($q) => $q->orderBy('sort_order')->limit(1),
                ]),
            ])
            ->withCount(['chapters', 'sections'])
            ->where('user_id', $request->user()->id)
            ->orderByDesc('updated_at')
            ->get()
            ->map(fn (Topic $topic) => [
                'id' => $topic->id,
                'title' => $topic->title,
                'chapters_count' => $topic->chapters_count,
                'sections_count' => $topic->sections_count,
                'category' => $topic->category?->only(['id', 'name']),
                'excerpt' => $this->excerptFromTopic($topic),
                'created_at' => $topic->created_at?->toIso8601String(),
                'updated_at' => $topic->updated_at?->toIso8601String(),
            ]);

        return Inertia::render('topics/index', [
            'topics' => $topics,
            'categories' => $this->categoriesForSelect(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('topics/create', [
            'categories' => $this->categoriesForSelect(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Topic::class);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'category_id' => ['required', 'integer', 'exists:categories,id'],
        ]);

        $topic = $request->user()->topics()->create([
            'title' => $validated['title'],
            'category_id' => (int) $validated['category_id'],
        ]);

        // Create an initial default chapter
        $chapter = $topic->chapters()->create([
            'title' => 'Grundlagen',
            'sort_order' => 0,
        ]);

        // Create an initial default section in the chapter
        $chapter->sections()->create([
            'title' => 'Einführung',
            'content' => [
                'time' => time() * 1000, // JS uses ms
                'blocks' => [
                    [
                        'type' => 'paragraph',
                        'data' => ['text' => 'Starte hier mit deinem Inhalt...'],
                    ],
                ],
                'version' => '2.31.0',
            ],
            'sort_order' => 0,
        ]);

        return redirect()
            ->route('topics.edit', $topic)
            ->with('flash', ['status' => 'success', 'message' => 'Thema erstellt.']);
    }

    public function edit(Request $request, Topic $topic): Response
    {
        $this->authorize('view', $topic);

        $topic->load(['chapters' => fn ($query) => $query->orderBy('sort_order')->with([
            'sections' => fn ($q) => $q->orderBy('sort_order'),
        ])]);

        $activeSectionId = $request->integer('section');
        $activeSection = null;

        // Find the active section across all chapters
        foreach ($topic->chapters as $chapter) {
            $found = $chapter->sections->firstWhere('id', $activeSectionId);
            if ($found) {
                $activeSection = $found;
                break;
            }
        }

        // Default to first section of first chapter
        if (! $activeSection) {
            $firstChapter = $topic->chapters->first();
            $activeSection = $firstChapter?->sections->first();
        }

        return Inertia::render('topics/edit', [
            'topic' => [
                'id' => $topic->id,
                'title' => $topic->title,
                'category_id' => $topic->category_id,
                'chapters' => $topic->chapters->map(fn (Chapter $chapter) => [
                    'id' => $chapter->id,
                    'title' => $chapter->title,
                    'sort_order' => $chapter->sort_order,
                    'sections' => $chapter->sections->map(fn (Section $section) => [
                        'id' => $section->id,
                        'title' => $section->title,
                        'sort_order' => $section->sort_order,
                    ]),
                ]),
            ],
            'activeSection' => $activeSection ? [
                'id' => $activeSection->id,
                'title' => $activeSection->title,
                'content' => $activeSection->content,
            ] : null,
            'categories' => $this->categoriesForSelect(),
        ]);
    }

    public function update(Request $request, Topic $topic): RedirectResponse
    {
        $this->authorize('update', $topic);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'category_id' => ['required', 'integer', 'exists:categories,id'],
        ]);

        $topic->update([
            'title' => $validated['title'],
            'category_id' => (int) $validated['category_id'],
        ]);

        return redirect()
            ->back()
            ->with('flash', ['status' => 'success', 'message' => 'Thema aktualisiert.']);
    }

    public function destroy(Topic $topic): RedirectResponse
    {
        $this->authorize('delete', $topic);

        $topic->delete();

        return redirect()
            ->route('topics.index')
            ->with('flash', ['status' => 'success', 'message' => 'Thema gelöscht.']);
    }

    /**
     * @return array<int, array{id: int, name: string}>
     */
    private function categoriesForSelect(): array
    {
        return Category::query()
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Category $category) => [
                'id' => $category->id,
                'name' => $category->name,
            ])
            ->all();
    }

    private function excerptFromTopic(Topic $topic): ?string
    {
        $firstChapter = $topic->chapters->first();
        if (! $firstChapter) {
            return null;
        }

        $section = $firstChapter->sections->first();
        if (! $section) {
            return null;
        }

        $blocks = $section->content['blocks'] ?? [];

        $firstTextBlock = collect($blocks)->first(function ($block) {
            return is_array($block)
                && isset($block['data']['text'])
                && is_string($block['data']['text'])
                && trim($block['data']['text']) !== '';
        });

        if (! $firstTextBlock) {
            return null;
        }

        $plainText = strip_tags($firstTextBlock['data']['text']);

        return $plainText !== '' ? Str::limit($plainText, 160) : null;
    }
}
