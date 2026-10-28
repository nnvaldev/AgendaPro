<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import {
    CalendarX2,
    Check,
    ChevronLeft,
    ChevronRight,
    MessageCircle,
    StickyNote,
    UserX,
    X,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import BookingStatusBadge from '@/components/BookingStatusBadge.vue';
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import { Button } from '@/components/ui/button';
import { addDays, formatDateLong, formatMoney, timeOf } from '@/lib/format';
import { index } from '@/routes/agenda';
import { update as updateStatus } from '@/routes/agenda/status';
import type { BookingStatus, PanelBooking } from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Agenda', href: index() }],
    },
});

const props = defineProps<{
    date: string;
    today: string;
    bookings: PanelBooking[];
    staff: { id: number; name: string }[];
}>();

const staffFilter = ref<number | null>(null);

const visibleBookings = computed(() =>
    staffFilter.value === null
        ? props.bookings
        : props.bookings.filter((b) => b.staff_member_id === staffFilter.value),
);

const activeCount = computed(
    () => props.bookings.filter((b) => b.status !== 'cancelled').length,
);

const expectedRevenue = computed(() =>
    props.bookings
        .filter((b) => b.status === 'confirmed' || b.status === 'completed')
        .reduce((sum, b) => sum + b.price_cents, 0),
);

function goTo(date: string) {
    router.get(
        index({ query: { date } }).url,
        {},
        { preserveState: true, preserveScroll: true },
    );
}

function setStatus(
    booking: PanelBooking,
    status: BookingStatus,
    close?: () => void,
) {
    router.patch(
        updateStatus(booking.id).url,
        { status },
        { preserveScroll: true, onFinish: close },
    );
}

function whatsappUrl(phone: string, booking: PanelBooking): string {
    const text = `Hola ${booking.customer_name}, te escribimos por tu reserva de ${booking.service} a las ${timeOf(booking.starts_at)}.`;

    return `https://wa.me/${phone.replace(/\D/g, '')}?text=${encodeURIComponent(text)}`;
}
</script>

<template>
    <Head title="Agenda" />

    <div class="flex flex-1 flex-col gap-6 p-4 md:p-6">
        <header class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h1
                    class="text-xl font-semibold tracking-tight first-letter:uppercase"
                >
                    {{ formatDateLong(date) }}
                </h1>
                <p class="text-sm text-muted-foreground">
                    {{ activeCount }}
                    {{ activeCount === 1 ? 'reserva' : 'reservas' }} ·
                    {{ formatMoney(expectedRevenue) }} estimados
                </p>
            </div>

            <div class="flex items-center gap-2">
                <Button
                    variant="outline"
                    size="icon"
                    title="Día anterior"
                    @click="goTo(addDays(date, -1))"
                >
                    <ChevronLeft />
                </Button>
                <Button
                    variant="outline"
                    :disabled="date === today"
                    @click="goTo(today)"
                >
                    Hoy
                </Button>
                <Button
                    variant="outline"
                    size="icon"
                    title="Día siguiente"
                    @click="goTo(addDays(date, 1))"
                >
                    <ChevronRight />
                </Button>
                <input
                    type="date"
                    :value="date"
                    class="h-9 rounded-md border border-input bg-transparent px-2 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 dark:bg-input/30"
                    aria-label="Elegir fecha"
                    @change="
                        (e) => {
                            const value = (e.target as HTMLInputElement).value;
                            if (value) goTo(value);
                        }
                    "
                />
            </div>
        </header>

        <div v-if="staff.length > 1" class="flex flex-wrap gap-2">
            <Button
                size="sm"
                :variant="staffFilter === null ? 'default' : 'outline'"
                @click="staffFilter = null"
            >
                Todos
            </Button>
            <Button
                v-for="member in staff"
                :key="member.id"
                size="sm"
                :variant="staffFilter === member.id ? 'default' : 'outline'"
                @click="staffFilter = member.id"
            >
                {{ member.name }}
            </Button>
        </div>

        <div
            v-if="!visibleBookings.length"
            class="flex flex-col items-center gap-3 rounded-xl border border-dashed p-12 text-center"
        >
            <CalendarX2 class="size-8 text-muted-foreground" />
            <p class="font-medium">Sin reservas para este día</p>
            <p class="text-sm text-muted-foreground">
                Cuando un cliente reserve desde tu página, aparecerá aquí.
            </p>
        </div>

        <ol v-else class="relative space-y-3 border-l-2 border-primary/20 pl-6">
            <li
                v-for="booking in visibleBookings"
                :key="booking.id"
                class="relative rounded-xl border bg-card p-4"
                :class="{ 'opacity-60': booking.status === 'cancelled' }"
            >
                <span
                    class="absolute top-5 -left-[1.95rem] size-3 rounded-full ring-4 ring-background"
                    :class="
                        booking.status === 'cancelled'
                            ? 'bg-muted-foreground/40'
                            : 'bg-fire'
                    "
                />

                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div class="flex gap-4">
                        <div class="w-14 shrink-0">
                            <p class="text-lg font-semibold tabular-nums">
                                {{ timeOf(booking.starts_at) }}
                            </p>
                            <p
                                class="text-xs text-muted-foreground tabular-nums"
                            >
                                {{ timeOf(booking.ends_at) }}
                            </p>
                        </div>
                        <div class="min-w-0">
                            <p class="font-medium">
                                {{ booking.customer_name }}
                            </p>
                            <p class="text-sm text-muted-foreground">
                                {{ booking.service }} · con
                                {{ booking.staff_member }} ·
                                {{ formatMoney(booking.price_cents) }}
                            </p>
                            <p class="mt-1 text-xs text-muted-foreground">
                                {{ booking.customer_email }}
                                <template v-if="booking.customer_phone">
                                    · {{ booking.customer_phone }}
                                </template>
                            </p>
                        </div>
                    </div>
                    <BookingStatusBadge
                        :status="booking.status"
                        :label="booking.status_label"
                    />
                </div>

                <p
                    v-if="booking.notes"
                    class="mt-3 flex gap-2 rounded-md bg-muted px-3 py-2 text-sm"
                >
                    <StickyNote
                        class="mt-0.5 size-4 shrink-0 text-muted-foreground"
                    />
                    {{ booking.notes }}
                </p>

                <div
                    v-if="booking.status !== 'cancelled'"
                    class="mt-3 flex flex-wrap justify-end gap-2"
                >
                    <Button
                        v-if="booking.customer_phone"
                        variant="ghost"
                        size="sm"
                        as-child
                    >
                        <a
                            :href="whatsappUrl(booking.customer_phone, booking)"
                            target="_blank"
                            rel="noopener"
                        >
                            <MessageCircle /> WhatsApp
                        </a>
                    </Button>
                    <Button
                        v-if="booking.status !== 'completed'"
                        variant="outline"
                        size="sm"
                        @click="setStatus(booking, 'completed')"
                    >
                        <Check /> Completada
                    </Button>
                    <Button
                        v-if="booking.status !== 'no_show'"
                        variant="outline"
                        size="sm"
                        @click="setStatus(booking, 'no_show')"
                    >
                        <UserX /> No asistió
                    </Button>
                    <ConfirmDialog
                        v-if="
                            booking.status === 'confirmed' ||
                            booking.status === 'pending'
                        "
                        title="¿Cancelar esta reserva?"
                        :description="`El horario de las ${timeOf(booking.starts_at)} volverá a quedar libre. Avísale a ${booking.customer_name}.`"
                        confirm-label="Cancelar reserva"
                        @confirm="
                            (close) => setStatus(booking, 'cancelled', close)
                        "
                    >
                        <Button
                            variant="ghost"
                            size="sm"
                            class="text-destructive"
                        >
                            <X /> Cancelar
                        </Button>
                    </ConfirmDialog>
                </div>
            </li>
        </ol>
    </div>
</template>
