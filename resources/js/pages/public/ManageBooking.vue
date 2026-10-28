<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { CalendarPlus, CalendarX2, LoaderCircle } from '@lucide/vue';
import { ref } from 'vue';
import { cancel } from '@/actions/App/Http/Controllers/Api/BookingController';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import BookingStatusBadge from '@/components/BookingStatusBadge.vue';
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import { Button } from '@/components/ui/button';
import { formatDateLong, formatMoney, timeOf } from '@/lib/format';
import { booking as publicBooking } from '@/routes/public';
import type { PublicBooking } from '@/types';

const props = defineProps<{
    booking: PublicBooking;
    token: string;
    cancellable: boolean;
}>();

const STATUS_LABELS: Record<PublicBooking['status'], string> = {
    pending: 'Pendiente',
    confirmed: 'Confirmada',
    cancelled: 'Cancelada',
    completed: 'Completada',
    no_show: 'No asististe',
};

const cancelling = ref(false);
const error = ref<string | null>(null);

async function cancelBooking(close: () => void) {
    cancelling.value = true;
    error.value = null;

    try {
        const response = await fetch(cancel.url(props.token), {
            method: 'POST',
            headers: { Accept: 'application/json' },
        });

        if (!response.ok) {
            error.value =
                (await response.json()).message ?? 'No se pudo cancelar.';

            return;
        }

        router.reload({ only: ['booking', 'cancellable'] });
    } catch {
        error.value = 'Sin conexión. Intenta de nuevo.';
    } finally {
        cancelling.value = false;
        close();
    }
}
</script>

<template>
    <Head title="Mi reserva" />

    <div
        class="flex min-h-screen flex-col items-center justify-center bg-background p-4"
    >
        <div
            class="w-full max-w-md overflow-hidden rounded-2xl border bg-card shadow-sm"
        >
            <div class="h-1.5 bg-fire" />
            <div class="p-6">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-sm text-muted-foreground">
                            Tu reserva en
                        </p>
                        <h1 class="text-xl font-semibold">
                            {{ booking.business.name }}
                        </h1>
                    </div>
                    <BookingStatusBadge
                        :status="booking.status"
                        :label="STATUS_LABELS[booking.status]"
                    />
                </div>

                <dl class="mt-6 space-y-3 text-sm">
                    <div class="flex justify-between gap-4">
                        <dt class="text-muted-foreground">Servicio</dt>
                        <dd class="font-medium">{{ booking.service }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-muted-foreground">Con</dt>
                        <dd class="font-medium">{{ booking.staff_member }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-muted-foreground">Cuándo</dt>
                        <dd
                            class="text-right font-medium first-letter:uppercase"
                        >
                            {{
                                formatDateLong(booking.starts_at.slice(0, 10))
                            }},
                            {{ timeOf(booking.starts_at) }}
                        </dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-muted-foreground">Total</dt>
                        <dd class="font-medium">
                            {{
                                formatMoney(
                                    booking.price_cents,
                                    booking.currency,
                                )
                            }}
                        </dd>
                    </div>
                </dl>

                <p
                    v-if="error"
                    class="mt-4 rounded-md bg-destructive/10 px-3 py-2 text-sm text-destructive"
                >
                    {{ error }}
                </p>

                <div class="mt-6 flex flex-col gap-2">
                    <ConfirmDialog
                        v-if="cancellable"
                        title="¿Cancelar tu reserva?"
                        description="El horario quedará libre para otra persona. Si quieres otro día, puedes reservar de nuevo."
                        confirm-label="Sí, cancelar"
                        @confirm="cancelBooking"
                    >
                        <Button
                            variant="outline"
                            class="w-full"
                            :disabled="cancelling"
                        >
                            <LoaderCircle
                                v-if="cancelling"
                                class="animate-spin"
                            />
                            <CalendarX2 v-else />
                            Cancelar reserva
                        </Button>
                    </ConfirmDialog>
                    <Button
                        as-child
                        :variant="cancellable ? 'ghost' : 'default'"
                    >
                        <Link :href="publicBooking(booking.business.slug)">
                            <CalendarPlus /> Hacer otra reserva
                        </Link>
                    </Button>
                </div>
            </div>
        </div>

        <p class="mt-6 flex items-center gap-2 text-xs text-muted-foreground">
            <span
                class="flex size-5 items-center justify-center rounded bg-fire"
            >
                <AppLogoIcon class="size-3 text-white" />
            </span>
            Reservas con AgendaPro
        </p>
    </div>
</template>
