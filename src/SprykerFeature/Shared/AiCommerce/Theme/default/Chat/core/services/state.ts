import type { ChatRecordedMessage, ChatState } from '../contracts/services';

const HEXADECIMAL_RADIX = 16;
const RANDOM_FRACTION_PREFIX_LENGTH = 2;

const generateConversationReference = (): string => {
    if (typeof crypto !== 'undefined' && typeof crypto.randomUUID === 'function') {
        return crypto.randomUUID();
    }

    return `${Date.now().toString(HEXADECIMAL_RADIX)}-${Math.random()
        .toString(HEXADECIMAL_RADIX)
        .slice(RANDOM_FRACTION_PREFIX_LENGTH)}`;
};

export class ChatConversationState implements ChatState {
    private reference = generateConversationReference();
    private recordedMessages: ChatRecordedMessage[] = [];

    get conversationReference(): string {
        return this.reference;
    }

    get messages(): readonly ChatRecordedMessage[] {
        return [...this.recordedMessages];
    }

    adoptServerReference(conversationReference: string): void {
        if (conversationReference.trim() !== '') {
            this.reference = conversationReference;
        }
    }

    restore(conversationReference: string, messages: readonly ChatRecordedMessage[]): void {
        this.reference = conversationReference;
        this.recordedMessages = [...messages];
    }

    record(message: ChatRecordedMessage): void {
        this.recordedMessages.push({ role: message.role, content: message.content });
    }

    truncate(count: number): void {
        this.recordedMessages = this.recordedMessages.slice(0, Math.max(0, count));
    }

    startNew(): void {
        this.reference = generateConversationReference();
        this.recordedMessages = [];
    }
}
