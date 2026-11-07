<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Compiled final grade per student: average of advisor + two examiners (BR-06 / BR-15).
        Schema::create('final_grades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            // project, proposal, thesis
            $table->string('work_type');
            $table->unsignedBigInteger('work_id')->nullable();
            $table->foreignId('academic_year_id')->constrained('academic_years')->cascadeOnDelete();
            $table->decimal('advisor_score', 6, 2)->nullable();
            $table->decimal('examiner_one_score', 6, 2)->nullable();
            $table->decimal('examiner_two_score', 6, 2)->nullable();
            $table->decimal('final_score', 6, 2)->nullable();
            $table->string('letter_grade')->nullable();
            $table->string('remark')->nullable(); // excellent / pass / fail ...
            $table->foreignId('recorded_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('final_grades');
    }
};
