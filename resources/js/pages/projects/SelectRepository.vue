<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Spinner } from '@/components/ui/spinner';
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import { confirm } from '@/routes/projects';
import { type BreadcrumbItem, type Repository } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import {
    ArrowLeft,
    Check,
    FolderGit2,
    GitBranch,
    Lock,
    Search,
    Sparkles,
} from 'lucide-vue-next';
import { computed, onMounted, ref } from 'vue';

interface Props {
    repositories: Repository[];
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: dashboard().url },
    { title: 'Select Repository', href: '#' },
];

const searchQuery = ref('');
const selectedRepo = ref<string | null>(null);
const isSubmitting = ref(false);
const isLoaded = ref(false);
const isLoading = ref(false); // Set true while fetching repos

onMounted(() => {
    requestAnimationFrame(() => {
        isLoaded.value = true;
    });
});

const filteredRepositories = computed(() => {
    if (!searchQuery.value) {
        return props.repositories;
    }
    const query = searchQuery.value.toLowerCase();
    return props.repositories.filter(
        (repo) =>
            repo.full_name.toLowerCase().includes(query) ||
            repo.description?.toLowerCase().includes(query),
    );
});

const resultCount = computed(() => filteredRepositories.value.length);
const totalCount = computed(() => props.repositories.length);

function selectRepository(fullName: string) {
    selectedRepo.value = fullName;
}

function continueToConfirm() {
    if (!selectedRepo.value) return;
    isSubmitting.value = true;
    router.get(
        confirm().url,
        { repo_full_name: selectedRepo.value },
        {
            onFinish: () => {
                isSubmitting.value = false;
            },
        },
    );
}

// Extract owner from full_name
function getOwner(fullName: string) {
    return fullName.split('/')[0] || '';
}

function getRepoName(fullName: string) {
    return fullName.split('/')[1] || fullName;
}

// Generate avatar initials
function getInitials(fullName: string) {
    const owner = getOwner(fullName);
    return owner.substring(0, 2).toUpperCase();
}
</script>

<template>
    <Head title="Select Repository" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex min-h-full flex-col">
            <!-- Main Content -->
            <div
                class="mx-auto w-full max-w-3xl flex-1 px-4 py-6 md:px-6 md:py-8"
            >
                <!-- Header -->
                <div class="mb-8">
                    <Link
                        :href="dashboard()"
                        class="mb-4 inline-flex items-center gap-1.5 text-sm text-muted-foreground transition-colors hover:text-foreground"
                    >
                        <ArrowLeft class="size-4" />
                        Back to Dashboard
                    </Link>

                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <h1
                                class="text-2xl font-semibold tracking-tight text-foreground md:text-3xl"
                            >
                                Select a repository
                            </h1>
                            <p class="mt-1 text-muted-foreground">
                                Choose a GitHub repository to add to your
                                projects
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Search Section -->
                <div class="mb-6 space-y-3">
                    <div class="relative">
                        <Search
                            class="pointer-events-none absolute top-1/2 left-3.5 size-4 -translate-y-1/2 text-muted-foreground"
                        />
                        <Input
                            v-model="searchQuery"
                            type="search"
                            placeholder="Search repositories..."
                            class="h-11 pr-4 pl-10 transition-shadow duration-200 focus:border-violet-500/50 focus:ring-2 focus:ring-violet-500/20"
                        />
                    </div>

                    <!-- Search meta row -->
                    <div class="flex items-center justify-between text-sm">
                        <p class="text-muted-foreground">
                            <span v-if="searchQuery">
                                {{ resultCount }} of
                                {{ totalCount }} repositories
                            </span>
                            <span v-else>
                                {{ totalCount }} repositories available
                            </span>
                        </p>

                        <!-- AI Hint -->
                        <div
                            class="hidden items-center gap-1.5 text-xs text-muted-foreground sm:flex"
                        >
                            <Sparkles class="size-3.5 text-violet-500" />
                            <span>AI auto-detects your stack after adding</span>
                        </div>
                    </div>
                </div>

                <!-- Loading Skeletons -->
                <div v-if="isLoading" class="space-y-3">
                    <div
                        v-for="i in 6"
                        :key="i"
                        class="relative overflow-hidden rounded-xl border border-border/50 bg-card p-4"
                    >
                        <div
                            class="absolute inset-0 -translate-x-full animate-[shimmer_2s_infinite] bg-gradient-to-r from-transparent via-white/5 to-transparent"
                        ></div>
                        <div class="flex items-start gap-4">
                            <div
                                class="size-10 shrink-0 animate-pulse rounded-lg bg-muted"
                            ></div>
                            <div class="flex-1 space-y-2">
                                <div
                                    class="h-5 w-2/3 animate-pulse rounded bg-muted"
                                ></div>
                                <div
                                    class="h-4 w-1/2 animate-pulse rounded bg-muted/70"
                                ></div>
                            </div>
                            <div
                                class="h-6 w-16 animate-pulse rounded-full bg-muted/50"
                            ></div>
                        </div>
                    </div>
                </div>

                <!-- Empty State: No Repos -->
                <div
                    v-else-if="repositories.length === 0"
                    class="flex flex-col items-center justify-center rounded-2xl border-2 border-dashed border-border/60 bg-muted/20 px-6 py-16 text-center"
                >
                    <div
                        class="mb-4 flex size-16 items-center justify-center rounded-2xl bg-muted"
                    >
                        <FolderGit2 class="size-8 text-muted-foreground" />
                    </div>
                    <h3 class="mb-2 text-lg font-medium text-foreground">
                        No repositories found
                    </h3>
                    <p class="max-w-sm text-sm text-muted-foreground">
                        Make sure your GitHub account has access to
                        repositories, or try reconnecting your account.
                    </p>
                </div>

                <!-- Empty State: No Search Results -->
                <div
                    v-else-if="filteredRepositories.length === 0"
                    class="flex flex-col items-center justify-center rounded-2xl border-2 border-dashed border-border/60 bg-muted/20 px-6 py-12 text-center"
                >
                    <Search class="mb-3 size-10 text-muted-foreground/50" />
                    <h3 class="mb-1 font-medium text-foreground">
                        No matches found
                    </h3>
                    <p class="text-sm text-muted-foreground">
                        Try a different search term
                    </p>
                </div>

                <!-- Repository List -->
                <div v-else class="space-y-2">
                    <button
                        v-for="(repo, index) in filteredRepositories"
                        :key="repo.id"
                        type="button"
                        class="group relative w-full rounded-xl border bg-card p-4 text-left transition-all duration-150 focus:outline-none focus-visible:ring-2 focus-visible:ring-violet-500 focus-visible:ring-offset-2"
                        :class="[
                            selectedRepo === repo.full_name
                                ? 'border-violet-500 bg-violet-500/5 ring-1 ring-violet-500/30'
                                : 'border-border/50 hover:-translate-y-px hover:border-border hover:bg-muted/30 hover:shadow-sm',
                            isLoaded
                                ? 'translate-y-0 opacity-100'
                                : 'translate-y-2 opacity-0',
                        ]"
                        :style="{ transitionDelay: `${index * 30}ms` }"
                        @click="selectRepository(repo.full_name)"
                    >
                        <div class="flex items-start gap-4">
                            <!-- Owner Avatar -->
                            <div
                                class="flex size-10 shrink-0 items-center justify-center rounded-lg text-sm font-medium transition-colors duration-150"
                                :class="
                                    selectedRepo === repo.full_name
                                        ? 'bg-violet-500 text-white'
                                        : 'bg-muted text-muted-foreground group-hover:bg-muted/80'
                                "
                            >
                                {{ getInitials(repo.full_name) }}
                            </div>

                            <!-- Content -->
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-2">
                                    <span class="text-sm text-muted-foreground">
                                        {{ getOwner(repo.full_name) }}
                                    </span>
                                    <span class="text-muted-foreground/40"
                                        >/</span
                                    >
                                    <span
                                        class="truncate font-medium text-foreground"
                                    >
                                        {{ getRepoName(repo.full_name) }}
                                    </span>
                                </div>

                                <p
                                    v-if="repo.description"
                                    class="mt-1 line-clamp-1 text-sm text-muted-foreground"
                                >
                                    {{ repo.description }}
                                </p>

                                <!-- Meta badges -->
                                <div class="mt-2 flex items-center gap-2">
                                    <Badge
                                        v-if="repo.private"
                                        variant="secondary"
                                        class="gap-1 text-xs font-normal"
                                    >
                                        <Lock class="size-3" />
                                        Private
                                    </Badge>
                                    <Badge
                                        variant="outline"
                                        class="gap-1 text-xs font-normal"
                                    >
                                        <GitBranch class="size-3" />
                                        {{ repo.default_branch }}
                                    </Badge>
                                </div>
                            </div>

                            <!-- Selection indicator -->
                            <div
                                class="flex size-6 shrink-0 items-center justify-center rounded-full border-2 transition-all duration-200"
                                :class="
                                    selectedRepo === repo.full_name
                                        ? 'scale-100 border-violet-500 bg-violet-500'
                                        : 'scale-90 border-border bg-transparent group-hover:scale-100 group-hover:border-muted-foreground/50'
                                "
                            >
                                <Check
                                    v-if="selectedRepo === repo.full_name"
                                    class="size-3.5 animate-[check-pop_0.2s_ease-out] text-white"
                                />
                            </div>
                        </div>
                    </button>
                </div>
            </div>

            <!-- Sticky Footer CTA -->
            <div
                v-if="repositories.length > 0"
                class="sticky bottom-0 border-t bg-background/95 backdrop-blur supports-[backdrop-filter]:bg-background/80"
            >
                <div
                    class="mx-auto flex max-w-3xl items-center justify-between gap-4 px-4 py-4 md:px-6"
                >
                    <p class="text-sm text-muted-foreground">
                        <span
                            v-if="selectedRepo"
                            class="font-medium text-foreground"
                        >
                            {{ selectedRepo }}
                        </span>
                        <span v-else> Select a repository to continue </span>
                    </p>

                    <Button
                        :disabled="!selectedRepo || isSubmitting"
                        class="min-w-[120px] transition-all duration-200"
                        :class="
                            selectedRepo && !isSubmitting
                                ? 'bg-gradient-to-r from-violet-600 to-indigo-600 text-white shadow-lg shadow-violet-500/25 hover:shadow-xl hover:shadow-violet-500/30'
                                : ''
                        "
                        @click="continueToConfirm"
                    >
                        <Spinner v-if="isSubmitting" class="mr-2 size-4" />
                        Continue
                    </Button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
@keyframes shimmer {
    100% {
        transform: translateX(100%);
    }
}

@keyframes check-pop {
    0% {
        transform: scale(0);
        opacity: 0;
    }
    50% {
        transform: scale(1.3);
    }
    100% {
        transform: scale(1);
        opacity: 1;
    }
}
</style>
