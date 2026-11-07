<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Defense schedules for project (EUC-04), proposal review (EUC-16) and thesis (EUC-18).
        Schema::create('schedules', function (Blueprint $table) {
            $table->id();
            // project, proposal, thesis
            $table->morphs('work');
            $table->foreignId('academic_year_id')->constrained('academic_years')->cascadeOnDelete();
            $table->foreignId('coordinator_id')->constrained('users')->cascadeOnDelete();
            $table->date('date');
            $table->time('time');
            $table->string('place');
            $table->string('room')->nullable();
            // Two examiners per BR-07 / BR-10 / BR-14.
            $table->foreignId('examiner_one_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('examiner_two_id')->nullable()->constrained('users')->nullOnDelete();
            // The advisor may also attend (chair).
            $table->foreignId('chair_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('status', ['scheduled', 'completed', 'cancelled'])->default('scheduled');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schedules');
    }
};
