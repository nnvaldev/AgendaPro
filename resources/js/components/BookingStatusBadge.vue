<script setup lang="ts">
import { computed } from 'vue';
import { cn } from '@/lib/utils';
import type { BookingStatus } from '@/types';

const props = defineProps<{
    status: BookingStatus;
    label: string;
}>();

const styles: Record<BookingStatus, string> = {
    pending:
        'border-yellow-300 bg-yellow-50 text-yellow-800 dark:border-yellow-500/30 dark:bg-yellow-500/10 dark:text-yellow-300',
    confirmed:
        'border-orange-300 bg-orange-50 text-orange-800 dark:border-orange-500/30 dark:bg-orange-500/10 dark:text-orange-300',
    completed:
        'border-emerald-300 bg-emerald-50 text-emerald-800 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-300',
    no_show:
        'border-red-300 bg-red-50 text-red-800 dark:border-red-500/30 dark:bg-red-500/10 dark:text-red-300',
    cancelled: 'border-border bg-muted text-muted-foreground line-through',
};

const classes = computed(() =>
    cn(
        'inline-flex items-center rounded-full border px-2 py-0.5 text-xs font-medium whitespace-nowrap',
        styles[props.status],
    ),
);
</script>

<template>
    <span :class="classes">{{ label }}</span>
</template>
