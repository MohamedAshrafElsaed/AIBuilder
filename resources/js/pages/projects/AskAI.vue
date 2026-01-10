<script setup lang="ts">
import ChatMessage from '@/components/ask-ai/ChatMessage.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Spinner } from '@/components/ui/spinner';
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import type { BreadcrumbItem, Project } from '@/types';
import type {
    AskAIContext,
    AskAIResponse,
    ChatMessage as ChatMessageType,
} from '@/types/askai';
import { Head, Link } from '@inertiajs/vue3';
import {
    AlertCircle,
    ArrowLeft,
    Bot,
    FileCode,
    Layers,
    Lightbulb,
    Send,
    Sparkles,
    Trash2,
} from 'lucide-vue-next';
import { computed, nextTick, onMounted, ref, watch } from 'vue';

interface Props {
    project: Project & {
        owner: string;
        repo_name: string;
        github_url: string;
    };
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: dashboard().url },
    { title: props.project.repo_name, href: `/projects/${props.project.id}` },
    { title: 'Ask AI', href: '#' },
];

// State
const messages = ref<ChatMessageType[]>([]);
const question = ref('');
const isLoading = ref(false);
const context = ref<AskAIContext | null>(null);
const contextLoading = ref(true);
const contextError = ref<string | null>(null);
const chatContainer = ref<HTMLDivElement | null>(null);
const inputRef = ref<HTMLInputElement | null>(null);

// Computed
const canSubmit = computed(() => {
    return (
        question.value.trim().length >= 10 &&
        !isLoading.value &&
        context.value?.ready
    );
});

const exampleQuestions = computed(() => {
    return (
        context.value?.example_questions ?? [
            'How is authentication handled?',
            'What routes are defined?',
            'Explain the database schema.',
        ]
    );
});

// Methods
async function loadContext() {
    contextLoading.value = true;
    contextError.value = null;

    try {
        const response = await fetch(
            `/api/projects/${props.project.id}/ask/context`,
            {
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
            },
        );

        if (!response.ok) {
            throw new Error('Failed to load context');
        }

        context.value = await response.json();
    } catch (e) {
        contextError.value = 'Failed to load project context. Please refresh.';
        console.error('Context load error:', e);
    } finally {
        contextLoading.value = false;
    }
}

async function submitQuestion() {
    if (!canSubmit.value) return;

    const userQuestion = question.value.trim();
    question.value = '';

    // Add user message
    const userMessage: ChatMessageType = {
        id: `user-${Date.now()}`,
        type: 'user',
        content: userQuestion,
        timestamp: new Date(),
    };
    messages.value.push(userMessage);

    // Add placeholder assistant message
    const assistantId = `assistant-${Date.now()}`;
    const assistantMessage: ChatMessageType = {
        id: assistantId,
        type: 'assistant',
        content: '',
        timestamp: new Date(),
        isLoading: true,
    };
    messages.value.push(assistantMessage);

    isLoading.value = true;
    scrollToBottom();

    try {
        const response = await fetch(`/api/projects/${props.project.id}/ask`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN':
                    document
                        .querySelector('meta[name="csrf-token"]')
                        ?.getAttribute('content') ?? '',
            },
            credentials: 'same-origin',
            body: JSON.stringify({
                question: userQuestion,
                mode: 'read_only',
                preferred_depth: 'quick',
            }),
        });

        const data: AskAIResponse | { error: string; message: string } =
            await response.json();

        // Find and update the assistant message
        const msgIndex = messages.value.findIndex((m) => m.id === assistantId);
        if (msgIndex !== -1) {
            if ('error' in data) {
                messages.value[msgIndex] = {
                    ...messages.value[msgIndex],
                    isLoading: false,
                    error: data.message,
                };
            } else {
                messages.value[msgIndex] = {
                    ...messages.value[msgIndex],
                    isLoading: false,
                    content: data.answer_markdown,
                    response: data,
                };
            }
        }
    } catch (e) {
        const msgIndex = messages.value.findIndex((m) => m.id === assistantId);
        if (msgIndex !== -1) {
            messages.value[msgIndex] = {
                ...messages.value[msgIndex],
                isLoading: false,
                error: 'Failed to get a response. Please try again.',
            };
        }
    } finally {
        isLoading.value = false;
        scrollToBottom();
        inputRef.value?.focus();
    }
}

function useExampleQuestion(q: string) {
    question.value = q;
    inputRef.value?.focus();
}

function clearChat() {
    messages.value = [];
}

function scrollToBottom() {
    nextTick(() => {
        if (chatContainer.value) {
            chatContainer.value.scrollTop = chatContainer.value.scrollHeight;
        }
    });
}

function handleKeydown(e: KeyboardEvent) {
    if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        submitQuestion();
    }
}

// Lifecycle
onMounted(() => {
    loadContext();
    inputRef.value?.focus();
});

watch(
    () => messages.value.length,
    () => scrollToBottom(),
);
</script>

<template>
    <Head :title="`Ask AI - ${project.repo_name}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col">
            <!-- Header -->
            <div class="border-b bg-card px-6 py-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <Link
                            :href="`/projects/${project.id}`"
                            class="flex items-center gap-1 text-sm text-muted-foreground hover:text-foreground"
                        >
                            <ArrowLeft class="size-4" />
                            Back to project
                        </Link>
                        <div class="h-6 w-px bg-border"></div>
                        <div class="flex items-center gap-2">
                            <div
                                class="flex size-8 items-center justify-center rounded-lg bg-gradient-to-br from-violet-500 to-indigo-500"
                            >
                                <Sparkles class="size-4 text-white" />
                            </div>
                            <div>
                                <h1 class="font-semibold">Ask AI</h1>
                                <p class="text-xs text-muted-foreground">
                                    {{ project.repo_name }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center gap-3">
                        <!-- Context stats -->
                        <div
                            v-if="context?.ready"
                            class="flex items-center gap-3 text-xs text-muted-foreground"
                        >
                            <span class="flex items-center gap-1">
                                <FileCode class="size-3.5" />
                                {{
                                    context.stats?.files_count.toLocaleString()
                                }}
                                files
                            </span>
                            <span class="flex items-center gap-1">
                                <Layers class="size-3.5" />
                                {{ context.stack?.framework ?? 'Unknown' }}
                            </span>
                        </div>
                        <Button
                            v-if="messages.length > 0"
                            variant="ghost"
                            size="sm"
                            @click="clearChat"
                        >
                            <Trash2 class="mr-1 size-3.5" />
                            Clear
                        </Button>
                    </div>
                </div>
            </div>

            <!-- Chat area -->
            <div ref="chatContainer" class="flex-1 overflow-y-auto p-6">
                <!-- Loading context -->
                <div
                    v-if="contextLoading"
                    class="flex h-full items-center justify-center"
                >
                    <div class="text-center">
                        <Spinner class="mx-auto mb-3 size-8" />
                        <p class="text-muted-foreground">
                            Loading project context...
                        </p>
                    </div>
                </div>

                <!-- Context error -->
                <div
                    v-else-if="contextError"
                    class="flex h-full items-center justify-center"
                >
                    <div class="text-center">
                        <AlertCircle
                            class="mx-auto mb-3 size-8 text-destructive"
                        />
                        <p class="text-destructive">{{ contextError }}</p>
                        <Button
                            variant="outline"
                            class="mt-4"
                            @click="loadContext"
                        >
                            Retry
                        </Button>
                    </div>
                </div>

                <!-- Empty state -->
                <div
                    v-else-if="messages.length === 0"
                    class="flex h-full flex-col items-center justify-center"
                >
                    <div class="max-w-lg text-center">
                        <div
                            class="mx-auto mb-6 flex size-16 items-center justify-center rounded-2xl bg-gradient-to-br from-violet-500 to-indigo-500"
                        >
                            <Bot class="size-8 text-white" />
                        </div>
                        <h2 class="mb-2 text-xl font-semibold">
                            Ask about your codebase
                        </h2>
                        <p class="mb-6 text-muted-foreground">
                            I can answer questions about
                            {{ project.repo_name }} based on the scanned code.
                            All answers include citations to specific files and
                            line numbers.
                        </p>

                        <!-- Example questions -->
                        <div class="mb-6">
                            <p
                                class="mb-3 flex items-center justify-center gap-1 text-sm text-muted-foreground"
                            >
                                <Lightbulb class="size-4" />
                                Try asking:
                            </p>
                            <div class="flex flex-wrap justify-center gap-2">
                                <button
                                    v-for="q in exampleQuestions"
                                    :key="q"
                                    class="rounded-full border bg-card px-4 py-2 text-sm transition-colors hover:bg-muted"
                                    @click="useExampleQuestion(q)"
                                >
                                    {{ q }}
                                </button>
                            </div>
                        </div>

                        <!-- Hints -->
                        <div
                            v-if="context?.hints?.sample_paths?.length"
                            class="rounded-lg border bg-muted/30 p-4"
                        >
                            <p
                                class="mb-2 text-xs font-medium text-muted-foreground"
                            >
                                Sample files in this project:
                            </p>
                            <div class="flex flex-wrap gap-1">
                                <Badge
                                    v-for="path in context.hints.sample_paths.slice(
                                        0,
                                        5,
                                    )"
                                    :key="path"
                                    variant="secondary"
                                    class="font-mono text-xs"
                                >
                                    {{ path }}
                                </Badge>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Messages -->
                <div v-else class="space-y-6">
                    <ChatMessage
                        v-for="message in messages"
                        :key="message.id"
                        :message="message"
                        :project-id="project.id"
                        :github-url="project.github_url"
                    />
                </div>
            </div>

            <!-- Input area -->
            <div class="border-t bg-card p-4">
                <div class="mx-auto max-w-4xl">
                    <div class="flex gap-3">
                        <div class="relative flex-1">
                            <Input
                                ref="inputRef"
                                v-model="question"
                                type="text"
                                placeholder="Ask a question about the codebase..."
                                class="pr-12"
                                :disabled="!context?.ready || isLoading"
                                @keydown="handleKeydown"
                            />
                            <Button
                                size="icon"
                                class="absolute top-1/2 right-1 size-8 -translate-y-1/2"
                                :disabled="!canSubmit"
                                @click="submitQuestion"
                            >
                                <Send class="size-4" />
                            </Button>
                        </div>
                    </div>
                    <p class="mt-2 text-center text-xs text-muted-foreground">
                        Answers are generated from scanned code only. All
                        responses include audit logs for verification.
                    </p>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
