<script lang="ts" setup>
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Spinner } from '@/components/ui/spinner';
import { dashboard } from '@/routes';
import { create, store } from '@/routes/projects';
import { type BreadcrumbItem, type Repository } from '@/types';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, ExternalLink, GitBranch, Lock } from 'lucide-vue-next';

interface Props {
    repository: Repository;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
    {
        title: 'Select Repository',
        href: create().url,
    },
    {
        title: 'Confirm',
        href: '#',
    },
];

const form = useForm({
    repo_full_name: props.repository.full_name,
    repo_id: String(props.repository.id),
    default_branch: props.repository.default_branch,
});

function submit() {
    form.post(store().url);
}
</script>

<template>
    <Head title="Confirm Repository" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto max-w-2xl p-4 md:p-6">
            <div class="mb-6">
                <Link
                    :href="create()"
                    class="mb-4 inline-flex items-center text-sm text-muted-foreground hover:text-foreground"
                >
                    <ArrowLeft class="mr-1 size-4" />
                    Back to repository selection
                </Link>
                <h1 class="text-2xl font-semibold text-foreground">
                    Confirm repository
                </h1>
                <p class="text-sm text-muted-foreground">
                    Review the repository details before adding to your projects
                </p>
            </div>

            <Card>
                <CardHeader>
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <CardTitle class="text-xl">
                                {{ repository.full_name }}
                            </CardTitle>
                            <CardDescription
                                v-if="repository.description"
                                class="mt-1"
                            >
                                {{ repository.description }}
                            </CardDescription>
                        </div>
                        <div class="flex items-center gap-2">
                            <Badge
                                v-if="repository.private"
                                class="flex items-center gap-1"
                                variant="secondary"
                            >
                                <Lock class="size-3" />
                                Private
                            </Badge>
                        </div>
                    </div>
                </CardHeader>
                <CardContent class="space-y-4">
                    <div
                        class="flex items-center justify-between rounded-lg border p-4"
                    >
                        <div class="flex items-center gap-3">
                            <div
                                class="flex size-10 items-center justify-center rounded-lg bg-muted"
                            >
                                <GitBranch
                                    class="size-5 text-muted-foreground"
                                />
                            </div>
                            <div>
                                <p class="text-sm font-medium">
                                    Default Branch
                                </p>
                                <p class="text-sm text-muted-foreground">
                                    Detected automatically from GitHub
                                </p>
                            </div>
                        </div>
                        <Badge class="text-base" variant="outline">
                            {{ repository.default_branch }}
                        </Badge>
                    </div>

                    <a
                        v-if="repository.html_url"
                        :href="repository.html_url"
                        class="inline-flex items-center gap-1 text-sm text-muted-foreground hover:text-foreground"
                        rel="noopener noreferrer"
                        target="_blank"
                    >
                        View on GitHub
                        <ExternalLink class="size-3" />
                    </a>
                </CardContent>
            </Card>

            <div class="mt-6 flex justify-end gap-3">
                <Link :href="create()">
                    <Button variant="outline">Cancel</Button>
                </Link>
                <Button :disabled="form.processing" @click="submit">
                    <Spinner v-if="form.processing" class="mr-2" />
                    Add Project
                </Button>
            </div>
        </div>
    </AppLayout>
</template>
