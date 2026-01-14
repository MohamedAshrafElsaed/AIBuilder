<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { Link, usePage } from '@inertiajs/vue3';
import {
    ChevronDown,
    Filter,
    GitBranch,
    Image as ImageIcon,
    Plus,
    Search,
    Sparkles,
    ExternalLink
} from 'lucide-vue-next';
import { ref, computed } from 'vue';

interface Session {
    id: number;
    title: string;
    timestamp: string;
    additions?: number;
    deletions?: number;
    isActive?: boolean;
}

interface Props {
    sessions?: Session[];
    currentSessionId?: number;
    projectName?: string;
    branchName?: string;
    environment?: string;
}

const props = withDefaults(defineProps<Props>(), {
    sessions: () => [],
    projectName: 'AI Builder',
    branchName: 'main',
    environment: 'Default',
});

const page = usePage();
const user = computed(() => page.props.auth?.user);

const searchQuery = ref('');

// Demo sessions if none provided
const displaySessions = computed(() => {
    if (props.sessions.length > 0) return props.sessions;
    return [
        { id: 1, title: 'Fix critical production bugs', timestamp: '3:42 pm', additions: 0, deletions: 27, isActive: false },
        { id: 2, title: 'Implement large features capability', timestamp: 'Tue', isActive: true },
        { id: 3, title: 'Implement a small feature', timestamp: 'Tue', isActive: false },
    ];
});

const formatStats = (additions?: number, deletions?: number) => {
    const parts = [];
    if (additions !== undefined) parts.push(`+${additions}`);
    if (deletions !== undefined) parts.push(`-${deletions}`);
    return parts.join(' ');
};
</script>

<template>
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
                    placeholder="Ask Claude to write code..."
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

        <!-- Project Selector -->
        <div class="px-3 pb-3">
            <div class="flex items-center gap-2 p-2 rounded-md dark:bg-[#1A1A1A] dark:border dark:border-[#262626]">
                <div class="flex items-center gap-2 flex-1">
                    <div class="flex items-center gap-1.5 px-2 py-1 rounded dark:bg-[#262626]">
                        <div class="size-4 rounded-full bg-gradient-to-br from-orange-500 to-amber-500 flex items-center justify-center">
                            <span class="text-[8px] font-bold text-white">{{ projectName.substring(0, 1) }}</span>
                        </div>
                        <span class="text-xs font-medium dark:text-[#E5E5E5]">{{ projectName }}</span>
                    </div>
                    <div class="flex items-center gap-1 px-2 py-1 rounded dark:bg-[#262626]">
                        <GitBranch class="size-3 dark:text-[#A3A3A3]" />
                        <span class="text-xs dark:text-[#A3A3A3]">{{ branchName }}</span>
                    </div>
                    <div class="flex items-center gap-1 px-2 py-1 rounded dark:bg-[#262626]">
                        <span class="text-xs dark:text-[#A3A3A3]">{{ environment }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sessions Header -->
        <div class="flex items-center justify-between px-4 py-2">
            <span class="text-xs font-medium dark:text-[#737373]">Sessions</span>
            <Button variant="ghost" size="icon-sm" class="size-6 dark:text-[#525252] dark:hover:text-[#A3A3A3]">
                <Filter class="size-3.5" />
            </Button>
        </div>

        <!-- Sessions List -->
        <div class="flex-1 overflow-y-auto px-2">
            <div class="space-y-0.5">
                <button
                    v-for="session in displaySessions"
                    :key="session.id"
                    :class="[
                        'w-full text-left px-3 py-2.5 rounded-md transition-all duration-150 group',
                        session.isActive
                            ? 'dark:bg-[#1A1A1A] border-l-2 border-l-primary rounded-l-none'
                            : 'dark:hover:bg-[#1A1A1A]'
                    ]"
                >
                    <div class="flex items-start justify-between gap-2">
                        <div class="flex-1 min-w-0">
                            <p :class="[
                                'text-sm font-medium truncate',
                                session.isActive ? 'dark:text-[#E5E5E5]' : 'dark:text-[#A3A3A3]'
                            ]">
                                {{ session.title }}
                            </p>
                            <div class="flex items-center gap-2 mt-0.5">
                                <span class="text-xs dark:text-[#525252]">{{ session.timestamp }}</span>
                                <span
                                    v-if="session.additions !== undefined || session.deletions !== undefined"
                                    class="text-xs"
                                >
                                    <span v-if="session.additions !== undefined" class="text-[#22C55E]">+{{ session.additions }}</span>
                                    <span v-if="session.deletions !== undefined" class="text-[#EF4444] ml-1">-{{ session.deletions }}</span>
                                </span>
                            </div>
                        </div>
                        <ExternalLink
                            v-if="session.additions !== undefined || session.deletions !== undefined"
                            class="size-3.5 dark:text-[#525252] opacity-0 group-hover:opacity-100 transition-opacity mt-1"
                        />
                    </div>
                </button>
            </div>
        </div>

        <!-- Footer -->
        <div class="p-3 border-t dark:border-[#1F1F1F]">
            <Button variant="ghost" class="w-full justify-start gap-2 dark:text-[#737373] dark:hover:text-[#A3A3A3] dark:hover:bg-[#1A1A1A]">
                <Plus class="size-4" />
                <span class="text-sm">New Session</span>
            </Button>
        </div>
    </aside>
</template>
