<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthBase from '@/layouts/AuthLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { LoaderCircle } from 'lucide-vue-next';

defineProps<{
    hasOrganizations: boolean;
}>();

const form = useForm({
    name: '',
});

const submit = () => {
    form.post(route('organizations.store'));
};
</script>

<template>
    <AuthBase title="Create your organization" description="An organization groups your teams, their missions and the people working on them.">
        <Head title="Create an organization" />

        <form @submit.prevent="submit" class="flex flex-col gap-6">
            <div class="grid gap-2">
                <Label for="name">Organization name</Label>
                <Input id="name" required autofocus v-model="form.name" placeholder="Acme Studio" />
                <InputError :message="form.errors.name" />
            </div>

            <Button type="submit" class="w-full" :disabled="form.processing">
                <LoaderCircle v-if="form.processing" class="h-4 w-4 animate-spin" />
                Create organization
            </Button>

            <div class="text-center text-sm text-muted-foreground">
                <TextLink v-if="hasOrganizations" :href="route('dashboard')">Back to my organization</TextLink>
                <span v-else>Invited by someone? Their invitation will add you to their organization.</span>
            </div>
        </form>
    </AuthBase>
</template>
