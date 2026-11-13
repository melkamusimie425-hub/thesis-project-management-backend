<?php

use App\Models\Notice;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/**
 * Purge expired announcements daily at midnight.
 * Notices with a past expires_at date are permanently deleted.
 */
Schedule::call(function (): void {
    Notice::whereNotNull('expires_at')
        ->where('expires_at', '<', now())
        ->delete();
})->daily();
