<script setup lang="ts">
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { Link } from '@inertiajs/vue3';

defineProps<{
    title?: string;
    description?: string;
}>();

const chain = [
    { role: 'Owner', line: 'entrusts a mission to a team' },
    { role: 'Lead', line: 'breaks it down and assigns the work' },
    { role: 'Member', line: 'moves each task to done' },
];
</script>

<template>
    <div class="grid min-h-dvh lg:grid-cols-[minmax(0,5fr)_minmax(0,6fr)]">
        <aside class="relative hidden flex-col justify-between bg-[#1c1a17] p-12 text-[#ede8e0] lg:flex">
            <Link :href="route('home')" class="flex items-center gap-2">
                <AppLogoIcon class="size-8 text-[#ede8e0]" />
                <span class="font-mono text-sm font-medium tracking-tight">laravel-task-delegation</span>
            </Link>

            <div class="max-w-md space-y-10">
                <p class="font-display text-5xl leading-[1.05]">
                    Missions flow down.<br />
                    <em class="text-signal">Progress flows up.</em>
                </p>

                <ol class="space-y-4 border-l border-white/15 pl-5">
                    <li v-for="(step, index) in chain" :key="step.role" class="text-sm text-white/70">
                        <span class="mr-2 font-mono text-xs text-white/40">0{{ index + 1 }}</span>
                        <span class="font-medium text-white">{{ step.role }}</span> {{ step.line }}
                    </li>
                </ol>
            </div>

            <p class="text-xs text-white/40">Assisted delegation for teams.</p>
        </aside>

        <main class="flex items-center justify-center px-6 py-12">
            <div class="w-full max-w-sm space-y-8">
                <Link :href="route('home')" class="flex items-center gap-2 lg:hidden">
                    <AppLogoIcon class="text-foreground size-7" />
                    <span class="font-mono text-sm font-medium tracking-tight">laravel-task-delegation</span>
                </Link>

                <div v-if="title || description" class="space-y-2">
                    <h1 v-if="title" class="font-display text-4xl leading-tight">{{ title }}</h1>
                    <p v-if="description" class="text-muted-foreground text-sm">{{ description }}</p>
                </div>

                <slot />
            </div>
        </main>
    </div>
</template>
