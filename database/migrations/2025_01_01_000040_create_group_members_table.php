<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // BR-02: a student is a member of one and only one industrial project group.
        Schema::create('group_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained('industrial_project_groups')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->boolean('is_leader')->default(false);
            $table->boolean('agreed')->default(false); // student consent to join (SUC-08)
            $table->timestamps();

            // Enforce BR-02: one group per student per academic context.
            $table->unique('student_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('group_members');
    }
};
