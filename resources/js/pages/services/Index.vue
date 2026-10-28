<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Clock, Pencil, Plus, Scissors, Trash2, Users } from '@lucide/vue';
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { formatDuration, formatMoney } from '@/lib/format';
import { create, destroy, edit, index } from '@/routes/services';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Servicios', href: index() }],
    },
});

defineProps<{
    services: {
        id: number;
        name: string;
        description: string | null;
        duration_minutes: number;
        price_cents: number;
        is_active: boolean;
        staff_count: number;
        bookings_count: number;
    }[];
}>();

function remove(id: number, close: () => void) {
    router.delete(destroy(id).url, {
        preserveScroll: true,
        onFinish: close,
    });
}
</script>

<template>
    <Head title="Servicios" />

    <div class="flex flex-1 flex-col gap-6 p-4 md:p-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <Heading
                title="Servicios"
                description="Lo que tus clientes pueden reservar."
            />
            <Button as-child>
                <Link :href="create()"><Plus /> Nuevo servicio</Link>
            </Button>
        </div>

        <div
            v-if="!services.length"
            class="flex flex-col items-center gap-3 rounded-xl border border-dashed p-12 text-center"
        >
            <div
                class="flex size-12 items-center justify-center rounded-full bg-accent text-primary"
            >
                <Scissors class="size-6" />
            </div>
            <p class="font-medium">Todavía no tienes servicios</p>
            <p class="max-w-sm text-sm text-muted-foreground">
                Crea el primero: por ejemplo "Corte clásico", 30 minutos, Bs 50.
            </p>
            <Button as-child class="mt-2">
                <Link :href="create()"><Plus /> Crear servicio</Link>
            </Button>
        </div>

        <ul v-else class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            <li
                v-for="service in services"
                :key="service.id"
                class="flex flex-col rounded-xl border bg-card p-5"
                :class="{ 'opacity-60': !service.is_active }"
            >
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <h3 class="truncate font-semibold">
                            {{ service.name }}
                        </h3>
                        <p
                            v-if="!service.is_active"
                            class="text-xs font-medium text-muted-foreground uppercase"
                        >
                            Inactivo
                        </p>
                    </div>
                    <p
                        class="text-lg font-semibold whitespace-nowrap text-primary"
                    >
                        {{ formatMoney(service.price_cents) }}
                    </p>
                </div>

                <p
                    v-if="service.description"
                    class="mt-2 line-clamp-2 text-sm text-muted-foreground"
                >
                    {{ service.description }}
                </p>

                <div
                    class="mt-4 flex flex-wrap gap-x-4 gap-y-1 text-sm text-muted-foreground"
                >
                    <span class="inline-flex items-center gap-1.5">
                        <Clock class="size-4" />
                        {{ formatDuration(service.duration_minutes) }}
                    </span>
                    <span class="inline-flex items-center gap-1.5">
                        <Users class="size-4" />
                        {{
                            service.staff_count === 1
                                ? '1 profesional'
                                : `${service.staff_count} profesionales`
                        }}
                    </span>
                </div>

                <p
                    v-if="service.is_active && service.staff_count === 0"
                    class="mt-3 rounded-md bg-secondary px-3 py-2 text-xs text-secondary-foreground"
                >
                    Asigna al menos un profesional para que se pueda reservar.
                </p>

                <div class="mt-auto flex justify-end gap-2 pt-4">
                    <ConfirmDialog
                        title="¿Eliminar este servicio?"
                        :description="
                            service.bookings_count
                                ? 'Tiene reservas registradas, así que no se puede eliminar. Puedes desactivarlo desde Editar.'
                                : 'Se eliminará de tu catálogo. Esta acción no se puede deshacer.'
                        "
                        confirm-label="Eliminar"
                        @confirm="(close) => remove(service.id, close)"
                    >
                        <Button variant="ghost" size="icon-sm" title="Eliminar">
                            <Trash2 />
                        </Button>
                    </ConfirmDialog>
                    <Button variant="outline" size="sm" as-child>
                        <Link :href="edit(service.id)"><Pencil /> Editar</Link>
                    </Button>
                </div>
            </li>
        </ul>
    </div>
</template>
