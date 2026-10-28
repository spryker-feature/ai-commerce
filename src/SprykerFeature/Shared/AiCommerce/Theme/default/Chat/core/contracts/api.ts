export interface ChatPromptPayload {
    readonly message: string;
    readonly conversationReference: string;
    readonly [field: string]: unknown;
}

export interface ChatConversationSummary {
    readonly conversationReference: string;
    readonly name: string;
    readonly agent?: string | null;
    readonly updatedAt: number;
}

export interface ChatConversationList {
    readonly conversations: readonly ChatConversationSummary[];
    readonly availableAgents?: unknown;
    readonly userSelectedAgent?: string | null;
}

export interface ChatStoredAttachment {
    readonly name: string;
}

export interface ChatStoredToolInvocation {
    readonly name: string;
    readonly result: string;
}

export interface ChatStoredMessage {
    readonly role: string;
    readonly content: string;
    readonly attachments?: readonly ChatStoredAttachment[];
    readonly toolInvocations?: readonly ChatStoredToolInvocation[];
}

export interface ChatApi {
    prompt(payload: ChatPromptPayload, signal: AbortSignal): Promise<ReadableStream<Uint8Array>>;
    conversations(): Promise<ChatConversationList>;
    messages(conversationReference: string): Promise<readonly ChatStoredMessage[]>;
    delete(conversationReferences: readonly string[]): Promise<number>;
}

export class ChatHttpError extends Error {
    name = 'ChatHttpError';
    // 0 when the failure is not an HTTP status: a redirect, a body that is not a stream, a body that is not JSON.
    status = 0;
}

export class ChatSessionExpiredError extends ChatHttpError {
    name = 'ChatSessionExpiredError';
}

export const createChatHttpError = (
    message: string,
    status: number = 0,
    errorClass: typeof ChatHttpError = ChatHttpError,
): ChatHttpError => {
    const error = new errorClass(message);

    error.status = status;

    return error;
};
