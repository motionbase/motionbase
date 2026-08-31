<?php

use App\Models\Chapter;
use App\Models\Section;
use App\Models\Topic;
use App\Models\User;

use function Pest\Laravel\actingAs;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

it('does not let a teacher edit another teacher\'s topic', function () {
    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    $topic = Topic::factory()->for($owner)->create();

    actingAs($intruder)->get("/admin/topics/{$topic->id}/edit")->assertForbidden();

    actingAs($intruder)->put("/admin/topics/{$topic->id}", [
        'title' => 'Übernommen',
    ])->assertForbidden();

    actingAs($intruder)->delete("/admin/topics/{$topic->id}")->assertForbidden();

    expect($topic->fresh()->title)->not->toBe('Übernommen');
});

it('does not let a teacher change chapters or sections they do not own', function () {
    $owner = User::factory()->create();
    $intruder = User::factory()->create();
    $topic = Topic::factory()->for($owner)->create();
    $chapter = Chapter::factory()->for($topic)->create();
    $section = Section::factory()->for($chapter)->create();

    actingAs($intruder)->patch("/admin/chapters/{$chapter->id}", ['title' => 'Fremd'])->assertForbidden();
    actingAs($intruder)->patch("/admin/sections/{$section->id}", ['title' => 'Fremd'])->assertForbidden();
    actingAs($intruder)->delete("/admin/sections/{$section->id}")->assertForbidden();

    expect($chapter->fresh()->title)->not->toBe('Fremd')
        ->and($section->fresh()->title)->not->toBe('Fremd');
});

it('only lists a teacher\'s own topics', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    Topic::factory()->for($owner)->create(['title' => 'Meines']);
    Topic::factory()->for($other)->create(['title' => 'Fremdes']);

    actingAs($owner)
        ->get('/admin/topics')
        ->assertInertia(fn ($page) => $page
            ->component('topics/index')
            ->has('topics', 1)
            ->where('topics.0.title', 'Meines'));
});

it('keeps user administration to admins', function () {
    actingAs(User::factory()->create(['is_admin' => false]))
        ->get('/admin/users')
        ->assertForbidden();

    actingAs(User::factory()->create(['is_admin' => true]))
        ->get('/admin/users')
        ->assertOk();
});
