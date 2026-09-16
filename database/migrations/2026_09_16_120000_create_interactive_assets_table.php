<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Files uploaded together with an interactive graphic - 3D models and
     * their textures - which the graphic loads by relative path.
     */
    public function up(): void
    {
        Schema::create('interactive_assets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('media_id')->constrained('media')->cascadeOnDelete();
            // The name the graphic refers to it by. The file on disk gets a
            // uuid instead, so no author-chosen name ever reaches a path.
            $table->string('name', 100);
            $table->string('path');
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('size');
            $table->timestamps();

            $table->unique(['media_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('interactive_assets');
    }
};
