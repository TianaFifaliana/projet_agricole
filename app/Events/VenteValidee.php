<?php

namespace App\Events;

use App\Models\Vente;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class VenteValidee
{
    use Dispatchable, SerializesModels;

    public function __construct(public Vente $vente) {}
}
