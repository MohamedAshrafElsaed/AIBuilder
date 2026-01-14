<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Image as ImageIcon, Send } from 'lucide-vue-next';
import { ref } from 'vue';

interface Props {
    placeholder?: string;
}

const props = withDefaults(defineProps<Props>(), {
    placeholder: 'Reply...',
});

const emit = defineEmits<{
    send: [message: string];
}>();

const message = ref('');
const textareaRef = ref<HTMLTextAreaElement>();

const handleSubmit = () => {
    if (!message.value.trim()) return;
    emit('send', message.value);
    message.value = '';
    // Reset textarea height
    if (textareaRef.value) {
        textareaRef.value.style.height = 'auto';
    }
};

const handleKeydown = (e: KeyboardEvent) => {
    if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        handleSubmit();
    }
};

const autoResize = (e: Event) => {
    const target = e.target as HTMLTextAreaElement;
    target.style.height = 'auto';
    target.style.height = Math.min(target.scrollHeight, 200) + 'px';
};
</script>

<template>
    <div class="relative">
        <div class="rounded-lg border dark:bg-[#1A1A1A] dark:border-[#262626] focus-within:border-[#404040] transition-colors">
            <textarea
                ref="textareaRef"
                v-model="message"
                @keydown="handleKeydown"
                @input="autoResize"
                :placeholder="placeholder"
                rows="1"
                class="w-full resize-none bg-transparent px-4 py-3 text-sm outline-none dark:text-[#E5E5E5] dark:placeholder:text-[#525252] min-h-[44px] max-h-[200px]"
            />
            <div class="flex items-center justify-between px-3 pb-3">
                <div class="flex items-center gap-2">
                    <Button
                        variant="ghost"
                        size="icon-sm"
                        class="size-8 dark:text-[#525252] dark:hover:text-[#A3A3A3] dark:hover:bg-[#262626]"
                    >
                        <ImageIcon class="size-4" />
                    </Button>
                </div>
                <Button
                    @click="handleSubmit"
                    size="icon-sm"
                    :disabled="!message.trim()"
                    class="size-8 rounded-full bg-primary hover:bg-primary/90 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    <Send class="size-4 text-white" />
                </Button>
            </div>
        </div>
    </div>
</template>
