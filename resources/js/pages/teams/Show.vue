<script setup lang="ts">
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

import Heading from '@/components/Heading.vue';
import HeadingSmall from '@/components/HeadingSmall.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';

interface Person {
    id: number;
    name: string;
    email?: string;
}

const props = defineProps<{
    team: {
        id: number;
        name: string;
        description: string | null;
        lead: Person | null;
        members: Person[];
    };
    people: Person[];
    can: { update: boolean; delete: boolean; manageMembers: boolean };
}>();

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    { title: 'Teams', href: '/teams' },
    { title: props.team.name, href: `/teams/${props.team.id}` },
]);

const page = usePage();
const errors = computed(() => page.props.errors as Record<string, string>);

const candidates = computed(() =>
    props.people.filter((person) => person.id !== props.team.lead?.id && !props.team.members.some((member) => member.id === person.id)),
);

const editing = ref(false);
const details = useForm({
    name: props.team.name,
    description: props.team.description ?? '',
});

const saveDetails = () => {
    details.put(route('teams.update', props.team.id), {
        preserveScroll: true,
        onSuccess: () => (editing.value = false),
    });
};

const memberForm = useForm({ user_id: null as number | null });

const addMember = () => {
    memberForm.post(route('teams.members.store', props.team.id), {
        preserveScroll: true,
        onSuccess: () => memberForm.reset(),
    });
};

const removeMember = (member: Person) => {
    if (window.confirm(`Remove ${member.name} from ${props.team.name}?`)) {
        router.delete(route('teams.members.destroy', [props.team.id, member.id]), { preserveScroll: true });
    }
};

const changeLead = (value: string) => {
    router.put(route('teams.lead.update', props.team.id), { lead_id: value ? Number(value) : null }, { preserveScroll: true });
};

const destroy = () => {
    if (window.confirm(`Delete the ${props.team.name} team? This cannot be undone.`)) {
        router.delete(route('teams.destroy', props.team.id));
    }
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head :title="team.name" />

        <div class="max-w-3xl space-y-10 px-4 py-6">
            <section>
                <div v-if="!editing" class="flex items-start justify-between gap-4">
                    <Heading :title="team.name" :description="team.description ?? undefined" />
                    <Button v-if="can.update" variant="outline" size="sm" @click="editing = true">Edit</Button>
                </div>

                <form v-else @submit.prevent="saveDetails" class="mb-8 space-y-4">
                    <div class="grid gap-2">
                        <Label for="name">Name</Label>
                        <Input id="name" v-model="details.name" required />
                        <InputError :message="details.errors.name" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="description">Description</Label>
                        <Input id="description" v-model="details.description" />
                        <InputError :message="details.errors.description" />
                    </div>
                    <div class="flex gap-2">
                        <Button :disabled="details.processing">Save</Button>
                        <Button type="button" variant="ghost" @click="editing = false">Cancel</Button>
                    </div>
                </form>
            </section>

            <section class="space-y-4">
                <HeadingSmall title="Lead" description="Organizes the team's work and assigns it to members" />
                <select
                    v-if="can.update"
                    :value="team.lead?.id ?? ''"
                    aria-label="Team lead"
                    class="h-9 w-full max-w-sm rounded-md border border-input bg-background px-3 text-sm"
                    @change="changeLead(($event.target as HTMLSelectElement).value)"
                >
                    <option value="">No lead</option>
                    <option v-for="person in people" :key="person.id" :value="person.id">{{ person.name }}</option>
                </select>
                <p v-else class="text-sm">{{ team.lead?.name ?? 'No lead yet' }}</p>
                <InputError :message="errors.lead_id" />
            </section>

            <section class="space-y-4">
                <HeadingSmall
                    title="Members"
                    :description="`${team.members.length} ${team.members.length === 1 ? 'person' : 'people'} in this team`"
                />

                <form v-if="can.manageMembers && candidates.length" @submit.prevent="addMember" class="flex flex-wrap gap-2">
                    <select
                        v-model="memberForm.user_id"
                        aria-label="Person to add"
                        class="h-9 min-w-0 flex-1 rounded-md border border-input bg-background px-3 text-sm"
                    >
                        <option :value="null" disabled>Add a member of the organization…</option>
                        <option v-for="person in candidates" :key="person.id" :value="person.id">{{ person.name }}</option>
                    </select>
                    <Button :disabled="memberForm.processing || memberForm.user_id === null">Add</Button>
                </form>
                <InputError :message="memberForm.errors.user_id" />

                <ul v-if="team.members.length" class="divide-y rounded-lg border">
                    <li v-for="member in team.members" :key="member.id" class="flex items-center gap-4 p-4">
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-medium">{{ member.name }}</p>
                            <p class="truncate text-sm text-muted-foreground">{{ member.email }}</p>
                        </div>
                        <Button v-if="can.manageMembers" variant="ghost" size="sm" @click="removeMember(member)">Remove</Button>
                    </li>
                </ul>
                <p v-else class="text-sm text-muted-foreground">No members yet.</p>
            </section>

            <section v-if="can.delete" class="space-y-4 border-t pt-6">
                <HeadingSmall title="Delete team" description="Members stay in the organization" />
                <Button variant="destructive" @click="destroy">Delete team</Button>
            </section>
        </div>
    </AppLayout>
</template>
