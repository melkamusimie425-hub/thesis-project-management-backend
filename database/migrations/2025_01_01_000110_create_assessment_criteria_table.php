<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Assessment criteria for IP I, IP II, thesis examination (CRC Assessment Criteria).
        Schema::create('assessment_criteria', function (Blueprint $table) {
            $table->id();
            $table->string('name');                    // e.g. "Research content"
            $table->string('category');               // presentation, documentation, implementation, methodology...
            // ip_one, ip_two, proposal, thesis
            $table->enum('applies_to', ['ip_one', 'ip_two', 'proposal', 'thesis']);
            // Weight as a percentage (e.g. 20 = 20%).
            $table->decimal('weight', 5, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assessment_criteria');
    }
};
