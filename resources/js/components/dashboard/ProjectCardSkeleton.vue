<script setup lang="ts">
// Skeleton loading card for projects
// Uses CSS shimmer animation for premium loading feel
</script>

<template>
    <div
        class="group relative overflow-hidden rounded-xl border border-border/50 bg-card p-5 shadow-sm"
    >
        <!-- Shimmer overlay -->
        <div
            class="absolute inset-0 -translate-x-full animate-[shimmer_2s_infinite] bg-gradient-to-r from-transparent via-white/10 to-transparent"
        ></div>

        <!-- Header skeleton -->
        <div class="flex items-start gap-4">
            <!-- Avatar skeleton -->
            <div
                class="size-11 shrink-0 animate-pulse rounded-lg bg-muted"
            ></div>

            <div class="flex-1 space-y-2">
                <!-- Title skeleton -->
                <div class="h-5 w-3/4 animate-pulse rounded-md bg-muted"></div>
                <!-- Subtitle skeleton -->
                <div
                    class="h-4 w-1/2 animate-pulse rounded-md bg-muted/70"
                ></div>
            </div>

            <!-- Badge skeleton -->
            <div class="h-6 w-20 animate-pulse rounded-full bg-muted"></div>
        </div>

        <!-- Content skeleton -->
        <div class="mt-5 space-y-3">
            <div class="h-3 w-full animate-pulse rounded bg-muted/60"></div>
            <div class="h-3 w-5/6 animate-pulse rounded bg-muted/60"></div>
        </div>

        <!-- Footer skeleton -->
        <div
            class="mt-5 flex items-center justify-between border-t border-border/50 pt-4"
        >
            <div class="h-4 w-24 animate-pulse rounded bg-muted/50"></div>
            <div class="h-8 w-20 animate-pulse rounded-md bg-muted/50"></div>
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
