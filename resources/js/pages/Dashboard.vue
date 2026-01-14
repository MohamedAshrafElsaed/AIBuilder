<script setup lang="ts">
import ProjectCard from '@/components/ProjectCard.vue';
import ProjectSetupLoader from '@/components/ProjectSetupLoader.vue';
import EmptyProjectsState from '@/components/dashboard/EmptyProjectsState.vue';
import ProjectCardSkeleton from '@/components/dashboard/ProjectCardSkeleton.vue';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import { create } from '@/routes/projects';
import { type BreadcrumbItem, type PipelineStage, type Project } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { Plus, Sparkles, Zap } from 'lucide-vue-next';
import { computed, onMounted, ref } from 'vue';

interface Props {
    projects: Project[];
    newProjectId: number | null;
    hasGitHubToken: boolean;
    pipelineStages: PipelineStage[];
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: dashboard().url },
];

// Animation state for staggered card entrance
const isLoaded = ref(false);
const isLoading = ref(false);

onMounted(() => {
    // Trigger staggered animation after mount
    requestAnimationFrame(() => {
        isLoaded.value = true;
    });
});

// Count active AI processes (scanning or newly added)
const activeAIProcesses = computed(() => {
    return props.projects.filter(
        (p) =>
            p.status === 'scanning' ||
            p.status === 'pending' ||
            p.id === props.newProjectId,
    ).length;
});

// Separate projects by status
const scanningProjects = computed(() => {
    return props.projects.filter(
        (p) =>
            p.status === 'scanning' ||
            p.status === 'pending' ||
            p.id === props.newProjectId,
    );
});

const readyProjects = computed(() => {
    return props.projects.filter(
        (p) => p.status === 'ready' && p.id !== props.newProjectId,
    );
});

const failedProjects = computed(() => {
    return props.projects.filter(
        (p) => p.status === 'failed' && p.id !== props.newProjectId,
    );
});

// All non-scanning projects (ready + failed)
const completedProjects = computed(() => {
    return [...readyProjects.value, ...failedProjects.value];
});
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-8 p-6 md:p-8 dark:bg-[#0D0D0D]">
            <!-- Page Header -->
            <header
                class="flex flex-col gap-6 sm:flex-row sm:items-end sm:justify-between animate-fade-in"
            >
                <div class="space-y-1">
                    <div class="flex items-center gap-3">
                        <h1
                            class="text-2xl font-semibold tracking-tight text-foreground dark:text-[#E5E5E5]"
                        >
                            Projects
                        </h1>
                        <!-- AI Activity Indicator -->
                        <div
                            v-if="activeAIProcesses > 0"
                            class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-medium ring-1 ring-inset bg-primary/10 text-primary ring-primary/20 dark:bg-[#F97316]/10 dark:text-[#FB923C] dark:ring-[#F97316]/20"
                        >
                            <span class="relative flex h-2 w-2">
                                <span
                                    class="absolute inline-flex h-full w-full animate-ping rounded-full bg-primary opacity-75 dark:bg-[#F97316]"
                                ></span>
                                <span
                                    class="relative inline-flex h-2 w-2 rounded-full bg-primary dark:bg-[#F97316]"
                                ></span>
                            </span>
                            {{ activeAIProcesses }} AI
                            {{
                                activeAIProcesses === 1
                                    ? 'process'
                                    : 'processes'
                            }}
                            active
                        </div>
                    </div>
                    <p class="text-sm text-muted-foreground dark:text-[#737373]">
                        Manage your connected repositories and AI-powered insights
                    </p>
                </div>

                <Link :href="create()">
                    <Button
                        class="group relative overflow-hidden bg-primary text-white shadow-md transition-all duration-200 hover:-translate-y-0.5 hover:shadow-lg dark:bg-[#F97316] dark:hover:bg-[#EA580C]"
                    >
                        <Plus
                            class="mr-2 size-4 transition-transform duration-200 group-hover:rotate-90"
                        />
                        Add Project
                        <Sparkles class="ml-2 size-3.5 opacity-70" />
                    </Button>
                </Link>
            </header>

            <!-- Loading Skeletons -->
            <div
                v-if="isLoading"
                class="grid gap-5 md:grid-cols-2 xl:grid-cols-3"
            >
                <ProjectCardSkeleton v-for="i in 3" :key="i" />
            </div>

            <!-- Empty State -->
            <EmptyProjectsState v-else-if="projects.length === 0" />

            <!-- Projects Grid -->
            <div v-else class="space-y-8">
                <!-- Scanning/Processing Projects Section -->
                <div
                    v-if="
                        scanningProjects.length > 0 || failedProjects.length > 0
                    "
                >
                    <h2
                        v-if="completedProjects.length > 0"
                        class="mb-4 text-xs font-medium uppercase tracking-wider text-muted-foreground dark:text-[#525252]"
                    >
                        <Zap class="inline-block size-3.5 mr-1.5 -mt-0.5" />
                        In Progress
                    </h2>
                    <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
                        <template
                            v-for="(project, index) in [
                                ...scanningProjects,
                                ...failedProjects,
                            ]"
                            :key="project.id"
                        >
                            <div
                                :class="[
                                    'transition-all duration-500 ease-out',
                                    isLoaded
                                        ? 'translate-y-0 opacity-100'
                                        : 'translate-y-4 opacity-0',
                                ]"
                                :style="{ transitionDelay: `${index * 75}ms` }"
                            >
                                <ProjectSetupLoader
                                    :project="project"
                                    :is-new="project.id === newProjectId"
                                />
                            </div>
                        </template>
                    </div>
                </div>

                <!-- Ready Projects Section -->
                <div v-if="readyProjects.length > 0">
                    <h2
                        v-if="
                            scanningProjects.length > 0 ||
                            failedProjects.length > 0
                        "
                        class="mb-4 text-xs font-medium uppercase tracking-wider text-muted-foreground dark:text-[#525252]"
                    >
                        Ready
                    </h2>
                    <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
                        <template
                            v-for="(project, index) in readyProjects"
                            :key="project.id"
                        >
                            <div
                                :class="[
                                    'transition-all duration-500 ease-out',
                                    isLoaded
                                        ? 'translate-y-0 opacity-100'
                                        : 'translate-y-4 opacity-0',
                                ]"
                                :style="{
                                    transitionDelay: `${(scanningProjects.length + failedProjects.length + index) * 75}ms`,
                                }"
                            >
                                <ProjectCard :project="project" />
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
