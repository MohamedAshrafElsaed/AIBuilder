<script setup lang="ts">
import SetupTimeline from '@/components/dashboard/SetupTimeline.vue';
import { Badge } from '@/components/ui/badge';
import { type Project } from '@/types';
import {
    Bot,
    FileSearch,
    Files,
    FolderSync,
    GitBranch,
    Layers,
    Loader2,
} from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

interface Props {
    project: Project;
    isNew?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    isNew: false,
});

// Setup steps configuration
const setupSteps = [
    { id: 'connect', label: 'Connecting to GitHub', icon: GitBranch },
    { id: 'clone', label: 'Cloning repository', icon: FolderSync },
    { id: 'manifest', label: 'Building file manifest', icon: FileSearch },
    { id: 'index', label: 'Indexing files', icon: Files },
    { id: 'detect', label: 'Detecting stack', icon: Layers },
    { id: 'agents', label: 'Preparing AI agents', icon: Bot },
];

// Simulated progress (in real app, this comes from backend/websocket)
// This maintains existing logic - just reading from project.status or similar
const currentStepIndex = ref(0);
const completedSteps = ref<Set<string>>(new Set());

// Calculate progress percentage
const progressPercent = computed(() => {
    return Math.round((completedSteps.value.size / setupSteps.length) * 100);
});

// Generate avatar initials from repo name
const avatarInitials = computed(() => {
    const parts = props.project.name?.split('/') || ['?'];
    const repoName = parts[parts.length - 1] || '?';
    return repoName.substring(0, 2).toUpperCase();
});

// Simulate progress for demo (remove in production - use real status)
watch(
    () => props.project,
    () => {
        // In production, derive this from project.setupProgress or similar
    },
    { immediate: true },
);

// For demo: simulate step completion
const simulateProgress = () => {
    const interval = setInterval(() => {
        if (currentStepIndex.value < setupSteps.length) {
            completedSteps.value.add(setupSteps[currentStepIndex.value].id);
            currentStepIndex.value++;
        } else {
            clearInterval(interval);
        }
    }, 2000);
};

// Start simulation on mount (remove in production)
if (props.isNew) {
    simulateProgress();
}
</script>

<template>
    <div
        class="group relative overflow-hidden rounded-xl border border-violet-500/30 bg-card shadow-lg shadow-violet-500/5 transition-all duration-300"
    >
        <!-- Animated gradient border effect -->
        <div
            class="absolute inset-0 rounded-xl bg-gradient-to-r from-violet-500/20 via-indigo-500/20 to-violet-500/20 opacity-0 transition-opacity duration-300 group-hover:opacity-100"
        ></div>
        <div class="absolute inset-px rounded-[11px] bg-card"></div>

        <!-- Shimmer border animation -->
        <div class="pointer-events-none absolute inset-0 rounded-xl">
            <div
                class="absolute inset-[-1px] animate-[shimmer-spin_4s_linear_infinite] rounded-xl bg-[conic-gradient(from_var(--shimmer-angle),transparent_0%,theme(colors.violet.500)_10%,transparent_20%)] opacity-30"
                style="--shimmer-angle: 0deg"
            ></div>
        </div>

        <div class="relative p-5">
            <!-- Header -->
            <div class="mb-5 flex items-start gap-4">
                <!-- Repository Avatar -->
                <div class="relative shrink-0">
                    <div
                        class="flex size-11 items-center justify-center rounded-lg bg-gradient-to-br from-violet-500/20 to-indigo-500/20 font-semibold text-violet-600 ring-1 ring-violet-500/30 dark:text-violet-400"
                    >
                        {{ avatarInitials }}
                    </div>
                    <!-- Pulse indicator -->
                    <span class="absolute -right-0.5 -bottom-0.5 flex size-3">
                        <span
                            class="absolute inline-flex h-full w-full animate-ping rounded-full bg-violet-400 opacity-75"
                        ></span>
                        <span
                            class="relative inline-flex size-3 rounded-full bg-violet-500"
                        ></span>
                    </span>
                </div>

                <div class="min-w-0 flex-1">
                    <h3 class="truncate font-semibold text-foreground">
                        {{ project.name }}
                    </h3>
                    <p class="text-sm text-muted-foreground">
                        Setting up project...
                    </p>
                </div>

                <!-- AI Status Badge -->
                <Badge
                    class="shrink-0 gap-1.5 bg-gradient-to-r from-violet-500/10 to-indigo-500/10 text-violet-600 ring-1 ring-violet-500/20 ring-inset dark:text-violet-400"
                    variant="secondary"
                >
                    <Loader2 class="size-3 animate-spin" />
                    AI Indexing
                </Badge>
            </div>

            <!-- Progress Bar -->
            <div class="mb-5">
                <div class="mb-2 flex items-center justify-between">
                    <span class="text-xs font-medium text-muted-foreground"
                        >Progress</span
                    >
                    <span
                        class="text-xs font-semibold text-violet-600 dark:text-violet-400"
                        >{{ progressPercent }}%</span
                    >
                </div>
                <div
                    class="h-1.5 w-full overflow-hidden rounded-full bg-muted/50"
                >
                    <div
                        class="h-full rounded-full bg-gradient-to-r from-violet-500 to-indigo-500 transition-all duration-500 ease-out"
                        :style="{ width: `${progressPercent}%` }"
                    ></div>
                </div>
            </div>

            <!-- Setup Timeline -->
            <SetupTimeline
                :steps="setupSteps"
                :current-step-index="currentStepIndex"
                :completed-steps="completedSteps"
            />
        </div>
    </div>
</template>

<style scoped>
@keyframes shimmer-spin {
    from {
        --shimmer-angle: 0deg;
    }
    to {
        --shimmer-angle: 360deg;
    }
}

@property --shimmer-angle {
    syntax: '<angle>';
    initial-value: 0deg;
    inherits: false;
}
</style>
