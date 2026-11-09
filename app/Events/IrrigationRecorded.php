<?php

namespace App\Events;

use App\Models\Irrigation;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class IrrigationRecorded
{
    use Dispatchable, SerializesModels;

    public function __construct(public Irrigation $irrigation) {}
}
