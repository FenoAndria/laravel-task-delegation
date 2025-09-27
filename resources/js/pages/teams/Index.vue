<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';

interface TeamSummary {
    id: number;
    name: string;
    description: string | null;
    lead: { id: number; name: string } | null;
    members_count: number;
}

defineProps<{
    teams: TeamSummary[];
    people: { id: number; name: string }[];
    canCreate: boolean;
}>();

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Teams', href: '/teams' }];

const creating = ref(false);
const form = useForm({
    name: '',
    description: '',
    lead_id: null as number | null,
});

const submit = () => {
    form.post(route('teams.store'), {
        onSuccess: () => {
            creating.value = false;
            form.reset();
        },
    });
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Teams" />

        <div class="px-4 py-6">
            <div class="flex items-start justify-between gap-4">
                <Heading title="Teams" description="Each team has a lead who organizes the work and members who carry it out" />
                <Button v-if="canCreate" @click="creating = true">New team</Button>
            </div>

            <div v-if="teams.length" class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                <Link
                    v-for="team in teams"
                    :key="team.id"
                    :href="route('teams.show', team.id)"
                    class="hover:bg-accent flex flex-col gap-2 rounded-lg border p-4 transition-colors"
                >
                    <span class="font-medium">{{ team.name }}</span>
                    <span v-if="team.description" class="text-muted-foreground line-clamp-2 text-sm">{{ team.description }}</span>
                    <span class="text-muted-foreground mt-auto text-sm">
                        {{ team.lead ? `Led by ${team.lead.name}` : 'No lead yet' }} · {{ team.members_count }}
                        {{ team.members_count === 1 ? 'member' : 'members' }}
                    </span>
                </Link>
            </div>

            <div v-else class="rounded-lg border border-dashed p-10 text-center">
                <p class="font-medium">No teams yet</p>
                <p class="text-muted-foreground mt-1 text-sm">
                    {{ canCreate ? 'Create a first team and appoint its lead.' : 'An owner or admin of the organization will create them.' }}
                </p>
            </div>
        </div>

        <Dialog v-model:open="creating">
            <DialogContent>
                <form @submit.prevent="submit" class="space-y-6">
                    <DialogHeader>
                        <DialogTitle>New team</DialogTitle>
                        <DialogDescription>You can add members once the team exists.</DialogDescription>
                    </DialogHeader>

                    <div class="grid gap-2">
                        <Label for="team-name">Name</Label>
                        <Input id="team-name" v-model="form.name" required autofocus placeholder="Design" />
                        <InputError :message="form.errors.name" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="team-description">Description</Label>
                        <Input id="team-description" v-model="form.description" placeholder="What this team takes care of" />
                        <InputError :message="form.errors.description" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="team-lead">Lead</Label>
                        <select id="team-lead" v-model="form.lead_id" class="border-input bg-background h-9 rounded-md border px-3 text-sm">
                            <option :value="null">Appoint later</option>
                            <option v-for="person in people" :key="person.id" :value="person.id">{{ person.name }}</option>
                        </select>
                        <InputError :message="form.errors.lead_id" />
                    </div>

                    <DialogFooter>
                        <Button type="button" variant="ghost" @click="creating = false">Cancel</Button>
                        <Button :disabled="form.processing">Create team</Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
