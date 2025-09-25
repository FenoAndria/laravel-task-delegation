<script setup lang="ts">
interface Props {
    title: string;
    description?: string;
}

defineProps<Props>();
</script>

<template>
    <div class="mb-8 space-y-1">
        <h1 class="font-display text-4xl leading-tight">{{ title }}</h1>
        <p v-if="description" class="text-muted-foreground text-sm">
            {{ description }}
        </p>
    </div>
</template>
