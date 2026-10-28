<?php

namespace App\Models\Concerns;

use App\Models\Business;
use App\Tenancy\CurrentBusiness;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Aísla los datos por negocio: con un negocio activo, todas las consultas se
 * filtran por él (incluido el route model binding, que devuelve 404 para
 * registros de otros negocios) y los registros nuevos se asignan a él.
 *
 * @mixin Model
 */
trait BelongsToBusiness
{
    public static function bootBelongsToBusiness(): void
    {
        static::addGlobalScope('business', function (Builder $query) {
            $businessId = app(CurrentBusiness::class)->id();

            if ($businessId !== null) {
                $query->where($query->qualifyColumn('business_id'), $businessId);
            }
        });

        static::creating(function (Model $model) {
            if ($model->getAttribute('business_id') === null) {
                $model->setAttribute('business_id', app(CurrentBusiness::class)->id());
            }
        });
    }

    /**
     * @return BelongsTo<Business, $this>
     */
    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }
}
