<script setup lang="ts">
export interface WorkloadItem {
    user: { id: number; name: string };
    open_tasks: number;
    hours: number;
    capacity: number;
    overdue: number;
    percent: number;
    level: 'available' | 'balanced' | 'overloaded';
}

defineProps<{
    items: WorkloadItem[];
}>();

const barTone: Record<WorkloadItem['level'], string> = {
    available: 'bg-success',
    balanced: 'bg-foreground',
    overloaded: 'bg-signal',
};
</script>

<template>
    <ul class="space-y-3">
        <li v-for="item in items" :key="item.user.id" class="space-y-1.5">
            <div class="flex items-baseline justify-between gap-2 text-sm">
                <span class="truncate font-medium">{{ item.user.name }}</span>
                <span class="text-muted-foreground shrink-0 tabular-nums">
                    <span class="font-mono text-xs">{{ item.hours }}h / {{ item.capacity }}h</span>
                    <span v-if="item.overdue" class="text-signal"> · {{ item.overdue }} overdue</span>
                </span>
            </div>
            <div
                class="bg-muted h-1.5 overflow-hidden rounded-full"
                role="meter"
                :aria-valuenow="item.percent"
                aria-valuemin="0"
                aria-valuemax="100"
                :aria-label="`${item.user.name} workload`"
            >
                <div class="h-full rounded-full" :class="barTone[item.level]" :style="{ width: `${Math.min(item.percent, 100)}%` }" />
            </div>
        </li>
    </ul>
</template>
