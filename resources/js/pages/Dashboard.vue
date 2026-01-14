<script setup lang="ts">
import ChatLayout from '@/layouts/ChatLayout.vue';
import ChatInput from '@/components/chat/ChatInput.vue';
import ChatMessage from '@/components/chat/ChatMessage.vue';
import ProjectSetupLoader from '@/components/ProjectSetupLoader.vue';
import EmptyProjectsState from '@/components/dashboard/EmptyProjectsState.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { dashboard } from '@/routes';
import { create, show } from '@/routes/projects';
import { type Project } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import {
    ChevronDown,
    ExternalLink,
    Filter,
    GitBranch,
    Image as ImageIcon,
    Plus,
    Search,
    Sparkles,
    Terminal,
    FolderGit2,
    Settings,
    Zap,
} from 'lucide-vue-next';
import { computed, onMounted, ref } from 'vue';

interface Props {
    projects: Project[];
    newProjectId: number | null;
    hasGitHubToken: boolean;
}

const props = defineProps<Props>();

// Animation state for staggered card entrance
const isLoaded = ref(false);
const searchQuery = ref('');
const selectedProjectId = ref<number | null>(null);

onMounted(() => {
    requestAnimationFrame(() => {
        isLoaded.value = true;
    });
    // Select first ready project by default
    const firstReady = props.projects.find(p => p.status === 'ready');
    if (firstReady) {
        selectedProjectId.value = firstReady.id;
    }
});

// Separate projects by status
const scanningProjects = computed(() => {
    return props.projects.filter(
        (p) =>
            p.status === 'scanning' ||
            p.status === 'pending' ||
            p.id === props.newProjectId,
    );
});

const readyProjects = computed(() => {
    return props.projects.filter(
        (p) => p.status === 'ready' && p.id !== props.newProjectId,
    );
});

const failedProjects = computed(() => {
    return props.projects.filter(
        (p) => p.status === 'failed' && p.id !== props.newProjectId,
    );
});

const selectedProject = computed(() => {
    return props.projects.find(p => p.id === selectedProjectId.value);
});

const formatTimestamp = (date: string) => {
    const d = new Date(date);
    const now = new Date();
    const diff = now.getTime() - d.getTime();
    const days = Math.floor(diff / (1000 * 60 * 60 * 24));

    if (days === 0) {
        return d.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' });
    } else if (days < 7) {
        return d.toLocaleDateString('en-US', { weekday: 'short' });
    } else {
        return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
    }
};

const selectProject = (project: Project) => {
    if (project.status === 'ready') {
        selectedProjectId.value = project.id;
    }
};

const openProject = (project: Project) => {
    router.visit(show(project.id).url);
};

// Demo messages for chat content
const demoMessages = [
    {
        id: 1,
        role: 'assistant' as const,
        content: `Welcome to **AI Builder**! I'm ready to help you with your project.

Select a project from the sidebar to start a conversation, or create a new project to get started.

I can help you with:
- **Code analysis** - Understanding and improving your codebase
- **Feature implementation** - Building new functionality
- **Bug fixes** - Identifying and resolving issues
- **Code reviews** - Providing feedback on your code`,
        timestamp: 'Now',
    },
];

const projectMessages = computed(() => {
    if (!selectedProject.value) return demoMessages;
    return [
        {
            id: 1,
            role: 'assistant' as const,
            content: `Ready to help with **${selectedProject.value.name}**!

**Repository:** ${selectedProject.value.full_name || selectedProject.value.name}
**Branch:** ${selectedProject.value.default_branch || 'main'}
**Status:** ${selectedProject.value.status}

What would you like to work on today?`,
            timestamp: 'Now',
        },
    ];
});

const handleSendMessage = (message: string) => {
    if (selectedProject.value) {
        router.visit(show(selectedProject.value.id).url);
    }
};
</script>

<template>
    <Head title="Dashboard" />

    <div class="flex h-screen w-full dark:bg-[#0D0D0D]">
        <!-- Sidebar -->
        <aside class="flex h-screen w-[320px] flex-col border-r bg-sidebar dark:bg-[#0F0F0F] dark:border-[#1F1F1F]">
            <!-- Header -->
            <div class="flex items-center justify-between p-4 pb-3">
                <Link href="/" class="flex items-center gap-2">
                    <div class="flex items-center justify-center size-8 rounded-md bg-primary">
                        <AppLogoIcon class="size-5 fill-current text-white" />
                    </div>
                    <span class="text-base font-semibold text-foreground dark:text-[#E5E5E5]">
                        AI Builder
                    </span>
                    <Badge variant="preview" class="text-[10px]">
                        Beta
                    </Badge>
                </Link>
            </div>

            <!-- Search Input -->
            <div class="px-3 pb-3">
                <div class="relative">
                    <Search class="absolute left-3 top-1/2 -translate-y-1/2 size-4 dark:text-[#525252]" />
                    <Input
                        v-model="searchQuery"
                        type="text"
                        placeholder="Ask AI to help you code..."
                        class="pl-9 pr-10 h-10 text-sm dark:bg-[#1A1A1A] dark:border-[#262626] dark:placeholder:text-[#525252] dark:text-[#A3A3A3]"
                    />
                    <div class="absolute right-3 top-1/2 -translate-y-1/2 flex items-center gap-1">
                        <ImageIcon class="size-4 dark:text-[#525252] cursor-pointer hover:text-[#737373] transition-colors" />
                        <Button size="icon-sm" variant="ghost" class="size-6 rounded-full bg-primary hover:bg-primary/90">
                            <Sparkles class="size-3 text-white" />
                        </Button>
                    </div>
                </div>
            </div>

            <!-- Scanning Projects Section -->
            <div v-if="scanningProjects.length > 0" class="px-3 pb-3">
                <div class="space-y-2">
                    <span class="text-xs font-medium dark:text-[#525252] px-1">Setting up</span>
                    <div
                        v-for="project in scanningProjects"
                        :key="project.id"
                        class="p-3 rounded-md dark:bg-[#1A1A1A] dark:border dark:border-[#262626]"
                    >
                        <div class="flex items-center gap-2">
                            <div class="relative">
                                <div class="size-8 rounded-full bg-gradient-to-br from-orange-500 to-amber-500 flex items-center justify-center">
                                    <span class="text-xs font-bold text-white">{{ project.name.substring(0, 1).toUpperCase() }}</span>
                                </div>
                                <div class="absolute -bottom-0.5 -right-0.5 size-3 rounded-full bg-primary flex items-center justify-center">
                                    <Zap class="size-2 text-white animate-pulse" />
                                </div>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium truncate dark:text-[#E5E5E5]">{{ project.name }}</p>
                                <p class="text-xs dark:text-[#F97316]">AI analyzing...</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Projects Header -->
            <div class="flex items-center justify-between px-4 py-2">
                <span class="text-xs font-medium dark:text-[#737373]">Projects</span>
                <Button variant="ghost" size="icon-sm" class="size-6 dark:text-[#525252] dark:hover:text-[#A3A3A3]">
                    <Filter class="size-3.5" />
                </Button>
            </div>

            <!-- Projects List -->
            <div class="flex-1 overflow-y-auto px-2">
                <div v-if="readyProjects.length === 0 && scanningProjects.length === 0" class="px-2 py-8 text-center">
                    <FolderGit2 class="size-8 mx-auto mb-3 dark:text-[#525252]" />
                    <p class="text-sm dark:text-[#525252]">No projects yet</p>
                </div>
                <div v-else class="space-y-0.5">
                    <button
                        v-for="project in readyProjects"
                        :key="project.id"
                        @click="selectProject(project)"
                        @dblclick="openProject(project)"
                        :class="[
                            'w-full text-left px-3 py-2.5 rounded-md transition-all duration-150 group',
                            selectedProjectId === project.id
                                ? 'dark:bg-[#1A1A1A] border-l-2 border-l-primary rounded-l-none'
                                : 'dark:hover:bg-[#1A1A1A]'
                        ]"
                    >
                        <div class="flex items-start justify-between gap-2">
                            <div class="flex items-center gap-2.5 flex-1 min-w-0">
                                <div class="size-8 rounded-full bg-gradient-to-br from-orange-500 to-amber-500 flex items-center justify-center flex-shrink-0">
                                    <span class="text-xs font-bold text-white">{{ project.name.substring(0, 1).toUpperCase() }}</span>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p :class="[
                                        'text-sm font-medium truncate',
                                        selectedProjectId === project.id ? 'dark:text-[#E5E5E5]' : 'dark:text-[#A3A3A3]'
                                    ]">
                                        {{ project.name }}
                                    </p>
                                    <div class="flex items-center gap-2 mt-0.5">
                                        <GitBranch class="size-3 dark:text-[#525252]" />
                                        <span class="text-xs dark:text-[#525252]">{{ project.default_branch || 'main' }}</span>
                                        <span class="text-xs dark:text-[#525252]">·</span>
                                        <span class="text-xs dark:text-[#525252]">{{ formatTimestamp(project.updated_at) }}</span>
                                    </div>
                                </div>
                            </div>
                            <ExternalLink
                                class="size-3.5 dark:text-[#525252] opacity-0 group-hover:opacity-100 transition-opacity mt-2"
                                @click.stop="openProject(project)"
                            />
                        </div>
                    </button>

                    <!-- Failed Projects -->
                    <div v-if="failedProjects.length > 0" class="pt-3">
                        <span class="text-xs font-medium dark:text-[#525252] px-1">Failed</span>
                        <button
                            v-for="project in failedProjects"
                            :key="project.id"
                            class="w-full text-left px-3 py-2.5 rounded-md transition-all duration-150 dark:hover:bg-[#1A1A1A] opacity-60"
                        >
                            <div class="flex items-center gap-2.5">
                                <div class="size-8 rounded-full bg-gradient-to-br from-red-500 to-red-600 flex items-center justify-center">
                                    <span class="text-xs font-bold text-white">{{ project.name.substring(0, 1).toUpperCase() }}</span>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium truncate dark:text-[#A3A3A3]">{{ project.name }}</p>
                                    <p class="text-xs dark:text-[#EF4444]">Setup failed</p>
                                </div>
                            </div>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="p-3 border-t dark:border-[#1F1F1F]">
                <Link :href="create()">
                    <Button variant="ghost" class="w-full justify-start gap-2 dark:text-[#737373] dark:hover:text-[#A3A3A3] dark:hover:bg-[#1A1A1A]">
                        <Plus class="size-4" />
                        <span class="text-sm">Add Project</span>
                    </Button>
                </Link>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 flex flex-col overflow-hidden">
            <!-- Content Area -->
            <div class="flex flex-1 flex-col h-screen dark:bg-[#0D0D0D]">
                <!-- Header -->
                <header class="flex items-center justify-between px-6 py-4 border-b dark:border-[#1F1F1F]">
                    <div class="flex items-center gap-2">
                        <button class="flex items-center gap-2 text-sm font-medium dark:text-[#E5E5E5] hover:text-primary transition-colors">
                            <Terminal class="size-4 dark:text-primary" />
                            {{ selectedProject?.name || 'AI Builder' }}
                            <ChevronDown class="size-4 dark:text-[#525252]" />
                        </button>
                    </div>
                    <div class="flex items-center gap-3">
                        <div v-if="selectedProject" class="flex items-center gap-1.5 px-2.5 py-1 rounded text-xs dark:bg-[#1A1A1A] dark:text-[#A3A3A3] dark:border dark:border-[#262626]">
                            <GitBranch class="size-3" />
                            {{ selectedProject.default_branch || 'main' }}
                        </div>
                        <Button v-if="selectedProject" variant="ghost" size="icon-sm" class="dark:text-[#525252] dark:hover:text-[#A3A3A3]" @click="openProject(selectedProject)">
                            <ExternalLink class="size-4" />
                        </Button>
                        <Link v-if="selectedProject" :href="show(selectedProject.id).url">
                            <Button variant="outline" size="sm" class="text-xs dark:border-[#333] dark:text-[#A3A3A3] dark:hover:bg-[#1A1A1A]">
                                Open Project
                                <Terminal class="size-3 ml-1.5" />
                            </Button>
                        </Link>
                    </div>
                </header>

                <!-- Messages Area -->
                <div class="flex-1 overflow-y-auto">
                    <div v-if="projects.length === 0" class="flex items-center justify-center h-full p-6">
                        <EmptyProjectsState />
                    </div>
                    <div v-else class="max-w-4xl mx-auto py-6 px-6 space-y-6">
                        <ChatMessage
                            v-for="message in projectMessages"
                            :key="message.id"
                            :role="message.role"
                            :content="message.content"
                            :timestamp="message.timestamp"
                        />
                    </div>
                </div>

                <!-- Input Area -->
                <div v-if="selectedProject" class="border-t dark:border-[#1F1F1F]">
                    <div class="max-w-4xl mx-auto p-6">
                        <ChatInput @send="handleSendMessage" :placeholder="`Message AI about ${selectedProject.name}...`" />
                    </div>
                </div>
                <div v-else-if="projects.length > 0" class="border-t dark:border-[#1F1F1F]">
                    <div class="max-w-4xl mx-auto p-6">
                        <div class="flex items-center justify-center gap-3 py-4 text-sm dark:text-[#525252]">
                            <Sparkles class="size-4" />
                            <span>Select a project from the sidebar to start chatting</span>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</template>
