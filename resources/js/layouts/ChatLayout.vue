<script setup lang="ts">
import ChatSidebar from '@/components/chat/ChatSidebar.vue';

interface Props {
    sessionTitle?: string;
    branchName?: string;
    projectName?: string;
}

withDefaults(defineProps<Props>(), {
    sessionTitle: 'New Session',
    branchName: 'main',
    projectName: 'AI Builder',
});
</script>

<template>
    <div class="flex h-screen w-full dark:bg-[#0D0D0D]">
        <!-- Sidebar -->
        <ChatSidebar
            :project-name="projectName"
            :branch-name="branchName"
        />

        <!-- Main Content -->
        <main class="flex-1 flex flex-col overflow-hidden">
            <slot />
        </main>
    </div>
</template>
