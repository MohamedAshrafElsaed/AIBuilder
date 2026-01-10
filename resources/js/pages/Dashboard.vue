<script setup lang="ts">
import ProjectCard from '@/components/ProjectCard.vue';
import ProjectSetupLoader from '@/components/ProjectSetupLoader.vue';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import { create } from '@/routes/projects';
import { type BreadcrumbItem, type Project } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { Plus } from 'lucide-vue-next';

interface Props {
    projects: Project[];
    newProjectId: number | null;
    hasGitHubToken: boolean;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
];
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-semibold text-foreground">
                        Projects
                    </h1>
                    <p class="text-sm text-muted-foreground">
                        Manage your connected repositories
                    </p>
                </div>
                <Link :href="create()">
                    <Button>
                        <Plus class="mr-2 size-4" />
                        Add Project (GitHub)
                    </Button>
                </Link>
            </div>

            <div
                v-if="projects.length === 0"
                class="flex flex-1 flex-col items-center justify-center rounded-xl border border-dashed border-sidebar-border/70 p-12 dark:border-sidebar-border"
            >
                <div
                    class="mb-4 flex size-16 items-center justify-center rounded-full bg-muted"
                >
                    <svg
                        class="size-8 text-muted-foreground"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="1.5"
                            d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"
                        />
                    </svg>
                </div>
                <h3 class="mb-2 text-lg font-medium">No projects yet</h3>
                <p
                    class="mb-6 max-w-sm text-center text-sm text-muted-foreground"
                >
                    Connect your first GitHub repository to get started with
                    code analysis and AI-powered insights.
                </p>
                <Link :href="create()">
                    <Button>
                        <Plus class="mr-2 size-4" />
                        Add Project (GitHub)
                    </Button>
                </Link>
            </div>

            <div v-else class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                <template v-for="project in projects" :key="project.id">
                    <ProjectSetupLoader
                        v-if="
                            project.id === newProjectId ||
                            project.status === 'processing'
                        "
                        :project="project"
                        :is-new="project.id === newProjectId"
                    />
                    <ProjectCard v-else :project="project" />
                </template>
            </div>
        </div>
    </AppLayout>
</template>
