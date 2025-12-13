<script setup lang="ts">
import AppLogoIcon from '@/components/AppLogoIcon.vue';

interface Props {
    class?: string;
}

defineProps<Props>();
</script>

<template>
    <AppLogoIcon class="text-foreground size-7 shrink-0" />
    <span class="ml-1 truncate font-mono text-sm font-medium tracking-tight">laravel-task-delegation</span>
</template>
