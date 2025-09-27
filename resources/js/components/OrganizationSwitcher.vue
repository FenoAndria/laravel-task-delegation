<script setup lang="ts">
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type SharedData } from '@/types';
import { Link, router, usePage } from '@inertiajs/vue3';
import { Check, ChevronsUpDown, Plus } from 'lucide-vue-next';
import { computed } from 'vue';

const page = usePage<SharedData>();
const current = computed(() => page.props.auth.organization);
const organizations = computed(() => page.props.auth.organizations);

const switchTo = (id: number) => {
    if (id !== current.value?.id) {
        router.put(route('current-organization.update'), { organization_id: id });
    }
};
</script>

<template>
    <SidebarMenu v-if="current">
        <SidebarMenuItem>
            <DropdownMenu>
                <DropdownMenuTrigger as-child>
                    <SidebarMenuButton size="lg" class="data-[state=open]:bg-sidebar-accent data-[state=open]:text-sidebar-accent-foreground">
                        <div
                            class="bg-sidebar-primary text-sidebar-primary-foreground flex aspect-square size-8 items-center justify-center rounded-md text-sm font-semibold"
                        >
                            {{ current.name.charAt(0).toUpperCase() }}
                        </div>
                        <div class="grid flex-1 text-left text-sm leading-tight">
                            <span class="truncate font-semibold">{{ current.name }}</span>
                            <span class="text-muted-foreground truncate text-xs capitalize">{{ current.role }}</span>
                        </div>
                        <ChevronsUpDown class="ml-auto size-4" />
                    </SidebarMenuButton>
                </DropdownMenuTrigger>
                <DropdownMenuContent class="w-(--radix-dropdown-menu-trigger-width) min-w-56 rounded-lg" align="start" :side-offset="4">
                    <DropdownMenuLabel class="text-muted-foreground text-xs">Organizations</DropdownMenuLabel>
                    <DropdownMenuItem v-for="organization in organizations" :key="organization.id" @select="switchTo(organization.id)">
                        <span class="truncate">{{ organization.name }}</span>
                        <Check v-if="organization.id === current.id" class="ml-auto size-4" />
                    </DropdownMenuItem>
                    <DropdownMenuSeparator />
                    <DropdownMenuItem :as-child="true">
                        <Link class="w-full" :href="route('organizations.create')">
                            <Plus class="mr-2 size-4" />
                            New organization
                        </Link>
                    </DropdownMenuItem>
                </DropdownMenuContent>
            </DropdownMenu>
        </SidebarMenuItem>
    </SidebarMenu>
</template>
