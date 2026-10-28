<?php

namespace App\Http\Middleware;

use App\Tenancy\CurrentBusiness;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Activa el negocio del usuario para el resto de la petición. Si todavía no
 * creó uno, lo manda al onboarding.
 */
class SetCurrentBusiness
{
    public function __construct(private CurrentBusiness $currentBusiness) {}

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $business = $request->user()?->business;

        if ($business === null) {
            return to_route('onboarding.create');
        }

        $this->currentBusiness->set($business);

        return $next($request);
    }
}
