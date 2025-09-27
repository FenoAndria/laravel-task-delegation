<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';

import AppLogoIcon from '@/components/AppLogoIcon.vue';
import RiskList, { type RiskItem } from '@/components/delegation/RiskList.vue';
import WorkloadList, { type WorkloadItem } from '@/components/delegation/WorkloadList.vue';
import { type SharedData } from '@/types';

const page = usePage<SharedData>();

const chain = [
    {
        step: '01',
        role: 'Owner',
        title: 'Entrust a mission',
        body: 'Give a team an outcome, a priority and a date. Not a to-do list.',
    },
    {
        step: '02',
        role: 'Lead',
        title: 'Break it down',
        body: 'Split the mission into tasks and hand each one to the person who has room for it.',
    },
    {
        step: '03',
        role: 'Member',
        title: 'Deliver',
        body: 'Move your tasks to done, say what blocks you, and the mission moves with you.',
    },
];

const sampleRisks: RiskItem[] = [
    { level: 'high', kind: 'mission_overdue', title: '"Public API v1" is overdue', detail: 'Was due Monday · 2 of 5 tasks done', url: '#' },
    {
        level: 'medium',
        kind: 'task_blocked',
        title: '"Load test the endpoints" has been blocked for 3 days',
        detail: 'Staging is down since the migration',
        url: '#',
    },
    { level: 'low', kind: 'tasks_unassigned', title: '1 task has no assignee in "Billing migration"', detail: 'The mission is due soon', url: '#' },
];

const sampleWorkload: WorkloadItem[] = [
    { user: { id: 1, name: 'Noah' }, open_tasks: 4, hours: 34, capacity: 30, overdue: 1, percent: 113, level: 'overloaded' },
    { user: { id: 2, name: 'Sara' }, open_tasks: 3, hours: 15, capacity: 30, overdue: 0, percent: 50, level: 'balanced' },
    { user: { id: 3, name: 'Amara' }, open_tasks: 1, hours: 4, capacity: 30, overdue: 0, percent: 13, level: 'available' },
];

const judgment = [
    { title: 'Workload, measured', body: 'Open estimated hours against a weekly capacity, across every team a person works in.' },
    { title: 'Suggestions that explain themselves', body: '“Amara · 4h of open work · already on this mission.” You see why, you decide.' },
    { title: 'Risks before they bite', body: 'Overdue and soon-due missions, stuck tasks, unassigned work and overloaded people, ranked.' },
];
</script>

<template>
    <Head title="Assisted delegation for teams" />

    <div class="bg-background text-foreground min-h-dvh">
        <header class="mx-auto flex max-w-6xl items-center justify-between px-6 py-6">
            <Link :href="route('home')" class="flex items-center gap-2">
                <AppLogoIcon class="text-foreground size-7" />
                <span class="font-display text-2xl leading-none">Delego</span>
            </Link>

            <nav class="flex items-center gap-2 text-sm">
                <Link
                    v-if="page.props.auth.user"
                    :href="route('dashboard')"
                    class="bg-primary text-primary-foreground rounded-md px-4 py-2 font-medium"
                >
                    Open dashboard
                </Link>
                <template v-else>
                    <Link :href="route('login')" class="hover:bg-accent rounded-md px-4 py-2">Log in</Link>
                    <Link :href="route('register')" class="hover:bg-accent rounded-md border px-4 py-2">Sign up</Link>
                </template>
            </nav>
        </header>

        <main>
            <section class="mx-auto grid max-w-6xl gap-16 px-6 pt-16 pb-24 lg:grid-cols-[minmax(0,7fr)_minmax(0,5fr)] lg:items-center">
                <div class="space-y-8">
                    <p class="eyebrow">Assisted delegation for teams</p>
                    <h1 class="font-display text-6xl leading-[0.95] sm:text-7xl">
                        Missions flow down.<br />
                        <em class="text-signal">Progress flows up.</em>
                    </h1>
                    <p class="text-muted-foreground max-w-xl text-lg leading-relaxed">
                        Delego follows work from the organization to the teams and the people doing it. It tells leads who has room, and everyone what
                        is about to slip.
                    </p>
                    <div class="flex flex-wrap gap-3">
                        <Link :href="route('login')" class="bg-primary text-primary-foreground rounded-md px-5 py-3 font-medium">
                            Try the live demo
                        </Link>
                        <Link :href="route('register')" class="hover:bg-accent rounded-md border px-5 py-3 font-medium"
                            >Create your organization</Link
                        >
                    </div>
                    <p class="text-muted-foreground text-sm">Log in as the owner, a team lead or a member. No sign-up needed.</p>
                </div>

                <figure class="bg-card space-y-6 rounded-lg border p-6" aria-label="Preview of the Delego dashboard">
                    <div>
                        <p class="eyebrow mb-1">Needs attention</p>
                        <RiskList :risks="sampleRisks" />
                    </div>
                    <div>
                        <p class="eyebrow mb-3">Workload</p>
                        <WorkloadList :items="sampleWorkload" />
                    </div>
                </figure>
            </section>

            <section class="border-y">
                <ol class="mx-auto grid max-w-6xl divide-y px-6 md:grid-cols-3 md:divide-x md:divide-y-0">
                    <li v-for="item in chain" :key="item.step" class="space-y-3 py-10 md:px-8 md:first:pl-0 md:last:pr-0">
                        <p class="text-muted-foreground font-mono text-xs">{{ item.step }} · {{ item.role }}</p>
                        <h2 class="font-display text-3xl">{{ item.title }}</h2>
                        <p class="text-muted-foreground">{{ item.body }}</p>
                    </li>
                </ol>
            </section>

            <section class="mx-auto max-w-6xl space-y-12 px-6 py-24">
                <div class="max-w-2xl space-y-3">
                    <p class="eyebrow">Built-in judgment</p>
                    <h2 class="font-display text-5xl leading-tight">Rules you can read, not a black box.</h2>
                    <p class="text-muted-foreground">
                        Every suggestion and every warning says why. It works without any AI, and stays honest when an AI helps later.
                    </p>
                </div>

                <dl class="grid gap-10 md:grid-cols-3">
                    <div v-for="item in judgment" :key="item.title" class="space-y-2 border-t pt-4">
                        <dt class="font-medium">{{ item.title }}</dt>
                        <dd class="text-muted-foreground text-sm leading-relaxed">{{ item.body }}</dd>
                    </div>
                </dl>
            </section>
        </main>

        <footer class="border-t">
            <div class="text-muted-foreground mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-4 px-6 py-8 text-sm">
                <span class="flex items-center gap-2">
                    <AppLogoIcon class="text-foreground size-5" />
                    Delego — Laravel, Inertia and Vue.
                </span>
                <a href="https://github.com/FenoAndria/team-flow" class="hover:text-foreground">Source on GitHub</a>
            </div>
        </footer>
    </div>
</template>
