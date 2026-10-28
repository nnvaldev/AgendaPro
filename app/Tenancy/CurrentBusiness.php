<?php

namespace App\Tenancy;

use App\Models\Business;
use LogicException;

/**
 * Negocio del usuario autenticado durante la petición actual.
 *
 * Lo fija el middleware SetCurrentBusiness en las rutas del panel; en la API
 * pública queda vacío y los modelos no se filtran por negocio.
 */
class CurrentBusiness
{
    private ?Business $business = null;

    public function set(?Business $business): void
    {
        $this->business = $business;
    }

    public function get(): ?Business
    {
        return $this->business;
    }

    public function getOrFail(): Business
    {
        return $this->business ?? throw new LogicException('No hay un negocio activo en esta petición.');
    }

    public function id(): ?int
    {
        return $this->business?->id;
    }
}
