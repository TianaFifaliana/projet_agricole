<?php

namespace App\Exceptions;

use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RegleMetierException extends DomainException
{
    public function status(): int
    {
        return 422;
    }

    public function errorCode(): string
    {
        return 'regle_metier';
    }

    public function render(Request $request): ?JsonResponse
    {
        if ($request->is('api/*') || $request->expectsJson()) {
            return response()->json([
                'message' => $this->getMessage(),
                'error' => $this->errorCode(),
            ], $this->status());
        }

        return null;
    }
}
