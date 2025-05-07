<script setup lang="ts">
import { cn } from '@/lib/utils';

defineProps<{
    priority: string;
}>();

const tone: Record<string, string> = {
    low: 'text-muted-foreground',
    normal: 'text-foreground',
    high: 'text-orange-600 dark:text-orange-400',
    urgent: 'text-red-600 dark:text-red-400 font-semibold',
};
</script>

<template>
    <span :class="cn('text-xs capitalize', tone[priority])">{{ priority }}</span>
</template>
