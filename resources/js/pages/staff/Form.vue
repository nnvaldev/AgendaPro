<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import WeeklyScheduleEditor from '@/components/WeeklyScheduleEditor.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { index, store, update } from '@/routes/staff';
import { update as updateSchedule } from '@/routes/staff/schedule';
import type { WeeklyBlock } from '@/types';

const props = defineProps<{
    member: {
        id: number;
        name: string;
        email: string | null;
        is_active: boolean;
        service_ids: number[];
    } | null;
    services: { id: number; name: string; is_active: boolean }[];
    schedule: WeeklyBlock[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Equipo', href: index() }],
    },
});

const editing = props.member !== null;

const form = useForm({
    name: props.member?.name ?? '',
    email: props.member?.email ?? '',
    is_active: props.member?.is_active ?? true,
    service_ids: props.member?.service_ids ?? props.services.map((s) => s.id),
});

const scheduleForm = useForm({
    blocks: props.schedule,
});

function toggleService(id: number, checked: boolean) {
    form.service_ids = checked
        ? [...form.service_ids, id]
        : form.service_ids.filter((current) => current !== id);
}

function submit() {
    form.submit(props.member ? update(props.member.id) : store(), {
        preserveScroll: true,
    });
}

function saveSchedule() {
    if (!props.member) {
        return;
    }

    scheduleForm.submit(updateSchedule(props.member.id), {
        preserveScroll: true,
        onSuccess: () => scheduleForm.defaults(),
    });
}

/** Errores por bloque (blocks.3.ends_at) resumidos en un solo mensaje. */
function scheduleError(): string | undefined {
    const errors = scheduleForm.errors as Record<string, string>;

    return errors.blocks ?? Object.values(errors)[0];
}
</script>

<template>
    <Head :title="editing ? member!.name : 'Nuevo profesional'" />

    <div class="flex flex-1 flex-col gap-10 p-4 md:p-6">
        <form class="max-w-2xl space-y-8" @submit.prevent="submit">
            <Heading
                :title="editing ? member!.name : 'Nuevo profesional'"
                description="Datos básicos y servicios que realiza."
            />

            <div class="grid gap-6 sm:grid-cols-2">
                <div class="grid gap-2">
                    <Label for="name">Nombre</Label>
                    <Input
                        id="name"
                        v-model="form.name"
                        required
                        placeholder="Carlos Mamani"
                    />
                    <InputError :message="form.errors.name" />
                </div>
                <div class="grid gap-2">
                    <Label for="email">
                        Email
                        <span class="text-muted-foreground">(opcional)</span>
                    </Label>
                    <Input
                        id="email"
                        v-model="form.email"
                        type="email"
                        placeholder="carlos@ejemplo.com"
                    />
                    <InputError :message="form.errors.email" />
                </div>
            </div>

            <fieldset class="space-y-3">
                <legend class="text-sm font-medium">
                    Servicios que realiza
                </legend>
                <p
                    v-if="!services.length"
                    class="text-sm text-muted-foreground"
                >
                    Aún no hay servicios creados.
                </p>
                <div v-else class="grid gap-2 sm:grid-cols-2">
                    <label
                        v-for="service in services"
                        :key="service.id"
                        class="flex cursor-pointer items-center gap-3 rounded-lg border p-3 transition-colors has-data-[state=checked]:border-primary/60 has-data-[state=checked]:bg-accent/40"
                    >
                        <Checkbox
                            :model-value="form.service_ids.includes(service.id)"
                            @update:model-value="
                                (checked) =>
                                    toggleService(service.id, checked === true)
                            "
                        />
                        <span class="text-sm">
                            {{ service.name }}
                            <span
                                v-if="!service.is_active"
                                class="text-muted-foreground"
                                >(inactivo)</span
                            >
                        </span>
                    </label>
                </div>
            </fieldset>

            <label class="flex items-start gap-3">
                <Checkbox
                    class="mt-0.5"
                    :model-value="form.is_active"
                    @update:model-value="
                        (checked) => (form.is_active = checked === true)
                    "
                />
                <span>
                    <span class="block text-sm font-medium">Activo</span>
                    <span class="text-sm text-muted-foreground">
                        Un profesional inactivo no recibe reservas nuevas.
                    </span>
                </span>
            </label>

            <div class="flex gap-3">
                <Button type="submit" :disabled="form.processing">
                    <Spinner v-if="form.processing" />
                    {{
                        editing ? 'Guardar datos' : 'Agregar y definir horario'
                    }}
                </Button>
                <Button variant="ghost" as-child>
                    <Link :href="index()">Volver</Link>
                </Button>
            </div>
        </form>

        <section v-if="editing" class="max-w-2xl space-y-6">
            <Heading
                title="Horario semanal"
                description="Bloques en los que se puede reservar. Usa dos bloques para dejar libre la hora del almuerzo."
            />

            <WeeklyScheduleEditor v-model="scheduleForm.blocks" />

            <InputError :message="scheduleError()" />

            <Button
                :disabled="scheduleForm.processing || !scheduleForm.isDirty"
                @click="saveSchedule"
            >
                <Spinner v-if="scheduleForm.processing" />
                Guardar horario
            </Button>
        </section>
    </div>
</template>
