<?php

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ChapterController;
use App\Http\Controllers\ImageUploadController;
use App\Http\Controllers\PublicTopicController;
use App\Http\Controllers\SectionController;
use App\Http\Controllers\TopicController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', [PublicTopicController::class, 'index'])->name('home');
Route::get('themen', [PublicTopicController::class, 'index'])->name('public.topics.index');
Route::get('themen/{topic}/{section?}', [PublicTopicController::class, 'show'])->name('public.topics.show');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', function () {
        return Inertia::render('dashboard');
    })->name('dashboard');

    Route::resource('categories', CategoryController::class)->only([
        'index',
        'store',
        'update',
        'destroy',
    ]);

    Route::resource('topics', TopicController::class)->except(['show']);

    // Chapters routes
    Route::post('topics/{topic}/chapters', [ChapterController::class, 'store'])->name('topics.chapters.store');
    Route::post('topics/{topic}/chapters/reorder', [ChapterController::class, 'reorder'])->name('topics.chapters.reorder');
    Route::patch('chapters/{chapter}', [ChapterController::class, 'update'])->name('chapters.update');
    Route::delete('chapters/{chapter}', [ChapterController::class, 'destroy'])->name('chapters.destroy');

    // Sections routes (now under chapters)
    Route::post('chapters/{chapter}/sections', [SectionController::class, 'store'])->name('chapters.sections.store');
    Route::post('chapters/{chapter}/sections/reorder', [SectionController::class, 'reorder'])->name('chapters.sections.reorder');
    Route::patch('sections/{section}', [SectionController::class, 'update'])->name('sections.update');
    Route::delete('sections/{section}', [SectionController::class, 'destroy'])->name('sections.destroy');

    // Image upload for Editor.js
    Route::post('upload/image', [ImageUploadController::class, 'upload'])->name('upload.image');
    Route::post('upload/image-by-url', [ImageUploadController::class, 'uploadByUrl'])->name('upload.image.url');
});

require __DIR__.'/settings.php';
