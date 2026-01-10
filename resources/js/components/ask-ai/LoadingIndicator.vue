<script setup lang="ts">
import { Loader2 } from 'lucide-vue-next';
import { computed, onMounted, onUnmounted, ref } from 'vue';

const steps = [
    'Analyzing your question...',
    'Searching knowledge base...',
    'Retrieving relevant code chunks...',
    'Building context...',
    'Generating response...',
];

const currentStep = ref(0);
const intervalId = ref<ReturnType<typeof setInterval> | null>(null);

const currentStepText = computed(() => steps[currentStep.value]);

onMounted(() => {
    intervalId.value = setInterval(() => {
        currentStep.value = (currentStep.value + 1) % steps.length;
    }, 2000);
});

onUnmounted(() => {
    if (intervalId.value) {
        clearInterval(intervalId.value);
    }
});
</script>

<template>
    <div class="flex items-start gap-3 p-4">
        <div
            class="flex size-8 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-violet-500 to-indigo-500"
        >
            <Loader2 class="size-4 animate-spin text-white" />
        </div>
        <div class="flex-1">
            <div class="flex items-center gap-2">
                <span class="text-sm font-medium text-foreground">
                    AI Assistant
                </span>
                <span
                    class="rounded-full bg-amber-500/10 px-2 py-0.5 text-xs font-medium text-amber-600 dark:text-amber-400"
                >
                    Thinking...
                </span>
            </div>
            <div class="mt-2">
                <div
                    class="inline-flex items-center gap-2 rounded-lg bg-muted/50 px-3 py-2 text-sm text-muted-foreground"
                >
                    <span
                        class="inline-block size-1.5 animate-pulse rounded-full bg-violet-500"
                    ></span>
                    {{ currentStepText }}
                </div>
            </div>
            <div class="mt-3 flex gap-1">
                <div
                    v-for="(step, index) in steps"
                    :key="index"
                    class="h-1 w-8 rounded-full transition-colors duration-300"
                    :class="
                        index <= currentStep
                            ? 'bg-violet-500'
                            : 'bg-muted-foreground/20'
                    "
                ></div>
            </div>
        </div>
    </div>
</template>
