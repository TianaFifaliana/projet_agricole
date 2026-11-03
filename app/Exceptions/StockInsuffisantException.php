<?php

namespace App\Exceptions;

use App\Models\LotStock;

class StockInsuffisantException extends RegleMetierException
{
    public static function pour(LotStock $lot, float $quantiteDemandee): self
    {
        return new self(sprintf(
            'Stock insuffisant pour le lot %s : %s demandé(s), %s disponible(s).',
            $lot->code_lot,
            number_format($quantiteDemandee, 2, ',', ' '),
            number_format((float) $lot->quantite_restante, 2, ',', ' '),
        ));
    }
}
