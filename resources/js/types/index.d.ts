import { InertiaLinkProps } from '@inertiajs/vue3';
import type { LucideIcon } from 'lucide-vue-next';

export interface Auth {
    user: User;
}

export interface BreadcrumbItem {
    title: string;
    href: string;
}

export interface NavItem {
    title: string;
    href: NonNullable<InertiaLinkProps['href']>;
    icon?: LucideIcon;
    isActive?: boolean;
}

export type AppPageProps<
    T extends Record<string, unknown> = Record<string, unknown>,
> = T & {
    name: string;
    quote: { message: string; author: string };
    auth: Auth;
    sidebarOpen: boolean;
};

export interface User {
    id: number;
    name: string;
    email: string;
    avatar?: string;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
}

export type BreadcrumbItemType = BreadcrumbItem;

export interface Repository {
    id: number;
    full_name: string;
    name: string;
    owner: string;
    private: boolean;
    description: string | null;
    default_branch: string;
    updated_at?: string;
    html_url?: string;
}

export interface StackInfo {
    framework?: string | null;
    framework_version?: string | null;
    php_version?: string | null;
    frontend?: string[];
    css?: string[];
    build_tools?: string[];
    testing?: string[];
    database?: string[];
    features?: string[];
    vue_version?: string | null;
    react_version?: string | null;
    livewire_version?: string | null;
}

export interface Project {
    id: number;
    repo_full_name: string;
    name?: string;
    owner?: string;
    default_branch: string;
    status: 'pending' | 'scanning' | 'ready' | 'failed';
    current_stage?: string | null;
    stage_percent?: number;
    scanned_at?: string | null;
    last_commit_sha?: string | null;
    last_error?: string | null;
    total_files?: number;
    total_lines?: number;
    total_size_bytes?: number;
    stack_info?: StackInfo | null;
    github_url?: string;
    created_at: string;
    updated_at?: string;
    description?: string | null;
    files_count?: number;
}

export interface PipelineStage {
    id: string;
    name: string;
    weight: number;
}

export interface ScanStatus {
    status: string;
    current_stage: string | null;
    percent: number;
    steps: ScanStep[];
    error: string | null;
    scanned_at: string | null;
    last_commit_sha: string | null;
}

export interface ScanStep {
    id: string;
    label: string;
    completed: boolean;
    current: boolean;
}

export interface DirectorySummary {
    directory: string;
    file_count: number;
    total_size: number;
    total_lines: number;
}

export interface ExtensionStats {
    extension: string;
    count: number;
    total_size: number;
    total_lines: number;
}
