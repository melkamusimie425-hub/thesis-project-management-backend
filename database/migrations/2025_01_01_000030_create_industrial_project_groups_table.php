<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('industrial_project_groups', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();                    // Group identifier
            $table->string('approved_title')->nullable();          // Approved project title (EUC-01)
            $table->foreignId('academic_year_id')->constrained('academic_years')->cascadeOnDelete();
            // Advisor assigned by UGPC (EUC-03 / SUC-03).
            $table->foreignId('advisor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            // pending -> title_submitted -> title_approved -> advising -> defended -> completed
            $table->enum('status', [
                'pending',
                'title_submitted',
                'title_approved',
                'advising',
                'scheduled',
                'defended',
                'completed',
            ])->default('pending');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('industrial_project_groups');
    }
};
