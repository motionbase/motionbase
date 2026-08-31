<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CategoryController extends Controller
{
    public function index(Request $request): Response
    {
        $categories = Category::query()
            ->withCount([
                'topics as my_topics_count' => static fn ($query) => $query
                    ->where('user_id', $request->user()->id),
            ])
            ->orderBy('name')
            ->get()
            ->map(fn (Category $category) => [
                'id' => $category->id,
                'name' => $category->name,
                'description' => $category->description,
                'my_topics_count' => $category->my_topics_count,
                'created_at' => $category->created_at?->toIso8601String(),
                'updated_at' => $category->updated_at?->toIso8601String(),
            ]);

        return Inertia::render('categories/index', [
            'categories' => $categories,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('categories/create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:categories,name'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        Category::create($validated);

        return redirect()
            ->route('categories.index')
            ->with('flash', ['status' => 'success', 'message' => 'Kategorie erstellt.']);
    }

    public function update(Request $request, Category $category): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:categories,name,'.$category->id],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        $category->update($validated);

        return redirect()
            ->back()
            ->with('flash', ['status' => 'success', 'message' => 'Kategorie aktualisiert.']);
    }

    public function destroy(Category $category): RedirectResponse
    {
        abort_unless(auth()->user()?->is_admin, 403);

        // topics.category_id cascades on delete, so removing a category that is
        // still in use silently destroys every course in it - other people's
        // included, with all their chapters and sections.
        $topics = $category->topics()->count();

        if ($topics > 0) {
            return redirect()
                ->back()
                ->withErrors(['category' => "Diese Kategorie wird von {$topics} Thema/Themen verwendet und kann nicht gelöscht werden."]);
        }

        $category->delete();

        return redirect()
            ->back()
            ->with('flash', ['status' => 'success', 'message' => 'Kategorie gelöscht.']);
    }
}

