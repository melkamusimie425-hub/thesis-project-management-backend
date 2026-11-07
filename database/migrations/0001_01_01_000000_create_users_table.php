<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('first_name');
            $table->string('middle_name')->nullable();
            $table->string('last_name');
            $table->enum('gender', ['Male', 'Female'])->nullable();
            // Institutional identifier (e.g. NSR/9256/07).
            $table->string('id_number')->unique();
            $table->string('email')->unique();
            $table->string('phone')->nullable();
            // Program: undergraduate (UGS) or graduate (GS).
            $table->enum('program', ['Undergraduate', 'Graduate', 'Staff'])->default('Staff');
            // System role. Maps to the actors identified in the analysis.
            $table->enum('role', [
                'Administrator',
                'DepartmentHead',
                'UGPC',          // Undergraduate Program Coordinator
                'GPC',           // Graduate Program Coordinator
                'IPC',           // Industrial Project Committee
                'SGC',           // School Graduate Committee
                'Student',       // Undergraduate Student
                'GradStudent',   // Graduate Student
                'Advisor',       // Project / Thesis Advisor (instructor)
                'Examiner',      // Examiner
                'Guest',
            ])->default('Student');
            $table->string('salutation')->nullable();
            $table->string('password');
            $table->boolean('is_active')->default(true);
            $table->timestamp('email_verified_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
