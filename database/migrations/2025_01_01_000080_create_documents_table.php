<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Polymorphic-style storage for project / proposal / thesis uploads (SUC-10 / SUC-11).
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('uploaded_by')->constrained('users')->cascadeOnDelete();
            $table->morphs('work'); // work_type + work_id (IndustrialProjectGroup / Proposal / Thesis)
            $table->string('original_name');
            $table->string('file_path');
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size')->nullable();
            $table->integer('version')->default(1);
            // draft, final, reviewed
            $table->enum('category', ['draft', 'final', 'evaluation', 'other'])->default('draft');
            // Whether the document is published to the public repository (guest view SUC-20).
            $table->boolean('is_published')->default(false);
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
