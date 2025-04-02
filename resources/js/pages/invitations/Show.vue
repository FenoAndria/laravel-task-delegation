<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import AuthBase from '@/layouts/AuthLayout.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps<{
    invitation: {
        token: string;
        email: string;
        role: string;
        organization: string;
        inviter: string | null;
        state: 'pending' | 'accepted' | 'declined' | 'cancelled' | 'expired';
        expires_at: string;
    };
    isGuest: boolean;
    isRecipient: boolean;
    hasAccount: boolean;
}>();

const page = usePage();
const error = computed(() => (page.props.errors as Record<string, string>).invitation);
const form = useForm({});

const closedMessages: Record<string, string> = {
    accepted: 'This invitation has already been accepted.',
    declined: 'This invitation was declined.',
    cancelled: 'This invitation was cancelled by the organization.',
    expired: 'This invitation has expired. Ask the organization to send a new one.',
};

const title = computed(() => `Join ${props.invitation.organization}`);
const description = computed(() =>
    props.invitation.inviter
        ? `${props.invitation.inviter} invited ${props.invitation.email} to join as ${props.invitation.role}.`
        : `${props.invitation.email} was invited to join as ${props.invitation.role}.`,
);
</script>

<template>
    <AuthBase :title="title" :description="description">
        <Head :title="title" />

        <div class="flex flex-col gap-4">
            <p v-if="invitation.state !== 'pending'" class="text-center text-sm text-muted-foreground">
                {{ closedMessages[invitation.state] }}
            </p>

            <template v-else-if="isGuest">
                <Button as-child class="w-full">
                    <Link v-if="hasAccount" :href="route('login')">Log in to accept</Link>
                    <Link v-else :href="route('register', { email: invitation.email })">Create an account to accept</Link>
                </Button>
                <p class="text-center text-sm text-muted-foreground">
                    <TextLink v-if="hasAccount" :href="route('register', { email: invitation.email })">Create an account instead</TextLink>
                    <TextLink v-else :href="route('login')">I already have an account</TextLink>
                </p>
            </template>

            <template v-else-if="isRecipient">
                <Button class="w-full" :disabled="form.processing" @click="form.post(route('invitations.accept', invitation.token))">
                    Accept invitation
                </Button>
                <Button variant="ghost" class="w-full" :disabled="form.processing" @click="form.post(route('invitations.decline', invitation.token))">
                    Decline
                </Button>
            </template>

            <p v-else class="text-center text-sm text-muted-foreground">
                This invitation was sent to <strong>{{ invitation.email }}</strong>. Log in with that address to answer it.
            </p>

            <InputError :message="error" class="text-center" />
        </div>
    </AuthBase>
</template>
