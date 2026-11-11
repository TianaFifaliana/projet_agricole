<?php

namespace App\Events;

use App\Models\ReleveNiveauEau;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class FuiteProbableDetectee
{
    use Dispatchable, SerializesModels;

    public function __construct(public ReleveNiveauEau $releve) {}
}
