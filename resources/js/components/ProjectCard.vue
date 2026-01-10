<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { show } from '@/routes/projects';
import { type Project } from '@/types';
import { Link } from '@inertiajs/vue3';
import {
    CheckCircle2,
    ChevronRight,
    Clock,
    FileCode,
    GitBranch,
    Sparkles,
} from 'lucide-vue-next';
import { computed } from 'vue';

interface Props {
    project: Project;
}

const props = defineProps<Props>();

// Generate avatar initials from repo name
const avatarInitials = computed(() => {
    const name = props.project.name || props.project.repo_full_name;
    const parts = name.split('/');
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
    const name = props.project.name || props.project.repo_full_name;
    const hash = name
        .split('')
        .reduce((acc, char) => acc + char.charCodeAt(0), 0);
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

// Format file size
const formatSize = (bytes: number) => {
    if (bytes === 0) return '0 B';
    const k = 1024;
    const sizes = ['B', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
};

// Format number with K/M suffix
const formatNumber = (num: number) => {
    if (num >= 1000000) return (num / 1000000).toFixed(1) + 'M';
    if (num >= 1000) return (num / 1000).toFixed(1) + 'K';
    return num.toString();
};

// Get stack badges to display
const stackBadges = computed(() => {
    const badges: string[] = [];
    const stack = props.project.stack_info;

    if (!stack) return badges;

    if (stack.framework) {
        badges.push(stack.framework);
    }
    if (stack.frontend?.length) {
        badges.push(...stack.frontend.slice(0, 2));
    }
    if (stack.css?.length) {
        badges.push(stack.css[0]);
    }

    return [...new Set(badges)].slice(0, 4);
});
</script>

<template>
    <Link :href="show({ project: project.id })" class="group block">
        <div
            class="relative overflow-hidden rounded-xl border border-border/50 bg-card p-5 shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:border-border hover:shadow-md"
        >
            <!-- Subtle hover gradient -->
            <div
                class="absolute inset-0 bg-gradient-to-br from-primary/5 to-transparent opacity-0 transition-opacity duration-200 group-hover:opacity-100"
            ></div>

            <div class="relative">
                <!-- Header Row -->
                <div class="mb-4 flex items-start gap-3">
                    <!-- Avatar -->
                    <div
                        class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-gradient-to-br text-sm font-semibold text-white shadow-sm"
                        :class="avatarGradient"
                    >
                        {{ avatarInitials }}
                    </div>

                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2">
                            <h3 class="truncate font-semibold text-foreground">
                                {{ project.name || project.repo_full_name }}
                            </h3>
                        </div>
                        <div
                            class="mt-0.5 flex items-center gap-2 text-xs text-muted-foreground"
                        >
                            <span class="truncate">{{ project.owner }}</span>
                            <span
                                class="inline-flex items-center gap-1 rounded bg-muted/50 px-1.5 py-0.5"
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

                <!-- Stack Badges -->
                <div
                    v-if="stackBadges.length > 0"
                    class="mb-4 flex flex-wrap gap-1.5"
                >
                    <Badge
                        v-for="badge in stackBadges"
                        :key="badge"
                        variant="secondary"
                        class="text-xs capitalize"
                    >
                        {{ badge }}
                    </Badge>
                </div>

                <!-- Stats row -->
                <div
                    class="mb-4 flex items-center gap-4 text-xs text-muted-foreground"
                >
                    <div class="flex items-center gap-1.5">
                        <FileCode class="size-3.5" />
                        <span
                            >{{
                                formatNumber(project.total_files || 0)
                            }}
                            files</span
                        >
                    </div>
                    <div
                        v-if="project.total_lines"
                        class="flex items-center gap-1.5"
                    >
                        <span
                            >{{ formatNumber(project.total_lines) }} lines</span
                        >
                    </div>
                    <div class="flex items-center gap-1.5">
                        <Sparkles class="size-3.5 text-violet-500" />
                        <span>AI indexed</span>
                    </div>
                </div>

                <!-- Footer -->
                <div
                    class="flex items-center justify-between border-t border-border/50 pt-4"
                >
                    <div
                        class="flex items-center gap-1.5 text-xs text-muted-foreground"
                    >
                        <Clock class="size-3.5" />
                        <span v-if="project.scanned_at">
                            Scanned {{ formatRelativeTime(project.scanned_at) }}
                        </span>
                        <span v-else>
                            Added {{ formatRelativeTime(project.created_at) }}
                        </span>
                    </div>

                    <div
                        class="flex items-center gap-1 text-xs font-medium text-primary opacity-0 transition-opacity duration-200 group-hover:opacity-100"
                    >
                        View Details
                        <ChevronRight class="size-3.5" />
                    </div>
                </div>
            </div>
        </div>
    </Link>
</template>
