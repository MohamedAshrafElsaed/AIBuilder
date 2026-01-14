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
        'from-orange-500 to-amber-500',
        'from-emerald-500 to-teal-500',
        'from-violet-500 to-purple-500',
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
            class="relative overflow-hidden rounded-lg border bg-card p-5 transition-all duration-200 hover:-translate-y-0.5 hover:shadow-lg dark:bg-[#171717] dark:border-[#262626] dark:hover:border-[#333] dark:hover:shadow-[0_8px_30px_rgba(0,0,0,0.4)]"
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
                            <h3 class="truncate font-semibold text-foreground dark:text-[#E5E5E5]">
                                {{ project.name || project.repo_full_name }}
                            </h3>
                        </div>
                        <div
                            class="mt-0.5 flex items-center gap-2 text-xs text-muted-foreground dark:text-[#737373]"
                        >
                            <span class="truncate">{{ project.owner }}</span>
                            <span
                                class="inline-flex items-center gap-1 rounded px-1.5 py-0.5 dark:bg-[#262626] dark:text-[#A3A3A3]"
                            >
                                <GitBranch class="size-3" />
                                {{ project.default_branch || 'main' }}
                            </span>
                        </div>
                    </div>

                    <!-- Status indicator -->
                    <div
                        class="flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium ring-1 ring-inset bg-emerald-500/10 text-emerald-600 ring-emerald-500/20 dark:bg-[#22C55E]/10 dark:text-[#4ADE80] dark:ring-[#22C55E]/20"
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
                    class="mb-4 flex items-center gap-4 text-xs text-muted-foreground dark:text-[#737373]"
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
                        <Sparkles class="size-3.5 text-primary dark:text-[#F97316]" />
                        <span>AI indexed</span>
                    </div>
                </div>

                <!-- Footer -->
                <div
                    class="flex items-center justify-between border-t pt-4 dark:border-[#262626]"
                >
                    <div
                        class="flex items-center gap-1.5 text-xs text-muted-foreground dark:text-[#525252]"
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
                        class="flex items-center gap-1 text-xs font-medium text-primary opacity-0 transition-opacity duration-200 group-hover:opacity-100 dark:text-[#F97316]"
                    >
                        View Details
                        <ChevronRight class="size-3.5" />
                    </div>
                </div>
            </div>
        </div>
    </Link>
</template>
