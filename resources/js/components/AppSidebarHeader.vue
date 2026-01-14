<script setup lang="ts">
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import { SidebarTrigger } from '@/components/ui/sidebar';
import type { BreadcrumbItemType } from '@/types';

withDefaults(
    defineProps<{
        breadcrumbs?: BreadcrumbItemType[];
    }>(),
    {
        breadcrumbs: () => [],
    },
);
</script>

<template>
    <header
        class="flex h-14 shrink-0 items-center gap-2 border-b px-4 transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:h-12 dark:border-[#1F1F1F] dark:bg-[#0D0D0D]"
    >
        <div class="flex items-center gap-2">
            <SidebarTrigger class="-ml-1 text-muted-foreground hover:text-foreground dark:text-[#525252] dark:hover:text-[#A3A3A3] transition-colors duration-150" />
            <template v-if="breadcrumbs && breadcrumbs.length > 0">
                <div class="h-4 w-px bg-border dark:bg-[#262626]" />
                <Breadcrumbs :breadcrumbs="breadcrumbs" />
            </template>
        </div>
    </header>
</template>
