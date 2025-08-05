<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

export interface RiskItem {
    level: 'high' | 'medium' | 'low';
    kind: string;
    title: string;
    detail: string | null;
    url: string;
}

defineProps<{
    risks: RiskItem[];
    linked?: boolean;
}>();

const dot: Record<RiskItem['level'], string> = {
    high: 'bg-red-500',
    medium: 'bg-amber-500',
    low: 'bg-muted-foreground/50',
};
</script>

<template>
    <ul class="divide-y">
        <li v-for="(risk, index) in risks" :key="`${risk.kind}-${index}`">
            <component
                :is="linked ? Link : 'div'"
                :href="linked ? risk.url : undefined"
                class="flex gap-3 py-3"
                :class="{ 'hover:bg-accent/50': linked }"
            >
                <span class="mt-1.5 size-2 shrink-0 rounded-full" :class="dot[risk.level]" :aria-label="`${risk.level} risk`" />
                <span class="min-w-0">
                    <span class="block text-sm font-medium">{{ risk.title }}</span>
                    <span v-if="risk.detail" class="block text-sm text-muted-foreground">{{ risk.detail }}</span>
                </span>
            </component>
        </li>
    </ul>
</template>
