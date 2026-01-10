<script setup lang="ts">
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { type Project } from '@/types';
import { Check, Loader2 } from 'lucide-vue-next';
import { onMounted, onUnmounted, ref } from 'vue';

interface Props {
    project: Project;
    isNew?: boolean;
}

defineProps<Props>();

const steps = [
    { id: 1, label: 'Connecting to GitHub' },
    { id: 2, label: 'Cloning repository' },
    { id: 3, label: 'Building file manifest' },
    { id: 4, label: 'Indexing files' },
    { id: 5, label: 'Detecting stack (Blade/Vue/React/Livewire)' },
    { id: 6, label: 'Preparing agent tasks' },
];

const currentStep = ref(1);
let interval: ReturnType<typeof setInterval> | null = null;

onMounted(() => {
    interval = setInterval(() => {
        if (currentStep.value < steps.length) {
            currentStep.value++;
        } else {
            if (interval) {
                clearInterval(interval);
            }
        }
    }, 2000);
});

onUnmounted(() => {
    if (interval) {
        clearInterval(interval);
    }
});
</script>

<template>
    <Card class="border-primary/30 bg-primary/5">
        <CardHeader class="pb-3">
            <div class="flex items-start justify-between gap-2">
                <CardTitle class="text-base font-medium">
                    {{ project.repo_full_name }}
                </CardTitle>
                <span class="flex items-center gap-1 text-xs text-primary">
                    <Loader2 class="size-3 animate-spin" />
                    Setting up...
                </span>
            </div>
        </CardHeader>
        <CardContent class="pt-0">
            <div class="space-y-2">
                <div
                    v-for="step in steps"
                    :key="step.id"
                    class="flex items-center gap-3"
                >
                    <div
                        :class="[
                            'flex size-5 items-center justify-center rounded-full transition-all',
                            step.id < currentStep
                                ? 'bg-primary text-primary-foreground'
                                : step.id === currentStep
                                  ? 'border-2 border-primary bg-background'
                                  : 'border border-muted-foreground/30 bg-background',
                        ]"
                    >
                        <Check v-if="step.id < currentStep" class="size-3" />
                        <Loader2
                            v-else-if="step.id === currentStep"
                            class="size-3 animate-spin text-primary"
                        />
                    </div>
                    <span
                        :class="[
                            'text-sm transition-colors',
                            step.id < currentStep
                                ? 'text-muted-foreground line-through'
                                : step.id === currentStep
                                  ? 'font-medium text-foreground'
                                  : 'text-muted-foreground',
                        ]"
                    >
                        {{ step.label }}
                    </span>
                </div>
            </div>
        </CardContent>
    </Card>
</template>
