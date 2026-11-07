<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // BR-03: number of alternative titles should not exceed three.
        Schema::create('project_titles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained('industrial_project_groups')->cascadeOnDelete();
            $table->string('title');
            $table->tinyInteger('title_order')->unsigned(); // 1, 2 or 3
            // pending -> approved -> rejected (set by IPC EUC-01)
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('reviewer_comment')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_titles');
    }
};
