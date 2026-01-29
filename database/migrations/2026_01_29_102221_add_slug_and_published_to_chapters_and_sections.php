<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add slug and is_published to chapters
        Schema::table('chapters', function (Blueprint $table) {
            $table->string('slug')->nullable()->after('title');
            $table->boolean('is_published')->default(true)->after('slug');
        });

        // Generate slugs for existing chapters
        DB::table('chapters')->orderBy('id')->chunk(100, function ($chapters) {
            foreach ($chapters as $chapter) {
                $slug = Str::slug($chapter->title);

                // Ensure uniqueness within the same topic
                $existingSlug = DB::table('chapters')
                    ->where('topic_id', $chapter->topic_id)
                    ->where('slug', $slug)
                    ->where('id', '!=', $chapter->id)
                    ->exists();

                if ($existingSlug) {
                    $slug = $slug . '-' . $chapter->id;
                }

                DB::table('chapters')
                    ->where('id', $chapter->id)
                    ->update(['slug' => $slug]);
            }
        });

        // Make slug non-nullable after populating
        Schema::table('chapters', function (Blueprint $table) {
            $table->string('slug')->nullable(false)->change();
        });

        // Add slug and is_published to sections
        Schema::table('sections', function (Blueprint $table) {
            $table->string('slug')->nullable()->after('title');
            $table->boolean('is_published')->default(true)->after('slug');
        });

        // Generate slugs for existing sections
        DB::table('sections')->orderBy('id')->chunk(100, function ($sections) {
            foreach ($sections as $section) {
                $slug = Str::slug($section->title);

                // Ensure uniqueness within the same chapter
                $existingSlug = DB::table('sections')
                    ->where('chapter_id', $section->chapter_id)
                    ->where('slug', $slug)
                    ->where('id', '!=', $section->id)
                    ->exists();

                if ($existingSlug) {
                    $slug = $slug . '-' . $section->id;
                }

                DB::table('sections')
                    ->where('id', $section->id)
                    ->update(['slug' => $slug]);
            }
        });

        // Make slug non-nullable after populating
        Schema::table('sections', function (Blueprint $table) {
            $table->string('slug')->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('chapters', function (Blueprint $table) {
            $table->dropColumn(['slug', 'is_published']);
        });

        Schema::table('sections', function (Blueprint $table) {
            $table->dropColumn(['slug', 'is_published']);
        });
    }
};
