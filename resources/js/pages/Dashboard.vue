<script setup lang="ts">
import ProjectCard from '@/components/ProjectCard.vue';
import ProjectSetupLoader from '@/components/ProjectSetupLoader.vue';
import ProjectCardSkeleton from '@/components/dashboard/ProjectCardSkeleton.vue';
import EmptyProjectsState from '@/components/dashboard/EmptyProjectsState.vue';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import { create } from '@/routes/projects';
import { type BreadcrumbItem, type Project } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { Plus, Sparkles } from 'lucide-vue-next';
import { ref, onMounted } from 'vue';

interface Props {
    projects: Project[];
    newProjectId: number | null;
    hasGitHubToken: boolean;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: dashboard().url },
];

// Animation state for staggered card entrance
const isLoaded = ref(false);
const isLoading = ref(false); // Set true when fetching

onMounted(() => {
    // Trigger staggered animation after mount
    requestAnimationFrame(() => {
        isLoaded.value = true;
    });
});

// Count active AI processes
const activeAIProcesses = props.projects.filter(
    p => p.status === 'processing' || p.id === props.newProjectId
).length;
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-8 p-6 md:p-8 lg:p-10">
            <!-- Page Header -->
            <header class="flex flex-col gap-6 sm:flex-row sm:items-end sm:justify-between">
                <div class="space-y-1">
                    <div class="flex items-center gap-3">
                        <h1 class="text-3xl font-semibold tracking-tight text-foreground">
                            Projects
                        </h1>
                        <!-- AI Activity Indicator -->
                        <div
                            v-if="activeAIProcesses > 0"
                            class="inline-flex items-center gap-1.5 rounded-full bg-gradient-to-r from-violet-500/10 to-indigo-500/10 px-3 py-1 text-xs font-medium text-violet-600 ring-1 ring-inset ring-violet-500/20 dark:text-violet-400"
                        >
                            <span class="relative flex h-2 w-2">
                                <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-violet-400 opacity-75"></span>
                                <span class="relative inline-flex h-2 w-2 rounded-full bg-violet-500"></span>
                            </span>
                            {{ activeAIProcesses }} AI {{ activeAIProcesses === 1 ? 'process' : 'processes' }} active
                        </div>
                    </div>
                    <p class="text-base text-muted-foreground">
                        Manage your connected repositories and AI-powered insights
                    </p>
                </div>

                <Link :href="create()">
                    <Button
                        class="group relative overflow-hidden bg-gradient-to-r from-violet-600 to-indigo-600 text-white shadow-lg shadow-violet-500/25 transition-all duration-200 hover:shadow-xl hover:shadow-violet-500/30 hover:-translate-y-0.5"
                    >
                        <Plus class="mr-2 size-4 transition-transform duration-200 group-hover:rotate-90" />
                        Add Project
                        <Sparkles class="ml-2 size-3.5 opacity-70" />
                    </Button>
                </Link>
            </header>

            <!-- Loading Skeletons -->
            <div v-if="isLoading" class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
                <ProjectCardSkeleton v-for="i in 3" :key="i" />
            </div>

            <!-- Empty State -->
            <EmptyProjectsState v-else-if="projects.length === 0" />

            <!-- Projects Grid -->
            <div
                v-else
                class="grid gap-5 md:grid-cols-2 xl:grid-cols-3"
            >
                <template v-for="(project, index) in projects" :key="project.id">
                    <!-- Staggered animation wrapper -->
                    <div
                        :class="[
                            'transition-all duration-500 ease-out',
                            isLoaded
                                ? 'opacity-100 translate-y-0'
                                : 'opacity-0 translate-y-4'
                        ]"
                        :style="{ transitionDelay: `${index * 75}ms` }"
                    >
                        <ProjectSetupLoader
                            v-if="project.id === newProjectId || project.status === 'processing'"
                            :project="project"
                            :is-new="project.id === newProjectId"
                        />
                        <ProjectCard v-else :project="project" />
                    </div>
                </template>
            </div>
        </div>
    </AppLayout>
</template>
