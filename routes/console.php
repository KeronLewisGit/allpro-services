<?php

use Illuminate\Support\Facades\Schedule;

// Hostinger cron (hPanel → Advanced → Cron Jobs), every minute (INSTALL-HOSTINGER.md, "Cron"):
//   /usr/bin/php /home/USER/domains/allpro.ie/public_html/allpro-app/artisan schedule:run
// Times are Europe/Dublin (APP_TIMEZONE).
Schedule::command('app:remind-expiring')->dailyAt('07:00')->withoutOverlapping();
Schedule::command('app:prune-held-uploads')->hourly();
