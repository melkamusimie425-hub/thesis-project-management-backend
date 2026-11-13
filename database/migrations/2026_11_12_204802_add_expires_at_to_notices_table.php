<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notices', function (Blueprint $table): void {
            // Optional expiry date — NULL means the notice never expires.
            $table->timestamp('expires_at')->nullable()->after('is_pinned');
        });
    }

    public function down(): void
    {
        Schema::table('notices', function (Blueprint $table): void {
            $table->dropColumn('expires_at');
        });
    }
};
