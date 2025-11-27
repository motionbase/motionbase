<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Create chapters table
        Schema::create('chapters', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('topic_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        // 2. Add chapter_id to sections table (nullable initially for migration)
        Schema::table('sections', function (Blueprint $table): void {
            $table->foreignId('chapter_id')->nullable()->after('topic_id');
        });

        // 3. Migrate existing sections: create a default chapter per topic
        $topics = DB::table('topics')->select('id', 'title')->get();

        foreach ($topics as $topic) {
            // Create a default chapter for each topic
            $chapterId = DB::table('chapters')->insertGetId([
                'topic_id' => $topic->id,
                'title' => 'Grundlagen',
                'sort_order' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Assign all existing sections of this topic to the new chapter
            DB::table('sections')
                ->where('topic_id', $topic->id)
                ->update(['chapter_id' => $chapterId]);
        }

        // 4. Now make chapter_id required and add foreign key constraint
        Schema::table('sections', function (Blueprint $table): void {
            $table->foreignId('chapter_id')->nullable(false)->change();
            $table->foreign('chapter_id')->references('id')->on('chapters')->cascadeOnDelete();
        });

        // 5. Remove topic_id from sections (chapters now link to topics)
        Schema::table('sections', function (Blueprint $table): void {
            $table->dropForeign(['topic_id']);
            $table->dropColumn('topic_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 1. Re-add topic_id to sections
        Schema::table('sections', function (Blueprint $table): void {
            $table->foreignId('topic_id')->nullable()->after('id');
        });

        // 2. Restore topic_id from chapter relationship
        $chapters = DB::table('chapters')->select('id', 'topic_id')->get();
        foreach ($chapters as $chapter) {
            DB::table('sections')
                ->where('chapter_id', $chapter->id)
                ->update(['topic_id' => $chapter->topic_id]);
        }

        // 3. Make topic_id required and add foreign key
        Schema::table('sections', function (Blueprint $table): void {
            $table->foreignId('topic_id')->nullable(false)->change();
            $table->foreign('topic_id')->references('id')->on('topics')->cascadeOnDelete();
        });

        // 4. Remove chapter_id from sections
        Schema::table('sections', function (Blueprint $table): void {
            $table->dropForeign(['chapter_id']);
            $table->dropColumn('chapter_id');
        });

        // 5. Drop chapters table
        Schema::dropIfExists('chapters');
    }
};


