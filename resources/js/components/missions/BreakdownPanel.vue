<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { type SharedData } from '@/types';

const props = defineProps<{
    missionId: number;
}>();

interface Draft {
    keep: boolean;
    title: string;
    estimate_hours: number | null;
}

const page = usePage<SharedData>();
const error = computed(() => (page.props.errors as Record<string, string>).breakdown);
const asking = ref(false);
const saving = ref(false);
const drafts = ref<Draft[]>([]);

// A fresh proposal for this mission arrives through the flashed session data.
watch(
    () => page.props.flash.breakdown,
    (proposal) => {
        if (proposal?.mission_id === props.missionId) {
            drafts.value = proposal.tasks.map((task) => ({ keep: true, ...task }));
        }
    },
    { immediate: true },
);

const kept = computed(() => drafts.value.filter((draft) => draft.keep && draft.title.trim()));

const ask = () => {
    asking.value = true;
    router.post(route('missions.breakdown', props.missionId), {}, { preserveScroll: true, onFinish: () => (asking.value = false) });
};

const accept = () => {
    saving.value = true;
    router.post(
        route('missions.breakdown.accept', props.missionId),
        { tasks: kept.value.map(({ title, estimate_hours }) => ({ title: title.trim(), estimate_hours })) },
        {
            preserveScroll: true,
            onSuccess: () => (drafts.value = []),
            onFinish: () => (saving.value = false),
        },
    );
};
</script>

<template>
    <div class="space-y-3">
        <div v-if="!drafts.length" class="flex flex-wrap items-center gap-3">
            <Button variant="outline" size="sm" :disabled="asking" @click="ask">
                {{ asking ? 'Drafting a breakdown…' : 'Draft tasks with the assistant' }}
            </Button>
            <span class="text-muted-foreground text-xs">You review every task before anything is created.</span>
        </div>

        <div v-else class="space-y-3 rounded-lg border p-4">
            <div class="flex items-baseline justify-between gap-4">
                <p class="eyebrow">Proposed breakdown</p>
                <span class="text-muted-foreground text-xs">Edit, untick what you don't need, then add.</span>
            </div>

            <ul class="divide-y">
                <li v-for="(draft, index) in drafts" :key="index" class="flex items-center gap-3 py-2">
                    <input v-model="draft.keep" type="checkbox" class="size-4 accent-[var(--signal)]" :aria-label="`Keep task ${index + 1}`" />
                    <input
                        v-model="draft.title"
                        class="min-w-0 flex-1 bg-transparent text-sm outline-none focus:underline"
                        :class="{ 'text-muted-foreground line-through': !draft.keep }"
                        :aria-label="`Title of task ${index + 1}`"
                    />
                    <label class="text-muted-foreground flex items-center gap-1 font-mono text-xs">
                        <input
                            v-model.number="draft.estimate_hours"
                            type="number"
                            min="1"
                            max="200"
                            class="w-12 rounded border bg-transparent px-1 py-0.5 text-right"
                            :aria-label="`Estimate in hours of task ${index + 1}`"
                        />h
                    </label>
                </li>
            </ul>

            <div class="flex gap-2">
                <Button size="sm" :disabled="saving || !kept.length" @click="accept">
                    Add {{ kept.length }} {{ kept.length === 1 ? 'task' : 'tasks' }}
                </Button>
                <Button variant="ghost" size="sm" @click="drafts = []">Discard</Button>
            </div>
        </div>

        <InputError :message="error" />
    </div>
</template>
