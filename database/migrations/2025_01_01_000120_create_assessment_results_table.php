<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Individual scores given by advisor/examiners; averaged by coordinator (BR-06 / EUC-05 / EUC-17).
        Schema::create('assessment_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('schedule_id')->nullable()->constrained('schedules')->cascadeOnDelete();
            // Who recorded the assessment (advisor or examiner).
            $table->foreignId('assessor_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            // project, proposal, thesis
            $table->string('work_type');
            $table->foreignId('criteria_id')->constrained('assessment_criteria')->cascadeOnDelete();
            $table->decimal('score', 6, 2)->default(0);
            $table->text('comment')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assessment_results');
    }
};
