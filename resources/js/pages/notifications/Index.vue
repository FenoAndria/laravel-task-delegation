<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { computed } from 'vue';

import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import { timeAgo } from '@/lib/time';
import { type BreadcrumbItem } from '@/types';

const props = defineProps<{
    notifications: {
        id: string;
        title: string;
        body: string | null;
        read: boolean;
        created_at: string | null;
    }[];
}>();

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Notifications', href: '/notifications' }];
const hasUnread = computed(() => props.notifications.some((notification) => !notification.read));

const markAllRead = () => router.post(route('notifications.read'), {}, { preserveScroll: true });
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Notifications" />

        <div class="max-w-3xl space-y-6 px-4 py-6">
            <div class="flex items-start justify-between gap-4">
                <Heading title="Notifications" description="What needs your attention in this organization" />
                <Button v-if="hasUnread" variant="outline" size="sm" @click="markAllRead">Mark all as read</Button>
            </div>

            <ul v-if="notifications.length" class="divide-y rounded-lg border">
                <li v-for="notification in notifications" :key="notification.id">
                    <Link :href="route('notifications.show', notification.id)" class="hover:bg-accent/50 flex gap-3 p-4">
                        <span class="mt-1.5 size-2 shrink-0 rounded-full" :class="notification.read ? 'bg-transparent' : 'bg-signal'" />
                        <span class="min-w-0 flex-1">
                            <span class="block text-sm" :class="notification.read ? 'text-muted-foreground' : 'font-medium'">{{
                                notification.title
                            }}</span>
                            <span v-if="notification.body" class="text-muted-foreground mt-0.5 block truncate text-sm">{{ notification.body }}</span>
                        </span>
                        <time :datetime="notification.created_at ?? undefined" class="text-muted-foreground shrink-0 text-xs">
                            {{ timeAgo(notification.created_at) }}
                        </time>
                    </Link>
                </li>
            </ul>

            <div v-else class="rounded-lg border border-dashed p-10 text-center">
                <p class="font-medium">You're all caught up</p>
                <p class="text-muted-foreground mt-1 text-sm">Assignments, blockers and comments on your missions will show up here.</p>
            </div>
        </div>
    </AppLayout>
</template>
