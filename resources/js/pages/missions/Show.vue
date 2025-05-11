<script setup lang="ts">
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

import InputError from '@/components/InputError.vue';
import MissionFields, { type MissionFormData } from '@/components/missions/MissionFields.vue';
import PriorityLabel from '@/components/missions/PriorityLabel.vue';
import StatusBadge from '@/components/missions/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';

const props = defineProps<{
    mission: {
        id: number;
        title: string;
        description: string | null;
        team: { id: number; name: string };
        team_id: number;
        lead: { id: number; name: string } | null;
        creator: { id: number; name: string } | null;
        status: string;
        status_label: string;
        priority: string;
        due_date: string | null;
        overdue: boolean;
        created_at: string | null;
        completed_at: string | null;
    };
    teams: { id: number; name: string }[];
    priorities: { value: string; label: string }[];
    can: { update: boolean; delete: boolean };
}>();

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    { title: 'Missions', href: '/missions' },
    { title: props.mission.title, href: `/missions/${props.mission.id}` },
]);

const page = usePage();
const statusError = computed(() => (page.props.errors as Record<string, string>).status);
const isActive = computed(() => ['open', 'in_progress'].includes(props.mission.status));

const editing = ref(false);
const form = useForm<MissionFormData>({
    title: props.mission.title,
    description: props.mission.description ?? '',
    team_id: props.mission.team_id,
    priority: props.mission.priority,
    due_date: props.mission.due_date ?? '',
});

const save = () => {
    form.put(route('missions.update', props.mission.id), {
        preserveScroll: true,
        onSuccess: () => (editing.value = false),
    });
};

const cancelMission = () => {
    if (window.confirm('Cancel this mission? It can be reopened later.')) {
        router.post(route('missions.cancel', props.mission.id), {}, { preserveScroll: true });
    }
};

const reopen = () => router.post(route('missions.reopen', props.mission.id), {}, { preserveScroll: true });

const destroy = () => {
    if (window.confirm('Delete this mission for good?')) {
        router.delete(route('missions.destroy', props.mission.id));
    }
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head :title="mission.title" />

        <div class="max-w-4xl space-y-8 px-4 py-6">
            <form v-if="editing" @submit.prevent="save" class="space-y-6">
                <MissionFields :form="form" :teams="teams" :priorities="priorities" />
                <div class="flex gap-2">
                    <Button :disabled="form.processing">Save</Button>
                    <Button type="button" variant="ghost" @click="editing = false">Cancel</Button>
                </div>
            </form>

            <template v-else>
                <header class="space-y-3">
                    <div class="flex flex-wrap items-center gap-3">
                        <StatusBadge :status="mission.status" :label="mission.status_label" />
                        <PriorityLabel :priority="mission.priority" />
                        <span v-if="mission.overdue" class="text-xs font-medium text-red-600 dark:text-red-400">Overdue</span>
                    </div>
                    <h1 class="text-2xl font-semibold tracking-tight">{{ mission.title }}</h1>
                    <p v-if="mission.description" class="whitespace-pre-line text-sm leading-relaxed text-muted-foreground">
                        {{ mission.description }}
                    </p>
                </header>

                <dl class="grid gap-4 rounded-lg border p-4 text-sm sm:grid-cols-4">
                    <div>
                        <dt class="text-muted-foreground">Team</dt>
                        <dd class="mt-1 font-medium">
                            <Link :href="route('teams.show', mission.team.id)" class="hover:underline">{{ mission.team.name }}</Link>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-muted-foreground">Lead</dt>
                        <dd class="mt-1 font-medium">{{ mission.lead?.name ?? 'No lead yet' }}</dd>
                    </div>
                    <div>
                        <dt class="text-muted-foreground">Due</dt>
                        <dd class="mt-1 font-medium tabular-nums">{{ mission.due_date ?? 'No due date' }}</dd>
                    </div>
                    <div>
                        <dt class="text-muted-foreground">Created</dt>
                        <dd class="mt-1 font-medium tabular-nums">
                            {{ mission.created_at
                            }}<span v-if="mission.creator" class="font-normal text-muted-foreground"> by {{ mission.creator.name }}</span>
                        </dd>
                    </div>
                </dl>

                <div v-if="can.update || can.delete" class="flex flex-wrap gap-2">
                    <Button v-if="can.update" variant="outline" size="sm" @click="editing = true">Edit</Button>
                    <Button v-if="can.update && isActive" variant="outline" size="sm" @click="cancelMission">Cancel mission</Button>
                    <Button v-if="can.update && !isActive" variant="outline" size="sm" @click="reopen">Reopen</Button>
                    <Button v-if="can.delete" variant="ghost" size="sm" class="text-destructive" @click="destroy">Delete</Button>
                </div>
                <InputError :message="statusError" />
            </template>
        </div>
    </AppLayout>
</template>
