<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { ExternalLink } from '@lucide/vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { edit, update } from '@/routes/business';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Mi negocio', href: edit() }],
    },
});

const props = defineProps<{
    business: {
        name: string;
        slug: string;
        phone: string | null;
        address: string | null;
        timezone: string;
        currency: string;
        slot_interval_minutes: number;
        min_notice_minutes: number;
        booking_window_days: number;
    };
    slotIntervals: number[];
    publicUrl: string;
}>();

const form = useForm({
    name: props.business.name,
    phone: props.business.phone ?? '',
    address: props.business.address ?? '',
    slot_interval_minutes: props.business.slot_interval_minutes,
    min_notice_minutes: props.business.min_notice_minutes,
    booking_window_days: props.business.booking_window_days,
});

const noticeOptions = [
    { value: 0, label: 'Sin anticipación' },
    { value: 30, label: '30 minutos' },
    { value: 60, label: '1 hora' },
    { value: 120, label: '2 horas' },
    { value: 240, label: '4 horas' },
    { value: 1440, label: '1 día' },
];

const selectClass =
    'h-9 w-full rounded-md border border-input bg-transparent px-3 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 dark:bg-input/30';

function submit() {
    form.submit(update(), { preserveScroll: true });
}
</script>

<template>
    <Head title="Mi negocio" />

    <div class="flex flex-1 flex-col p-4 md:p-6">
        <form class="max-w-2xl space-y-10" @submit.prevent="submit">
            <section class="space-y-6">
                <Heading
                    title="Mi negocio"
                    description="Así te ven tus clientes en la página de reservas."
                />

                <div class="rounded-lg border bg-muted/40 p-4">
                    <p
                        class="text-xs font-medium text-muted-foreground uppercase"
                    >
                        Tu página de reservas
                    </p>
                    <a
                        :href="publicUrl"
                        target="_blank"
                        rel="noopener"
                        class="mt-1 inline-flex items-center gap-1.5 font-mono text-sm break-all text-primary hover:underline"
                    >
                        {{ publicUrl }}
                        <ExternalLink class="size-3.5 shrink-0" />
                    </a>
                </div>

                <div class="grid gap-2">
                    <Label for="name">Nombre</Label>
                    <Input id="name" v-model="form.name" required />
                    <InputError :message="form.errors.name" />
                </div>

                <div class="grid gap-6 sm:grid-cols-2">
                    <div class="grid gap-2">
                        <Label for="phone">Teléfono / WhatsApp</Label>
                        <Input id="phone" v-model="form.phone" type="tel" />
                        <InputError :message="form.errors.phone" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="address">Dirección</Label>
                        <Input id="address" v-model="form.address" />
                        <InputError :message="form.errors.address" />
                    </div>
                </div>
            </section>

            <section class="space-y-6">
                <Heading
                    variant="small"
                    title="Reglas de reserva"
                    description="Controlan qué horarios ve el cliente."
                />

                <div class="grid gap-6 sm:grid-cols-3">
                    <div class="grid gap-2">
                        <Label for="interval">Horarios cada</Label>
                        <select
                            id="interval"
                            v-model.number="form.slot_interval_minutes"
                            :class="selectClass"
                        >
                            <option
                                v-for="minutes in slotIntervals"
                                :key="minutes"
                                :value="minutes"
                            >
                                {{ minutes }} minutos
                            </option>
                        </select>
                        <InputError
                            :message="form.errors.slot_interval_minutes"
                        />
                    </div>

                    <div class="grid gap-2">
                        <Label for="notice">Anticipación mínima</Label>
                        <select
                            id="notice"
                            v-model.number="form.min_notice_minutes"
                            :class="selectClass"
                        >
                            <option
                                v-for="option in noticeOptions"
                                :key="option.value"
                                :value="option.value"
                            >
                                {{ option.label }}
                            </option>
                        </select>
                        <InputError :message="form.errors.min_notice_minutes" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="window">Reservas hasta (días)</Label>
                        <Input
                            id="window"
                            v-model.number="form.booking_window_days"
                            type="number"
                            min="1"
                            max="365"
                        />
                        <InputError
                            :message="form.errors.booking_window_days"
                        />
                    </div>
                </div>

                <p class="text-sm text-muted-foreground">
                    Zona horaria: {{ business.timezone }} · Moneda:
                    {{ business.currency }}
                </p>
            </section>

            <Button type="submit" :disabled="form.processing">
                <Spinner v-if="form.processing" />
                Guardar cambios
            </Button>
        </form>
    </div>
</template>
