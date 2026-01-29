<?php

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ChapterController;
use App\Http\Controllers\ImageUploadController;
use App\Http\Controllers\LottieUploadController;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\PublicTopicController;
use App\Http\Controllers\RevisionController;
use App\Http\Controllers\SectionController;
use App\Http\Controllers\TopicController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// Public Frontend Routes
Route::get('/', [PublicTopicController::class, 'index'])->name('home');
Route::get('themen', [PublicTopicController::class, 'index'])->name('public.topics.index');
Route::get('themen/{topic:slug}/{chapter:slug?}/{section:slug?}', [PublicTopicController::class, 'show'])->name('public.topics.show');

// Admin Backend Routes
Route::middleware(['auth', 'verified'])->prefix('admin')->group(function () {
    Route::get('dashboard', function () {
        return Inertia::render('dashboard');
    })->name('dashboard');

    // Categories
    Route::resource('categories', CategoryController::class)->only([
        'index',
        'create',
        'store',
        'update',
        'destroy',
    ]);

    // Topics
    Route::resource('topics', TopicController::class)->except(['show']);
    Route::get('topics/{topic}/edit/{section}', [TopicController::class, 'edit'])->name('topics.edit.section');

    // Chapters routes
    Route::post('topics/{topic}/chapters', [ChapterController::class, 'store'])->name('topics.chapters.store');
    Route::post('topics/{topic}/chapters/reorder', [ChapterController::class, 'reorder'])->name('topics.chapters.reorder');
    Route::patch('chapters/{chapter}', [ChapterController::class, 'update'])->name('chapters.update');
    Route::delete('chapters/{chapter}', [ChapterController::class, 'destroy'])->name('chapters.destroy');

    // Sections routes
    Route::post('chapters/{chapter}/sections', [SectionController::class, 'store'])->name('chapters.sections.store');
    Route::post('chapters/{chapter}/sections/reorder', [SectionController::class, 'reorder'])->name('chapters.sections.reorder');
    Route::patch('sections/{section}', [SectionController::class, 'update'])->name('sections.update');
    Route::delete('sections/{section}', [SectionController::class, 'destroy'])->name('sections.destroy');

    // Revision routes
    Route::get('revisions/{modelType}/{modelId}', [RevisionController::class, 'index'])->name('revisions.index');
    Route::get('revisions/{revisionId}', [RevisionController::class, 'show'])->name('revisions.show');
    Route::post('revisions/{revisionId}/restore', [RevisionController::class, 'restore'])->name('revisions.restore');
    Route::get('topics/{topicId}/history', [RevisionController::class, 'topicHistory'])->name('topics.history');

    // Image upload for Editor.js
    Route::post('upload/image', [ImageUploadController::class, 'upload'])->name('upload.image');
    Route::post('upload/image-by-url', [ImageUploadController::class, 'uploadByUrl'])->name('upload.image.url');

    // Lottie upload for Editor.js
    Route::post('upload/lottie', [LottieUploadController::class, 'upload'])->name('upload.lottie');

    // Media Library
    Route::get('media', function (\Illuminate\Http\Request $request) {
        if ($request->header('X-Inertia')) {
            return \Inertia\Inertia::render('media/index');
        }
        if ($request->wantsJson() || $request->ajax()) {
            return app(MediaController::class)->index($request);
        }
        return \Inertia\Inertia::render('media/index');
    })->name('media.index');
    Route::get('media/{media}', [MediaController::class, 'show'])->name('media.show');
    Route::patch('media/{media}', [MediaController::class, 'update'])->name('media.update');
    Route::delete('media/{media}', [MediaController::class, 'destroy'])->name('media.destroy');
});

require __DIR__.'/settings.php';
