<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { create } from '@/routes/projects';
import { Link } from '@inertiajs/vue3';
import { FolderGit2, Plus, Sparkles, Zap } from 'lucide-vue-next';
</script>

<template>
    <div
        class="flex flex-1 flex-col items-center justify-center rounded-lg border-2 border-dashed px-6 py-16 text-center dark:border-[#262626] dark:bg-[#171717]/50"
    >
        <!-- Animated Icon Container -->
        <div class="relative mb-6">
            <!-- Glow effect -->
            <div
                class="absolute inset-0 animate-pulse rounded-full blur-xl dark:bg-[#F97316]/20"
            ></div>

            <!-- Icon container with gradient border -->
            <div
                class="relative flex size-20 items-center justify-center rounded-2xl ring-1 dark:bg-[#F97316]/10 dark:ring-[#F97316]/20"
            >
                <FolderGit2
                    class="size-9 dark:text-[#F97316]"
                    strokeWidth="{1.5}"
                />

                <!-- Sparkle accent -->
                <div
                    class="absolute -top-1 -right-1 flex size-6 items-center justify-center rounded-full shadow-lg dark:bg-[#F97316] dark:shadow-[#F97316]/30"
                >
                    <Sparkles class="size-3 text-white" />
                </div>
            </div>
        </div>

        <!-- Content -->
        <h3 class="mb-2 text-xl font-semibold text-foreground dark:text-[#E5E5E5]">
            No projects yet
        </h3>
        <p class="mb-8 max-w-md text-sm leading-relaxed text-muted-foreground dark:text-[#737373]">
            Connect your first GitHub repository to unlock AI-powered code
            analysis, intelligent insights, and automated task detection.
        </p>

        <!-- Feature highlights -->
        <div class="mb-8 flex flex-wrap justify-center gap-3 text-sm">
            <div
                class="flex items-center gap-2 rounded-full px-4 py-2 dark:bg-[#262626] dark:text-[#A3A3A3]"
            >
                <Zap class="size-4 dark:text-[#F59E0B]" />
                <span>Auto stack detection</span>
            </div>
            <div
                class="flex items-center gap-2 rounded-full px-4 py-2 dark:bg-[#262626] dark:text-[#A3A3A3]"
            >
                <Sparkles class="size-4 dark:text-[#F97316]" />
                <span>AI-powered indexing</span>
            </div>
        </div>

        <!-- CTA Button -->
        <Link :href="create()">
            <Button
                size="lg"
                class="group relative overflow-hidden px-6 text-white shadow-lg transition-all duration-200 hover:-translate-y-0.5 hover:shadow-xl dark:bg-[#F97316] dark:shadow-[#F97316]/25 dark:hover:bg-[#EA580C] dark:hover:shadow-[#F97316]/30"
            >
                <Plus
                    class="mr-2 size-5 transition-transform duration-200 group-hover:rotate-90"
                />
                Add Your First Project
            </Button>
        </Link>
    </div>
</template>
