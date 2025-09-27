<script setup lang="ts">
const props = defineProps<{
    status: string;
    label: string;
}>();

const dot: Record<string, string> = {
    open: 'border border-muted-foreground bg-transparent',
    todo: 'border border-muted-foreground bg-transparent',
    in_progress: 'bg-info',
    blocked: 'bg-warning',
    completed: 'bg-success',
    done: 'bg-success',
    cancelled: 'bg-muted-foreground/40',
};
</script>

<template>
    <span
        class="inline-flex shrink-0 items-center gap-1.5 text-xs font-medium whitespace-nowrap"
        :class="{ 'text-muted-foreground': status === 'cancelled' }"
    >
        <span class="size-2 rounded-full" :class="dot[props.status] ?? dot.open" aria-hidden="true" />
        {{ label }}
    </span>
</template>
