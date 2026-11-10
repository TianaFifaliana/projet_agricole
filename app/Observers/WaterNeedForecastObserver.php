<?php

namespace App\Observers;

use App\Models\WaterNeedForecast;
use App\Observers\Concerns\InvalidatesExploitationCache;

class WaterNeedForecastObserver
{
    use InvalidatesExploitationCache;
}
