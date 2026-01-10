<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { create } from '@/routes/projects';
import { Link } from '@inertiajs/vue3';
import { FolderGit2, Plus, Sparkles, Zap } from 'lucide-vue-next';
</script>

<template>
    <div
        class="flex flex-1 flex-col items-center justify-center rounded-2xl border-2 border-dashed border-border/60 bg-gradient-to-b from-muted/30 to-transparent px-6 py-16 text-center"
    >
        <!-- Animated Icon Container -->
        <div class="relative mb-6">
            <!-- Glow effect -->
            <div
                class="absolute inset-0 animate-pulse rounded-full bg-violet-500/20 blur-xl"
            ></div>

            <!-- Icon container with gradient border -->
            <div
                class="relative flex size-20 items-center justify-center rounded-2xl bg-gradient-to-br from-violet-500/10 to-indigo-500/10 ring-1 ring-violet-500/20"
            >
                <FolderGit2
                    class="size-9 text-violet-500"
                    strokeWidth="{1.5}"
                />

                <!-- Sparkle accent -->
                <div
                    class="absolute -top-1 -right-1 flex size-6 items-center justify-center rounded-full bg-gradient-to-br from-violet-500 to-indigo-500 shadow-lg shadow-violet-500/30"
                >
                    <Sparkles class="size-3 text-white" />
                </div>
            </div>
        </div>

        <!-- Content -->
        <h3 class="mb-2 text-xl font-semibold text-foreground">
            No projects yet
        </h3>
        <p class="mb-8 max-w-md leading-relaxed text-muted-foreground">
            Connect your first GitHub repository to unlock AI-powered code
            analysis, intelligent insights, and automated task detection.
        </p>

        <!-- Feature highlights -->
        <div class="mb-8 flex flex-wrap justify-center gap-4 text-sm">
            <div
                class="flex items-center gap-2 rounded-full bg-muted/50 px-4 py-2 text-muted-foreground"
            >
                <Zap class="size-4 text-amber-500" />
                <span>Auto stack detection</span>
            </div>
            <div
                class="flex items-center gap-2 rounded-full bg-muted/50 px-4 py-2 text-muted-foreground"
            >
                <Sparkles class="size-4 text-violet-500" />
                <span>AI-powered indexing</span>
            </div>
        </div>

        <!-- CTA Button -->
        <Link :href="create()">
            <Button
                size="lg"
                class="group relative overflow-hidden bg-gradient-to-r from-violet-600 to-indigo-600 px-6 text-white shadow-lg shadow-violet-500/25 transition-all duration-200 hover:-translate-y-0.5 hover:shadow-xl hover:shadow-violet-500/30"
            >
                <Plus
                    class="mr-2 size-5 transition-transform duration-200 group-hover:rotate-90"
                />
                Add Your First Project
            </Button>
        </Link>
    </div>
</template>
