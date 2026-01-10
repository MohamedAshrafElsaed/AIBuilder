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
const directoryTree = computed(() => {
    const tree: Map<string, DirectoryWithDepth & { children: string[], isExpanded: boolean }> = new Map();

    // First pass: create all directory entries
    for (const dir of props.directories) {
        tree.set(dir.directory, {
            ...dir,
            children: [],
            isExpanded: expandedDirs.value.has(dir.directory),
        });
    }

    // Second pass: build parent-child relationships
    for (const dir of props.directories) {
        if (dir.directory === '(root)') continue;

        const parts = dir.directory.split('/');
        if (parts.length > 1) {
            const parentPath = parts.slice(0, -1).join('/');
            const parent = tree.get(parentPath);
            if (parent) {
                parent.children.push(dir.directory);
            }
        }
    }

    return tree;
});

// Get visible directories based on expansion state
const visibleDirectories = computed(() => {
    const visible: DirectoryWithDepth[] = [];
    const processed = new Set<string>();

    // Sort directories by path
    const sortedDirs = [...props.directories].sort((a, b) =>
        a.directory.localeCompare(b.directory)
    );

    for (const dir of sortedDirs) {
        if (processed.has(dir.directory)) continue;

        // Check if all parent directories are expanded
        if (dir.directory !== '(root)') {
            const parts = dir.directory.split('/');
            let shouldShow = true;

            for (let i = 1; i < parts.length; i++) {
                const parentPath = parts.slice(0, i).join('/');
                if (!expandedDirs.value.has(parentPath)) {
                    shouldShow = false;
                    break;
                }
            }

            if (!shouldShow) continue;
        }

        visible.push(dir);
        processed.add(dir.directory);
    }

    return visible;
});

// Check if directory has children
const hasChildren = (dirPath: string): boolean => {
    return props.directories.some(d =>
        d.directory !== dirPath &&
        d.directory.startsWith(dirPath + '/')
    );
};

// Toggle directory expansion
const toggleDir = (dirPath: string) => {
    if (expandedDirs.value.has(dirPath)) {
        expandedDirs.value.delete(dirPath);
    } else {
        expandedDirs.value.add(dirPath);
    }
};

// Get directory name (last part of path)
const getDirName = (path: string): string => {
    if (path === '(root)') return '(root files)';
    const parts = path.split('/');
    return parts[parts.length - 1];
};

function rescan() {
    if (isRescanning.value) return;
    isRescanning.value = true;
    router.post(`/projects/${props.project.id}/retry-scan`, {}, {
        preserveScroll: true,
        onFinish: () => {
            isRescanning.value = false;
        },
    });
}

function deleteProject() {
    if (isDeleting.value) return;
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
</script>

<template>
    <Head :title="project.repo_name" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-6 md:p-8">
            <!-- Back Link -->
            <Link
                :href="dashboard().url"
                class="inline-flex w-fit items-center gap-1.5 text-sm text-muted-foreground transition-colors hover:text-foreground"
            >
                <ArrowLeft class="size-4" />
                Back to Dashboard
            </Link>

            <!-- Header -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div class="space-y-1">
                    <div class="flex items-center gap-3">
                        <h1 class="text-2xl font-semibold tracking-tight text-foreground md:text-3xl">
                            {{ project.repo_name }}
                        </h1>
                        <Badge
                            v-if="project.status === 'ready'"
                            class="gap-1 bg-emerald-500/10 text-emerald-600 dark:text-emerald-400"
                        >
                            <CheckCircle2 class="size-3" />
                            Ready
                        </Badge>
                        <Badge
                            v-else-if="project.status === 'failed'"
                            class="gap-1 bg-red-500/10 text-red-600 dark:text-red-400"
                        >
                            <AlertCircle class="size-3" />
                            Failed
                        </Badge>
                    </div>
                    <p class="text-muted-foreground">{{ project.owner }}/{{ project.repo_name }}</p>
                </div>

                <div class="flex gap-2">
                    <Button variant="outline" size="sm" as-child>
                        <a :href="project.github_url" target="_blank" rel="noopener noreferrer">
                            <ExternalLink class="mr-2 size-4" />
                            View on GitHub
                        </a>
                    </Button>
                    <Button variant="outline" size="sm" :disabled="isRescanning" @click="rescan">
                        <RefreshCw class="mr-2 size-4" :class="{ 'animate-spin': isRescanning }" />
                        Rescan
                    </Button>
                    <Button variant="destructive" size="sm" :disabled="isDeleting" @click="deleteProject">
                        <Trash2 class="mr-2 size-4" />
                        Delete
                    </Button>
                </div>
            </div>

            <!-- Error Message -->
            <div
                v-if="project.status === 'failed' && project.last_error"
                class="rounded-lg border border-red-500/30 bg-red-500/10 p-4"
            >
                <div class="flex items-start gap-3">
                    <AlertCircle class="mt-0.5 size-5 text-red-500" />
                    <div>
                        <p class="font-medium text-red-600 dark:text-red-400">Scan Failed</p>
                        <p class="mt-1 text-sm text-red-600/80 dark:text-red-400/80">
                            {{ project.last_error }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Stats Grid -->
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
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
                            <Sparkles class="size-5 text-violet-500" />
                        </div>
                        <div>
                            <p class="text-sm text-muted-foreground">Directories</p>
                            <p class="text-xl font-semibold">{{ formatNumber(directories.length) }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Info Grid -->
            <div class="grid gap-6 lg:grid-cols-3">
                <!-- Project Details -->
                <div class="rounded-xl border bg-card p-5 lg:col-span-1">
                    <h2 class="mb-4 flex items-center gap-2 font-semibold">
                        <GitBranch class="size-4" />
                        Project Details
                    </h2>
                    <dl class="space-y-3 text-sm">
                        <div class="flex justify-between">
                            <dt class="text-muted-foreground">Default Branch</dt>
                            <dd class="font-medium">{{ project.default_branch }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-muted-foreground">Last Commit</dt>
                            <dd class="flex items-center gap-1.5 font-mono text-xs">
                                <GitCommit class="size-3.5" />
                                {{ shortSha }}
                            </dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-muted-foreground">Last Scanned</dt>
                            <dd class="flex items-center gap-1.5">
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
                                <!-- Expand/Collapse toggle -->
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
