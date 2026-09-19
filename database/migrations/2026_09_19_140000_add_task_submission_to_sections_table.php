<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A lesson can be a task that a Moodle course takes over as a native
     * assignment. The value says what the learners hand in there: nothing,
     * a file, text, or either. Null means the lesson is not a task.
     */
    public function up(): void
    {
        Schema::table('sections', function (Blueprint $table) {
            $table->string('task_submission', 10)->nullable()->after('is_published');
        });
    }

    public function down(): void
    {
        Schema::table('sections', function (Blueprint $table) {
            $table->dropColumn('task_submission');
        });
    }
};
