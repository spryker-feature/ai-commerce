import type { ChatPromptPayload, ChatStoredMessage } from './api';
import type { ChatBlockResolver, ChatZoneDirectory } from './blocks';
import type { ChatMessageRole, ChatState } from './services';
import type { ChatStreamEvent } from './stream';
import type { ChatServiceDirectory, ChatServiceProvision } from './tokens';

export const CHAT_TURN_STATE = {
    idle: 'idle',
    sending: 'sending',
    streaming: 'streaming',
    done: 'done',
    error: 'error',
} as const;

export type ChatTurnState = (typeof CHAT_TURN_STATE)[keyof typeof CHAT_TURN_STATE];

export interface ChatFeature extends HTMLElement {
    // A missing tag or a cycle is reported at start-up and the feature stays uninitialized.
    readonly requires?: readonly string[];
    readonly provides?: readonly ChatServiceProvision[];
    initialize(context: ChatFeatureContext): void;
    dispose(): void;
}

export interface ChatFeatureDirectory {
    list(): readonly ChatFeature[];
    find(tagName: string): ChatFeature | null;
}

export interface ChatTurnStateChange {
    readonly state: ChatTurnState;
    readonly previousState: ChatTurnState;
    readonly message: string;
}

export interface ChatTurnEnd {
    readonly state: ChatTurnState;
    readonly message: string;
    readonly answer: string;
}

export interface ChatRenderedMessage {
    readonly role: ChatMessageRole;
    readonly element: HTMLElement;
    readonly text: string;
}

export interface ChatConversationReset {
    readonly conversationReference: string;
}

export interface ChatConversationLoaded {
    readonly conversationReference: string;
    readonly messages: readonly ChatStoredMessage[];
}

export interface ChatStoredMessageEvent {
    readonly message: ChatStoredMessage;
}

export interface ChatPanelToggle {
    readonly isOpen: boolean;
}

export interface ChatPanelExpand {
    readonly isExpanded: boolean;
}

export interface ChatBusyChange {
    readonly isBusy: boolean;
}

export interface ChatEventMap {
    stream: ChatStreamEvent;
    'turn:state': ChatTurnStateChange;
    'turn:end': ChatTurnEnd;
    'message:rendered': ChatRenderedMessage;
    'message:stored': ChatStoredMessageEvent;
    'conversation:reset': ChatConversationReset;
    'conversation:loaded': ChatConversationLoaded;
    'panel:toggle': ChatPanelToggle;
    'panel:expand': ChatPanelExpand;
    busy: ChatBusyChange;
}

export type ChatEventName = keyof ChatEventMap;

export type ChatEventListener<Name extends ChatEventName> = (detail: ChatEventMap[Name]) => void;

export interface ChatEventSubscriber {
    on<Name extends ChatEventName>(name: Name, listener: ChatEventListener<Name>): () => void;
}

export type ChatPromptContributor = () => Readonly<Record<string, unknown>>;

export interface ChatSendOptions {
    readonly renderUserMessage?: boolean;
    readonly fields?: Readonly<Record<string, unknown>>;
}

export interface ChatRegenerateOptions {
    readonly fields?: Readonly<Record<string, unknown>>;
}

export interface ChatConversationControl {
    readonly turnState: ChatTurnState;
    // Resolves to false when another request holds the request lock.
    send(message: string, options?: ChatSendOptions): Promise<boolean>;
    stop(): void;
    reset(): void;
    // Resolves to whether the conversation is now shown: false when the lock is held or loading failed (reported).
    load(conversationReference: string): Promise<boolean>;
    contribute(contributor: ChatPromptContributor): void;
    createPayload(message: string): ChatPromptPayload;
    // Resolves to false when the lock is held or the element is not a user message of this conversation (reported).
    regenerate(userMessage: Element, options?: ChatRegenerateOptions): Promise<boolean>;
}

export interface ChatTranscriptControl {
    readonly element: HTMLElement;
    readonly scroller: HTMLElement;
    append(element: Element): void;
    currentLoading(): HTMLElement | null;
    scrollToEnd(): void;
    removeAfter(element: Element): void;
    captureFollow(): () => void;
}

export interface ChatComposerControl {
    readonly input: HTMLTextAreaElement;
    read(): string;
    write(text: string): void;
    clear(): void;
    focus(): void;
}

export interface ChatPanelControl {
    isOpen(): boolean;
    open(): void;
    restore(): void;
    close(): void;
    toggle(): void;
    isExpanded(): boolean;
    setExpanded(isExpanded: boolean): void;
    showView(view: HTMLElement): void;
    hideView(): void;
}

export interface ChatFeatureContext {
    readonly chat: HTMLElement;
    readonly features: ChatFeatureDirectory;
    readonly services: ChatServiceDirectory;
    readonly blocks: ChatBlockResolver;
    readonly zones: ChatZoneDirectory;
    readonly state: ChatState;
    readonly events: ChatEventSubscriber;
    readonly conversation: ChatConversationControl;
    readonly transcript: ChatTranscriptControl;
    readonly composer: ChatComposerControl;
    readonly panel: ChatPanelControl;
}
