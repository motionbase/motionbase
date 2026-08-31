<?php

use App\Models\Category;
use App\Models\Chapter;
use App\Models\Media;
use App\Models\Section;
use App\Models\Topic;
use App\Models\User;

use function Pest\Laravel\actingAs;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

it('never lets a category deletion take courses down with it', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $category = Category::factory()->create();
    $topic = Topic::factory()->create(['category_id' => $category->id]);
    $chapter = Chapter::factory()->create(['topic_id' => $topic->id]);
    $section = Section::factory()->create(['chapter_id' => $chapter->id]);

    // topics.category_id is ON DELETE CASCADE, so an unguarded delete here wipes
    // the course, its chapters and its sections in one click.
    actingAs($admin)
        ->delete("/admin/categories/{$category->id}")
        ->assertSessionHasErrors('category');

    expect(Category::find($category->id))->not->toBeNull()
        ->and(Topic::find($topic->id))->not->toBeNull()
        ->and(Chapter::find($chapter->id))->not->toBeNull()
        ->and(Section::find($section->id))->not->toBeNull();
});

it('deletes an unused category', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $category = Category::factory()->create();

    actingAs($admin)->delete("/admin/categories/{$category->id}");

    expect(Category::find($category->id))->toBeNull();
});

it('keeps non-admins away from destructive shared resources', function () {
    $author = User::factory()->create(['is_admin' => false]);
    $category = Category::factory()->create();
    $media = Media::create([
        'filename' => 'x.png', 'original_filename' => 'x.png', 'path' => 'editor-images/x.png',
        'url' => '/storage/editor-images/x.png', 'mime_type' => 'image/png', 'type' => 'image', 'size' => 10,
    ]);

    actingAs($author)->delete("/admin/categories/{$category->id}")->assertForbidden();
    actingAs($author)->deleteJson("/admin/media/{$media->id}")->assertForbidden();
    actingAs($author)->patchJson("/admin/media/{$media->id}", ['alt' => 'x'])->assertForbidden();

    expect(Category::find($category->id))->not->toBeNull()
        ->and(Media::find($media->id))->not->toBeNull();
});
