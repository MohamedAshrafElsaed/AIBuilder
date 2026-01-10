<script setup lang="ts">
import AuditLogPanel from '@/components/ask-ai/AuditLogPanel.vue';
import LoadingIndicator from '@/components/ask-ai/LoadingIndicator.vue';
import { Badge } from '@/components/ui/badge';
import type { ChatMessage } from '@/types/askai';
import { AlertCircle, Bot, User } from 'lucide-vue-next';
import { computed } from 'vue';

interface Props {
    message: ChatMessage;
    projectId: number;
    githubUrl?: string;
}

const props = defineProps<Props>();

const formattedTime = computed(() => {
    return new Date(props.message.timestamp).toLocaleTimeString('en-US', {
        hour: '2-digit',
        minute: '2-digit',
    });
});

const isUser = computed(() => props.message.type === 'user');
const isAssistant = computed(() => props.message.type === 'assistant');

// Simple markdown rendering (code blocks, bold, links)
const renderMarkdown = (text: string) => {
    let html = text
        // Code blocks
        .replace(
            /```(\w+)?\n([\s\S]*?)```/g,
            '<pre class="my-2 overflow-x-auto rounded-lg bg-muted p-3"><code class="text-sm">$2</code></pre>',
        )
        // Inline code
        .replace(
            /`([^`]+)`/g,
            '<code class="rounded bg-muted px-1.5 py-0.5 text-sm font-mono">$1</code>',
        )
        // Bold
        .replace(/\*\*([^*]+)\*\*/g, '<strong>$1</strong>')
        // Headers
        .replace(
            /^### (.+)$/gm,
            '<h4 class="mt-4 mb-2 font-semibold text-foreground">$1</h4>',
        )
        .replace(
            /^## (.+)$/gm,
            '<h3 class="mt-4 mb-2 text-lg font-semibold text-foreground">$1</h3>',
        )
        // Lists
        .replace(/^- (.+)$/gm, '<li class="ml-4 list-disc">$1</li>')
        // Line breaks
        .replace(/\n\n/g, '</p><p class="mb-2">')
        .replace(/\n/g, '<br>');

    // Wrap list items
    html = html.replace(
        /(<li[^>]*>.*?<\/li>)+/g,
        '<ul class="my-2 space-y-1">$&</ul>',
    );

    return `<p class="mb-2">${html}</p>`;
};
</script>

<template>
    <div class="flex gap-3" :class="isUser ? 'flex-row-reverse' : 'flex-row'">
        <!-- Avatar -->
        <div
            class="flex size-8 shrink-0 items-center justify-center rounded-full"
            :class="
                isUser
                    ? 'bg-primary text-primary-foreground'
                    : 'bg-gradient-to-br from-violet-500 to-indigo-500 text-white'
            "
        >
            <User v-if="isUser" class="size-4" />
            <Bot v-else class="size-4" />
        </div>

        <!-- Message content -->
        <div
            class="flex max-w-[85%] flex-col gap-2"
            :class="isUser ? 'items-end' : 'items-start'"
        >
            <!-- Header -->
            <div class="flex items-center gap-2">
                <span class="text-sm font-medium text-foreground">
                    {{ isUser ? 'You' : 'AI Assistant' }}
                </span>
                <span class="text-xs text-muted-foreground">
                    {{ formattedTime }}
                </span>
            </div>

            <!-- Loading state -->
            <LoadingIndicator v-if="message.isLoading" />

            <!-- Error state -->
            <div
                v-else-if="message.error"
                class="flex items-center gap-2 rounded-lg border border-destructive/50 bg-destructive/10 px-4 py-3"
            >
                <AlertCircle class="size-4 text-destructive" />
                <span class="text-sm text-destructive">{{
                    message.error
                }}</span>
            </div>

            <!-- User message -->
            <div
                v-else-if="isUser"
                class="rounded-2xl rounded-tr-md bg-primary px-4 py-2 text-primary-foreground"
            >
                <p class="text-sm">{{ message.content }}</p>
            </div>

            <!-- Assistant message -->
            <div
                v-else-if="isAssistant && !message.isLoading"
                class="space-y-3"
            >
                <!-- Confidence badge for low confidence -->
                <Badge
                    v-if="message.response?.confidence === 'low'"
                    class="bg-amber-500/10 text-amber-600 dark:text-amber-400"
                >
                    ⚠️ Limited context available
                </Badge>

                <!-- Answer content -->
                <div
                    class="prose prose-sm dark:prose-invert max-w-none rounded-2xl rounded-tl-md bg-muted/50 px-4 py-3"
                    v-html="renderMarkdown(message.content)"
                ></div>

                <!-- Missing details -->
                <div
                    v-if="message.response?.missing_details?.length"
                    class="rounded-lg border border-amber-500/30 bg-amber-500/10 px-3 py-2"
                >
                    <p
                        class="mb-1 text-xs font-medium text-amber-600 dark:text-amber-400"
                    >
                        Missing information:
                    </p>
                    <ul
                        class="list-inside list-disc text-xs text-amber-600/80 dark:text-amber-400/80"
                    >
                        <li
                            v-for="detail in message.response.missing_details"
                            :key="detail"
                        >
                            {{ detail }}
                        </li>
                    </ul>
                </div>

                <!-- Audit log -->
                <AuditLogPanel
                    v-if="message.response?.audit_log?.length"
                    :audit-log="message.response.audit_log"
                    :confidence="message.response.confidence"
                    :project-id="projectId"
                    :github-url="githubUrl"
                />
            </div>
        </div>
    </div>
</template>
