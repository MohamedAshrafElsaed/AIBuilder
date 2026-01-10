<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Spinner } from '@/components/ui/spinner';
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import { confirm } from '@/routes/projects';
import { type BreadcrumbItem, type Repository } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowLeft, GitBranch, Lock, Search } from 'lucide-vue-next';
import { computed, ref } from 'vue';

interface Props {
    repositories: Repository[];
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
    {
        title: 'Select Repository',
        href: '#',
    },
];

const searchQuery = ref('');
const selectedRepo = ref<string | null>(null);
const isSubmitting = ref(false);

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

function selectRepository(fullName: string) {
    selectedRepo.value = fullName;
}

function continueToConfirm() {
    if (!selectedRepo.value) {
        return;
    }
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
</script>

<template>
    <Head title="Select Repository" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto max-w-4xl p-4 md:p-6">
            <div class="mb-6">
                <Link
                    :href="dashboard()"
                    class="mb-4 inline-flex items-center text-sm text-muted-foreground hover:text-foreground"
                >
                    <ArrowLeft class="mr-1 size-4" />
                    Back to Dashboard
                </Link>
                <h1 class="text-2xl font-semibold text-foreground">
                    Select a repository
                </h1>
                <p class="text-sm text-muted-foreground">
                    Choose a GitHub repository to add to your projects
                </p>
            </div>

            <div class="relative mb-6">
                <Search
                    class="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
                />
                <Input
                    v-model="searchQuery"
                    placeholder="Search repositories..."
                    class="pl-10"
                />
            </div>

            <div
                v-if="repositories.length === 0"
                class="rounded-xl border border-dashed p-12 text-center"
            >
                <p class="text-muted-foreground">
                    No repositories found. Make sure your GitHub account has
                    access to repositories.
                </p>
            </div>

            <div v-else class="space-y-2">
                <Card
                    v-for="repo in filteredRepositories"
                    :key="repo.id"
                    :class="[
                        'cursor-pointer transition-all hover:border-primary/50',
                        selectedRepo === repo.full_name
                            ? 'border-primary bg-primary/5 ring-1 ring-primary'
                            : '',
                    ]"
                    @click="selectRepository(repo.full_name)"
                >
                    <CardHeader class="pb-2">
                        <div class="flex items-start justify-between gap-2">
                            <div class="flex items-center gap-2">
                                <input
                                    type="radio"
                                    :checked="selectedRepo === repo.full_name"
                                    class="size-4 accent-primary"
                                    @click.stop
                                />
                                <CardTitle class="text-base font-medium">
                                    {{ repo.full_name }}
                                </CardTitle>
                            </div>
                            <div class="flex items-center gap-2">
                                <Badge
                                    v-if="repo.private"
                                    variant="secondary"
                                    class="flex items-center gap-1"
                                >
                                    <Lock class="size-3" />
                                    Private
                                </Badge>
                                <Badge
                                    variant="outline"
                                    class="flex items-center gap-1"
                                >
                                    <GitBranch class="size-3" />
                                    {{ repo.default_branch }}
                                </Badge>
                            </div>
                        </div>
                    </CardHeader>
                    <CardContent v-if="repo.description" class="pt-0">
                        <CardDescription class="line-clamp-2">
                            {{ repo.description }}
                        </CardDescription>
                    </CardContent>
                </Card>

                <div
                    v-if="filteredRepositories.length === 0"
                    class="py-8 text-center text-muted-foreground"
                >
                    No repositories match your search.
                </div>
            </div>

            <div
                v-if="repositories.length > 0"
                class="sticky bottom-0 mt-6 flex justify-end border-t bg-background pt-4"
            >
                <Button
                    :disabled="!selectedRepo || isSubmitting"
                    @click="continueToConfirm"
                >
                    <Spinner v-if="isSubmitting" class="mr-2" />
                    Continue
                </Button>
            </div>
        </div>
    </AppLayout>
</template>
