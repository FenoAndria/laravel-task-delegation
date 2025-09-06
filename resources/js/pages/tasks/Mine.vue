<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import TaskRow, { type TaskItem } from '@/components/tasks/TaskRow.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';

const props = defineProps<{
    groups: { status: string; label: string; tasks: TaskItem[] }[];
}>();

const breadcrumbs: BreadcrumbItem[] = [{ title: 'My tasks', href: '/my-tasks' }];

const page = usePage();
const errors = computed(() => page.props.errors as Record<string, string>);
const isEmpty = computed(() => props.groups.every((group) => group.tasks.length === 0));
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="My tasks" />

        <div class="max-w-4xl space-y-8 px-4 py-6">
            <Heading title="My tasks" description="What your leads assigned to you, across every team" />

            <InputError :message="errors.status ?? errors.blocked_reason" />

            <div v-if="isEmpty" class="rounded-lg border border-dashed p-10 text-center">
                <p class="font-medium">Nothing on your plate</p>
                <p class="text-muted-foreground mt-1 text-sm">Tasks assigned to you will show up here.</p>
            </div>

            <template v-else>
                <section v-for="group in groups.filter((group) => group.tasks.length)" :key="group.status" class="space-y-2">
                    <h2 class="text-muted-foreground text-sm font-medium">
                        {{ group.label }} <span class="tabular-nums">({{ group.tasks.length }})</span>
                    </h2>
                    <ul class="divide-y rounded-lg border">
                        <TaskRow v-for="task in group.tasks" :key="task.id" :task="task" />
                    </ul>
                </section>
            </template>
        </div>
    </AppLayout>
</template>
