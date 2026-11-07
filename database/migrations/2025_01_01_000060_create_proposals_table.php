<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // BR-09: only one student per thesis. Proposals precede theses (EUC-19 / SUC).
        Schema::create('proposals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('advisor_id')->nullable()->constrained('users')->nullOnDelete(); // assigned by SGC (EUC-15)
            $table->foreignId('academic_year_id')->constrained('academic_years')->cascadeOnDelete();
            $table->string('title');
            $table->text('abstract')->nullable();
            // submitted -> scheduled -> reviewed -> accepted -> rejected -> major_revision
            $table->enum('status', [
                'submitted',
                'scheduled',
                'reviewed',
                'accepted',
                'rejected',
                'major_revision',
            ])->default('submitted');
            // Proposal defense result fields (EUC-17 / SUC-14).
            $table->string('reviewer_recommendation')->nullable(); // Accepted / Accepted on condition / Rejected
            $table->string('modified_title')->nullable();
            $table->text('general_comment')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('proposals');
    }
};
