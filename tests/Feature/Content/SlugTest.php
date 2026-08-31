<?php

use App\Models\Chapter;
use App\Models\Section;
use App\Models\Topic;
use App\Models\User;

use function Pest\Laravel\actingAs;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

it('gives colliding topic titles distinct slugs', function () {
    $user = User::factory()->create();
    $category = \App\Models\Category::factory()->create();

    actingAs($user)->post('/admin/topics', [
        'title' => 'Physik Grundlagen',
        'category_id' => $category->id,
    ])->assertRedirect();

    actingAs($user)->post('/admin/topics', [
        'title' => 'Physik Grundlagen',
        'category_id' => $category->id,
    ])->assertRedirect();

    expect(Topic::pluck('slug')->all())->toBe(['physik-grundlagen', 'physik-grundlagen-1']);
});

it('scopes chapter slugs to their topic', function () {
    $user = User::factory()->create();
    $first = Topic::factory()->for($user)->create();
    $second = Topic::factory()->for($user)->create();

    actingAs($user)->post("/admin/topics/{$first->id}/chapters", ['title' => 'Einstieg']);
    actingAs($user)->post("/admin/topics/{$first->id}/chapters", ['title' => 'Einstieg']);
    actingAs($user)->post("/admin/topics/{$second->id}/chapters", ['title' => 'Einstieg']);

    expect($first->chapters()->pluck('slug')->all())->toBe(['einstieg', 'einstieg-1'])
        // A different topic may reuse the same slug.
        ->and($second->chapters()->pluck('slug')->all())->toBe(['einstieg']);
});

it('scopes section slugs to their chapter', function () {
    $user = User::factory()->create();
    $topic = Topic::factory()->for($user)->create();
    $chapter = Chapter::factory()->for($topic)->create();

    actingAs($user)->post("/admin/chapters/{$chapter->id}/sections", ['title' => 'Aufgabe 1']);
    actingAs($user)->post("/admin/chapters/{$chapter->id}/sections", ['title' => 'Aufgabe 1']);

    expect($chapter->sections()->pluck('slug')->all())->toBe(['aufgabe-1', 'aufgabe-1-1']);
});

it('keeps a slug stable when the record is saved unchanged', function () {
    $user = User::factory()->create();
    $topic = Topic::factory()->for($user)->create(['slug' => 'chemie']);

    actingAs($user)->put("/admin/topics/{$topic->id}", [
        'title' => $topic->title,
        'slug' => 'chemie',
        'category_id' => $topic->category_id,
    ]);

    expect($topic->fresh()->slug)->toBe('chemie');
});

it('falls back to a usable slug when the title has no sluggable characters', function () {
    $user = User::factory()->create();
    $topic = Topic::factory()->for($user)->create();
    $chapter = Chapter::factory()->for($topic)->create();

    $section = $chapter->sections()->make();

    expect($section->uniqueSlug('***'))->toBe('section');
});

it('rejects duplicate section slugs at the database level', function () {
    $chapter = Chapter::factory()->create();
    Section::factory()->for($chapter)->create(['slug' => 'intro']);

    expect(fn () => Section::factory()->for($chapter)->create(['slug' => 'intro']))
        ->toThrow(Illuminate\Database\QueryException::class);
});
