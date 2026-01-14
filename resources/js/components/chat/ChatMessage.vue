<script setup lang="ts">
import { computed } from 'vue';

interface Props {
    role: 'user' | 'assistant';
    content: string;
    timestamp?: string;
}

const props = defineProps<Props>();

// Simple markdown-like parsing for bold, code, and lists
const formattedContent = computed(() => {
    let html = props.content;

    // Escape HTML
    html = html.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');

    // Code blocks (```)
    html = html.replace(/```(\w*)\n([\s\S]*?)```/g, '<pre class="code-block my-3 p-3 rounded-md text-xs overflow-x-auto dark:bg-[#0D0D0D] dark:border dark:border-[#262626]"><code>$2</code></pre>');

    // Inline code (`)
    html = html.replace(/`([^`]+)`/g, '<code class="px-1.5 py-0.5 rounded text-xs dark:bg-[#262626] dark:text-[#FB923C]">$1</code>');

    // Bold (**)
    html = html.replace(/\*\*([^*]+)\*\*/g, '<strong class="font-semibold dark:text-[#E5E5E5]">$1</strong>');

    // Numbered lists
    html = html.replace(/^(\d+)\.\s+(.+)$/gm, '<li class="ml-4 list-decimal dark:text-[#A3A3A3]">$2</li>');

    // Wrap consecutive list items
    html = html.replace(/(<li[^>]*>.*?<\/li>\n?)+/g, '<ol class="my-2 space-y-1 list-inside">$&</ol>');

    // Line breaks
    html = html.replace(/\n/g, '<br>');

    return html;
});
</script>

<template>
    <div :class="[
        'flex gap-4',
        role === 'user' ? 'justify-end' : 'justify-start'
    ]">
        <!-- User Message -->
        <div v-if="role === 'user'" class="max-w-[80%]">
            <div class="px-4 py-3 rounded-2xl rounded-br-md dark:bg-[#262626]">
                <p class="text-sm dark:text-[#E5E5E5]">{{ content }}</p>
            </div>
        </div>

        <!-- Assistant Message -->
        <div v-else class="max-w-[90%]">
            <div class="flex items-start gap-3">
                <!-- Bullet indicator -->
                <div class="mt-2 size-1.5 rounded-full shrink-0 bg-primary"></div>

                <div class="flex-1">
                    <div
                        class="text-sm dark:text-[#A3A3A3] leading-relaxed prose prose-sm prose-invert max-w-none"
                        v-html="formattedContent"
                    />
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
@reference "tailwindcss";

:deep(strong) {
    @apply font-semibold;
}

:deep(code) {
    font-family: 'JetBrains Mono', 'Fira Code', monospace;
}

:deep(ol) {
    @apply list-decimal;
}

:deep(li) {
    @apply my-1;
}
</style>
