<script setup lang="ts">
import ChatInput from './ChatInput.vue';
import ChatMessage from './ChatMessage.vue';
import { Button } from '@/components/ui/button';
import {
    ChevronDown,
    ExternalLink,
    GitBranch,
    Terminal,
} from 'lucide-vue-next';
import { ref } from 'vue';

interface Message {
    id: number;
    role: 'user' | 'assistant';
    content: string;
    timestamp?: string;
    codeBlocks?: CodeBlock[];
    todos?: TodoItem[];
}

interface CodeBlock {
    filename: string;
    language: string;
    additions?: { line: number; content: string }[];
    deletions?: { line: number; content: string }[];
}

interface TodoItem {
    id: number;
    text: string;
    completed: boolean;
}

interface Props {
    sessionTitle?: string;
    branchName?: string;
    messages?: Message[];
}

const props = withDefaults(defineProps<Props>(), {
    sessionTitle: 'New Session',
    branchName: 'main',
    messages: () => [],
});

const emit = defineEmits<{
    sendMessage: [message: string];
}>();

// Demo messages if none provided
const displayMessages = computed(() => {
    if (props.messages.length > 0) return props.messages;
    return [
        {
            id: 1,
            role: 'user' as const,
            content: 'can i implement large features ?',
            timestamp: '2:30 pm',
        },
        {
            id: 2,
            role: 'assistant' as const,
            content: `Yes, I can help you **implement large features**! I'm set up on the branch \`claude/large-features-implementation-ot4SP\` and ready to work.

To get started, could you tell me:
1. **What feature(s)** do you want to implement?
2. **Any specific requirements** or designs you have in mind?

If you'd like, I can also explore the codebase first to understand the project structure and suggest features that might be valuable. Just let me know what you'd like to build!`,
            timestamp: '2:31 pm',
        },
    ];
});

import { computed } from 'vue';

const handleSendMessage = (message: string) => {
    emit('sendMessage', message);
};
</script>

<template>
    <div class="flex flex-1 flex-col h-screen dark:bg-[#0D0D0D]">
        <!-- Header -->
        <header class="flex items-center justify-between px-6 py-4 border-b dark:border-[#1F1F1F]">
            <div class="flex items-center gap-2">
                <button class="flex items-center gap-2 text-sm font-medium dark:text-[#E5E5E5] hover:text-primary transition-colors">
                    <Terminal class="size-4 dark:text-primary" />
                    {{ sessionTitle }}
                    <ChevronDown class="size-4 dark:text-[#525252]" />
                </button>
            </div>
            <div class="flex items-center gap-3">
                <div class="flex items-center gap-1.5 px-2.5 py-1 rounded text-xs dark:bg-[#1A1A1A] dark:text-[#A3A3A3] dark:border dark:border-[#262626]">
                    <GitBranch class="size-3" />
                    {{ branchName }}
                </div>
                <Button variant="ghost" size="icon-sm" class="dark:text-[#525252] dark:hover:text-[#A3A3A3]">
                    <ExternalLink class="size-4" />
                </Button>
                <Button variant="outline" size="sm" class="text-xs dark:border-[#333] dark:text-[#A3A3A3] dark:hover:bg-[#1A1A1A]">
                    Open in CLI
                    <Terminal class="size-3 ml-1.5" />
                </Button>
            </div>
        </header>

        <!-- Messages Area -->
        <div class="flex-1 overflow-y-auto">
            <div class="max-w-4xl mx-auto py-6 px-6 space-y-6">
                <ChatMessage
                    v-for="message in displayMessages"
                    :key="message.id"
                    :role="message.role"
                    :content="message.content"
                    :timestamp="message.timestamp"
                />
            </div>
        </div>

        <!-- Input Area -->
        <div class="border-t dark:border-[#1F1F1F]">
            <div class="max-w-4xl mx-auto p-6">
                <ChatInput @send="handleSendMessage" />
            </div>
        </div>
    </div>
</template>
