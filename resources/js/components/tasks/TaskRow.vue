<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';

import StatusBadge from '@/components/missions/StatusBadge.vue';
import { Button } from '@/components/ui/button';
import { shortDate } from '@/lib/time';

export interface TaskItem {
    id: number;
    title: string;
    description: string | null;
    status: string;
    status_label: string;
    blocked_reason: string | null;
    assignee: { id: number; name: string } | null;
    due_date: string | null;
    estimate_hours: number | null;
    overdue: boolean;
    transitions: { value: string; label: string }[];
    mission?: { id: number; title: string };
    team?: string;
}

const props = defineProps<{
    task: TaskItem;
    assignees?: { id: number; name: string }[];
    canManage?: boolean;
}>();

const changeStatus = (status: string) => {
    let blockedReason: string | null = null;

    if (status === 'blocked') {
        blockedReason = window.prompt('What is blocking this task?');
        if (!blockedReason) return;
    }

    router.put(route('tasks.status.update', props.task.id), { status, blocked_reason: blockedReason }, { preserveScroll: true });
};

const assign = (value: string) => {
    router.put(
        route('tasks.update', props.task.id),
        {
            title: props.task.title,
            description: props.task.description,
            due_date: props.task.due_date,
            estimate_hours: props.task.estimate_hours,
            assignee_id: value ? Number(value) : null,
        },
        { preserveScroll: true },
    );
};

const destroy = () => {
    if (window.confirm(`Delete "${props.task.title}"?`)) {
        router.delete(route('tasks.destroy', props.task.id), { preserveScroll: true });
    }
};
</script>

<template>
    <li class="flex flex-wrap items-center gap-x-4 gap-y-2 p-4">
        <div class="min-w-0 flex-1">
            <p class="truncate text-sm font-medium" :class="{ 'text-muted-foreground line-through': task.status === 'cancelled' }">
                {{ task.title }}
            </p>
            <p v-if="task.mission" class="text-muted-foreground truncate text-xs">
                <Link :href="route('missions.show', task.mission.id)" class="hover:underline">{{ task.mission.title }}</Link>
                · {{ task.team }}
            </p>
            <p v-if="task.status === 'blocked' && task.blocked_reason" class="text-warning mt-1 text-xs">Blocked: {{ task.blocked_reason }}</p>
        </div>

        <select
            v-if="canManage && assignees"
            :value="task.assignee?.id ?? ''"
            aria-label="Assignee"
            class="border-input bg-background h-8 rounded-md border px-2 text-sm"
            @change="assign(($event.target as HTMLSelectElement).value)"
        >
            <option value="">Unassigned</option>
            <option v-for="person in assignees" :key="person.id" :value="person.id">{{ person.name }}</option>
        </select>
        <span v-else-if="!task.mission" class="text-muted-foreground text-sm">{{ task.assignee?.name ?? 'Unassigned' }}</span>

        <span v-if="task.due_date" class="w-14 font-mono text-xs tabular-nums" :class="task.overdue ? 'text-signal' : 'text-muted-foreground'">
            {{ shortDate(task.due_date) }}
        </span>

        <StatusBadge :status="task.status" :label="task.status_label" />

        <select
            v-if="task.transitions.length"
            value=""
            aria-label="Move task"
            class="border-input bg-background h-8 rounded-md border px-2 text-sm"
            @change="changeStatus(($event.target as HTMLSelectElement).value)"
        >
            <option value="" disabled>Move to…</option>
            <option v-for="transition in task.transitions" :key="transition.value" :value="transition.value">{{ transition.label }}</option>
        </select>

        <Button v-if="canManage" variant="ghost" size="sm" class="text-muted-foreground" @click="destroy">Delete</Button>
    </li>
</template>
