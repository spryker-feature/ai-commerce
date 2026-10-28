import type { ChatHandle } from './handle';
import type { ChatServiceClass, ChatServiceProvision, ChatServiceToken } from './tokens';

export const CHAT_TAG_NAME = 'spryker-chat';
export const FEATURE_TAG_PREFIX = 'chat-';

const SOURCE_FILE = 'Shared/AiCommerce/Theme/default/Chat/core/contracts/access.ts';
const SESSION_RELOAD_STORAGE_KEY = 'spryker-chat:session-expired-reload-at';
const SESSION_RELOAD_INTERVAL_MILLISECONDS = 30000;

export const CHAT_EVENT = {
    featureConnected: 'spryker-chat:feature-connected',
    provide: 'spryker-chat:provide',
    init: 'spryker-chat:init',
    ready: 'spryker-chat:ready',
    request: 'spryker-chat:request',
    sessionExpired: 'spryker-chat:session-expired',
} as const;

export interface ChatRequestDetail {
    readonly respond: (handle: ChatHandle) => void;
    readonly reject?: (error: Error) => void;
}

export interface ChatReadyDetail {
    readonly handle: ChatHandle;
    readonly features: readonly string[];
}

// Two entries on one Back Office page may each carry a copy; a second define would throw NotSupportedError.
export const defineElementOnce = (tagName: string, elementClass: CustomElementConstructor): void => {
    if (customElements.get(tagName) !== undefined) {
        return;
    }

    customElements.define(tagName, elementClass);
};

export const announceFeature = (feature: HTMLElement): void => {
    feature.dispatchEvent(
        new CustomEvent(CHAT_EVENT.featureConnected, { bubbles: true, composed: true, detail: feature }),
    );
};

// Call from a `spryker-chat:init` listener or before the chat initializes: services resolve once, after that event.
export const provideChatService = <Service>(
    target: Element,
    token: ChatServiceToken<Service>,
    serviceClass: ChatServiceClass<Service>,
): void => {
    const detail: ChatServiceProvision<Service> = { token: token, class: serviceClass };

    target.dispatchEvent(new CustomEvent(CHAT_EVENT.provide, { bubbles: true, composed: true, detail: detail }));
};

// A chat that failed to start, or stops first, calls `reject` instead (reports the error when there is none).
export const requestChat = (
    target: Element,
    respond: (handle: ChatHandle) => void,
    reject?: (error: Error) => void,
): void => {
    const chat = target.closest(CHAT_TAG_NAME);

    if (chat === null) {
        throw new Error(
            `requestChat() was called for <${target.localName}>, which is neither a <${CHAT_TAG_NAME}> element nor ` +
                `rendered inside one (reported by ${SOURCE_FILE}). Pass the <${CHAT_TAG_NAME}> element itself or ` +
                `an element inside it.`,
        );
    }

    const detail: ChatRequestDetail = { respond: respond, reject: reject };

    // The chat listens only once its bundle defines the element; an earlier request would reach nobody.
    customElements
        .whenDefined(CHAT_TAG_NAME)
        .then(() => chat.dispatchEvent(new CustomEvent(CHAT_EVENT.request, { detail: detail })));
};

const hasReloadedRecently = (): boolean => {
    try {
        const reloadedAt = Number(sessionStorage.getItem(SESSION_RELOAD_STORAGE_KEY) ?? 0);

        return Date.now() - reloadedAt < SESSION_RELOAD_INTERVAL_MILLISECONDS;
    } catch {
        return true;
    }
};

const rememberReload = (): void => {
    try {
        sessionStorage.setItem(SESSION_RELOAD_STORAGE_KEY, String(Date.now()));
    } catch {
        // hasReloadedRecently() already treats an unreadable storage as a recent reload.
    }
};

// Returns false within 30 seconds of the last reload, so the caller shows an error instead of reloading in a loop.
export const raiseSessionExpired = (chat: HTMLElement): boolean => {
    if (hasReloadedRecently()) {
        return false;
    }

    const isUnhandled = chat.dispatchEvent(
        new CustomEvent(CHAT_EVENT.sessionExpired, { bubbles: true, composed: true, cancelable: true }),
    );

    if (isUnhandled) {
        rememberReload();
        window.location.reload();
    }

    return true;
};
