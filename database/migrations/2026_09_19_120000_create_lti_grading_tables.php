<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // What a Moodle activity shows, when the teacher chose it while opening
        // the activity rather than through "Inhalt auswählen". Chosen that way,
        // the choice has nowhere to live in Moodle, so it lives here.
        Schema::create('lti_resource_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lti_platform_id')->constrained()->cascadeOnDelete();
            $table->string('resource_link_id');
            $table->string('content_type', 20);
            $table->foreignId('topic_id')->constrained()->cascadeOnDelete();
            $table->foreignId('chapter_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('section_id')->nullable()->constrained()->cascadeOnDelete();
            // The grade column MotionBase created itself, if Moodle allowed it
            $table->text('lineitem_url')->nullable();
            $table->timestamps();

            $table->unique(['lti_platform_id', 'resource_link_id']);
        });

        // The first complete run through each knowledge check, per learner and
        // activity. Later runs are practice and do not change the grade.
        Schema::create('lti_quiz_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lti_platform_id')->constrained()->cascadeOnDelete();
            $table->string('lti_user_id');
            $table->string('resource_link_id');
            $table->foreignId('section_id')->constrained()->cascadeOnDelete();
            $table->string('block_id', 64);
            $table->unsignedSmallInteger('score');
            $table->unsignedSmallInteger('max_score');
            $table->json('answers');
            $table->timestamps();

            $table->unique(
                ['lti_platform_id', 'lti_user_id', 'resource_link_id', 'section_id', 'block_id'],
                'lti_quiz_attempts_first_run'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lti_quiz_attempts');
        Schema::dropIfExists('lti_resource_links');
    }
};
