<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { store } from '@/routes/onboarding';

defineOptions({
    layout: {
        title: 'Crea tu negocio',
        description:
            'Con estos datos armamos tu página de reservas. Puedes cambiarlos después.',
    },
});
</script>

<template>
    <Head title="Crea tu negocio" />

    <Form
        v-bind="store.form()"
        v-slot="{ errors, processing }"
        class="flex flex-col gap-6"
    >
        <div class="grid gap-2">
            <Label for="name">Nombre del negocio</Label>
            <Input
                id="name"
                name="name"
                required
                autofocus
                placeholder="Barbería El Prado"
            />
            <InputError :message="errors.name" />
        </div>

        <div class="grid gap-2">
            <Label for="phone">
                Teléfono / WhatsApp
                <span class="text-muted-foreground">(opcional)</span>
            </Label>
            <Input
                id="phone"
                name="phone"
                type="tel"
                autocomplete="tel"
                placeholder="+591 70000000"
            />
            <InputError :message="errors.phone" />
        </div>

        <div class="grid gap-2">
            <Label for="address">
                Dirección <span class="text-muted-foreground">(opcional)</span>
            </Label>
            <Input
                id="address"
                name="address"
                autocomplete="street-address"
                placeholder="Av. 16 de Julio, La Paz"
            />
            <InputError :message="errors.address" />
        </div>

        <Button type="submit" class="w-full" :disabled="processing">
            <Spinner v-if="processing" />
            Crear mi negocio
        </Button>
    </Form>
</template>
