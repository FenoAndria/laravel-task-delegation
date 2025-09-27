<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

import RiskList, { type RiskItem } from '@/components/delegation/RiskList.vue';
import WorkloadList, { type WorkloadItem } from '@/components/delegation/WorkloadList.vue';
import StatusBadge from '@/components/missions/StatusBadge.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { shortDate } from '@/lib/time';
import { type BreadcrumbItem, type SharedData } from '@/types';

const props = defineProps<{
    stats: { active_missions: number; my_open_tasks: number; blocked_tasks: number; completed_this_month: number };
    risks: RiskItem[];
    missions: {
        id: number;
        title: string;
        team: string;
        status: string;
        status_label: string;
        due_date: string | null;
        overdue: boolean;
        progress: { done: number; total: number };
    }[];
    workload: WorkloadItem[];
}>();

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Dashboard', href: '/dashboard' }];
const page = usePage<SharedData>();
const firstName = computed(() => page.props.auth.user.name.split(' ')[0]);
const today = new Intl.DateTimeFormat('en', { weekday: 'long', day: 'numeric', month: 'long' }).format(new Date());

const tiles = computed(() => [
    { label: 'Active missions', value: props.stats.active_missions, href: route('missions.index'), alert: false },
    { label: 'My open tasks', value: props.stats.my_open_tasks, href: route('tasks.mine'), alert: false },
    { label: 'Blocked tasks', value: props.stats.blocked_tasks, href: null, alert: props.stats.blocked_tasks > 0 },
    { label: 'Completed this month', value: props.stats.completed_this_month, href: route('missions.index', { status: 'completed' }), alert: false },
]);

const percent = (progress: { done: number; total: number }) => (progress.total ? Math.round((progress.done / progress.total) * 100) : 0);
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-10 px-6 py-8">
            <header class="space-y-1">
                <p class="eyebrow">{{ today }}</p>
                <h1 class="font-display text-5xl leading-tight">Hello {{ firstName }}.</h1>
                <p class="text-muted-foreground text-sm">Here is where the work stands in {{ page.props.auth.organization?.name }}.</p>
            </header>

            <dl class="grid grid-cols-2 divide-x divide-y border-y lg:grid-cols-4 lg:divide-y-0">
                <component
                    :is="tile.href ? Link : 'div'"
                    v-for="tile in tiles"
                    :key="tile.label"
                    :href="tile.href ?? undefined"
                    class="space-y-2 px-5 py-5 odd:pl-0 lg:first:pl-0 lg:odd:pl-5"
                    :class="{ 'hover:bg-accent/40 transition-colors': tile.href }"
                >
                    <dt class="eyebrow">{{ tile.label }}</dt>
                    <dd class="font-mono text-4xl font-light tabular-nums" :class="{ 'text-signal': tile.alert }">{{ tile.value }}</dd>
                </component>
            </dl>

            <div class="grid gap-10 lg:grid-cols-5">
                <section class="space-y-3 lg:col-span-3">
                    <div class="flex items-baseline justify-between border-b pb-2">
                        <h2 class="font-display text-2xl">Needs attention</h2>
                        <span v-if="risks.length" class="text-muted-foreground font-mono text-xs">{{ risks.length }}</span>
                    </div>
                    <RiskList v-if="risks.length" :risks="risks" linked />
                    <p v-else class="text-muted-foreground py-8 text-sm">Nothing at risk right now. Enjoy it.</p>
                </section>

                <section class="space-y-3 lg:col-span-2">
                    <div class="flex items-baseline justify-between border-b pb-2">
                        <h2 class="font-display text-2xl">Workload</h2>
                        <span class="text-muted-foreground text-xs">open hours / weekly capacity</span>
                    </div>
                    <WorkloadList v-if="workload.length" :items="workload" class="pt-2" />
                </section>
            </div>

            <section class="space-y-3">
                <div class="flex items-baseline justify-between border-b pb-2">
                    <h2 class="font-display text-2xl">Missions in motion</h2>
                    <Link :href="route('missions.index')" class="text-muted-foreground hover:text-foreground text-sm">All missions →</Link>
                </div>

                <ul v-if="missions.length" class="divide-y">
                    <li v-for="mission in missions" :key="mission.id">
                        <Link
                            :href="route('missions.show', mission.id)"
                            class="hover:bg-accent/40 grid grid-cols-[1fr_auto] items-center gap-x-6 gap-y-2 py-3 transition-colors sm:grid-cols-[1fr_10rem_5rem_7rem]"
                        >
                            <span class="min-w-0">
                                <span class="block truncate font-medium">{{ mission.title }}</span>
                                <span class="text-muted-foreground block text-xs">{{ mission.team }}</span>
                            </span>
                            <span class="hidden items-center gap-3 sm:flex">
                                <span class="bg-muted h-1 flex-1 overflow-hidden rounded-full">
                                    <span class="bg-foreground block h-full rounded-full" :style="{ width: `${percent(mission.progress)}%` }" />
                                </span>
                                <span class="text-muted-foreground font-mono text-xs tabular-nums"
                                    >{{ mission.progress.done }}/{{ mission.progress.total }}</span
                                >
                            </span>
                            <span
                                class="hidden font-mono text-xs tabular-nums sm:block"
                                :class="mission.overdue ? 'text-signal' : 'text-muted-foreground'"
                            >
                                {{ shortDate(mission.due_date) || '—' }}
                            </span>
                            <StatusBadge :status="mission.status" :label="mission.status_label" />
                        </Link>
                    </li>
                </ul>
                <p v-else class="text-muted-foreground py-8 text-sm">No active missions.</p>
            </section>
        </div>
    </AppLayout>
</template>
