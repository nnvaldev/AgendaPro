<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBusiness;
use Database\Factories\StaffMemberFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * Profesional que atiende en el negocio. Puede no tener cuenta de usuario
 * (p. ej. un barbero cuya agenda gestiona el dueño).
 *
 * @property int $id
 * @property int $business_id
 * @property int|null $user_id
 * @property string $name
 * @property string|null $email
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, WorkingHour> $workingHours
 * @property-read Collection<int, TimeOff> $timeOffs
 * @property-read Collection<int, Booking> $bookings
 * @property-read Collection<int, Service> $services
 */
#[Fillable(['user_id', 'name', 'email', 'is_active'])]
class StaffMember extends Model
{
    use BelongsToBusiness;

    /** @use HasFactory<StaffMemberFactory> */
    use HasFactory;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'is_active' => true,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * @param  Builder<StaffMember>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('staff_members.is_active', true);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsToMany<Service, $this>
     */
    public function services(): BelongsToMany
    {
        return $this->belongsToMany(Service::class);
    }

    /**
     * @return HasMany<WorkingHour, $this>
     */
    public function workingHours(): HasMany
    {
        return $this->hasMany(WorkingHour::class);
    }

    /**
     * @return HasMany<TimeOff, $this>
     */
    public function timeOffs(): HasMany
    {
        return $this->hasMany(TimeOff::class);
    }

    /**
     * @return HasMany<Booking, $this>
     */
    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }
}
