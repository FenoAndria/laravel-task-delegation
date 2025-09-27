<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { InertiaForm } from '@inertiajs/vue3';

export interface MissionFormData {
    title: string;
    description: string;
    team_id: number | null;
    priority: string;
    due_date: string;
}

defineProps<{
    form: InertiaForm<MissionFormData>;
    teams: { id: number; name: string }[];
    priorities: { value: string; label: string }[];
}>();
</script>

<template>
    <!-- The parent owns this Inertia form; these fields only fill it in. -->
    <!-- eslint-disable vue/no-mutating-props -->
    <div class="grid gap-2">
        <Label for="mission-title">Title</Label>
        <Input id="mission-title" v-model="form.title" required placeholder="Redesign the onboarding" />
        <InputError :message="form.errors.title" />
    </div>

    <div class="grid gap-2">
        <Label for="mission-description">Description</Label>
        <textarea
            id="mission-description"
            v-model="form.description"
            rows="4"
            placeholder="Context, expected outcome, constraints…"
            class="border-input bg-background rounded-md border px-3 py-2 text-sm"
        />
        <InputError :message="form.errors.description" />
    </div>

    <div class="grid gap-4 sm:grid-cols-3">
        <div class="grid gap-2">
            <Label for="mission-team">Team</Label>
            <select id="mission-team" v-model="form.team_id" required class="border-input bg-background h-9 rounded-md border px-3 text-sm">
                <option :value="null" disabled>Choose a team</option>
                <option v-for="team in teams" :key="team.id" :value="team.id">{{ team.name }}</option>
            </select>
            <InputError :message="form.errors.team_id" />
        </div>

        <div class="grid gap-2">
            <Label for="mission-priority">Priority</Label>
            <select id="mission-priority" v-model="form.priority" class="border-input bg-background h-9 rounded-md border px-3 text-sm">
                <option v-for="priority in priorities" :key="priority.value" :value="priority.value">{{ priority.label }}</option>
            </select>
            <InputError :message="form.errors.priority" />
        </div>

        <div class="grid gap-2">
            <Label for="mission-due">Due date</Label>
            <Input id="mission-due" v-model="form.due_date" type="date" />
            <InputError :message="form.errors.due_date" />
        </div>
    </div>
</template>
