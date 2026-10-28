<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import { index, store, update } from '@/routes/services';
import { index as staffIndex } from '@/routes/staff';

const props = defineProps<{
    service: {
        id: number;
        name: string;
        description: string | null;
        duration_minutes: number;
        price: string;
        is_active: boolean;
        staff_member_ids: number[];
    } | null;
    staff: { id: number; name: string; is_active: boolean }[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Servicios', href: index() }],
    },
});

const editing = props.service !== null;

const form = useForm({
    name: props.service?.name ?? '',
    description: props.service?.description ?? '',
    duration_minutes: props.service?.duration_minutes ?? 30,
    price: props.service?.price ?? '',
    is_active: props.service?.is_active ?? true,
    staff_member_ids:
        props.service?.staff_member_ids ?? props.staff.map((m) => m.id),
});

const durations = [15, 20, 30, 45, 60, 90, 120];

function toggleStaff(id: number, checked: boolean) {
    form.staff_member_ids = checked
        ? [...form.staff_member_ids, id]
        : form.staff_member_ids.filter((current) => current !== id);
}

function submit() {
    form.submit(props.service ? update(props.service.id) : store());
}
</script>

<template>
    <Head :title="editing ? 'Editar servicio' : 'Nuevo servicio'" />

    <div class="flex flex-1 flex-col p-4 md:p-6">
        <form class="max-w-2xl space-y-8" @submit.prevent="submit">
            <Heading
                :title="editing ? 'Editar servicio' : 'Nuevo servicio'"
                description="Duración y precio se usan para calcular los horarios y el total de la reserva."
            />

            <div class="grid gap-2">
                <Label for="name">Nombre</Label>
                <Input
                    id="name"
                    v-model="form.name"
                    required
                    placeholder="Corte clásico"
                />
                <InputError :message="form.errors.name" />
            </div>

            <div class="grid gap-2">
                <Label for="description">
                    Descripción
                    <span class="text-muted-foreground">(opcional)</span>
                </Label>
                <Textarea
                    id="description"
                    v-model="form.description"
                    rows="3"
                    placeholder="Qué incluye, recomendaciones…"
                />
                <InputError :message="form.errors.description" />
            </div>

            <div class="grid gap-6 sm:grid-cols-2">
                <div class="grid gap-2">
                    <Label for="duration">Duración (minutos)</Label>
                    <Input
                        id="duration"
                        v-model.number="form.duration_minutes"
                        type="number"
                        min="5"
                        max="480"
                        step="5"
                        required
                        list="duration-options"
                    />
                    <datalist id="duration-options">
                        <option v-for="d in durations" :key="d" :value="d" />
                    </datalist>
                    <InputError :message="form.errors.duration_minutes" />
                </div>

                <div class="grid gap-2">
                    <Label for="price">Precio (Bs)</Label>
                    <Input
                        id="price"
                        v-model="form.price"
                        type="number"
                        min="0"
                        step="0.01"
                        inputmode="decimal"
                        required
                        placeholder="50.00"
                    />
                    <InputError :message="form.errors.price" />
                </div>
            </div>

            <fieldset class="space-y-3">
                <legend class="text-sm font-medium">¿Quién lo realiza?</legend>
                <p v-if="!staff.length" class="text-sm text-muted-foreground">
                    Aún no agregaste profesionales.
                    <Link :href="staffIndex()" class="text-primary underline">
                        Agrega tu equipo
                    </Link>
                    y luego asígnalos aquí.
                </p>
                <div v-else class="grid gap-2 sm:grid-cols-2">
                    <label
                        v-for="member in staff"
                        :key="member.id"
                        class="flex cursor-pointer items-center gap-3 rounded-lg border p-3 transition-colors has-data-[state=checked]:border-primary/60 has-data-[state=checked]:bg-accent/40"
                    >
                        <Checkbox
                            :model-value="
                                form.staff_member_ids.includes(member.id)
                            "
                            @update:model-value="
                                (checked) =>
                                    toggleStaff(member.id, checked === true)
                            "
                        />
                        <span class="text-sm">
                            {{ member.name }}
                            <span
                                v-if="!member.is_active"
                                class="text-muted-foreground"
                                >(inactivo)</span
                            >
                        </span>
                    </label>
                </div>
                <InputError :message="form.errors.staff_member_ids" />
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
                        Si lo desactivas, deja de aparecer en tu página de
                        reservas (las reservas existentes se mantienen).
                    </span>
                </span>
            </label>

            <div class="flex gap-3">
                <Button type="submit" :disabled="form.processing">
                    <Spinner v-if="form.processing" />
                    {{ editing ? 'Guardar cambios' : 'Crear servicio' }}
                </Button>
                <Button variant="ghost" as-child>
                    <Link :href="index()">Cancelar</Link>
                </Button>
            </div>
        </form>
    </div>
</template>
