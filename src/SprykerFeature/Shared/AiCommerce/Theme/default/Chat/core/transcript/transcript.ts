import type { ChatBlockTemplate } from '../contracts/blocks';
import type { ChatTranscriptControl } from '../contracts/context';
import type { ChatEventBus } from '../contracts/event-bus';
import { CHAT_MESSAGE_ROLE } from '../contracts/services';
import type { ChatMessageRole } from '../contracts/services';

const BUSY_ATTRIBUTE = 'aria-busy';
const FOLLOW_DISTANCE_PROPERTY = '--chat-scroll-follow-distance';
const DEFAULT_FOLLOW_DISTANCE_PIXELS = 48;

export interface ChatTranscriptTemplates {
    readonly messageUser: ChatBlockTemplate<'text'>;
    readonly messageAssistant: ChatBlockTemplate<'content'>;
    readonly messageError: ChatBlockTemplate<'text'>;
    readonly loading: ChatBlockTemplate<never>;
    readonly retry: ChatBlockTemplate<never>;
}

export interface ChatTranscriptElements {
    readonly transcript: HTMLElement;
    readonly scroller: HTMLElement;
    readonly welcomeZone: HTMLElement | null;
}

export interface ChatAssistantMessage {
    readonly root: HTMLElement;
    readonly content: HTMLElement;
}

export interface ChatTranscript extends ChatTranscriptControl {
    userMessages(): readonly HTMLElement[];
    renderUserMessage(text: string): HTMLElement;
    renderAssistantMessage(): ChatAssistantMessage;
    renderErrorMessage(text?: string): HTMLElement;
    renderRetry(onRetry: () => boolean): HTMLElement;
    showLoading(): HTMLElement;
    hideLoading(): void;
    setBusy(isBusy: boolean): void;
    clear(): void;
}

export interface ChatTranscriptParts {
    readonly elements: ChatTranscriptElements;
    readonly templates: ChatTranscriptTemplates;
    readonly events: ChatEventBus;
    readonly onFeatureContent: () => void;
}

const readFollowDistance = (scroller: HTMLElement): number => {
    const value = Number.parseFloat(getComputedStyle(scroller).getPropertyValue(FOLLOW_DISTANCE_PROPERTY));

    return Number.isNaN(value) ? DEFAULT_FOLLOW_DISTANCE_PIXELS : value;
};

export const createTranscript = (parts: ChatTranscriptParts): ChatTranscript => {
    const { transcript, scroller, welcomeZone } = parts.elements;
    let loading: HTMLElement | null = null;
    let userMessageRoots: HTMLElement[] = [];
    let followDistance: number | null = null;

    const syncWelcomeZone = (): void => {
        if (welcomeZone !== null) {
            welcomeZone.hidden = loading !== null || userMessageRoots.some((root) => root.isConnected);
        }
    };

    const scrollToEnd = (): void => {
        scroller.scrollTop = scroller.scrollHeight;
    };

    const isAtEnd = (): boolean => {
        if (followDistance === null) {
            followDistance = readFollowDistance(scroller);
        }

        return scroller.scrollHeight - scroller.scrollTop - scroller.clientHeight <= followDistance;
    };

    const captureFollow = (): (() => void) => {
        const wasAtEnd = isAtEnd();

        return () => {
            if (wasAtEnd) {
                scrollToEnd();
            }
        };
    };

    const insert = (element: Element): void => {
        const follow = captureFollow();

        if (loading !== null && loading.parentElement === transcript) {
            transcript.insertBefore(element, loading);
        } else {
            transcript.append(element);
        }

        syncWelcomeZone();
        follow();
    };

    const announce = (role: ChatMessageRole, element: HTMLElement, text: string): void => {
        parts.events.emit('message:rendered', { role: role, element: element, text: text });
    };

    const hideLoading = (): void => {
        loading?.remove();
        loading = null;
        syncWelcomeZone();
    };

    return {
        element: transcript,
        scroller: scroller,
        append: (element: Element): void => {
            parts.onFeatureContent();
            insert(element);
        },
        currentLoading: (): HTMLElement | null => loading,
        scrollToEnd: scrollToEnd,
        removeAfter: (element: Element): void => {
            if (element.parentElement !== transcript) {
                return;
            }

            while (element.nextElementSibling !== null) {
                element.nextElementSibling.remove();
            }

            if (loading !== null && !loading.isConnected) {
                loading = null;
            }

            userMessageRoots = userMessageRoots.filter((root) => root.isConnected);
            syncWelcomeZone();
        },
        userMessages: (): readonly HTMLElement[] => userMessageRoots.filter((root) => root.isConnected),
        renderUserMessage: (text: string): HTMLElement => {
            const block = parts.templates.messageUser.create();

            block.references.text.textContent = text;
            userMessageRoots.push(block.root);
            insert(block.root);
            scrollToEnd();
            announce(CHAT_MESSAGE_ROLE.user, block.root, text);

            return block.root;
        },
        renderAssistantMessage: (): ChatAssistantMessage => {
            const block = parts.templates.messageAssistant.create();

            insert(block.root);
            announce(CHAT_MESSAGE_ROLE.assistant, block.root, '');

            return { root: block.root, content: block.references.content };
        },
        renderErrorMessage: (text?: string): HTMLElement => {
            const block = parts.templates.messageError.create();

            if (text !== undefined && text.trim() !== '') {
                block.references.text.textContent = text;
            }

            insert(block.root);
            announce(CHAT_MESSAGE_ROLE.error, block.root, block.references.text.textContent ?? '');

            return block.root;
        },
        renderRetry: (onRetry: () => boolean): HTMLElement => {
            const block = parts.templates.retry.create();
            let isAccepted = false;

            block.root.addEventListener('click', () => {
                isAccepted = isAccepted || onRetry();
            });
            insert(block.root);

            return block.root;
        },
        showLoading: (): HTMLElement => {
            const follow = captureFollow();

            hideLoading();
            loading = parts.templates.loading.create().root;
            transcript.append(loading);
            syncWelcomeZone();
            follow();

            return loading;
        },
        hideLoading: hideLoading,
        setBusy: (isBusy: boolean): void => {
            transcript.setAttribute(BUSY_ATTRIBUTE, String(isBusy));
        },
        captureFollow: captureFollow,
        clear: (): void => {
            userMessageRoots = [];
            transcript.replaceChildren();
            loading = null;
            syncWelcomeZone();
        },
    };
};
