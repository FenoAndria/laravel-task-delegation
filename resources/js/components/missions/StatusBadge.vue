<script setup lang="ts">
import { cn } from '@/lib/utils';
import { computed } from 'vue';

const props = defineProps<{
    status: string;
    label: string;
}>();

const tone: Record<string, string> = {
    open: 'border-border text-muted-foreground',
    in_progress: 'border-blue-300 text-blue-700 dark:border-blue-800 dark:text-blue-300',
    blocked: 'border-amber-300 text-amber-700 dark:border-amber-800 dark:text-amber-300',
    completed: 'border-emerald-300 text-emerald-700 dark:border-emerald-800 dark:text-emerald-300',
    done: 'border-emerald-300 text-emerald-700 dark:border-emerald-800 dark:text-emerald-300',
    cancelled: 'border-border text-muted-foreground line-through',
};

const classes = computed(() => cn('inline-flex items-center rounded-full border px-2 py-0.5 text-xs font-medium', tone[props.status] ?? tone.open));
</script>

<template>
    <span :class="classes">{{ label }}</span>
</template>
