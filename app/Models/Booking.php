<?php

namespace App\Models;

use App\Enums\BookingStatus;
use App\Models\Concerns\BelongsToBusiness;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Database\Factories\BookingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property int $business_id
 * @property int $service_id
 * @property int $staff_member_id
 * @property CarbonImmutable $starts_at
 * @property CarbonImmutable $ends_at
 * @property BookingStatus $status
 * @property int $price_cents
 * @property string $customer_name
 * @property string $customer_email
 * @property string|null $customer_phone
 * @property string|null $notes
 * @property string $manage_token
 * @property CarbonImmutable|null $cancelled_at
 * @property CarbonImmutable|null $reminder_sent_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Business $business
 * @property-read Service $service
 * @property-read StaffMember $staffMember
 */
#[Fillable([
    'business_id',
    'service_id',
    'staff_member_id',
    'starts_at',
    'ends_at',
    'status',
    'price_cents',
    'customer_name',
    'customer_email',
    'customer_phone',
    'notes',
])]
#[Hidden(['manage_token'])]
class Booking extends Model
{
    use BelongsToBusiness;

    /** @use HasFactory<BookingFactory> */
    use HasFactory, HasUuids;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'confirmed',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'starts_at' => 'immutable_datetime',
            'ends_at' => 'immutable_datetime',
            'cancelled_at' => 'immutable_datetime',
            'reminder_sent_at' => 'immutable_datetime',
            'status' => BookingStatus::class,
            'price_cents' => 'integer',
        ];
    }

    /**
     * Columnas que reciben un UUID al insertar. La clave primaria sigue siendo
     * autoincremental; solo el token de gestión es un UUID.
     *
     * @return list<string>
     */
    public function uniqueIds(): array
    {
        return ['manage_token'];
    }

    /**
     * UUID v4 (aleatorio) en vez del v7 por defecto: el token da acceso a la
     * reserva, así que no debe revelar cuándo se creó.
     */
    public function newUniqueId(): string
    {
        return (string) Str::uuid();
    }

    /**
     * @param  Builder<Booking>  $query
     */
    public function scopeBlocking(Builder $query): void
    {
        $query->whereIn('bookings.status', array_map(
            fn (BookingStatus $status) => $status->value,
            BookingStatus::blocking(),
        ));
    }

    /**
     * Reservas cuyo rango [starts_at, ends_at) se cruza con el dado.
     *
     * @param  Builder<Booking>  $query
     */
    public function scopeOverlapping(Builder $query, CarbonInterface $start, CarbonInterface $end): void
    {
        // Las columnas se guardan en UTC; Eloquent no convierte la zona al enlazar.
        $query->where('bookings.starts_at', '<', CarbonImmutable::instance($end)->utc())
            ->where('bookings.ends_at', '>', CarbonImmutable::instance($start)->utc());
    }

    /**
     * Inicio de la reserva en la hora local del negocio.
     */
    public function localStartsAt(): CarbonImmutable
    {
        return $this->starts_at->setTimezone($this->business->timezone);
    }

    public function isCancellable(): bool
    {
        return $this->status->blocksSlot() && $this->starts_at->isFuture();
    }

    public function cancel(): void
    {
        $this->transitionTo(BookingStatus::Cancelled);
    }

    public function transitionTo(BookingStatus $status): void
    {
        $this->forceFill([
            'status' => $status,
            'cancelled_at' => $status === BookingStatus::Cancelled ? now() : null,
        ])->save();
    }

    /**
     * @return BelongsTo<Service, $this>
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    /**
     * @return BelongsTo<StaffMember, $this>
     */
    public function staffMember(): BelongsTo
    {
        return $this->belongsTo(StaffMember::class);
    }
}
