<script setup lang="ts">
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    SidebarSeparator,
} from '@/components/ui/sidebar';
import { Input } from '@/components/ui/input';
import { dashboard } from '@/routes';
import { type NavItem } from '@/types';
import { Link } from '@inertiajs/vue3';
import { LayoutGrid, FolderKanban, Settings, Search, Sparkles, HelpCircle, BookOpen } from 'lucide-vue-next';
import AppLogo from './AppLogo.vue';

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    },
    {
        title: 'Projects',
        href: dashboard(),
        icon: FolderKanban,
    },
];

const secondaryNavItems: NavItem[] = [
    {
        title: 'Settings',
        href: '/settings/profile',
        icon: Settings,
    },
    {
        title: 'Documentation',
        href: 'https://laravel.com/docs',
        icon: BookOpen,
    },
    {
        title: 'Help',
        href: '/help',
        icon: HelpCircle,
    },
];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset" class="dark:bg-[#0F0F0F] dark:border-[#262626]">
        <SidebarHeader class="p-3">
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child class="hover:bg-transparent">
                        <Link :href="dashboard()" class="flex items-center">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>

            <!-- Search/Command Input -->
            <div class="mt-3 group-data-[collapsible=icon]:hidden">
                <div class="relative">
                    <Search class="absolute left-3 top-1/2 -translate-y-1/2 size-4 text-muted-foreground dark:text-[#525252]" />
                    <Input
                        type="text"
                        placeholder="Ask AI to help..."
                        class="pl-9 h-9 text-sm bg-secondary dark:bg-[#1A1A1A] dark:border-[#262626] dark:placeholder:text-[#525252] dark:text-[#A3A3A3]"
                    />
                    <div class="absolute right-3 top-1/2 -translate-y-1/2">
                        <Sparkles class="size-3.5 text-primary/60 dark:text-[#F97316]/60" />
                    </div>
                </div>
            </div>
        </SidebarHeader>

        <SidebarSeparator class="dark:bg-[#1F1F1F]" />

        <SidebarContent class="px-1">
            <NavMain :items="mainNavItems" label="Platform" />
            <NavMain :items="secondaryNavItems" label="Support" />
        </SidebarContent>

        <SidebarSeparator class="dark:bg-[#1F1F1F]" />

        <SidebarFooter class="p-2">
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
