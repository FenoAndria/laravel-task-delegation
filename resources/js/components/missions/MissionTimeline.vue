<script setup lang="ts">
import { router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

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

const VISIBLE = 8;
const expanded = ref(false);
const hidden = computed(() => (expanded.value ? 0 : Math.max(props.entries.length - VISIBLE, 0)));
const visible = computed(() => props.entries.slice(hidden.value));

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
        <h2 class="font-display border-b pb-2 text-3xl">Activity</h2>

        <button v-if="hidden" type="button" class="text-muted-foreground hover:text-foreground text-sm" @click="expanded = true">
            Show {{ hidden }} earlier {{ hidden === 1 ? 'event' : 'events' }}
        </button>

        <ol v-if="entries.length" class="space-y-3">
            <li v-for="entry in visible" :key="entry.key">
                <div v-if="entry.kind === 'comment'" class="rounded-lg border p-3">
                    <div class="flex items-baseline gap-2 text-sm">
                        <span class="font-medium">{{ entry.actor }}</span>
                        <time :datetime="entry.created_at ?? undefined" class="text-muted-foreground text-xs">{{ timeAgo(entry.created_at) }}</time>
                        <button
                            v-if="entry.can_delete"
                            type="button"
                            class="text-muted-foreground hover:text-foreground ml-auto text-xs"
                            @click="remove(entry)"
                        >
                            Delete
                        </button>
                    </div>
                    <p class="mt-1 text-sm whitespace-pre-line">{{ entry.body }}</p>
                </div>

                <p v-else class="text-muted-foreground flex items-baseline gap-2 pl-3 text-sm">
                    <span
                        ><span class="text-foreground font-medium">{{ entry.actor }}</span> {{ entry.body }}</span
                    >
                    <time :datetime="entry.created_at ?? undefined" class="shrink-0 text-xs">{{ timeAgo(entry.created_at) }}</time>
                </p>
            </li>
        </ol>
        <p v-else class="text-muted-foreground text-sm">Nothing happened here yet.</p>

        <form @submit.prevent="submit" class="space-y-2">
            <textarea
                v-model="form.body"
                rows="3"
                required
                aria-label="Comment"
                placeholder="Ask a question, share progress, flag a risk…"
                class="border-input bg-background w-full rounded-md border px-3 py-2 text-sm"
            />
            <InputError :message="form.errors.body" />
            <Button size="sm" :disabled="form.processing || !form.body.trim()">Comment</Button>
        </form>
    </section>
</template>
