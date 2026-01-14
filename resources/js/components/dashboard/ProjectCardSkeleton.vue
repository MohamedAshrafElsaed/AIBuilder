<script setup lang="ts">
// Skeleton loading card for projects
// Uses CSS shimmer animation for premium loading feel
</script>

<template>
    <div
        class="group relative overflow-hidden rounded-lg border bg-card p-5 shadow-sm dark:bg-[#171717] dark:border-[#262626]"
    >
        <!-- Shimmer overlay -->
        <div
            class="absolute inset-0 -translate-x-full animate-[shimmer_2s_infinite] bg-gradient-to-r from-transparent via-white/5 to-transparent dark:via-[#F97316]/5"
        ></div>

        <!-- Header skeleton -->
        <div class="flex items-start gap-4">
            <!-- Avatar skeleton -->
            <div
                class="size-10 shrink-0 animate-pulse rounded-lg dark:bg-[#262626]"
            ></div>

            <div class="flex-1 space-y-2">
                <!-- Title skeleton -->
                <div class="h-5 w-3/4 animate-pulse rounded-md dark:bg-[#262626]"></div>
                <!-- Subtitle skeleton -->
                <div
                    class="h-4 w-1/2 animate-pulse rounded-md dark:bg-[#1F1F1F]"
                ></div>
            </div>

            <!-- Badge skeleton -->
            <div class="h-6 w-20 animate-pulse rounded-full dark:bg-[#262626]"></div>
        </div>

        <!-- Content skeleton -->
        <div class="mt-5 space-y-3">
            <div class="h-3 w-full animate-pulse rounded dark:bg-[#1F1F1F]"></div>
            <div class="h-3 w-5/6 animate-pulse rounded dark:bg-[#1F1F1F]"></div>
        </div>

        <!-- Footer skeleton -->
        <div
            class="mt-5 flex items-center justify-between border-t pt-4 dark:border-[#262626]"
        >
            <div class="h-4 w-24 animate-pulse rounded dark:bg-[#1A1A1A]"></div>
            <div class="h-8 w-20 animate-pulse rounded-md dark:bg-[#1A1A1A]"></div>
        </div>
    </div>
</template>

<style scoped>
@keyframes shimmer {
    100% {
        transform: translateX(100%);
    }
}
</style>
