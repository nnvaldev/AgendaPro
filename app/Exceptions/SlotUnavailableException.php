<?php

namespace App\Exceptions;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RuntimeException;

class SlotUnavailableException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('El horario seleccionado ya no está disponible.');
    }

    public function render(Request $request): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => $this->getMessage()], 409);
        }

        return back()->withErrors(['starts_at' => $this->getMessage()]);
    }
}
