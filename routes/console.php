<?php

use App\Jobs\GenererAlertesStockJob;
use Illuminate\Support\Facades\Schedule;

Schedule::job(new GenererAlertesStockJob)->dailyAt('06:00');
