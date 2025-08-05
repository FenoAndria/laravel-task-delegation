<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

import RiskList, { type RiskItem } from '@/components/delegation/RiskList.vue';
import WorkloadList, { type WorkloadItem } from '@/components/delegation/WorkloadList.vue';
import StatusBadge from '@/components/missions/StatusBadge.vue';
import AppLayout from '@/layouts/AppLayout.vue';
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

const tiles = computed(() => [
    { label: 'Active missions', value: props.stats.active_missions, href: route('missions.index') },
    { label: 'My open tasks', value: props.stats.my_open_tasks, href: route('tasks.mine') },
    { label: 'Blocked tasks', value: props.stats.blocked_tasks, href: null },
    { label: 'Completed this month', value: props.stats.completed_this_month, href: route('missions.index', { status: 'completed' }) },
]);

const percent = (progress: { done: number; total: number }) => (progress.total ? Math.round((progress.done / progress.total) * 100) : 0);
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6 px-4 py-6">
            <div>
                <h1 class="text-xl font-semibold tracking-tight">Hello {{ firstName }}</h1>
                <p class="text-sm text-muted-foreground">Here is where the work stands in {{ page.props.auth.organization?.name }}.</p>
            </div>

            <dl class="grid grid-cols-2 gap-3 lg:grid-cols-4">
                <component
                    :is="tile.href ? Link : 'div'"
                    v-for="tile in tiles"
                    :key="tile.label"
                    :href="tile.href ?? undefined"
                    class="rounded-lg border p-4"
                    :class="{ 'hover:bg-accent/50': tile.href }"
                >
                    <dt class="text-sm text-muted-foreground">{{ tile.label }}</dt>
                    <dd class="mt-1 text-2xl font-semibold tabular-nums">{{ tile.value }}</dd>
                </component>
            </dl>

            <div class="grid gap-6 lg:grid-cols-5">
                <section class="space-y-2 rounded-lg border p-4 lg:col-span-3">
                    <h2 class="font-semibold">Needs attention</h2>
                    <RiskList v-if="risks.length" :risks="risks" linked />
                    <p v-else class="py-6 text-center text-sm text-muted-foreground">Nothing at risk right now.</p>
                </section>

                <section class="space-y-3 rounded-lg border p-4 lg:col-span-2">
                    <div>
                        <h2 class="font-semibold">Workload</h2>
                        <p class="text-sm text-muted-foreground">Open work against a weekly capacity</p>
                    </div>
                    <WorkloadList v-if="workload.length" :items="workload" />
                </section>
            </div>

            <section class="space-y-2 rounded-lg border p-4">
                <div class="flex items-baseline justify-between">
                    <h2 class="font-semibold">Missions in motion</h2>
                    <Link :href="route('missions.index')" class="text-sm text-muted-foreground hover:text-foreground">All missions</Link>
                </div>

                <ul v-if="missions.length" class="divide-y">
                    <li v-for="mission in missions" :key="mission.id">
                        <Link
                            :href="route('missions.show', mission.id)"
                            class="grid grid-cols-[1fr_auto] items-center gap-x-4 gap-y-2 py-3 sm:grid-cols-[1fr_8rem_6rem_auto]"
                        >
                            <span class="min-w-0">
                                <span class="block truncate text-sm font-medium">{{ mission.title }}</span>
                                <span class="block text-xs text-muted-foreground">{{ mission.team }}</span>
                            </span>
                            <span class="hidden items-center gap-2 sm:flex">
                                <span class="h-1.5 flex-1 overflow-hidden rounded-full bg-muted">
                                    <span class="block h-full rounded-full bg-primary" :style="{ width: `${percent(mission.progress)}%` }" />
                                </span>
                                <span class="text-xs tabular-nums text-muted-foreground"
                                    >{{ mission.progress.done }}/{{ mission.progress.total }}</span
                                >
                            </span>
                            <span
                                class="hidden text-xs tabular-nums sm:block"
                                :class="mission.overdue ? 'text-red-600 dark:text-red-400' : 'text-muted-foreground'"
                            >
                                {{ mission.due_date ?? '—' }}
                            </span>
                            <StatusBadge :status="mission.status" :label="mission.status_label" />
                        </Link>
                    </li>
                </ul>
                <p v-else class="py-6 text-center text-sm text-muted-foreground">No active missions.</p>
            </section>
        </div>
    </AppLayout>
</template>
