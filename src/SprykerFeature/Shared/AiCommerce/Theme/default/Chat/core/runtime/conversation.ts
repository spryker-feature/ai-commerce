import { ChatSessionExpiredError } from '../contracts/api';
import type { ChatApi, ChatPromptPayload, ChatStoredMessage } from '../contracts/api';
import { CHAT_TURN_STATE } from '../contracts/context';
import type {
    ChatConversationControl,
    ChatPromptContributor,
    ChatRegenerateOptions,
    ChatSendOptions,
    ChatTurnState,
} from '../contracts/context';
import type { ChatEventBus } from '../contracts/event-bus';
import { CHAT_MESSAGE_ROLE } from '../contracts/services';
import type { ChatMarkdown, ChatRecordedMessage, ChatRequestLock, ChatState } from '../contracts/services';
import type { ChatStreamParser } from '../contracts/stream';
import type { ChatComposer } from '../composer/composer';
import type { ChatTranscript } from '../transcript/transcript';
import { createTurn } from './turn';
import type { ChatTurn } from './turn';

const SOURCE_FILE = 'Shared/AiCommerce/Theme/default/Chat/core/runtime/conversation.ts';
// Read by RegeneratedPromptMarker::PAYLOAD_KEY_REGENERATE on both hosts.
const REGENERATE_FIELD = 'regenerate';
const TURN_STATE_ATTRIBUTE = 'data-turn-state';

export interface ChatConversationParts {
    readonly chat: HTMLElement;
    readonly api: ChatApi;
    readonly parser: ChatStreamParser;
    readonly markdown: ChatMarkdown;
    readonly state: ChatState;
    readonly lock: ChatRequestLock;
    readonly transcript: ChatTranscript;
    readonly composer: ChatComposer;
    readonly events: ChatEventBus;
    readonly takeBrokenRequirements: () => readonly Error[];
}

export interface ChatConversation extends Omit<ChatConversationControl, 'contribute'> {
    contributeFor(owner: object, contributor: ChatPromptContributor): void;
    release(owner: object): void;
    closeAssistantMessage(): void;
    dispose(): void;
}

const reportFailure = (error: unknown): void => {
    reportError(error);
};

export const createConversation = (parts: ChatConversationParts): ChatConversation => {
    const contributorsByOwner = new Map<object, Set<ChatPromptContributor>>();
    let turnState: ChatTurnState = CHAT_TURN_STATE.idle;
    let currentTurn: ChatTurn | null = null;
    let isDisposed = false;
    let isResetQueued = false;

    const setTurnState = (nextState: ChatTurnState, message: string): void => {
        const previousState = turnState;

        turnState = nextState;
        parts.chat.setAttribute(TURN_STATE_ATTRIBUTE, nextState);
        parts.events.emit('turn:state', { state: nextState, previousState: previousState, message: message });
    };

    const collectContributions = (): Record<string, unknown> => {
        const fields: Record<string, unknown> = {};

        for (const contributors of contributorsByOwner.values()) {
            for (const contributor of contributors) {
                try {
                    Object.assign(fields, contributor());
                } catch (error) {
                    reportError(error);
                }
            }
        }

        return fields;
    };

    const createPayload = (message: string, fields: Readonly<Record<string, unknown>> = {}): ChatPromptPayload => ({
        ...collectContributions(),
        ...fields,
        message: message,
        conversationReference: parts.state.conversationReference,
    });

    // Called after the lock is released, because focus() on the composer input disabled by the lock does nothing.
    const returnFocusAfterTurn = (): void => {
        const active = document.activeElement;

        if (!isDisposed && (active === null || active === document.body || parts.chat.contains(active))) {
            parts.composer.focus();
        }
    };

    const runTurn = async (payload: ChatPromptPayload, renderUserMessage: boolean): Promise<void> => {
        for (const error of parts.takeBrokenRequirements()) {
            reportError(error);
        }

        parts.composer.setStreaming(true);

        const turn = createTurn(
            {
                api: parts.api,
                parser: parts.parser,
                markdown: parts.markdown,
                state: parts.state,
                transcript: parts.transcript,
                events: parts.events,
                onStateChange: (nextState: ChatTurnState) => setTurnState(nextState, payload.message),
                onRetry: (failedPayload: ChatPromptPayload): boolean => {
                    if (isDisposed || parts.lock.isBusy()) {
                        return false;
                    }

                    parts.lock
                        .run(() =>
                            runTurn(
                                { ...failedPayload, conversationReference: parts.state.conversationReference },
                                false,
                            ),
                        )
                        .then(returnFocusAfterTurn)
                        .catch(reportFailure);

                    return true;
                },
            },
            { payload: payload, renderUserMessage: renderUserMessage },
        );

        currentTurn = turn;

        try {
            await turn.run();
        } finally {
            currentTurn = null;
            parts.composer.setStreaming(false);
        }
    };

    const send = async (message: string, options: ChatSendOptions = {}): Promise<boolean> => {
        const text = message.trim();

        if (text === '' || isDisposed) {
            return false;
        }

        const isSent = await parts.lock.run(() =>
            runTurn(createPayload(text, options.fields), options.renderUserMessage ?? true),
        );

        if (isSent) {
            returnFocusAfterTurn();
        }

        return isSent;
    };

    const findRecordedUserMessage = (userMessage: Element): number => {
        const position = parts.transcript.userMessages().findIndex((root) => root === userMessage);
        let userCount = -1;

        if (position < 0) {
            return -1;
        }

        return parts.state.messages.findIndex((recorded) => {
            userCount += recorded.role === CHAT_MESSAGE_ROLE.user ? 1 : 0;

            return recorded.role === CHAT_MESSAGE_ROLE.user && userCount === position;
        });
    };

    const regenerate = async (userMessage: Element, options: ChatRegenerateOptions = {}): Promise<boolean> => {
        if (isDisposed || parts.lock.isBusy()) {
            return false;
        }

        const recordedIndex = findRecordedUserMessage(userMessage);

        if (recordedIndex < 0) {
            reportError(
                new Error(
                    `conversation.regenerate() got <${userMessage.localName}>, which is not a user message of the ` +
                        `current conversation (reported by ${SOURCE_FILE}); nothing was resent. Pass the element of a ` +
                        `'message:rendered' event with the user role, and only while that conversation is shown.`,
                ),
            );

            return false;
        }

        const message = parts.state.messages[recordedIndex].content;

        parts.state.truncate(recordedIndex + 1);
        parts.transcript.removeAfter(userMessage);

        // The server stores the resent prompt as a new turn; the flag lets it hide the exchange this one replaces.
        return send(message, { renderUserMessage: false, fields: { ...options.fields, [REGENERATE_FIELD]: true } });
    };

    const stop = (): void => {
        currentTurn?.abort();
    };

    const applyReset = (): void => {
        parts.state.startNew();
        parts.transcript.clear();
        parts.composer.clear();
        parts.events.emit('conversation:reset', { conversationReference: parts.state.conversationReference });
        parts.composer.focus();
    };

    const reset = (): void => {
        stop();

        if (parts.lock.isBusy()) {
            isResetQueued = true;

            return;
        }

        applyReset();
    };

    const renderStoredMessages = (messages: readonly ChatStoredMessage[]): ChatRecordedMessage[] => {
        const recorded: ChatRecordedMessage[] = [];

        for (const message of messages) {
            if (message.role === CHAT_MESSAGE_ROLE.user) {
                parts.transcript.renderUserMessage(message.content);
            } else if (message.role === CHAT_MESSAGE_ROLE.assistant) {
                parts.markdown.render(parts.transcript.renderAssistantMessage().content, message.content);
            } else {
                parts.events.emit('message:stored', { message: message });
            }

            recorded.push({ role: message.role, content: message.content });
        }

        return recorded;
    };

    const load = async (conversationReference: string): Promise<boolean> => {
        // Read before the lock disables the controls; a load on page start must not take the focus from the page.
        const isFocusInChat = parts.chat.contains(document.activeElement);
        let isLoaded = false;

        await parts.lock.run(async () => {
            let messages: readonly ChatStoredMessage[];

            parts.transcript.clear();
            parts.composer.clear();
            parts.transcript.showLoading();

            try {
                messages = await parts.api.messages(conversationReference);
            } catch (error) {
                if (!(error instanceof ChatSessionExpiredError)) {
                    reportError(
                        new Error(
                            `The chat could not load conversation "${conversationReference}" (reported by ` +
                                `${SOURCE_FILE}); it started a new conversation instead. Check the conversation ` +
                                `messages request in the network panel.`,
                            { cause: error },
                        ),
                    );
                }

                applyReset();
                parts.transcript.renderErrorMessage();

                return;
            }

            parts.transcript.clear();
            parts.state.restore(conversationReference, renderStoredMessages(messages));
            parts.transcript.scrollToEnd();
            parts.events.emit('conversation:loaded', {
                conversationReference: conversationReference,
                messages: messages,
            });
            isLoaded = true;
        });

        if (isLoaded && isFocusInChat && !isDisposed) {
            parts.composer.focus();
        }

        return isLoaded;
    };

    const unsubscribeFromLock = parts.lock.subscribe((isBusy: boolean) => {
        parts.composer.setBusy(isBusy);
        parts.events.emit('busy', { isBusy: isBusy });

        if (!isBusy && isResetQueued && !isDisposed) {
            isResetQueued = false;
            applyReset();
        }
    });

    parts.chat.setAttribute(TURN_STATE_ATTRIBUTE, turnState);

    return {
        get turnState(): ChatTurnState {
            return turnState;
        },
        send: send,
        stop: stop,
        reset: reset,
        load: load,
        regenerate: regenerate,
        createPayload: (message: string): ChatPromptPayload => createPayload(message),
        contributeFor: (owner: object, contributor: ChatPromptContributor): void => {
            const contributors = contributorsByOwner.get(owner) ?? new Set<ChatPromptContributor>();

            contributors.add(contributor);
            contributorsByOwner.set(owner, contributors);
        },
        release: (owner: object): void => {
            contributorsByOwner.delete(owner);
        },
        closeAssistantMessage: (): void => currentTurn?.closeAssistantMessage(),
        dispose: (): void => {
            isDisposed = true;
            stop();
            unsubscribeFromLock();
            parts.chat.removeAttribute(TURN_STATE_ATTRIBUTE);
        },
    };
};
