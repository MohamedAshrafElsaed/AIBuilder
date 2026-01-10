<script lang="ts" setup>
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import { create, store } from '@/routes/projects';
import { type BreadcrumbItem, type Repository } from '@/types';
import { Head, Link, useForm } from '@inertiajs/vue3';
import {
    ArrowLeft,
    Bot,
    Check,
    ExternalLink,
    FileCode,
    GitBranch,
    Layers,
    Lock,
    Sparkles,
} from 'lucide-vue-next';
import { computed } from 'vue';

interface Props {
    repository: Repository;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: dashboard().url },
    { title: 'Select Repository', href: create().url },
    { title: 'Confirm', href: '#' },
];

const form = useForm({
    repo_full_name: props.repository.full_name,
    repo_id: String(props.repository.id),
    default_branch: props.repository.default_branch,
});

function submit() {
    form.post(store().url);
}

// Extract owner and repo name
const owner = computed(() => props.repository.full_name.split('/')[0] || '');
const repoName = computed(
    () =>
        props.repository.full_name.split('/')[1] || props.repository.full_name,
);
const initials = computed(() => owner.value.substring(0, 2).toUpperCase());

// AI features that will be enabled
const aiFeatures = [
    {
        icon: Layers,
        label: 'Stack Detection',
        desc: 'Auto-detect Vue, React, Laravel, etc.',
    },
    {
        icon: FileCode,
        label: 'Code Indexing',
        desc: 'AI-powered file analysis',
    },
    { icon: Bot, label: 'Agent Tasks', desc: 'Automated code insights' },
];
</script>

<template>
    <Head title="Confirm Repository" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto w-full max-w-2xl px-4 py-6 md:px-6 md:py-8">
            <!-- Header -->
            <div class="mb-8">
                <Link
                    :href="create()"
                    class="mb-4 inline-flex items-center gap-1.5 text-sm text-muted-foreground transition-colors hover:text-foreground"
                >
                    <ArrowLeft class="size-4" />
                    Back to repository selection
                </Link>

                <h1
                    class="text-2xl font-semibold tracking-tight text-foreground md:text-3xl"
                >
                    Confirm repository
                </h1>
                <p class="mt-1 text-muted-foreground">
                    Review the details before adding to your projects
                </p>
            </div>

            <!-- Repository Card -->
            <div
                class="overflow-hidden rounded-2xl border border-border/50 bg-card shadow-sm"
            >
                <!-- Header Section -->
                <div class="border-b border-border/50 p-6">
                    <div class="flex items-start gap-4">
                        <!-- Avatar -->
                        <div
                            class="flex size-14 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-violet-500 to-indigo-500 text-lg font-semibold text-white shadow-lg shadow-violet-500/20"
                        >
                            {{ initials }}
                        </div>

                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2">
                                <span class="text-sm text-muted-foreground">{{
                                    owner
                                }}</span>
                                <span class="text-muted-foreground/40">/</span>
                                <span
                                    class="truncate text-xl font-semibold text-foreground"
                                >
                                    {{ repoName }}
                                </span>
                            </div>

                            <p
                                v-if="repository.description"
                                class="mt-1 line-clamp-2 text-sm text-muted-foreground"
                            >
                                {{ repository.description }}
                            </p>

                            <!-- Badges -->
                            <div class="mt-3 flex items-center gap-2">
                                <Badge
                                    v-if="repository.private"
                                    variant="secondary"
                                    class="gap-1"
                                >
                                    <Lock class="size-3" />
                                    Private
                                </Badge>
                                <a
                                    v-if="repository.html_url"
                                    :href="repository.html_url"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="inline-flex items-center gap-1 text-xs text-muted-foreground transition-colors hover:text-foreground"
                                >
                                    View on GitHub
                                    <ExternalLink class="size-3" />
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Branch Info -->
                <div class="border-b border-border/50 p-6">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div
                                class="flex size-10 items-center justify-center rounded-lg bg-muted"
                            >
                                <GitBranch
                                    class="size-5 text-muted-foreground"
                                />
                            </div>
                            <div>
                                <p class="text-sm font-medium text-foreground">
                                    Default Branch
                                </p>
                                <p class="text-xs text-muted-foreground">
                                    Detected automatically
                                </p>
                            </div>
                        </div>
                        <Badge variant="outline" class="font-mono text-sm">
                            {{ repository.default_branch }}
                        </Badge>
                    </div>
                </div>

                <!-- AI Features Section -->
                <div
                    class="bg-gradient-to-b from-violet-500/5 to-transparent p-6"
                >
                    <div class="mb-4 flex items-center gap-2">
                        <Sparkles class="size-4 text-violet-500" />
                        <span class="text-sm font-medium text-foreground"
                            >AI features will be enabled</span
                        >
                    </div>

                    <div class="space-y-3">
                        <div
                            v-for="feature in aiFeatures"
                            :key="feature.label"
                            class="flex items-start gap-3"
                        >
                            <div
                                class="flex size-6 shrink-0 items-center justify-center rounded-full bg-violet-500/10 text-violet-500"
                            >
                                <Check class="size-3.5" />
                            </div>
                            <div>
                                <p class="text-sm font-medium text-foreground">
                                    {{ feature.label }}
                                </p>
                                <p class="text-xs text-muted-foreground">
                                    {{ feature.desc }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="mt-6 flex items-center justify-between gap-4">
                <Link :href="create()">
                    <Button variant="outline"> Cancel </Button>
                </Link>

                <Button
                    :disabled="form.processing"
                    class="min-w-[140px] bg-gradient-to-r from-violet-600 to-indigo-600 text-white shadow-lg shadow-violet-500/25 transition-all duration-200 hover:shadow-xl hover:shadow-violet-500/30 disabled:opacity-50"
                    @click="submit"
                >
                    <Spinner v-if="form.processing" class="mr-2 size-4" />
                    <Sparkles v-else class="mr-2 size-4" />
                    Add Project
                </Button>
            </div>
        </div>
    </AppLayout>
</template>
