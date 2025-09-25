<script setup lang="ts">
import { TransitionRoot } from '@headlessui/vue';
import { Head, useForm } from '@inertiajs/vue3';

import HeadingSmall from '@/components/HeadingSmall.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { type BreadcrumbItem } from '@/types';

const props = defineProps<{
    organization: { id: number; name: string; slug: string };
    canUpdate: boolean;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Organization settings',
        href: '/settings/organization',
    },
];

const form = useForm({
    name: props.organization.name,
});

const submit = () => {
    form.patch(route('organization.update'), {
        preserveScroll: true,
    });
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Organization settings" />

        <SettingsLayout>
            <div class="flex flex-col space-y-6">
                <HeadingSmall title="Organization" description="The name your members see across Delego" />

                <form @submit.prevent="submit" class="space-y-6">
                    <div class="grid gap-2">
                        <Label for="name">Name</Label>
                        <Input id="name" class="mt-1 block w-full" v-model="form.name" required :disabled="!canUpdate" />
                        <InputError class="mt-2" :message="form.errors.name" />
                        <p v-if="!canUpdate" class="text-muted-foreground text-sm">Only owners and admins can rename the organization.</p>
                    </div>

                    <div v-if="canUpdate" class="flex items-center gap-4">
                        <Button :disabled="form.processing">Save</Button>

                        <TransitionRoot
                            :show="form.recentlySuccessful"
                            enter="transition ease-in-out"
                            enter-from="opacity-0"
                            leave="transition ease-in-out"
                            leave-to="opacity-0"
                        >
                            <p class="text-muted-foreground text-sm">Saved.</p>
                        </TransitionRoot>
                    </div>
                </form>
            </div>
        </SettingsLayout>
    </AppLayout>
</template>
