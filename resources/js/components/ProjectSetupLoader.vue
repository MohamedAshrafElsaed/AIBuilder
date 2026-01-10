<script setup lang="ts">
import SetupTimeline from '@/components/dashboard/SetupTimeline.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { type Project, type ScanStatus } from '@/types';
import { router } from '@inertiajs/vue3';
import {
    AlertCircle,
    Bot,
    FileSearch,
    Files,
    FolderSync,
    GitBranch,
    Layers,
    Loader2,
    RefreshCw,
} from 'lucide-vue-next';
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';

interface Props {
    project: Project;
    isNew?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    isNew: false,
});

// Setup steps configuration with icons
const setupStepsConfig = [
    { id: 'workspace', label: 'Preparing workspace', icon: GitBranch },
    { id: 'clone', label: 'Cloning repository', icon: FolderSync },
    { id: 'manifest', label: 'Building file manifest', icon: FileSearch },
    { id: 'stack', label: 'Detecting stack', icon: Layers },
    { id: 'chunks', label: 'Building knowledge chunks', icon: Files },
    { id: 'finalize', label: 'Preparing AI agents', icon: Bot },
];

// Reactive status state
const scanStatus = ref<ScanStatus | null>(null);
const isLoading = ref(true);
const error = ref<string | null>(null);
const pollInterval = ref<ReturnType<typeof setInterval> | null>(null);

// Computed values based on scan status
const currentStepIndex = computed(() => {
    if (!scanStatus.value?.current_stage) return 0;
    const index = setupStepsConfig.findIndex(
        (s) => s.id === scanStatus.value?.current_stage,
    );
    return index >= 0 ? index : 0;
});

const completedSteps = computed(() => {
    const completed = new Set<string>();
    if (!scanStatus.value) return completed;

    for (const step of scanStatus.value.steps) {
        if (step.completed) {
            completed.add(step.id);
        }
    }
    return completed;
});

const progressPercent = computed(() => {
    return scanStatus.value?.percent ?? props.project.stage_percent ?? 0;
});

const isFailed = computed(() => {
    return (
        props.project.status === 'failed' ||
        scanStatus.value?.status === 'failed'
    );
});

const errorMessage = computed(() => {
    return scanStatus.value?.error || props.project.last_error;
});

// Generate avatar initials from repo name
const avatarInitials = computed(() => {
    const name = props.project.name || props.project.repo_full_name;
    const parts = name.split('/');
    const repoName = parts[parts.length - 1] || '?';
    return repoName.substring(0, 2).toUpperCase();
});

// Fetch scan status from API
async function fetchStatus() {
    try {
        const response = await fetch(
            `/projects/${props.project.id}/scan-status`,
            {
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
            },
        );

        if (response.ok) {
            scanStatus.value = await response.json();
            error.value = null;

            // Stop polling if scan is complete or failed
            if (
                scanStatus.value?.status === 'ready' ||
                scanStatus.value?.status === 'failed'
            ) {
                stopPolling();
                // Refresh the page to show updated state
                if (scanStatus.value.status === 'ready') {
                    setTimeout(() => {
                        router.reload({ only: ['projects'] });
                    }, 1000);
                }
            }
        }
    } catch (e) {
        console.error('Failed to fetch scan status:', e);
        error.value = 'Failed to fetch status';
    } finally {
        isLoading.value = false;
    }
}

function startPolling() {
    // Initial fetch
    fetchStatus();

    // Poll every 2 seconds
    pollInterval.value = setInterval(fetchStatus, 2000);
}

function stopPolling() {
    if (pollInterval.value) {
        clearInterval(pollInterval.value);
        pollInterval.value = null;
    }
}

function retryScan() {
    router.post(
        `/projects/${props.project.id}/retry-scan`,
        {},
        {
            preserveScroll: true,
            onSuccess: () => {
                isLoading.value = true;
                startPolling();
            },
        },
    );
}

// Lifecycle
onMounted(() => {
    if (
        props.project.status === 'scanning' ||
        props.project.status === 'pending'
    ) {
        startPolling();
    } else {
        isLoading.value = false;
    }
});

onUnmounted(() => {
    stopPolling();
});

// Watch for project status changes
watch(
    () => props.project.status,
    (newStatus) => {
        if (newStatus === 'scanning' && !pollInterval.value) {
            startPolling();
        }
    },
);
</script>

<template>
    <div
        class="group relative overflow-hidden rounded-xl border bg-card shadow-lg transition-all duration-300"
        :class="[
            isFailed
                ? 'border-red-500/30 shadow-red-500/5'
                : 'border-violet-500/30 shadow-violet-500/5',
        ]"
    >
        <!-- Animated gradient border effect -->
        <div
            v-if="!isFailed"
            class="absolute inset-0 rounded-xl bg-gradient-to-r from-violet-500/20 via-indigo-500/20 to-violet-500/20 opacity-0 transition-opacity duration-300 group-hover:opacity-100"
        ></div>
        <div class="absolute inset-px rounded-[11px] bg-card"></div>

        <!-- Shimmer border animation (only when scanning) -->
        <div
            v-if="!isFailed"
            class="pointer-events-none absolute inset-0 rounded-xl"
        >
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
                        class="flex size-11 items-center justify-center rounded-lg font-semibold ring-1"
                        :class="[
                            isFailed
                                ? 'bg-gradient-to-br from-red-500/20 to-orange-500/20 text-red-600 ring-red-500/30 dark:text-red-400'
                                : 'bg-gradient-to-br from-violet-500/20 to-indigo-500/20 text-violet-600 ring-violet-500/30 dark:text-violet-400',
                        ]"
                    >
                        {{ avatarInitials }}
                    </div>
                    <!-- Pulse indicator (only when scanning) -->
                    <span
                        v-if="!isFailed"
                        class="absolute -right-0.5 -bottom-0.5 flex size-3"
                    >
                        <span
                            class="absolute inline-flex h-full w-full animate-ping rounded-full bg-violet-400 opacity-75"
                        ></span>
                        <span
                            class="relative inline-flex size-3 rounded-full bg-violet-500"
                        ></span>
                    </span>
                    <!-- Error indicator -->
                    <span
                        v-else
                        class="absolute -right-0.5 -bottom-0.5 flex size-3"
                    >
                        <span
                            class="relative inline-flex size-3 rounded-full bg-red-500"
                        ></span>
                    </span>
                </div>

                <div class="min-w-0 flex-1">
                    <h3 class="truncate font-semibold text-foreground">
                        {{ project.name || project.repo_full_name }}
                    </h3>
                    <p class="text-sm text-muted-foreground">
                        {{ isFailed ? 'Scan failed' : 'Setting up project...' }}
                    </p>
                </div>

                <!-- Status Badge -->
                <Badge
                    v-if="!isFailed"
                    class="shrink-0 gap-1.5 bg-gradient-to-r from-violet-500/10 to-indigo-500/10 text-violet-600 ring-1 ring-violet-500/20 ring-inset dark:text-violet-400"
                    variant="secondary"
                >
                    <Loader2 class="size-3 animate-spin" />
                    AI Indexing
                </Badge>
                <Badge
                    v-else
                    class="shrink-0 gap-1.5 bg-red-500/10 text-red-600 ring-1 ring-red-500/20 ring-inset dark:text-red-400"
                    variant="secondary"
                >
                    <AlertCircle class="size-3" />
                    Failed
                </Badge>
            </div>

            <!-- Error Message -->
            <div
                v-if="isFailed && errorMessage"
                class="mb-4 rounded-lg bg-red-500/10 p-3 text-sm text-red-600 dark:text-red-400"
            >
                <p class="line-clamp-2">{{ errorMessage }}</p>
            </div>

            <!-- Progress Bar -->
            <div v-if="!isFailed" class="mb-5">
                <div class="mb-2 flex items-center justify-between">
                    <span class="text-xs font-medium text-muted-foreground"
                        >Progress</span
                    >
                    <span
                        class="text-xs font-semibold text-violet-600 dark:text-violet-400"
                    >
                        {{ progressPercent }}%
                    </span>
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
                v-if="!isFailed"
                :steps="setupStepsConfig"
                :current-step-index="currentStepIndex"
                :completed-steps="completedSteps"
            />

            <!-- Retry Button -->
            <div v-if="isFailed" class="mt-4">
                <Button
                    variant="outline"
                    size="sm"
                    class="w-full gap-2"
                    @click="retryScan"
                >
                    <RefreshCw class="size-4" />
                    Retry Scan
                </Button>
            </div>
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
