<script setup lang="ts">
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import OrganizationSwitcher from '@/components/OrganizationSwitcher.vue';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader } from '@/components/ui/sidebar';
import { type NavItem, type SharedData } from '@/types';
import { router, usePage } from '@inertiajs/vue3';
import { echoIsConfigured, useEchoNotification } from '@laravel/echo-vue';
import { Bell, Flag, LayoutGrid, ListTodo, Users } from 'lucide-vue-next';
import { computed } from 'vue';

const page = usePage<SharedData>();

// New notifications show up in the counter without a page load.
if (echoIsConfigured()) {
    useEchoNotification(`App.Models.User.${page.props.auth.user.id}`, () => router.reload({ only: ['unreadNotifications'] }));
}

const mainNavItems = computed<NavItem[]>(() => [
    {
        title: 'Dashboard',
        href: '/dashboard',
        icon: LayoutGrid,
    },
    {
        title: 'My tasks',
        href: '/my-tasks',
        icon: ListTodo,
    },
    {
        title: 'Missions',
        href: '/missions',
        icon: Flag,
    },
    {
        title: 'Teams',
        href: '/teams',
        icon: Users,
    },
    {
        title: 'Notifications',
        href: '/notifications',
        icon: Bell,
        badge: page.props.unreadNotifications,
    },
]);
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <OrganizationSwitcher />
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
