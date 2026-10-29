<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

\Illuminate\Support\Facades\Schedule::command('wholesale:expire-quotations')->daily();
\Illuminate\Support\Facades\Schedule::command('permits:purge-orphan-screenshots')->daily();
\Illuminate\Support\Facades\Schedule::command('stock:reconcile')->daily();

