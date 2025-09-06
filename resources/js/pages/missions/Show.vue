<script setup lang="ts">
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

import InputError from '@/components/InputError.vue';
import RiskList, { type RiskItem } from '@/components/delegation/RiskList.vue';
import MissionFields, { type MissionFormData } from '@/components/missions/MissionFields.vue';
import MissionTimeline, { type TimelineEntry } from '@/components/missions/MissionTimeline.vue';
import PriorityLabel from '@/components/missions/PriorityLabel.vue';
import StatusBadge from '@/components/missions/StatusBadge.vue';
import TaskRow, { type TaskItem } from '@/components/tasks/TaskRow.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
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
        progress: { done: number; total: number };
    };
    tasks: TaskItem[];
    timeline: TimelineEntry[];
    suggestions: { user: { id: number; name: string }; level: string; reason: string }[];
    risks: RiskItem[];
    assignees: { id: number; name: string }[];
    teams: { id: number; name: string }[];
    priorities: { value: string; label: string }[];
    can: { update: boolean; delete: boolean; complete: boolean; manageTasks: boolean };
}>();

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    { title: 'Missions', href: '/missions' },
    { title: props.mission.title, href: `/missions/${props.mission.id}` },
]);

const page = usePage();
const errors = computed(() => page.props.errors as Record<string, string>);
const progress = computed(() => (props.mission.progress.total ? Math.round((props.mission.progress.done / props.mission.progress.total) * 100) : 0));
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

const taskForm = useForm({
    title: '',
    assignee_id: null as number | null,
    due_date: '',
});

const addTask = () => {
    taskForm.post(route('tasks.store', props.mission.id), {
        preserveScroll: true,
        onSuccess: () => taskForm.reset(),
    });
};

const complete = () => router.post(route('missions.complete', props.mission.id), {}, { preserveScroll: true });

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
                    <p v-if="mission.description" class="text-muted-foreground text-sm leading-relaxed whitespace-pre-line">
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
                            }}<span v-if="mission.creator" class="text-muted-foreground font-normal"> by {{ mission.creator.name }}</span>
                        </dd>
                    </div>
                </dl>

                <div v-if="can.update || can.delete || can.complete" class="flex flex-wrap gap-2">
                    <Button v-if="can.complete" size="sm" @click="complete">Complete mission</Button>
                    <Button v-if="can.update" variant="outline" size="sm" @click="editing = true">Edit</Button>
                    <Button v-if="can.update && isActive" variant="outline" size="sm" @click="cancelMission">Cancel mission</Button>
                    <Button v-if="can.update && !isActive" variant="outline" size="sm" @click="reopen">Reopen</Button>
                    <Button v-if="can.delete" variant="ghost" size="sm" class="text-destructive" @click="destroy">Delete</Button>
                </div>
                <InputError :message="errors.status ?? errors.blocked_reason" />

                <section
                    v-if="risks.length"
                    class="rounded-lg border border-amber-300/60 bg-amber-50/50 px-4 dark:border-amber-900/60 dark:bg-amber-950/20"
                >
                    <RiskList :risks="risks" />
                </section>

                <section class="space-y-4">
                    <div class="flex items-end justify-between gap-4">
                        <div>
                            <h2 class="text-lg font-semibold">Tasks</h2>
                            <p class="text-muted-foreground text-sm">
                                {{ mission.progress.done }} of {{ mission.progress.total }} done
                                <template v-if="!mission.lead">· appoint a team lead to break this mission down</template>
                            </p>
                        </div>
                        <span class="text-sm font-medium tabular-nums">{{ progress }}%</span>
                    </div>
                    <div class="bg-muted h-1.5 overflow-hidden rounded-full">
                        <div class="bg-primary h-full rounded-full transition-all" :style="{ width: `${progress}%` }" />
                    </div>

                    <form v-if="can.manageTasks && isActive" @submit.prevent="addTask" class="flex flex-wrap gap-2">
                        <Input v-model="taskForm.title" required placeholder="Add a task…" class="min-w-0 flex-1" aria-label="Task title" />
                        <select
                            v-model="taskForm.assignee_id"
                            aria-label="Assignee"
                            class="border-input bg-background h-9 rounded-md border px-3 text-sm"
                        >
                            <option :value="null">Unassigned</option>
                            <option v-for="person in assignees" :key="person.id" :value="person.id">{{ person.name }}</option>
                        </select>
                        <Input v-model="taskForm.due_date" type="date" class="w-auto" aria-label="Due date" />
                        <Button :disabled="taskForm.processing">Add</Button>
                    </form>
                    <div v-if="can.manageTasks && isActive && suggestions.length" class="flex flex-wrap items-center gap-2 text-sm">
                        <span class="text-muted-foreground">Suggested:</span>
                        <button
                            v-for="suggestion in suggestions"
                            :key="suggestion.user.id"
                            type="button"
                            :title="suggestion.reason"
                            class="hover:bg-accent rounded-full border px-3 py-1"
                            :class="{ 'border-primary bg-accent': taskForm.assignee_id === suggestion.user.id }"
                            @click="taskForm.assignee_id = suggestion.user.id"
                        >
                            {{ suggestion.user.name }}
                            <span class="text-muted-foreground">· {{ suggestion.reason }}</span>
                        </button>
                    </div>
                    <InputError :message="taskForm.errors.title ?? taskForm.errors.assignee_id" />

                    <ul v-if="tasks.length" class="divide-y rounded-lg border">
                        <TaskRow v-for="task in tasks" :key="task.id" :task="task" :assignees="assignees" :can-manage="can.manageTasks" />
                    </ul>
                    <p v-else class="text-muted-foreground rounded-lg border border-dashed p-6 text-center text-sm">
                        No tasks yet. The team lead breaks the mission down into tasks and assigns them.
                    </p>
                </section>

                <MissionTimeline :mission-id="mission.id" :entries="timeline" />
            </template>
        </div>
    </AppLayout>
</template>
