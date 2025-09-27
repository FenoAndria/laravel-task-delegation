<script setup lang="ts">
import type { HTMLAttributes } from 'vue';

defineOptions({
    inheritAttrs: false,
});

interface Props {
    className?: HTMLAttributes['class'];
}

defineProps<Props>();
</script>

<template>
    <!-- Two batons mid-handoff: the work passing from one hand to the next. -->
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32" :class="className" v-bind="$attrs" aria-hidden="true">
        <path d="M5 25 14.5 7H20L10.5 25H5Z" fill="currentColor" />
        <path d="M13 25 22.5 7H28L18.5 25H13Z" fill="var(--signal)" />
    </svg>
</template>
