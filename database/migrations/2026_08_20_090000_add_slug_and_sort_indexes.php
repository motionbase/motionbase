<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Slugs drive route model binding (/themen/{topic:slug}/{chapter:slug}/{section:slug}),
     * so duplicates silently route visitors to the wrong content. The application
     * generated unique slugs in PHP, but two concurrent saves could still collide.
     * Enforce it in the schema, and index the ordering columns the editor and the
     * public views sort by on every request.
     */
    public function up(): void
    {
        // topics.slug already carries a unique index from the migration that
        // introduced the column - only chapters and sections are unguarded.
        $this->deduplicate('chapters', 'topic_id');
        $this->deduplicate('sections', 'chapter_id');

        Schema::table('chapters', function (Blueprint $table): void {
            if (! Schema::hasIndex('chapters', 'chapters_topic_id_slug_unique')) {
                $table->unique(['topic_id', 'slug'], 'chapters_topic_id_slug_unique');
            }

            if (! Schema::hasIndex('chapters', 'chapters_topic_id_sort_order_index')) {
                $table->index(['topic_id', 'sort_order'], 'chapters_topic_id_sort_order_index');
            }
        });

        Schema::table('sections', function (Blueprint $table): void {
            if (! Schema::hasIndex('sections', 'sections_chapter_id_slug_unique')) {
                $table->unique(['chapter_id', 'slug'], 'sections_chapter_id_slug_unique');
            }

            if (! Schema::hasIndex('sections', 'sections_chapter_id_sort_order_index')) {
                $table->index(['chapter_id', 'sort_order'], 'sections_chapter_id_sort_order_index');
            }
        });
    }

    public function down(): void
    {
        Schema::table('chapters', function (Blueprint $table): void {
            $table->dropUnique('chapters_topic_id_slug_unique');
            $table->dropIndex('chapters_topic_id_sort_order_index');
        });

        Schema::table('sections', function (Blueprint $table): void {
            $table->dropUnique('sections_chapter_id_slug_unique');
            $table->dropIndex('sections_chapter_id_sort_order_index');
        });
    }

    /**
     * Suffix any pre-existing duplicate slugs with the row id so the unique
     * index can be created on live data.
     */
    private function deduplicate(string $table, ?string $scope): void
    {
        $groupBy = $scope ? [$scope, 'slug'] : ['slug'];

        $duplicates = DB::table($table)
            ->select($groupBy)
            ->groupBy($groupBy)
            ->havingRaw('count(*) > 1')
            ->get();

        foreach ($duplicates as $duplicate) {
            $rows = DB::table($table)
                ->where('slug', $duplicate->slug)
                ->when($scope, fn ($query) => $query->where($scope, $duplicate->{$scope}))
                ->orderBy('id')
                ->pluck('id')
                ->skip(1);

            foreach ($rows as $id) {
                DB::table($table)->where('id', $id)->update(['slug' => $duplicate->slug.'-'.$id]);
            }
        }
    }
};
