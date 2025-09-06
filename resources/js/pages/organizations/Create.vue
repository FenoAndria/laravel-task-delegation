<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthBase from '@/layouts/AuthLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { LoaderCircle } from 'lucide-vue-next';

defineProps<{
    hasOrganizations: boolean;
    invitations: { token: string; organization: string; role: string }[];
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

        <div v-if="invitations.length" class="mb-6 flex flex-col gap-2">
            <p class="text-sm font-medium">You have been invited</p>
            <Link
                v-for="invitation in invitations"
                :key="invitation.token"
                :href="route('invitations.show', invitation.token)"
                class="hover:bg-accent flex items-center justify-between rounded-md border px-4 py-3 text-sm"
            >
                <span class="font-medium">{{ invitation.organization }}</span>
                <span class="text-muted-foreground">as {{ invitation.role }}</span>
            </Link>
            <p class="text-muted-foreground mt-2 text-center text-xs tracking-wide uppercase">or start your own</p>
        </div>

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

            <div class="text-muted-foreground text-center text-sm">
                <TextLink v-if="hasOrganizations" :href="route('dashboard')">Back to my organization</TextLink>
                <span v-else>Invited by someone? Their invitation will add you to their organization.</span>
            </div>
        </form>
    </AuthBase>
</template>
