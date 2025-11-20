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
        if (! Schema::hasTable('sections')) {
            Schema::create('sections', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('topic_id')->constrained()->cascadeOnDelete();
                $table->string('title');
                $table->json('content')->nullable();
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();
            });
        }

        $sectionsAreEmpty = Schema::hasTable('sections')
            ? DB::table('sections')->count() === 0
            : true;

        if (Schema::hasColumn('topics', 'content') && $sectionsAreEmpty) {
            DB::table('topics')
                ->select(['id', 'title', 'content'])
                ->orderBy('id')
                ->chunk(100, function ($topics): void {
                    $now = now();
                    $payload = [];

                    foreach ($topics as $topic) {
                        $payload[] = [
                            'topic_id' => $topic->id,
                            'title' => 'Einführung',
                            'content' => $topic->content,
                            'sort_order' => 0,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                    }

                    if ($payload !== []) {
                        DB::table('sections')->insert($payload);
                    }
                });

            Schema::table('topics', function (Blueprint $table): void {
                $table->dropColumn('content');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasColumn('topics', 'content')) {
            Schema::table('topics', function (Blueprint $table): void {
                $table->json('content')->nullable();
            });
        }

        if (Schema::hasTable('sections')) {
            DB::table('sections')
                ->orderBy('sort_order')
                ->chunk(100, function ($sections): void {
                    foreach ($sections as $section) {
                        DB::table('topics')
                            ->where('id', $section->topic_id)
                            ->update(['content' => $section->content]);
                    }
                });

            Schema::dropIfExists('sections');
        }
    }
};
