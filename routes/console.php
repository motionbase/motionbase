<?php

use App\Models\LtiNonce;
use App\Models\LtiSession;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('lti:prune', function () {
    $nonces = LtiNonce::cleanup();
    $sessions = LtiSession::where('expires_at', '<', now())->delete();

    $this->info("Removed {$nonces} expired LTI nonces and {$sessions} expired LTI sessions.");
})->purpose('Remove expired LTI nonces and launch sessions');

// Every Moodle launch writes a nonce row and a session row that are only
// meaningful for minutes/hours. Without this both tables grow forever.
Schedule::command('lti:prune')->hourly();
