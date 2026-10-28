import type { ChatApi } from './api';
import type { ChatMarkdown, ChatRequestLock, ChatSanitizer, ChatState, ChatTransport } from './services';
import type { ChatStreamParser } from './stream';

export interface ChatServiceToken<Service> {
    readonly name: string;
    // Never assigned; it only carries the service type so `services.get(TOKEN)` is typed without a cast.
    readonly serviceType?: Service;
}

export type ChatServiceClass<Service> = new () => Service;

export interface ChatServiceDirectory {
    get<Service>(token: ChatServiceToken<Service>): Service;
}

export interface ChatServiceEnvironment {
    readonly chat: HTMLElement;
    readonly services: ChatServiceDirectory;
}

export interface ChatServiceProvision<Service = unknown> {
    readonly token: ChatServiceToken<Service>;
    readonly class: ChatServiceClass<Service>;
}

export interface ChatService {
    attach?(environment: ChatServiceEnvironment): void;
}

const createServiceToken = <Service>(name: string): ChatServiceToken<Service> => Object.freeze({ name: name });

export const TRANSPORT = createServiceToken<ChatTransport>('transport');
export const STREAM_PARSER = createServiceToken<ChatStreamParser>('stream-parser');
export const SANITIZER = createServiceToken<ChatSanitizer>('sanitizer');
export const MARKDOWN = createServiceToken<ChatMarkdown>('markdown');
export const STATE = createServiceToken<ChatState>('state');
export const REQUEST_LOCK = createServiceToken<ChatRequestLock>('request-lock');
export const API = createServiceToken<ChatApi>('api');

export const SERVICE_TOKENS: readonly ChatServiceToken<unknown>[] = [
    TRANSPORT,
    STREAM_PARSER,
    SANITIZER,
    MARKDOWN,
    STATE,
    REQUEST_LOCK,
    API,
];
