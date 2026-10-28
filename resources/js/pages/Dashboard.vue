<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    CalendarCheck,
    CalendarClock,
    Check,
    Copy,
    ExternalLink,
    UserX,
    Wallet,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import BookingStatusBadge from '@/components/BookingStatusBadge.vue';
import { Button } from '@/components/ui/button';
import { formatDateShort, formatMoney, dateOf, timeOf } from '@/lib/format';
import { dashboard } from '@/routes';
import { index as agenda } from '@/routes/agenda';
import { create as createService } from '@/routes/services';
import { index as staffIndex } from '@/routes/staff';
import type { PanelBooking } from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Inicio', href: dashboard() }],
    },
});

const props = defineProps<{
    stats: {
        today: number;
        next_7_days: number;
        month_revenue_cents: number;
        month_no_shows: number;
    };
    upcoming: PanelBooking[];
    setup: { services: boolean; staff: boolean; schedule: boolean };
    publicUrl: string;
}>();

const setupSteps = computed(() => [
    {
        done: props.setup.services,
        title: 'Crea tus servicios',
        description: 'Qué ofreces, cuánto dura y cuánto cuesta.',
        href: createService(),
    },
    {
        done: props.setup.staff,
        title: 'Agrega a tu equipo',
        description: 'Quién atiende y qué servicios realiza.',
        href: staffIndex(),
    },
    {
        done: props.setup.schedule,
        title: 'Define los horarios',
        description: 'Los días y horas en que se puede reservar.',
        href: staffIndex(),
    },
]);

const setupComplete = computed(() =>
    setupSteps.value.every((step) => step.done),
);

const statCards = computed(() => [
    { label: 'Reservas hoy', value: props.stats.today, icon: CalendarCheck },
    {
        label: 'Próximos 7 días',
        value: props.stats.next_7_days,
        icon: CalendarClock,
    },
    {
        label: 'Ingresos del mes',
        value: formatMoney(props.stats.month_revenue_cents),
        icon: Wallet,
        hint: 'Reservas confirmadas y completadas',
    },
    {
        label: 'No asistieron (mes)',
        value: props.stats.month_no_shows,
        icon: UserX,
    },
]);

const copied = ref(false);

async function copyPublicUrl() {
    await navigator.clipboard.writeText(props.publicUrl);
    copied.value = true;
    setTimeout(() => (copied.value = false), 2000);
}
</script>

<template>
    <Head title="Inicio" />

    <div class="flex flex-1 flex-col gap-6 p-4 md:p-6">
        <section
            v-if="!setupComplete"
            class="relative overflow-hidden rounded-xl border bg-card p-6"
        >
            <div class="absolute inset-x-0 top-0 h-1 bg-fire" />
            <h2 class="text-lg font-semibold">Pon en marcha tu agenda</h2>
            <p class="mt-1 text-sm text-muted-foreground">
                Tres pasos y tus clientes ya podrán reservar en línea.
            </p>
            <ol class="mt-5 grid gap-3 md:grid-cols-3">
                <li v-for="(step, i) in setupSteps" :key="step.title">
                    <Link
                        :href="step.href"
                        class="flex h-full gap-3 rounded-lg border p-4 transition-colors hover:border-primary/50 hover:bg-accent/40"
                    >
                        <span
                            class="flex size-7 shrink-0 items-center justify-center rounded-full text-sm font-semibold"
                            :class="
                                step.done
                                    ? 'bg-fire text-white'
                                    : 'border-2 border-primary/40 text-primary'
                            "
                        >
                            <Check v-if="step.done" class="size-4" />
                            <template v-else>{{ i + 1 }}</template>
                        </span>
                        <span>
                            <span
                                class="block font-medium"
                                :class="{
                                    'text-muted-foreground line-through':
                                        step.done,
                                }"
                                >{{ step.title }}</span
                            >
                            <span class="text-sm text-muted-foreground">{{
                                step.description
                            }}</span>
                        </span>
                    </Link>
                </li>
            </ol>
        </section>

        <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <div
                v-for="card in statCards"
                :key="card.label"
                class="rounded-xl border bg-card p-5"
            >
                <div class="flex items-center justify-between">
                    <p class="text-sm text-muted-foreground">
                        {{ card.label }}
                    </p>
                    <component :is="card.icon" class="size-4 text-primary" />
                </div>
                <p class="mt-2 text-2xl font-semibold tracking-tight">
                    {{ card.value }}
                </p>
                <p v-if="card.hint" class="mt-1 text-xs text-muted-foreground">
                    {{ card.hint }}
                </p>
            </div>
        </section>

        <div class="grid gap-6 lg:grid-cols-3">
            <section class="rounded-xl border bg-card lg:col-span-2">
                <header
                    class="flex items-center justify-between border-b px-5 py-4"
                >
                    <h2 class="font-semibold">Próximas reservas</h2>
                    <Button variant="ghost" size="sm" as-child>
                        <Link :href="agenda()">Ver agenda</Link>
                    </Button>
                </header>

                <ul v-if="upcoming.length" class="divide-y">
                    <li
                        v-for="booking in upcoming"
                        :key="booking.id"
                        class="flex items-center gap-4 px-5 py-3"
                    >
                        <div class="w-20 shrink-0 text-center">
                            <p class="text-xs text-muted-foreground capitalize">
                                {{ formatDateShort(dateOf(booking.starts_at)) }}
                            </p>
                            <p class="font-semibold tabular-nums">
                                {{ timeOf(booking.starts_at) }}
                            </p>
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="truncate font-medium">
                                {{ booking.customer_name }}
                            </p>
                            <p class="truncate text-sm text-muted-foreground">
                                {{ booking.service }} ·
                                {{ booking.staff_member }}
                            </p>
                        </div>
                        <BookingStatusBadge
                            :status="booking.status"
                            :label="booking.status_label"
                        />
                    </li>
                </ul>
                <p
                    v-else
                    class="px-5 py-10 text-center text-sm text-muted-foreground"
                >
                    No hay reservas próximas. Comparte tu link para recibir las
                    primeras.
                </p>
            </section>

            <section
                class="flex flex-col justify-between gap-4 rounded-xl bg-sidebar p-5 text-sidebar-foreground"
            >
                <div>
                    <p
                        class="text-xs font-semibold tracking-widest text-sidebar-accent-foreground uppercase"
                    >
                        Tu página de reservas
                    </p>
                    <p class="mt-2 text-sm text-sidebar-foreground/70">
                        Compártela por WhatsApp, Instagram o en tu local. Tus
                        clientes reservan sin crear cuenta.
                    </p>
                    <p
                        class="mt-4 truncate rounded-md bg-sidebar-accent px-3 py-2 font-mono text-xs"
                    >
                        {{ publicUrl }}
                    </p>
                </div>
                <div class="flex gap-2">
                    <Button class="flex-1" @click="copyPublicUrl">
                        <Check v-if="copied" />
                        <Copy v-else />
                        {{ copied ? 'Copiado' : 'Copiar link' }}
                    </Button>
                    <Button
                        variant="outline"
                        class="border-sidebar-border bg-transparent text-sidebar-foreground hover:bg-sidebar-accent hover:text-sidebar-accent-foreground"
                        as-child
                    >
                        <a :href="publicUrl" target="_blank" rel="noopener">
                            <ExternalLink />
                        </a>
                    </Button>
                </div>
            </section>
        </div>
    </div>
</template>
