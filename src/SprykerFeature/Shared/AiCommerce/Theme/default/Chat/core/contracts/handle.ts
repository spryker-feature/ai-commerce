// Other bundles import this file with `import type` only, so no second copy of the core gets bundled.

import type {
    ChatConversationControl,
    ChatEventListener,
    ChatEventName,
    ChatEventSubscriber,
    ChatPanelControl,
} from './context';

export interface ChatHandle {
    readonly element: HTMLElement;
    send(message: string): Promise<boolean>;
    stop(): void;
    reset(): void;
    open(): void;
    close(): void;
    on<Name extends ChatEventName>(name: Name, listener: ChatEventListener<Name>): () => void;
}

export interface ChatHandleParts {
    readonly element: HTMLElement;
    readonly conversation: Pick<ChatConversationControl, 'send' | 'stop' | 'reset'>;
    readonly panel: ChatPanelControl;
    readonly events: ChatEventSubscriber;
}

export const createChatHandle = (parts: ChatHandleParts): ChatHandle =>
    Object.freeze({
        element: parts.element,
        send: (message: string): Promise<boolean> => parts.conversation.send(message),
        stop: (): void => parts.conversation.stop(),
        reset: (): void => parts.conversation.reset(),
        open: (): void => parts.panel.open(),
        close: (): void => parts.panel.close(),
        on: <Name extends ChatEventName>(name: Name, listener: ChatEventListener<Name>): (() => void) =>
            parts.events.on(name, listener),
    });
