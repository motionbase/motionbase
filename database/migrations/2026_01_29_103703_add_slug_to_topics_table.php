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
        // Add slug to topics
        Schema::table('topics', function (Blueprint $table) {
            $table->string('slug')->nullable()->after('title')->unique();
        });

        // Generate slugs for existing topics
        DB::table('topics')->orderBy('id')->chunk(100, function ($topics) {
            foreach ($topics as $topic) {
                $slug = Str::slug($topic->title);

                // Ensure uniqueness globally
                $existingSlug = DB::table('topics')
                    ->where('slug', $slug)
                    ->where('id', '!=', $topic->id)
                    ->exists();

                if ($existingSlug) {
                    $slug = $slug . '-' . $topic->id;
                }

                DB::table('topics')
                    ->where('id', $topic->id)
                    ->update(['slug' => $slug]);
            }
        });

        // Make slug non-nullable after populating
        Schema::table('topics', function (Blueprint $table) {
            $table->string('slug')->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('topics', function (Blueprint $table) {
            $table->dropColumn('slug');
        });
    }
};
