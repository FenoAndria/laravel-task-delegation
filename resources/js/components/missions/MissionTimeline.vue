<script setup lang="ts">
import { router, useForm } from '@inertiajs/vue3';

import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { timeAgo } from '@/lib/time';

export interface TimelineEntry {
    key: string;
    kind: 'comment' | 'activity';
    id?: number;
    type?: string;
    actor: string;
    body: string;
    created_at: string | null;
    can_delete?: boolean;
}

const props = defineProps<{
    missionId: number;
    entries: TimelineEntry[];
}>();

const form = useForm({ body: '' });

const submit = () => {
    form.post(route('comments.store', props.missionId), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
    });
};

const remove = (entry: TimelineEntry) => {
    if (entry.id && window.confirm('Delete this comment?')) {
        router.delete(route('comments.destroy', entry.id), { preserveScroll: true });
    }
};
</script>

<template>
    <section class="space-y-4">
        <h2 class="text-lg font-semibold">Activity</h2>

        <ol v-if="entries.length" class="space-y-3">
            <li v-for="entry in entries" :key="entry.key">
                <div v-if="entry.kind === 'comment'" class="rounded-lg border p-3">
                    <div class="flex items-baseline gap-2 text-sm">
                        <span class="font-medium">{{ entry.actor }}</span>
                        <time :datetime="entry.created_at ?? undefined" class="text-xs text-muted-foreground">{{ timeAgo(entry.created_at) }}</time>
                        <button
                            v-if="entry.can_delete"
                            type="button"
                            class="ml-auto text-xs text-muted-foreground hover:text-foreground"
                            @click="remove(entry)"
                        >
                            Delete
                        </button>
                    </div>
                    <p class="mt-1 whitespace-pre-line text-sm">{{ entry.body }}</p>
                </div>

                <p v-else class="flex items-baseline gap-2 pl-3 text-sm text-muted-foreground">
                    <span
                        ><span class="font-medium text-foreground">{{ entry.actor }}</span> {{ entry.body }}</span
                    >
                    <time :datetime="entry.created_at ?? undefined" class="shrink-0 text-xs">{{ timeAgo(entry.created_at) }}</time>
                </p>
            </li>
        </ol>
        <p v-else class="text-sm text-muted-foreground">Nothing happened here yet.</p>

        <form @submit.prevent="submit" class="space-y-2">
            <textarea
                v-model="form.body"
                rows="3"
                required
                aria-label="Comment"
                placeholder="Ask a question, share progress, flag a risk…"
                class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
            />
            <InputError :message="form.errors.body" />
            <Button size="sm" :disabled="form.processing || !form.body.trim()">Comment</Button>
        </form>
    </section>
</template>
