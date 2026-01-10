<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { type Project } from '@/types';
import {
    CheckCircle2,
    ChevronRight,
    Clock,
    FileCode,
    GitBranch,
    Lock,
    Sparkles,
} from 'lucide-vue-next';
import { computed } from 'vue';

// NOTE: Add this import once you create the projects.show route:
// import { show } from '@/routes/projects';

interface Props {
    project: Project;
}

const props = defineProps<Props>();

// Generate avatar initials from repo name
const avatarInitials = computed(() => {
    const parts = props.project.name?.split('/') || ['?'];
    const repoName = parts[parts.length - 1] || '?';
    return repoName.substring(0, 2).toUpperCase();
});

// Avatar gradient based on repo name (for visual variety)
const avatarGradient = computed(() => {
    const gradients = [
        'from-violet-500 to-indigo-500',
        'from-emerald-500 to-teal-500',
        'from-amber-500 to-orange-500',
        'from-pink-500 to-rose-500',
        'from-cyan-500 to-blue-500',
    ];
    const hash =
        props.project.name
            ?.split('')
            .reduce((acc, char) => acc + char.charCodeAt(0), 0) || 0;
    return gradients[hash % gradients.length];
});

// Format relative time
const formatRelativeTime = (date: string) => {
    const now = new Date();
    const then = new Date(date);
    const diff = now.getTime() - then.getTime();
    const days = Math.floor(diff / (1000 * 60 * 60 * 24));
    const hours = Math.floor(diff / (1000 * 60 * 60));
    const minutes = Math.floor(diff / (1000 * 60));

    if (days > 0) return `${days}d ago`;
    if (hours > 0) return `${hours}h ago`;
    if (minutes > 0) return `${minutes}m ago`;
    return 'Just now';
};
</script>

<template>
    <!--
        TODO: Wrap with <Link :href="show({ project: project.id })">
        once you add the projects.show route
    -->
    <div class="group block cursor-pointer">
        <div
            class="relative overflow-hidden rounded-xl border border-border/50 bg-card p-5 shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:border-border hover:shadow-md"
        >
            <!-- Subtle hover gradient -->
            <div
                class="absolute inset-0 bg-gradient-to-br from-violet-500/[0.02] to-indigo-500/[0.02] opacity-0 transition-opacity duration-200 group-hover:opacity-100"
            ></div>

            <div class="relative">
                <!-- Header -->
                <div class="mb-4 flex items-start gap-4">
                    <!-- Repository Avatar -->
                    <div
                        class="flex size-11 shrink-0 items-center justify-center rounded-lg text-sm font-semibold text-white shadow-sm transition-transform duration-200 group-hover:scale-105"
                        :class="`bg-gradient-to-br ${avatarGradient}`"
                    >
                        {{ avatarInitials }}
                    </div>

                    <div class="min-w-0 flex-1">
                        <h3
                            class="truncate font-semibold text-foreground transition-colors duration-200 group-hover:text-violet-600 dark:group-hover:text-violet-400"
                        >
                            {{ project.name }}
                        </h3>
                        <div class="mt-1 flex items-center gap-2">
                            <Badge
                                v-if="project.private"
                                variant="secondary"
                                class="gap-1 text-xs"
                            >
                                <Lock class="size-3" />
                                Private
                            </Badge>
                            <span
                                class="flex items-center gap-1 text-xs text-muted-foreground"
                            >
                                <GitBranch class="size-3" />
                                {{ project.default_branch || 'main' }}
                            </span>
                        </div>
                    </div>

                    <!-- Status indicator -->
                    <div
                        class="flex items-center gap-1.5 rounded-full bg-emerald-500/10 px-2.5 py-1 text-xs font-medium text-emerald-600 ring-1 ring-emerald-500/20 ring-inset dark:text-emerald-400"
                    >
                        <CheckCircle2 class="size-3" />
                        Ready
                    </div>
                </div>

                <!-- Description -->
                <p
                    v-if="project.description"
                    class="mb-4 line-clamp-2 text-sm text-muted-foreground"
                >
                    {{ project.description }}
                </p>

                <!-- Stats row -->
                <div
                    class="mb-4 flex items-center gap-4 text-xs text-muted-foreground"
                >
                    <div class="flex items-center gap-1.5">
                        <FileCode class="size-3.5" />
                        <span>{{ project.files_count || '—' }} files</span>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <Sparkles class="size-3.5 text-violet-500" />
                        <span>AI indexed</span>
                    </div>
                    <div
                        v-if="project.updated_at"
                        class="flex items-center gap-1.5"
                    >
                        <Clock class="size-3.5" />
                        <span>{{
                            formatRelativeTime(project.updated_at)
                        }}</span>
                    </div>
                </div>

                <!-- Footer -->
                <div
                    class="flex items-center justify-between border-t border-border/50 pt-4"
                >
                    <!-- Stack badges -->
                    <div class="flex items-center gap-1.5">
                        <Badge
                            v-for="tech in (
                                project.stack || ['Vue', 'Laravel']
                            ).slice(0, 3)"
                            :key="tech"
                            variant="outline"
                            class="text-xs font-normal"
                        >
                            {{ tech }}
                        </Badge>
                    </div>

                    <!-- Open button -->
                    <Button
                        variant="ghost"
                        size="sm"
                        class="gap-1 text-muted-foreground group-hover:text-foreground"
                    >
                        Open
                        <ChevronRight
                            class="size-4 transition-transform duration-200 group-hover:translate-x-0.5"
                        />
                    </Button>
                </div>
            </div>
        </div>
    </div>
</template>
