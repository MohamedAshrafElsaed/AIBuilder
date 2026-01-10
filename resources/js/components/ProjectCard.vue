<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { type Project } from '@/types';
import { CheckCircle2, GitBranch } from 'lucide-vue-next';

interface Props {
    project: Project;
}

defineProps<Props>();
</script>

<template>
    <Card class="transition-all hover:border-primary/30 hover:shadow-sm">
        <CardHeader class="pb-3">
            <div class="flex items-start justify-between gap-2">
                <CardTitle class="text-base font-medium">
                    {{ project.repo_full_name }}
                </CardTitle>
                <Badge
                    variant="outline"
                    class="flex items-center gap-1 bg-green-50 text-green-700 dark:bg-green-950 dark:text-green-400"
                >
                    <CheckCircle2 class="size-3" />
                    Ready
                </Badge>
            </div>
        </CardHeader>
        <CardContent class="pt-0">
            <div class="flex items-center gap-2 text-sm text-muted-foreground">
                <GitBranch class="size-4" />
                <span>{{ project.default_branch }}</span>
            </div>
        </CardContent>
    </Card>
</template>
