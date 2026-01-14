<script setup lang="ts">
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { Badge } from '@/components/ui/badge';
</script>

<template>
    <div class="flex items-center gap-2">
        <div
            class="flex aspect-square size-8 items-center justify-center rounded-md bg-primary text-primary-foreground"
        >
            <AppLogoIcon class="size-5 fill-current" />
        </div>
        <div class="flex flex-col">
            <span class="text-sm font-semibold text-foreground dark:text-[#E5E5E5]">
                AI Builder
            </span>
        </div>
        <Badge variant="preview" class="ml-1 hidden group-data-[collapsible=icon]:hidden sm:flex">
            Beta
        </Badge>
    </div>
</template>
