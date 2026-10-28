<?php

namespace App\Models;

use Database\Factories\BusinessFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property int $owner_id
 * @property string $name
 * @property string $slug
 * @property string $timezone
 * @property string $currency
 * @property string|null $phone
 * @property string|null $address
 * @property int $slot_interval_minutes
 * @property int $min_notice_minutes
 * @property int $booking_window_days
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'owner_id',
    'name',
    'slug',
    'timezone',
    'currency',
    'phone',
    'address',
    'slot_interval_minutes',
    'min_notice_minutes',
    'booking_window_days',
])]
class Business extends Model
{
    /** @use HasFactory<BusinessFactory> */
    use HasFactory;

    /**
     * Valores por defecto, iguales a los de la migración, para que estén
     * disponibles en el modelo recién creado sin volver a consultarlo.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'timezone' => 'America/La_Paz',
        'currency' => 'BOB',
        'slot_interval_minutes' => 15,
        'min_notice_minutes' => 60,
        'booking_window_days' => 30,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'slot_interval_minutes' => 'integer',
            'min_notice_minutes' => 'integer',
            'booking_window_days' => 'integer',
        ];
    }

    public static function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'negocio';
        $slug = $base;

        for ($suffix = 2; static::where('slug', $slug)->exists(); $suffix++) {
            $slug = "{$base}-{$suffix}";
        }

        return $slug;
    }

    /**
     * Servicios y profesionales activos, tal como los ve el cliente al reservar.
     */
    public function loadBookableCatalog(): static
    {
        return $this->load([
            'services' => fn ($query) => $query->active()->orderBy('name')->with([
                'staffMembers' => fn ($query) => $query->active(),
            ]),
            'staffMembers' => fn ($query) => $query->active()->orderBy('name'),
        ]);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    /**
     * @return HasMany<Service, $this>
     */
    public function services(): HasMany
    {
        return $this->hasMany(Service::class);
    }

    /**
     * @return HasMany<StaffMember, $this>
     */
    public function staffMembers(): HasMany
    {
        return $this->hasMany(StaffMember::class);
    }

    /**
     * @return HasMany<Booking, $this>
     */
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }
}
