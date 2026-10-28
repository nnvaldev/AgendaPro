<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { CalendarDays, Pencil, Plus, Trash2, Users } from '@lucide/vue';
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import Heading from '@/components/Heading.vue';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { useInitials } from '@/composables/useInitials';
import { WEEKDAYS, WEEKDAY_ORDER } from '@/lib/format';
import { create, destroy, edit, index } from '@/routes/staff';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Equipo', href: index() }],
    },
});

defineProps<{
    staff: {
        id: number;
        name: string;
        email: string | null;
        is_active: boolean;
        services: string[];
        working_days: number[];
        weekly_minutes: number;
    }[];
}>();

const { getInitials } = useInitials();

function remove(id: number, close: () => void) {
    router.delete(destroy(id).url, { preserveScroll: true, onFinish: close });
}
</script>

<template>
    <Head title="Equipo" />

    <div class="flex flex-1 flex-col gap-6 p-4 md:p-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <Heading
                title="Equipo"
                description="Las personas que atienden y sus horarios."
            />
            <Button as-child>
                <Link :href="create()"><Plus /> Agregar profesional</Link>
            </Button>
        </div>

        <div
            v-if="!staff.length"
            class="flex flex-col items-center gap-3 rounded-xl border border-dashed p-12 text-center"
        >
            <div
                class="flex size-12 items-center justify-center rounded-full bg-accent text-primary"
            >
                <Users class="size-6" />
            </div>
            <p class="font-medium">Agrega a tu primer profesional</p>
            <p class="max-w-sm text-sm text-muted-foreground">
                Si trabajas solo, agrégate a ti mismo: cada profesional tiene su
                propia agenda.
            </p>
            <Button as-child class="mt-2">
                <Link :href="create()"><Plus /> Agregar profesional</Link>
            </Button>
        </div>

        <ul v-else class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            <li
                v-for="member in staff"
                :key="member.id"
                class="flex flex-col rounded-xl border bg-card p-5"
                :class="{ 'opacity-60': !member.is_active }"
            >
                <div class="flex items-center gap-3">
                    <Avatar class="size-10">
                        <AvatarFallback
                            class="bg-fire font-semibold text-white"
                        >
                            {{ getInitials(member.name) }}
                        </AvatarFallback>
                    </Avatar>
                    <div class="min-w-0">
                        <h3 class="truncate font-semibold">
                            {{ member.name }}
                        </h3>
                        <p class="truncate text-sm text-muted-foreground">
                            {{
                                member.is_active
                                    ? (member.email ?? 'Sin email')
                                    : 'Inactivo'
                            }}
                        </p>
                    </div>
                </div>

                <div class="mt-4 flex gap-1" aria-label="Días que atiende">
                    <span
                        v-for="weekday in WEEKDAY_ORDER"
                        :key="weekday"
                        :title="WEEKDAYS[weekday]"
                        class="flex size-7 items-center justify-center rounded-md text-xs font-medium"
                        :class="
                            member.working_days.includes(weekday)
                                ? 'bg-primary text-primary-foreground'
                                : 'bg-muted text-muted-foreground'
                        "
                    >
                        {{ WEEKDAYS[weekday].charAt(0) }}
                    </span>
                </div>
                <p
                    v-if="!member.working_days.length"
                    class="mt-3 rounded-md bg-secondary px-3 py-2 text-xs text-secondary-foreground"
                >
                    <CalendarDays class="mr-1 inline size-3.5" />
                    Sin horario: no aparecerá disponible para reservas.
                </p>
                <p v-else class="mt-2 text-xs text-muted-foreground">
                    {{ Math.round(member.weekly_minutes / 60) }} h por semana
                </p>

                <p class="mt-3 line-clamp-2 text-sm text-muted-foreground">
                    {{
                        member.services.length
                            ? member.services.join(' · ')
                            : 'Sin servicios asignados'
                    }}
                </p>

                <div class="mt-auto flex justify-end gap-2 pt-4">
                    <ConfirmDialog
                        title="¿Eliminar a este profesional?"
                        description="Si tiene reservas registradas no se podrá eliminar; en ese caso desactívalo desde Editar."
                        confirm-label="Eliminar"
                        @confirm="(close) => remove(member.id, close)"
                    >
                        <Button variant="ghost" size="icon-sm" title="Eliminar">
                            <Trash2 />
                        </Button>
                    </ConfirmDialog>
                    <Button variant="outline" size="sm" as-child>
                        <Link :href="edit(member.id)"><Pencil /> Editar</Link>
                    </Button>
                </div>
            </li>
        </ul>
    </div>
</template>
