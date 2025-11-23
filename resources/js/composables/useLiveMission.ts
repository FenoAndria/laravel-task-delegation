import { router } from '@inertiajs/vue3';
import { echoIsConfigured, useEchoPresence } from '@laravel/echo-vue';
import { onBeforeUnmount, ref } from 'vue';

export interface Viewer {
    id: number;
    name: string;
}

/**
 * Keeps a mission page in sync with what others do, and tells who else is looking at it.
 * Does nothing when no WebSocket server is configured.
 */
export function useLiveMission(missionId: number, reloadOnly: string[]) {
    const viewers = ref<Viewer[]>([]);

    if (!echoIsConfigured()) {
        return { viewers };
    }

    let timer: ReturnType<typeof setTimeout> | undefined;

    // Several events often arrive together (a task added, then assigned): reload once.
    const refresh = () => {
        clearTimeout(timer);
        timer = setTimeout(() => router.reload({ only: reloadOnly }), 300);
    };

    const { channel } = useEchoPresence(`missions.${missionId}`, '.mission.changed', refresh);

    channel()
        .here((users: Viewer[]) => (viewers.value = users))
        .joining((user: Viewer) => viewers.value.push(user))
        .leaving((user: Viewer) => (viewers.value = viewers.value.filter((viewer) => viewer.id !== user.id)));

    onBeforeUnmount(() => clearTimeout(timer));

    return { viewers };
}
