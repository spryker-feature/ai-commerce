export type ChatHttpMethod = 'GET' | 'POST' | 'DELETE';

export interface ChatTransportRequest {
    readonly url: string;
    readonly method: ChatHttpMethod;
    readonly query?: Readonly<Record<string, string>>;
    readonly body?: unknown;
    readonly headers?: Readonly<Record<string, string>>;
    readonly signal?: AbortSignal;
}

export interface ChatTransport {
    send(request: ChatTransportRequest): Promise<Response>;
}

export interface ChatSanitizer {
    sanitize(html: string): DocumentFragment;
}

export interface ChatMarkdownStream {
    append(delta: string): void;
    finish(): void;
}

export interface ChatMarkdownStreamOptions {
    readonly onBeforeRender?: () => void;
    readonly onRender?: () => void;
}

export interface ChatMarkdown {
    render(target: HTMLElement, text: string): void;
    stream(target: HTMLElement, options?: ChatMarkdownStreamOptions): ChatMarkdownStream;
}

export const CHAT_MESSAGE_ROLE = {
    user: 'user',
    assistant: 'ai',
    toolResult: 'tool_result',
    error: 'error',
} as const;

export type ChatMessageRole = (typeof CHAT_MESSAGE_ROLE)[keyof typeof CHAT_MESSAGE_ROLE];

export interface ChatRecordedMessage {
    readonly role: string;
    readonly content: string;
}

export interface ChatState {
    readonly conversationReference: string;
    readonly messages: readonly ChatRecordedMessage[];
    adoptServerReference(conversationReference: string): void;
    restore(conversationReference: string, messages: readonly ChatRecordedMessage[]): void;
    record(message: ChatRecordedMessage): void;
    truncate(count: number): void;
    startNew(): void;
}

export interface ChatRequestLock {
    isBusy(): boolean;
    // Resolves to false without running while another operation holds the lock.
    run(operation: () => Promise<void>): Promise<boolean>;
    subscribe(listener: (isBusy: boolean) => void): () => void;
}
