<script setup lang="ts">
import {
    SidebarGroup,
    SidebarGroupLabel,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { useActiveUrl } from '@/composables/useActiveUrl';
import { type NavItem } from '@/types';
import { Link } from '@inertiajs/vue3';

interface Props {
    items: NavItem[];
    label?: string;
}

defineProps<Props>();

const { urlIsActive } = useActiveUrl();
</script>

<template>
    <SidebarGroup class="px-2 py-2">
        <SidebarGroupLabel
            v-if="label"
            class="text-[11px] font-medium text-muted-foreground dark:text-[#525252] uppercase tracking-wider mb-1 px-2"
        >
            {{ label }}
        </SidebarGroupLabel>
        <SidebarMenu>
            <SidebarMenuItem v-for="item in items" :key="item.title">
                <SidebarMenuButton
                    as-child
                    :is-active="urlIsActive(item.href)"
                    :tooltip="item.title"
                    class="transition-all duration-150 rounded-md dark:text-[#A3A3A3] dark:hover:text-[#E5E5E5] dark:hover:bg-[#1A1A1A] dark:data-[active=true]:bg-[#1A1A1A] dark:data-[active=true]:text-[#E5E5E5]"
                >
                    <Link :href="item.href" class="flex items-center gap-3">
                        <component :is="item.icon" class="size-4 shrink-0" />
                        <span class="text-sm truncate">{{ item.title }}</span>
                    </Link>
                </SidebarMenuButton>
            </SidebarMenuItem>
        </SidebarMenu>
    </SidebarGroup>
</template>
