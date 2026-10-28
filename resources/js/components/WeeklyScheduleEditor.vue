<script setup lang="ts">
import { CopyCheck, Plus, X } from '@lucide/vue';
import { computed } from 'vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { WEEKDAYS, WEEKDAY_ORDER } from '@/lib/format';
import type { WeeklyBlock } from '@/types';

const blocks = defineModel<WeeklyBlock[]>({ required: true });

const DEFAULT_BLOCK = { starts_at: '09:00', ends_at: '18:00' };

const days = computed(() =>
    WEEKDAY_ORDER.map((weekday) => ({
        weekday,
        name: WEEKDAYS[weekday],
        blocks: blocks.value
            .map((block, index) => ({ ...block, index }))
            .filter((block) => block.weekday === weekday)
            .sort((a, b) => a.starts_at.localeCompare(b.starts_at)),
    })),
);

function toggleDay(weekday: number, enabled: boolean) {
    blocks.value = enabled
        ? [...blocks.value, { weekday, ...DEFAULT_BLOCK }]
        : blocks.value.filter((block) => block.weekday !== weekday);
}

function addBlock(weekday: number) {
    const last = days.value
        .find((day) => day.weekday === weekday)
        ?.blocks.at(-1);
    // Un bloque nuevo arranca una hora después del anterior, p. ej. tras el almuerzo.
    const startsAt = last
        ? shiftHour(last.ends_at, 1)
        : DEFAULT_BLOCK.starts_at;

    blocks.value = [
        ...blocks.value,
        { weekday, starts_at: startsAt, ends_at: shiftHour(startsAt, 4) },
    ];
}

function removeBlock(index: number) {
    blocks.value = blocks.value.filter((_, i) => i !== index);
}

function updateBlock(
    index: number,
    field: 'starts_at' | 'ends_at',
    value: string,
) {
    blocks.value = blocks.value.map((block, i) =>
        i === index ? { ...block, [field]: value } : block,
    );
}

/** Copia el horario del lunes al resto de la semana laboral (martes a sábado). */
function copyMondayToWeek() {
    const monday = blocks.value.filter((block) => block.weekday === 1);
    const others = blocks.value.filter(
        (block) => block.weekday === 0 || block.weekday === 1,
    );

    blocks.value = [
        ...others,
        ...[2, 3, 4, 5, 6].flatMap((weekday) =>
            monday.map((block) => ({ ...block, weekday })),
        ),
    ];
}

function shiftHour(time: string, hours: number): string {
    const [h, m] = time.split(':').map(Number);

    return `${String(Math.min(h + hours, 23)).padStart(2, '0')}:${String(m).padStart(2, '0')}`;
}
</script>

<template>
    <div class="space-y-3">
        <div class="flex justify-end">
            <Button
                type="button"
                variant="outline"
                size="sm"
                :disabled="!days[0].blocks.length"
                @click="copyMondayToWeek"
            >
                <CopyCheck /> Copiar lunes a martes–sábado
            </Button>
        </div>

        <ul class="divide-y rounded-lg border">
            <li
                v-for="day in days"
                :key="day.weekday"
                class="flex flex-col gap-3 p-3 sm:flex-row sm:items-start"
            >
                <label class="flex w-36 shrink-0 items-center gap-3 pt-1.5">
                    <Checkbox
                        :model-value="day.blocks.length > 0"
                        @update:model-value="
                            (checked) =>
                                toggleDay(day.weekday, checked === true)
                        "
                    />
                    <span
                        class="text-sm font-medium"
                        :class="{ 'text-muted-foreground': !day.blocks.length }"
                        >{{ day.name }}</span
                    >
                </label>

                <p
                    v-if="!day.blocks.length"
                    class="pt-1.5 text-sm text-muted-foreground"
                >
                    No atiende
                </p>

                <div v-else class="flex flex-1 flex-col gap-2">
                    <div
                        v-for="block in day.blocks"
                        :key="block.index"
                        class="flex items-center gap-2"
                    >
                        <input
                            type="time"
                            step="900"
                            :value="block.starts_at"
                            class="h-9 rounded-md border border-input bg-transparent px-2 text-sm tabular-nums shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 dark:bg-input/30"
                            aria-label="Desde"
                            @change="
                                updateBlock(
                                    block.index,
                                    'starts_at',
                                    ($event.target as HTMLInputElement).value,
                                )
                            "
                        />
                        <span class="text-sm text-muted-foreground">a</span>
                        <input
                            type="time"
                            step="900"
                            :value="block.ends_at"
                            class="h-9 rounded-md border border-input bg-transparent px-2 text-sm tabular-nums shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 dark:bg-input/30"
                            aria-label="Hasta"
                            @change="
                                updateBlock(
                                    block.index,
                                    'ends_at',
                                    ($event.target as HTMLInputElement).value,
                                )
                            "
                        />
                        <Button
                            type="button"
                            variant="ghost"
                            size="icon-sm"
                            title="Quitar bloque"
                            @click="removeBlock(block.index)"
                        >
                            <X />
                        </Button>
                    </div>
                    <Button
                        type="button"
                        variant="ghost"
                        size="sm"
                        class="w-fit text-primary"
                        @click="addBlock(day.weekday)"
                    >
                        <Plus /> Agregar bloque
                    </Button>
                </div>
            </li>
        </ul>
    </div>
</template>
