<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('revisions', function (Blueprint $table) {
            $table->id();
            $table->morphs('revisionable'); // topic_id/chapter_id
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->json('content'); // Full snapshot of data
            $table->string('revision_type')->default('update'); // update, create, delete
            $table->text('change_summary')->nullable();
            $table->timestamps();

            // Index for faster queries
            $table->index(['revisionable_type', 'revisionable_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('revisions');
    }
};
