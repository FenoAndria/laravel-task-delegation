<script setup lang="ts">
import AppLogoIcon from '@/components/AppLogoIcon.vue';

interface Props {
    class?: string;
}

defineProps<Props>();
</script>

<template>
    <AppLogoIcon class="text-foreground size-7 shrink-0" />
    <span class="font-display ml-1 truncate text-2xl leading-none">Delego</span>
</template>
