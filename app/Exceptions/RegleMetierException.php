<?php

namespace App\Exceptions;

use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RegleMetierException extends DomainException
{
    public function render(Request $request): ?JsonResponse
    {
        if ($request->expectsJson()) {
            return response()->json([
                'message' => $this->getMessage(),
            ], 422);
        }

        return null;
    }
}
