export interface AuditLogEntry {
    path: string;
    chunk_id: string;
    start_line: number;
    end_line: number;
    sha1: string;
    quoted_snippets: string[];
    relevance_score: number;
}

export interface AskAIResponse {
    answer_markdown: string;
    audit_log: AuditLogEntry[];
    missing_details: string[];
    confidence: 'high' | 'medium' | 'low';
    meta: {
        chunks_used: number;
        total_content_length: number;
        processing_note: string | null;
    };
}

export interface AskAIContext {
    ready: boolean;
    status?: string;
    message?: string;
    project?: {
        id: number;
        repo_full_name: string;
        scanned_at: string | null;
    };
    stats?: {
        files_count: number;
        chunks_count: number;
        total_lines: number;
    };
    stack?: {
        framework: string | null;
        frontend: string[];
        features: string[];
    };
    hints?: {
        sample_paths: string[];
        symbols: string[];
    };
    example_questions?: string[];
}

export interface ChatMessage {
    id: string;
    type: 'user' | 'assistant';
    content: string;
    timestamp: Date;
    response?: AskAIResponse;
    isLoading?: boolean;
    error?: string;
}

export interface AskAIState {
    messages: ChatMessage[];
    isLoading: boolean;
    context: AskAIContext | null;
    error: string | null;
}
