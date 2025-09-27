<script setup lang="ts">
interface Props {
    title: string;
    description?: string;
}

defineProps<Props>();
</script>

<template>
    <header>
        <h2 class="font-display mb-0.5 text-2xl leading-tight">{{ title }}</h2>
        <p v-if="description" class="text-muted-foreground text-sm">
            {{ description }}
        </p>
    </header>
</template>
