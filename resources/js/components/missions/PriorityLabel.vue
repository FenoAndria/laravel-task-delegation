<script setup lang="ts">
import { cn } from '@/lib/utils';

defineProps<{
    priority: string;
}>();

const tone: Record<string, string> = {
    low: 'text-muted-foreground',
    normal: 'text-foreground',
    high: 'text-warning',
    urgent: 'text-signal font-semibold',
};
</script>

<template>
    <span :class="cn('text-xs capitalize', tone[priority])">{{ priority }}</span>
</template>
