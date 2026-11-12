<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
| P3-T2: lifecycle maintenance.
| `invitations:expire` is safe and idempotent, so it runs hourly.
| `invitations:purge` is destructive and dry-run by default; it is NOT
| scheduled here because retention is still an owner decision (AGENTS.md 11.3).
| Enable it with a cron or `Schedule::command('invitations:purge --force')->daily()`
| once the owner confirms the retention period.
*/
Schedule::command('invitations:expire')->hourly();
