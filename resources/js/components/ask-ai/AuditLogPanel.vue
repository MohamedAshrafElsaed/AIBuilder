<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import type { AuditLogEntry } from '@/types/askai';
import {
    ChevronDown,
    ChevronRight,
    Code,
    ExternalLink,
    FileCode,
    ShieldCheck,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';

interface Props {
    auditLog: AuditLogEntry[];
    confidence: 'high' | 'medium' | 'low';
    projectId: number;
    githubUrl?: string;
}

const props = defineProps<Props>();

const isExpanded = ref(false);
const expandedEntries = ref<Set<string>>(new Set());

const toggleEntry = (chunkId: string) => {
    if (expandedEntries.value.has(chunkId)) {
        expandedEntries.value.delete(chunkId);
    } else {
        expandedEntries.value.add(chunkId);
    }
};

const confidenceConfig = computed(() => {
    const configs = {
        high: {
            label: 'High Confidence',
            color: 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400',
            icon: ShieldCheck,
        },
        medium: {
            label: 'Medium Confidence',
            color: 'bg-amber-500/10 text-amber-600 dark:text-amber-400',
            icon: ShieldCheck,
        },
        low: {
            label: 'Low Confidence',
            color: 'bg-red-500/10 text-red-600 dark:text-red-400',
            icon: ShieldCheck,
        },
    };
    return configs[props.confidence];
});

const uniqueFiles = computed(() => {
    const files = new Set(props.auditLog.map((e) => e.path));
    return files.size;
});

const formatLineRange = (entry: AuditLogEntry) => {
    return entry.start_line === entry.end_line
        ? `L${entry.start_line}`
        : `L${entry.start_line}-${entry.end_line}`;
};

const getGitHubLink = (entry: AuditLogEntry) => {
    if (!props.githubUrl) return null;
    const base = props.githubUrl.replace(/\/$/, '');
    return `${base}/blob/main/${entry.path}#L${entry.start_line}-L${entry.end_line}`;
};
</script>

<template>
    <div class="rounded-lg border bg-card">
        <button
            class="flex w-full items-center justify-between px-4 py-3 text-left hover:bg-muted/30"
            @click="isExpanded = !isExpanded"
        >
            <div class="flex items-center gap-3">
                <FileCode class="size-4 text-muted-foreground" />
                <span class="text-sm font-medium">Audit Log</span>
                <Badge variant="secondary" class="text-xs">
                    {{ auditLog.length }} chunks from {{ uniqueFiles }} files
                </Badge>
                <Badge :class="confidenceConfig.color" class="text-xs">
                    <component
                        :is="confidenceConfig.icon"
                        class="mr-1 size-3"
                    />
                    {{ confidenceConfig.label }}
                </Badge>
            </div>
            <ChevronDown
                v-if="isExpanded"
                class="size-4 text-muted-foreground"
            />
            <ChevronRight v-else class="size-4 text-muted-foreground" />
        </button>

        <div
            v-if="isExpanded"
            class="max-h-96 overflow-y-auto border-t px-4 py-3"
        >
            <div class="space-y-2">
                <div
                    v-for="entry in auditLog"
                    :key="entry.chunk_id"
                    class="rounded-lg border bg-muted/20"
                >
                    <button
                        class="flex w-full items-center justify-between px-3 py-2 text-left hover:bg-muted/30"
                        @click="toggleEntry(entry.chunk_id)"
                    >
                        <div class="flex items-center gap-2">
                            <Code class="size-3.5 text-muted-foreground" />
                            <span class="font-mono text-xs text-foreground">
                                {{ entry.path }}
                            </span>
                            <span class="text-xs text-muted-foreground">
                                {{ formatLineRange(entry) }}
                            </span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span
                                class="text-xs text-muted-foreground"
                                :title="`Relevance score: ${entry.relevance_score}`"
                            >
                                Score: {{ entry.relevance_score.toFixed(1) }}
                            </span>
                            <a
                                v-if="getGitHubLink(entry)"
                                :href="getGitHubLink(entry)!"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="text-muted-foreground hover:text-foreground"
                                @click.stop
                            >
                                <ExternalLink class="size-3.5" />
                            </a>
                            <ChevronDown
                                v-if="expandedEntries.has(entry.chunk_id)"
                                class="size-3.5 text-muted-foreground"
                            />
                            <ChevronRight
                                v-else
                                class="size-3.5 text-muted-foreground"
                            />
                        </div>
                    </button>

                    <div
                        v-if="
                            expandedEntries.has(entry.chunk_id) &&
                            entry.quoted_snippets.length > 0
                        "
                        class="border-t px-3 py-2"
                    >
                        <p class="mb-2 text-xs text-muted-foreground">
                            Referenced snippets:
                        </p>
                        <div
                            v-for="(snippet, idx) in entry.quoted_snippets"
                            :key="idx"
                            class="mb-2 rounded bg-muted/50 p-2"
                        >
                            <pre
                                class="overflow-x-auto text-xs text-muted-foreground"
                            ><code>{{ snippet }}</code></pre>
                        </div>
                    </div>

                    <div
                        v-else-if="
                            expandedEntries.has(entry.chunk_id) &&
                            entry.quoted_snippets.length === 0
                        "
                        class="border-t px-3 py-2"
                    >
                        <p class="text-xs text-muted-foreground italic">
                            This chunk provided context but no specific snippets
                            were quoted.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
