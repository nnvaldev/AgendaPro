<script setup lang="ts">
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import type { ButtonVariants } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';

const { confirmLabel = 'Confirmar', variant = 'destructive' } = defineProps<{
    title: string;
    description: string;
    confirmLabel?: string;
    variant?: ButtonVariants['variant'];
}>();

const emit = defineEmits<{
    confirm: [close: () => void];
}>();

const open = ref(false);

function confirm() {
    emit('confirm', () => (open.value = false));
}
</script>

<template>
    <Dialog v-model:open="open">
        <DialogTrigger as-child>
            <slot />
        </DialogTrigger>
        <DialogContent>
            <DialogHeader class="space-y-3">
                <DialogTitle>{{ title }}</DialogTitle>
                <DialogDescription>{{ description }}</DialogDescription>
            </DialogHeader>
            <DialogFooter class="gap-2">
                <DialogClose as-child>
                    <Button variant="secondary">Volver</Button>
                </DialogClose>
                <Button :variant="variant" @click="confirm">
                    {{ confirmLabel }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
