<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Chapter;
use App\Models\Section;
use App\Models\Topic;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class PublicTopicController extends Controller
{
    public function index(Request $request): Response
    {
        $topics = Topic::query()
            ->with([
                'category:id,name',
                'user:id,name',
                'chapters' => fn ($query) => $query
                    ->orderBy('sort_order')
                    ->with(['sections' => fn ($q) => $q->orderBy('sort_order')->limit(1)]),
            ])
            ->when(
                $request->string('category')->isNotEmpty(),
                fn ($query) => $query->where('category_id', $request->integer('category')),
            )
            ->latest('updated_at')
            ->get()
            ->map(fn (Topic $topic) => [
                'id' => $topic->id,
                'title' => $topic->title,
                'excerpt' => $this->excerptFromTopic($topic),
                'category' => $topic->category?->only(['id', 'name']),
                'author' => $topic->user?->only(['id', 'name']),
                'updated_at' => $topic->updated_at?->toIso8601String(),
            ])
            ->all();

        return Inertia::render('public/topics/index', [
            'topics' => $topics,
            'filters' => [
                'category' => $request->input('category'),
            ],
            'categories' => Category::query()
                ->orderBy('name')
                ->get(['id', 'name']),
        ]);
    }

    public function show(Topic $topic, ?Section $section = null): Response
    {
        $topic->loadMissing([
            'category:id,name',
            'user:id,name',
            'chapters' => fn ($query) => $query->orderBy('sort_order')->with([
                'sections' => fn ($q) => $q->orderBy('sort_order'),
            ]),
        ]);

        // Validate section belongs to topic
        if ($section) {
            $chapter = $section->chapter;
            if (! $chapter || $chapter->topic_id !== $topic->id) {
                abort(404);
            }
        }

        // Default to first section of first chapter
        $activeSection = $section;
        if (! $activeSection) {
            $firstChapter = $topic->chapters->first();
            $activeSection = $firstChapter?->sections->first();
        }

        return Inertia::render('public/topics/show', [
            'topic' => [
                'id' => $topic->id,
                'title' => $topic->title,
                'category' => $topic->category?->only(['id', 'name']),
                'author' => $topic->user?->only(['id', 'name']),
                'updated_at' => $topic->updated_at?->toIso8601String(),
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
                'activeSection' => $activeSection ? [
                    'id' => $activeSection->id,
                    'title' => $activeSection->title,
                    'content' => $activeSection->content,
                ] : null,
            ],
        ]);
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

        return $plainText !== '' ? Str::limit($plainText, 180) : null;
    }
}
