<script setup lang="ts">
import { Check, Loader2 } from 'lucide-vue-next';
import type { Component } from 'vue';

interface SetupStep {
    id: string;
    label: string;
    icon: Component;
}

interface Props {
    steps: SetupStep[];
    currentStepIndex: number;
    completedSteps: Set<string>;
}

defineProps<Props>();
</script>

<template>
    <div class="space-y-0">
        <div
            v-for="(step, index) in steps"
            :key="step.id"
            class="relative flex items-start gap-3 pb-3 last:pb-0"
        >
            <!-- Timeline connector line -->
            <div
                v-if="index < steps.length - 1"
                class="absolute top-6 left-[11px] h-[calc(100%-8px)] w-0.5 transition-colors duration-300"
                :class="
                    completedSteps.has(step.id) ? 'bg-violet-500' : 'bg-border'
                "
            ></div>

            <!-- Step indicator -->
            <div
                class="relative z-10 flex size-6 shrink-0 items-center justify-center rounded-full transition-all duration-300"
                :class="[
                    completedSteps.has(step.id)
                        ? 'bg-violet-500 text-white'
                        : index === currentStepIndex
                          ? 'bg-violet-500/20 text-violet-500 ring-2 ring-violet-500/50'
                          : 'bg-muted text-muted-foreground',
                ]"
            >
                <!-- Completed check with pop animation -->
                <Check
                    v-if="completedSteps.has(step.id)"
                    class="size-3.5 animate-[check-pop_0.3s_ease-out]"
                />
                <!-- Current step spinner -->
                <Loader2
                    v-else-if="index === currentStepIndex"
                    class="size-3.5 animate-spin"
                />
                <!-- Pending step icon -->
                <component v-else :is="step.icon" class="size-3" />
            </div>

            <!-- Step label -->
            <span
                class="pt-0.5 text-sm transition-colors duration-200"
                :class="[
                    completedSteps.has(step.id)
                        ? 'font-medium text-foreground'
                        : index === currentStepIndex
                          ? 'font-medium text-violet-600 dark:text-violet-400'
                          : 'text-muted-foreground',
                ]"
            >
                {{ step.label }}
            </span>
        </div>
    </div>
</template>

<style scoped>
@keyframes check-pop {
    0% {
        transform: scale(0);
        opacity: 0;
    }
    50% {
        transform: scale(1.2);
    }
    100% {
        transform: scale(1);
        opacity: 1;
    }
}
</style>
