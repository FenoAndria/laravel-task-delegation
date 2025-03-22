<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

import HeadingSmall from '@/components/HeadingSmall.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { type BreadcrumbItem, type OrganizationRole, type SharedData } from '@/types';

interface Member {
    id: number;
    name: string;
    email: string;
    role: OrganizationRole;
    joined_at: string | null;
}

defineProps<{
    members: Member[];
    roles: { value: OrganizationRole; label: string }[];
    canManage: boolean;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Members',
        href: '/settings/members',
    },
];

const page = usePage<SharedData>();
const currentUserId = computed(() => page.props.auth.user.id);
const errors = computed(() => page.props.errors as Record<string, string>);

const changeRole = (member: Member, role: string) => {
    router.patch(route('members.update', member.id), { role }, { preserveScroll: true });
};

const remove = (member: Member) => {
    const leaving = member.id === currentUserId.value;
    const message = leaving ? 'Leave this organization?' : `Remove ${member.name} from the organization?`;

    if (window.confirm(message)) {
        router.delete(route('members.destroy', member.id), { preserveScroll: true });
    }
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Members" />

        <SettingsLayout>
            <div class="flex flex-col space-y-6">
                <HeadingSmall title="Members" description="People who belong to this organization and what they can do" />

                <InputError :message="errors.role ?? errors.member" />

                <ul class="divide-y rounded-lg border">
                    <li v-for="member in members" :key="member.id" class="flex flex-wrap items-center gap-4 p-4">
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-medium">
                                {{ member.name }}
                                <span v-if="member.id === currentUserId" class="text-muted-foreground">(you)</span>
                            </p>
                            <p class="truncate text-sm text-muted-foreground">{{ member.email }}</p>
                        </div>

                        <select
                            v-if="canManage"
                            :value="member.role"
                            :aria-label="`Role of ${member.name}`"
                            class="h-9 rounded-md border border-input bg-background px-3 text-sm"
                            @change="changeRole(member, ($event.target as HTMLSelectElement).value)"
                        >
                            <option v-for="role in roles" :key="role.value" :value="role.value">{{ role.label }}</option>
                        </select>
                        <span v-else class="text-sm capitalize text-muted-foreground">{{ member.role }}</span>

                        <Button v-if="canManage || member.id === currentUserId" variant="ghost" size="sm" @click="remove(member)">
                            {{ member.id === currentUserId ? 'Leave' : 'Remove' }}
                        </Button>
                    </li>
                </ul>
            </div>
        </SettingsLayout>
    </AppLayout>
</template>
