<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    CalendarCheck,
    Check,
    Clock,
    LoaderCircle,
    MapPin,
    Phone,
    Sparkles,
} from '@lucide/vue';
import { computed, reactive, ref, watch } from 'vue';
import { availability } from '@/actions/App/Http/Controllers/Api/BusinessController';
import { store as storeBooking } from '@/actions/App/Http/Controllers/Api/BookingController';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import {
    addDays,
    formatDateLong,
    formatDuration,
    formatMoney,
    timeOf,
} from '@/lib/format';
import { cn } from '@/lib/utils';
import { manage } from '@/routes/public/booking';
import type { PublicBooking, PublicBusiness, Slot } from '@/types';

const props = defineProps<{
    business: PublicBusiness;
    today: string;
    bookingWindowDays: number;
}>();

const ANY_STAFF = 0;

const serviceId = ref<number | null>(
    props.business.services.length === 1 ? props.business.services[0].id : null,
);
const staffId = ref<number>(ANY_STAFF);
const date = ref(props.today);
const slots = ref<Slot[]>([]);
const loadingSlots = ref(false);
const selectedSlot = ref<Slot | null>(null);
const slotsError = ref<string | null>(null);

const customer = reactive({
    customer_name: '',
    customer_email: '',
    customer_phone: '',
    notes: '',
});
const errors = ref<Record<string, string>>({});
const submitting = ref(false);
const confirmed = ref<PublicBooking | null>(null);

const service = computed(
    () => props.business.services.find((s) => s.id === serviceId.value) ?? null,
);

const staffForService = computed(() =>
    service.value
        ? props.business.staff.filter((m) =>
              service.value!.staff_member_ids.includes(m.id),
          )
        : [],
);

const staffName = (id: number) =>
    props.business.staff.find((m) => m.id === id)?.name ?? '';

/** Próximos 14 días (o menos si la ventana de reserva es más corta). */
const dayOptions = computed(() =>
    Array.from({ length: Math.min(14, props.bookingWindowDays + 1) }, (_, i) =>
        addDays(props.today, i),
    ),
);

const lastBookableDay = computed(() =>
    addDays(props.today, props.bookingWindowDays),
);

/**
 * Con "cualquiera" se muestra cada hora una sola vez; al reservar sin
 * profesional, el servidor asigna al primero que esté libre.
 */
const visibleSlots = computed(() => {
    if (staffId.value !== ANY_STAFF) {
        return slots.value;
    }

    const seen = new Set<string>();

    return slots.value.filter((slot) => {
        if (seen.has(slot.starts_at)) {
            return false;
        }

        seen.add(slot.starts_at);

        return true;
    });
});

const slotGroups = computed(() => {
    const groups = [
        { label: 'Mañana', slots: [] as Slot[] },
        { label: 'Tarde', slots: [] as Slot[] },
        { label: 'Noche', slots: [] as Slot[] },
    ];

    for (const slot of visibleSlots.value) {
        const hour = Number(timeOf(slot.starts_at).slice(0, 2));
        groups[hour < 12 ? 0 : hour < 18 ? 1 : 2].slots.push(slot);
    }

    return groups.filter((group) => group.slots.length);
});

async function loadSlots() {
    selectedSlot.value = null;
    slotsError.value = null;

    if (!serviceId.value) {
        slots.value = [];

        return;
    }

    loadingSlots.value = true;

    try {
        const query: Record<string, string | number> = {
            service_id: serviceId.value,
            date: date.value,
        };

        if (staffId.value !== ANY_STAFF) {
            query.staff_member_id = staffId.value;
        }

        const response = await fetch(
            availability.url(props.business.slug, { query }),
            { headers: { Accept: 'application/json' } },
        );

        if (!response.ok) {
            throw new Error();
        }

        slots.value = (await response.json()).data;
    } catch {
        slots.value = [];
        slotsError.value = 'No pudimos cargar los horarios. Intenta de nuevo.';
    } finally {
        loadingSlots.value = false;
    }
}

watch([serviceId, staffId, date], loadSlots, { immediate: true });

watch(serviceId, () => {
    // Si el profesional elegido no hace el nuevo servicio, vuelve a "cualquiera".
    if (!staffForService.value.some((m) => m.id === staffId.value)) {
        staffId.value = ANY_STAFF;
    }
});

async function submit() {
    if (!service.value || !selectedSlot.value) {
        return;
    }

    submitting.value = true;
    errors.value = {};

    try {
        const response = await fetch(storeBooking.url(props.business.slug), {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                service_id: service.value.id,
                staff_member_id:
                    staffId.value === ANY_STAFF ? null : staffId.value,
                starts_at: selectedSlot.value.starts_at,
                ...customer,
            }),
        });

        const body = await response.json();

        if (response.status === 201) {
            confirmed.value = body.data;
            window.scrollTo({ top: 0, behavior: 'smooth' });

            return;
        }

        if (response.status === 422) {
            errors.value = Object.fromEntries(
                Object.entries(body.errors as Record<string, string[]>).map(
                    ([field, messages]) => [field, messages[0]],
                ),
            );

            return;
        }

        if (response.status === 409) {
            // Alguien tomó ese horario mientras el cliente llenaba sus datos.
            await loadSlots();
            slotsError.value = body.message;

            return;
        }

        errors.value = {
            form: 'Algo salió mal. Intenta de nuevo en un momento.',
        };
    } catch {
        errors.value = {
            form: 'Sin conexión. Revisa tu internet e intenta de nuevo.',
        };
    } finally {
        submitting.value = false;
    }
}

function stepClass(active: boolean) {
    return cn(
        'flex size-7 shrink-0 items-center justify-center rounded-full text-sm font-semibold',
        active ? 'bg-fire text-white' : 'bg-muted text-muted-foreground',
    );
}
</script>

<template>
    <Head :title="`Reservar en ${business.name}`" />

    <div class="min-h-screen bg-background">
        <header
            class="relative overflow-hidden bg-sidebar text-sidebar-foreground"
        >
            <div
                class="pointer-events-none absolute -top-24 -right-24 size-80 rounded-full bg-fire opacity-30 blur-3xl"
            />
            <div class="relative mx-auto max-w-5xl px-4 py-10 sm:px-6">
                <p
                    class="text-xs font-semibold tracking-widest text-sidebar-accent-foreground uppercase"
                >
                    Reserva en línea
                </p>
                <h1
                    class="mt-2 text-3xl font-bold tracking-tight text-white sm:text-4xl"
                >
                    {{ business.name }}
                </h1>
                <div
                    class="mt-4 flex flex-wrap gap-x-6 gap-y-2 text-sm text-sidebar-foreground/70"
                >
                    <span
                        v-if="business.address"
                        class="inline-flex items-center gap-1.5"
                    >
                        <MapPin class="size-4" /> {{ business.address }}
                    </span>
                    <a
                        v-if="business.phone"
                        :href="`tel:${business.phone}`"
                        class="inline-flex items-center gap-1.5 hover:text-sidebar-accent-foreground"
                    >
                        <Phone class="size-4" /> {{ business.phone }}
                    </a>
                </div>
            </div>
        </header>

        <main class="mx-auto max-w-5xl px-4 py-8 sm:px-6">
            <!-- Confirmación -->
            <section
                v-if="confirmed"
                class="mx-auto max-w-lg rounded-2xl border bg-card p-8 text-center shadow-sm"
            >
                <div
                    class="mx-auto flex size-14 items-center justify-center rounded-full bg-fire text-white"
                >
                    <Check class="size-7" />
                </div>
                <h2 class="mt-5 text-2xl font-semibold">
                    ¡Reserva confirmada!
                </h2>
                <p class="mt-2 text-muted-foreground">
                    Te esperamos, {{ confirmed.customer_name.split(' ')[0] }}.
                </p>
                <dl
                    class="mt-6 space-y-2 rounded-lg bg-muted p-4 text-left text-sm"
                >
                    <div class="flex justify-between gap-4">
                        <dt class="text-muted-foreground">Servicio</dt>
                        <dd class="font-medium">{{ confirmed.service }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-muted-foreground">Con</dt>
                        <dd class="font-medium">
                            {{ confirmed.staff_member }}
                        </dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-muted-foreground">Cuándo</dt>
                        <dd
                            class="text-right font-medium first-letter:uppercase"
                        >
                            {{
                                formatDateLong(
                                    confirmed.starts_at.slice(0, 10),
                                )
                            }},
                            {{ timeOf(confirmed.starts_at) }}
                        </dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-muted-foreground">Total</dt>
                        <dd class="font-medium">
                            {{
                                formatMoney(
                                    confirmed.price_cents,
                                    confirmed.currency,
                                )
                            }}
                        </dd>
                    </div>
                </dl>
                <p class="mt-6 text-sm text-muted-foreground">
                    Guarda este link para ver o cancelar tu reserva:
                </p>
                <Button
                    v-if="confirmed.manage_token"
                    as-child
                    variant="outline"
                    class="mt-2"
                >
                    <Link :href="manage(confirmed.manage_token)">
                        <CalendarCheck /> Ver mi reserva
                    </Link>
                </Button>
            </section>

            <div v-else class="grid gap-8 lg:grid-cols-[1fr_320px]">
                <div class="min-w-0 space-y-10">
                    <!-- 1. Servicio -->
                    <section class="space-y-4">
                        <h2
                            class="flex items-center gap-3 text-lg font-semibold"
                        >
                            <span :class="stepClass(true)">1</span> Elige el
                            servicio
                        </h2>
                        <p
                            v-if="!business.services.length"
                            class="text-muted-foreground"
                        >
                            Este negocio todavía no publicó servicios.
                        </p>
                        <div class="grid gap-3 sm:grid-cols-2">
                            <button
                                v-for="item in business.services"
                                :key="item.id"
                                type="button"
                                class="rounded-xl border bg-card p-4 text-left transition-all hover:border-primary/50"
                                :class="{
                                    'border-primary ring-2 ring-primary/30':
                                        serviceId === item.id,
                                }"
                                @click="serviceId = item.id"
                            >
                                <div
                                    class="flex items-start justify-between gap-3"
                                >
                                    <span class="font-medium">{{
                                        item.name
                                    }}</span>
                                    <span
                                        class="font-semibold whitespace-nowrap text-primary"
                                    >
                                        {{
                                            formatMoney(
                                                item.price_cents,
                                                business.currency,
                                            )
                                        }}
                                    </span>
                                </div>
                                <p
                                    v-if="item.description"
                                    class="mt-1 line-clamp-2 text-sm text-muted-foreground"
                                >
                                    {{ item.description }}
                                </p>
                                <p
                                    class="mt-2 inline-flex items-center gap-1.5 text-xs text-muted-foreground"
                                >
                                    <Clock class="size-3.5" />
                                    {{ formatDuration(item.duration_minutes) }}
                                </p>
                            </button>
                        </div>
                    </section>

                    <template v-if="service">
                        <!-- 2. Profesional -->
                        <section
                            v-if="staffForService.length > 1"
                            class="space-y-4"
                        >
                            <h2
                                class="flex items-center gap-3 text-lg font-semibold"
                            >
                                <span :class="stepClass(true)">2</span> ¿Con
                                quién?
                            </h2>
                            <div class="flex flex-wrap gap-2">
                                <Button
                                    :variant="
                                        staffId === ANY_STAFF
                                            ? 'default'
                                            : 'outline'
                                    "
                                    @click="staffId = ANY_STAFF"
                                >
                                    <Sparkles /> Cualquiera
                                </Button>
                                <Button
                                    v-for="member in staffForService"
                                    :key="member.id"
                                    :variant="
                                        staffId === member.id
                                            ? 'default'
                                            : 'outline'
                                    "
                                    @click="staffId = member.id"
                                >
                                    {{ member.name }}
                                </Button>
                            </div>
                        </section>

                        <!-- 3. Fecha y hora -->
                        <section class="space-y-4">
                            <h2
                                class="flex items-center gap-3 text-lg font-semibold"
                            >
                                <span :class="stepClass(true)">{{
                                    staffForService.length > 1 ? 3 : 2
                                }}</span>
                                Día y hora
                            </h2>

                            <div
                                class="-mx-4 flex gap-2 overflow-x-auto px-4 pb-2"
                            >
                                <button
                                    v-for="day in dayOptions"
                                    :key="day"
                                    type="button"
                                    class="flex w-16 shrink-0 flex-col items-center rounded-xl border py-2 transition-colors"
                                    :class="
                                        date === day
                                            ? 'border-transparent bg-fire text-white'
                                            : 'bg-card hover:border-primary/50'
                                    "
                                    @click="date = day"
                                >
                                    <span class="text-xs capitalize opacity-80">
                                        {{
                                            new Intl.DateTimeFormat('es-BO', {
                                                weekday: 'short',
                                                timeZone: 'UTC',
                                            }).format(
                                                new Date(`${day}T12:00:00Z`),
                                            )
                                        }}
                                    </span>
                                    <span class="text-lg font-semibold">{{
                                        Number(day.slice(8))
                                    }}</span>
                                </button>
                                <input
                                    type="date"
                                    :min="today"
                                    :max="lastBookableDay"
                                    :value="date"
                                    class="h-auto shrink-0 rounded-xl border bg-card px-2 text-sm"
                                    aria-label="Otra fecha"
                                    @change="
                                        (e) => {
                                            const value = (
                                                e.target as HTMLInputElement
                                            ).value;
                                            if (value) date = value;
                                        }
                                    "
                                />
                            </div>

                            <p
                                class="text-sm font-medium first-letter:uppercase"
                            >
                                {{ formatDateLong(date) }}
                            </p>

                            <p
                                v-if="slotsError"
                                class="rounded-md border border-destructive/30 bg-destructive/10 px-3 py-2 text-sm text-destructive"
                            >
                                {{ slotsError }}
                            </p>

                            <div
                                v-if="loadingSlots"
                                class="flex items-center gap-2 py-6 text-sm text-muted-foreground"
                            >
                                <LoaderCircle class="size-4 animate-spin" />
                                Buscando horarios…
                            </div>
                            <p
                                v-else-if="!visibleSlots.length"
                                class="rounded-xl border border-dashed p-6 text-center text-sm text-muted-foreground"
                            >
                                No quedan horarios libres este día. Prueba con
                                otra fecha.
                            </p>
                            <div v-else class="space-y-4">
                                <div
                                    v-for="group in slotGroups"
                                    :key="group.label"
                                >
                                    <p
                                        class="mb-2 text-xs font-medium text-muted-foreground uppercase"
                                    >
                                        {{ group.label }}
                                    </p>
                                    <div
                                        class="grid grid-cols-4 gap-2 sm:grid-cols-6"
                                    >
                                        <button
                                            v-for="slot in group.slots"
                                            :key="`${slot.starts_at}-${slot.staff_member_id}`"
                                            type="button"
                                            class="rounded-lg border py-2 text-sm font-medium tabular-nums transition-colors"
                                            :class="
                                                selectedSlot?.starts_at ===
                                                slot.starts_at
                                                    ? 'border-transparent bg-primary text-primary-foreground'
                                                    : 'bg-card hover:border-primary/50 hover:text-primary'
                                            "
                                            @click="selectedSlot = slot"
                                        >
                                            {{ timeOf(slot.starts_at) }}
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </section>

                        <!-- 4. Datos -->
                        <section v-if="selectedSlot" class="space-y-4">
                            <h2
                                class="flex items-center gap-3 text-lg font-semibold"
                            >
                                <span :class="stepClass(true)">{{
                                    staffForService.length > 1 ? 4 : 3
                                }}</span>
                                Tus datos
                            </h2>
                            <form
                                id="booking-form"
                                class="grid gap-4 sm:grid-cols-2"
                                @submit.prevent="submit"
                            >
                                <div class="grid gap-2 sm:col-span-2">
                                    <Label for="customer_name"
                                        >Nombre completo</Label
                                    >
                                    <Input
                                        id="customer_name"
                                        v-model="customer.customer_name"
                                        required
                                        autocomplete="name"
                                    />
                                    <InputError
                                        :message="errors.customer_name"
                                    />
                                </div>
                                <div class="grid gap-2">
                                    <Label for="customer_email">Email</Label>
                                    <Input
                                        id="customer_email"
                                        v-model="customer.customer_email"
                                        type="email"
                                        required
                                        autocomplete="email"
                                    />
                                    <InputError
                                        :message="errors.customer_email"
                                    />
                                </div>
                                <div class="grid gap-2">
                                    <Label for="customer_phone">
                                        Celular / WhatsApp
                                        <span class="text-muted-foreground"
                                            >(opcional)</span
                                        >
                                    </Label>
                                    <Input
                                        id="customer_phone"
                                        v-model="customer.customer_phone"
                                        type="tel"
                                        autocomplete="tel"
                                        placeholder="+591 7…"
                                    />
                                    <InputError
                                        :message="errors.customer_phone"
                                    />
                                </div>
                                <div class="grid gap-2 sm:col-span-2">
                                    <Label for="notes">
                                        Nota para el negocio
                                        <span class="text-muted-foreground"
                                            >(opcional)</span
                                        >
                                    </Label>
                                    <Textarea
                                        id="notes"
                                        v-model="customer.notes"
                                        rows="2"
                                    />
                                    <InputError :message="errors.notes" />
                                </div>
                            </form>
                        </section>
                    </template>
                </div>

                <!-- Resumen -->
                <aside class="lg:sticky lg:top-6 lg:self-start">
                    <div class="rounded-2xl border bg-card p-5 shadow-sm">
                        <h2 class="font-semibold">Tu reserva</h2>
                        <dl v-if="service" class="mt-4 space-y-3 text-sm">
                            <div>
                                <dt class="text-muted-foreground">Servicio</dt>
                                <dd class="font-medium">
                                    {{ service.name }} ·
                                    {{
                                        formatDuration(service.duration_minutes)
                                    }}
                                </dd>
                            </div>
                            <div v-if="selectedSlot">
                                <dt class="text-muted-foreground">Cuándo</dt>
                                <dd class="font-medium first-letter:uppercase">
                                    {{ formatDateLong(date) }},
                                    {{ timeOf(selectedSlot.starts_at) }}
                                </dd>
                            </div>
                            <div>
                                <dt class="text-muted-foreground">Con</dt>
                                <dd class="font-medium">
                                    {{
                                        staffId !== ANY_STAFF
                                            ? staffName(staffId)
                                            : 'El primer profesional disponible'
                                    }}
                                </dd>
                            </div>
                            <div
                                class="flex items-baseline justify-between border-t pt-3"
                            >
                                <dt class="text-muted-foreground">Total</dt>
                                <dd class="text-xl font-semibold">
                                    {{
                                        formatMoney(
                                            service.price_cents,
                                            business.currency,
                                        )
                                    }}
                                </dd>
                            </div>
                        </dl>
                        <p v-else class="mt-2 text-sm text-muted-foreground">
                            Elige un servicio para empezar.
                        </p>

                        <InputError class="mt-3" :message="errors.form" />

                        <Button
                            type="submit"
                            form="booking-form"
                            class="mt-5 w-full"
                            size="lg"
                            :disabled="!selectedSlot || submitting"
                        >
                            <LoaderCircle
                                v-if="submitting"
                                class="animate-spin"
                            />
                            Confirmar reserva
                        </Button>
                        <p
                            class="mt-3 text-center text-xs text-muted-foreground"
                        >
                            Sin crear cuenta · Pagas en el local
                        </p>
                    </div>
                </aside>
            </div>
        </main>

        <footer
            class="flex items-center justify-center gap-2 py-8 text-xs text-muted-foreground"
        >
            <span
                class="flex size-5 items-center justify-center rounded bg-fire"
            >
                <AppLogoIcon class="size-3 text-white" />
            </span>
            Reservas con AgendaPro
        </footer>
    </div>
</template>
