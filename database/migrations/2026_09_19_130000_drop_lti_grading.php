<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * MotionBase does not grade anything in Moodle after all. The migration
     * that added this had already been pushed, so it stays and this one takes
     * its grading half back out - on a database that ran it or not.
     */
    public function up(): void
    {
        Schema::dropIfExists('lti_quiz_attempts');

        if (Schema::hasColumn('lti_resource_links', 'lineitem_url')) {
            Schema::table('lti_resource_links', function (Blueprint $table) {
                $table->dropColumn('lineitem_url');
            });
        }
    }

    public function down(): void
    {
        Schema::table('lti_resource_links', function (Blueprint $table) {
            $table->text('lineitem_url')->nullable();
        });

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
};
