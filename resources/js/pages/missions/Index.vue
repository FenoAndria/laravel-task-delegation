<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

import Heading from '@/components/Heading.vue';
import MissionFields, { type MissionFormData } from '@/components/missions/MissionFields.vue';
import PriorityLabel from '@/components/missions/PriorityLabel.vue';
import StatusBadge from '@/components/missions/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import AppLayout from '@/layouts/AppLayout.vue';
import { shortDate } from '@/lib/time';
import { type BreadcrumbItem } from '@/types';

interface MissionSummary {
    id: number;
    title: string;
    team: { id: number; name: string };
    status: string;
    status_label: string;
    priority: string;
    due_date: string | null;
    overdue: boolean;
}

const props = defineProps<{
    missions: MissionSummary[];
    filters: { status: string; team: number | null };
    teams: { id: number; name: string }[];
    priorities: { value: string; label: string }[];
    canCreate: boolean;
}>();

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Missions', href: '/missions' }];
const statusFilters = [
    { value: 'active', label: 'Active' },
    { value: 'completed', label: 'Completed' },
    { value: 'cancelled', label: 'Cancelled' },
    { value: 'all', label: 'All' },
];

const filter = (changes: Partial<typeof props.filters>) => {
    router.get(route('missions.index'), { ...props.filters, ...changes }, { preserveState: true, preserveScroll: true, replace: true });
};

const creating = ref(false);
const form = useForm<MissionFormData>({
    title: '',
    description: '',
    team_id: props.filters.team,
    priority: 'normal',
    due_date: '',
});

const submit = () => {
    form.post(route('missions.store'), {
        onSuccess: () => (creating.value = false),
    });
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Missions" />

        <div class="px-4 py-6">
            <div class="flex items-start justify-between gap-4">
                <Heading title="Missions" description="Work the organization entrusts to its teams" />
                <Button v-if="canCreate && teams.length" @click="creating = true">New mission</Button>
            </div>

            <div class="mb-4 flex flex-wrap items-center gap-2">
                <div class="flex rounded-md border p-0.5">
                    <button
                        v-for="option in statusFilters"
                        :key="option.value"
                        type="button"
                        class="rounded px-3 py-1 text-sm"
                        :class="filters.status === option.value ? 'bg-accent font-medium' : 'text-muted-foreground hover:text-foreground'"
                        @click="filter({ status: option.value })"
                    >
                        {{ option.label }}
                    </button>
                </div>

                <select
                    :value="filters.team ?? ''"
                    aria-label="Filter by team"
                    class="border-input bg-background h-9 rounded-md border px-3 text-sm"
                    @change="filter({ team: Number(($event.target as HTMLSelectElement).value) || null })"
                >
                    <option value="">All teams</option>
                    <option v-for="team in teams" :key="team.id" :value="team.id">{{ team.name }}</option>
                </select>
            </div>

            <ul v-if="missions.length" class="divide-y rounded-lg border">
                <li v-for="mission in missions" :key="mission.id">
                    <Link :href="route('missions.show', mission.id)" class="hover:bg-accent/50 flex flex-wrap items-center gap-x-4 gap-y-1 p-4">
                        <span class="min-w-0 flex-1 truncate font-medium">{{ mission.title }}</span>
                        <span class="text-muted-foreground text-sm">{{ mission.team.name }}</span>
                        <PriorityLabel :priority="mission.priority" />
                        <span
                            v-if="mission.due_date"
                            class="w-24 text-sm tabular-nums"
                            :class="mission.overdue ? 'text-signal' : 'text-muted-foreground'"
                        >
                            {{ shortDate(mission.due_date) }}
                        </span>
                        <StatusBadge :status="mission.status" :label="mission.status_label" />
                    </Link>
                </li>
            </ul>

            <div v-else class="rounded-lg border border-dashed p-10 text-center">
                <p class="font-medium">No missions here</p>
                <p class="text-muted-foreground mt-1 text-sm">
                    <template v-if="!teams.length">Create a team first: missions are entrusted to teams.</template>
                    <template v-else-if="canCreate">Entrust a first mission to one of your teams.</template>
                    <template v-else>Missions entrusted to your teams will show up here.</template>
                </p>
            </div>
        </div>

        <Dialog v-model:open="creating">
            <DialogContent class="sm:max-w-2xl">
                <form @submit.prevent="submit" class="space-y-6">
                    <DialogHeader>
                        <DialogTitle>New mission</DialogTitle>
                    </DialogHeader>

                    <MissionFields :form="form" :teams="teams" :priorities="priorities" />

                    <DialogFooter>
                        <Button type="button" variant="ghost" @click="creating = false">Cancel</Button>
                        <Button :disabled="form.processing">Create mission</Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
