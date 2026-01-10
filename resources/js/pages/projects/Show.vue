<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import { type BreadcrumbItem, type DirectorySummary, type ExtensionStats, type Project } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import {
    AlertCircle,
    ArrowLeft,
    Bot,
    Calendar,
    CheckCircle2,
    ChevronDown,
    ChevronRight,
    Code,
    ExternalLink,
    FileCode,
    Folder,
    FolderOpen,
    GitBranch,
    GitCommit,
    Layers,
    MessageSquare,
    RefreshCw,
    Sparkles,
    Trash2,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';

interface DirectoryWithDepth extends DirectorySummary {
    depth?: number;
}

interface Props {
    project: Project & {
        owner: string;
        repo_name: string;
        github_url: string;
    };
    directories: DirectoryWithDepth[];
    topLevelDirectories: DirectorySummary[];
    extensionStats: ExtensionStats[];
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: dashboard().url },
    { title: props.project.repo_name, href: '#' },
];

const isDeleting = ref(false);
const isRescanning = ref(false);
const showFullTree = ref(false);
const expandedDirs = ref<Set<string>>(new Set());

// Format file size
const formatSize = (bytes: number) => {
    if (!bytes || bytes === 0) return '0 B';
    const k = 1024;
    const sizes = ['B', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
};

// Format number with commas
const formatNumber = (num: number) => {
    return num?.toLocaleString() ?? '0';
};

// Format date
const formatDate = (date: string | null) => {
    if (!date) return 'Never';
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
};

// Shorten commit SHA
const shortSha = computed(() => {
    return props.project.last_commit_sha?.substring(0, 7) ?? 'N/A';
});

// Get stack badges
const stackBadges = computed(() => {
    const badges: { label: string; type: string }[] = [];
    const stack = props.project.stack_info;

    if (!stack) return badges;

    if (stack.framework) {
        badges.push({ label: stack.framework, type: 'framework' });
        if (stack.framework_version) {
            badges[badges.length - 1].label += ` ${stack.framework_version}`;
        }
    }
    stack.frontend?.forEach(f => badges.push({ label: f, type: 'frontend' }));
    stack.css?.forEach(c => badges.push({ label: c, type: 'css' }));
    stack.build_tools?.forEach(b => badges.push({ label: b, type: 'build' }));
    stack.testing?.forEach(t => badges.push({ label: t, type: 'testing' }));
    stack.features?.forEach(f => badges.push({ label: f, type: 'feature' }));

    return badges;
});

// Badge color based on type
const getBadgeClass = (type: string) => {
    const classes: Record<string, string> = {
        framework: 'bg-red-500/10 text-red-600 dark:text-red-400',
        frontend: 'bg-blue-500/10 text-blue-600 dark:text-blue-400',
        css: 'bg-cyan-500/10 text-cyan-600 dark:text-cyan-400',
        build: 'bg-amber-500/10 text-amber-600 dark:text-amber-400',
        testing: 'bg-green-500/10 text-green-600 dark:text-green-400',
        feature: 'bg-violet-500/10 text-violet-600 dark:text-violet-400',
    };
    return classes[type] || 'bg-muted';
};

// Build hierarchical tree from flat directory list
const visibleDirectories = computed(() => {
    if (!showFullTree.value) return [];

    return props.directories.filter(dir => {
        if (dir.depth === 0) return true;

        // Check if parent is expanded
        const parts = dir.directory.split('/');
        parts.pop();
        const parentPath = parts.join('/');

        return expandedDirs.value.has(parentPath);
    });
});

const hasChildren = (directory: string) => {
    return props.directories.some(d =>
        d.directory.startsWith(directory + '/') &&
        d.directory.split('/').length === directory.split('/').length + 1
    );
};

const toggleDir = (directory: string) => {
    if (expandedDirs.value.has(directory)) {
        expandedDirs.value.delete(directory);
    } else {
        expandedDirs.value.add(directory);
    }
};

const getDirName = (directory: string) => {
    const parts = directory.split('/');
    return parts[parts.length - 1] || directory;
};

function handleDelete() {
    if (!confirm('Are you sure you want to delete this project? This action cannot be undone.')) {
        return;
    }

    isDeleting.value = true;
    router.delete(`/projects/${props.project.id}`, {
        onFinish: () => {
            isDeleting.value = false;
        },
    });
}

function handleRescan() {
    isRescanning.value = true;
    router.post(`/projects/${props.project.id}/retry-scan`, {}, {
        onFinish: () => {
            isRescanning.value = false;
        },
    });
}
</script>

<template>
    <Head :title="project.repo_name" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-6 md:p-8 lg:p-10">
            <!-- Header -->
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div class="flex items-start gap-4">
                    <Link
                        :href="dashboard().url"
                        class="mt-1 flex items-center text-muted-foreground hover:text-foreground"
                    >
                        <ArrowLeft class="size-5" />
                    </Link>
                    <div>
                        <div class="flex items-center gap-3">
                            <h1 class="text-2xl font-bold">{{ project.repo_name }}</h1>
                            <Badge
                                v-if="project.status === 'ready'"
                                class="bg-emerald-500/10 text-emerald-600 dark:text-emerald-400"
                            >
                                <CheckCircle2 class="mr-1 size-3" />
                                Ready
                            </Badge>
                            <Badge
                                v-else-if="project.status === 'failed'"
                                variant="destructive"
                            >
                                <AlertCircle class="mr-1 size-3" />
                                Failed
                            </Badge>
                        </div>
                        <p class="mt-1 text-muted-foreground">{{ project.owner }}/{{ project.repo_name }}</p>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <!-- ASK AI BUTTON - Prominent -->
                    <Link :href="`/projects/${project.id}/ask`">
                        <Button
                            size="lg"
                            class="bg-gradient-to-r from-violet-600 to-indigo-600 text-white hover:from-violet-700 hover:to-indigo-700"
                        >
                            <Sparkles class="mr-2 size-4" />
                            Ask AI
                        </Button>
                    </Link>

                    <a
                        :href="project.github_url"
                        target="_blank"
                        rel="noopener noreferrer"
                    >
                        <Button variant="outline" size="sm">
                            <ExternalLink class="mr-1 size-3.5" />
                            GitHub
                        </Button>
                    </a>
                    <Button
                        variant="outline"
                        size="sm"
                        :disabled="isRescanning"
                        @click="handleRescan"
                    >
                        <RefreshCw class="mr-1 size-3.5" :class="{ 'animate-spin': isRescanning }" />
                        Rescan
                    </Button>
                    <Button
                        variant="outline"
                        size="sm"
                        class="text-destructive hover:bg-destructive/10"
                        :disabled="isDeleting"
                        @click="handleDelete"
                    >
                        <Trash2 class="mr-1 size-3.5" />
                        Delete
                    </Button>
                </div>
            </div>

            <!-- Ask AI Feature Card -->
            <div class="rounded-xl border-2 border-violet-500/20 bg-gradient-to-r from-violet-500/5 to-indigo-500/5 p-6">
                <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                    <div class="flex items-start gap-4">
                        <div class="flex size-12 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-violet-500 to-indigo-500">
                            <Bot class="size-6 text-white" />
                        </div>
                        <div>
                            <h2 class="text-lg font-semibold">Ask AI about this codebase</h2>
                            <p class="text-sm text-muted-foreground">
                                Get instant answers about code structure, patterns, routes, and more.
                                All responses are grounded in your actual code with full citations.
                            </p>
                        </div>
                    </div>
                    <Link :href="`/projects/${project.id}/ask`">
                        <Button
                            size="lg"
                            class="whitespace-nowrap bg-gradient-to-r from-violet-600 to-indigo-600 text-white hover:from-violet-700 hover:to-indigo-700"
                        >
                            <MessageSquare class="mr-2 size-4" />
                            Start Conversation
                        </Button>
                    </Link>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="grid gap-4 md:grid-cols-4">
                <div class="rounded-xl border bg-card p-4">
                    <div class="flex items-center gap-3">
                        <div class="flex size-10 items-center justify-center rounded-lg bg-blue-500/10">
                            <FileCode class="size-5 text-blue-500" />
                        </div>
                        <div>
                            <p class="text-sm text-muted-foreground">Total Files</p>
                            <p class="text-xl font-semibold">{{ formatNumber(project.total_files || 0) }}</p>
                        </div>
                    </div>
                </div>

                <div class="rounded-xl border bg-card p-4">
                    <div class="flex items-center gap-3">
                        <div class="flex size-10 items-center justify-center rounded-lg bg-emerald-500/10">
                            <Code class="size-5 text-emerald-500" />
                        </div>
                        <div>
                            <p class="text-sm text-muted-foreground">Lines of Code</p>
                            <p class="text-xl font-semibold">{{ formatNumber(project.total_lines || 0) }}</p>
                        </div>
                    </div>
                </div>

                <div class="rounded-xl border bg-card p-4">
                    <div class="flex items-center gap-3">
                        <div class="flex size-10 items-center justify-center rounded-lg bg-amber-500/10">
                            <FolderOpen class="size-5 text-amber-500" />
                        </div>
                        <div>
                            <p class="text-sm text-muted-foreground">Total Size</p>
                            <p class="text-xl font-semibold">{{ formatSize(project.total_size_bytes || 0) }}</p>
                        </div>
                    </div>
                </div>

                <div class="rounded-xl border bg-card p-4">
                    <div class="flex items-center gap-3">
                        <div class="flex size-10 items-center justify-center rounded-lg bg-violet-500/10">
                            <GitCommit class="size-5 text-violet-500" />
                        </div>
                        <div>
                            <p class="text-sm text-muted-foreground">Last Commit</p>
                            <p class="text-xl font-semibold font-mono">{{ shortSha }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Details Grid -->
            <div class="grid gap-6 lg:grid-cols-3">
                <!-- Project Info -->
                <div class="rounded-xl border bg-card p-5">
                    <h2 class="mb-4 flex items-center gap-2 font-semibold">
                        <GitBranch class="size-4" />
                        Project Info
                    </h2>
                    <dl class="space-y-3 text-sm">
                        <div class="flex justify-between">
                            <dt class="text-muted-foreground">Branch</dt>
                            <dd class="font-mono">{{ project.default_branch }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-muted-foreground">Provider</dt>
                            <dd>GitHub</dd>
                        </div>
                        <div class="flex items-center justify-between">
                            <dt class="text-muted-foreground">Last Scan</dt>
                            <dd class="flex items-center gap-1.5 text-xs">
                                <Calendar class="size-3.5" />
                                {{ formatDate(project.scanned_at) }}
                            </dd>
                        </div>
                    </dl>
                </div>

                <!-- Detected Stack -->
                <div class="rounded-xl border bg-card p-5 lg:col-span-2">
                    <h2 class="mb-4 flex items-center gap-2 font-semibold">
                        <Layers class="size-4" />
                        Detected Stack
                    </h2>
                    <div v-if="stackBadges.length > 0" class="flex flex-wrap gap-2">
                        <Badge
                            v-for="badge in stackBadges"
                            :key="badge.label"
                            :class="getBadgeClass(badge.type)"
                            class="capitalize"
                        >
                            {{ badge.label }}
                        </Badge>
                    </div>
                    <p v-else class="text-sm text-muted-foreground">No stack information detected.</p>
                </div>
            </div>

            <!-- Directory Tree & Extensions -->
            <div class="grid gap-6 lg:grid-cols-2">
                <!-- Directory Tree -->
                <div class="rounded-xl border bg-card p-5">
                    <div class="mb-4 flex items-center justify-between">
                        <h2 class="flex items-center gap-2 font-semibold">
                            <FolderOpen class="size-4" />
                            Directory Structure
                        </h2>
                        <Button
                            variant="ghost"
                            size="sm"
                            @click="showFullTree = !showFullTree"
                        >
                            {{ showFullTree ? 'Show Summary' : 'Show Full Tree' }}
                        </Button>
                    </div>

                    <!-- Summary View (Top-level directories) -->
                    <div v-if="!showFullTree && topLevelDirectories.length > 0" class="space-y-1.5">
                        <div
                            v-for="dir in topLevelDirectories"
                            :key="dir.directory"
                            class="flex items-center justify-between rounded-lg bg-muted/30 px-3 py-2 text-sm"
                        >
                            <div class="flex items-center gap-2">
                                <Folder class="size-4 text-amber-500" />
                                <span class="font-mono text-xs">
                                    {{ dir.directory === '(root)' ? '(root files)' : dir.directory + '/' }}
                                </span>
                            </div>
                            <div class="flex items-center gap-3 text-xs text-muted-foreground">
                                <span>{{ formatNumber(dir.file_count) }} files</span>
                                <span>{{ formatSize(dir.total_size) }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Full Tree View -->
                    <div v-else-if="showFullTree && directories.length > 0" class="max-h-96 space-y-0.5 overflow-y-auto">
                        <div
                            v-for="dir in visibleDirectories"
                            :key="dir.directory"
                            class="flex items-center justify-between rounded px-2 py-1.5 text-sm hover:bg-muted/30"
                            :style="{ paddingLeft: `${(dir.depth || 0) * 16 + 8}px` }"
                        >
                            <div class="flex items-center gap-1.5">
                                <button
                                    v-if="hasChildren(dir.directory)"
                                    class="flex size-5 items-center justify-center rounded hover:bg-muted"
                                    @click="toggleDir(dir.directory)"
                                >
                                    <ChevronRight
                                        v-if="!expandedDirs.has(dir.directory)"
                                        class="size-3.5 text-muted-foreground"
                                    />
                                    <ChevronDown v-else class="size-3.5 text-muted-foreground" />
                                </button>
                                <span v-else class="size-5" />

                                <Folder class="size-4 text-amber-500" />
                                <span class="font-mono text-xs">{{ getDirName(dir.directory) }}</span>
                            </div>
                            <div class="flex items-center gap-2 text-xs text-muted-foreground">
                                <span>{{ dir.file_count }}</span>
                            </div>
                        </div>
                    </div>

                    <p v-else class="text-sm text-muted-foreground">No directory information available.</p>
                </div>

                <!-- File Extensions -->
                <div class="rounded-xl border bg-card p-5">
                    <h2 class="mb-4 flex items-center gap-2 font-semibold">
                        <FileCode class="size-4" />
                        File Types
                    </h2>
                    <div v-if="extensionStats.length > 0" class="max-h-96 space-y-1.5 overflow-y-auto">
                        <div
                            v-for="ext in extensionStats"
                            :key="ext.extension"
                            class="flex items-center justify-between rounded-lg bg-muted/30 px-3 py-2 text-sm"
                        >
                            <span class="font-mono text-xs">.{{ ext.extension }}</span>
                            <div class="flex items-center gap-4 text-xs text-muted-foreground">
                                <span>{{ formatNumber(ext.count) }} files</span>
                                <span>{{ formatNumber(ext.total_lines || 0) }} lines</span>
                            </div>
                        </div>
                    </div>
                    <p v-else class="text-sm text-muted-foreground">No file type information available.</p>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
