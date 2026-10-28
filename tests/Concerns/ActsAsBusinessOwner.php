<?php

namespace Tests\Concerns;

use App\Models\Business;
use App\Models\User;

trait ActsAsBusinessOwner
{
    /**
     * Autentica al dueño de un negocio nuevo y devuelve el negocio.
     */
    protected function actingAsOwner(): Business
    {
        $business = Business::factory()->create();
        $owner = $business->owner;
        assert($owner instanceof User);

        $this->actingAs($owner);

        return $business;
    }
}
