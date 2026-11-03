<?php

namespace App\Events;

use App\Models\CycleCulture;
use App\Models\Intervention;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RecolteEnregistree
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Intervention $intervention,
        public CycleCulture $cycle,
    ) {}
}
