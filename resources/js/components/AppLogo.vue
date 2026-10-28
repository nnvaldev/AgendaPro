<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppLogoIcon from '@/components/AppLogoIcon.vue';

const page = usePage();
const name = computed(() => page.props.name);
const business = computed(() => page.props.business);
</script>

<template>
    <div
        class="flex aspect-square size-8 items-center justify-center rounded-md bg-fire shadow-sm shadow-orange-900/40"
    >
        <AppLogoIcon class="size-5 text-white" />
    </div>
    <div class="ml-1 grid flex-1 text-left text-sm">
        <span class="truncate leading-tight font-semibold">{{ name }}</span>
        <span
            v-if="business"
            class="truncate text-xs leading-tight text-sidebar-foreground/60"
        >
            {{ business.name }}
        </span>
    </div>
</template>
