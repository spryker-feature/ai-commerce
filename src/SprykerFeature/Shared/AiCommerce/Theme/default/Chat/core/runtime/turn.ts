import { ChatSessionExpiredError } from '../contracts/api';
import type { ChatApi, ChatPromptPayload } from '../contracts/api';
import { CHAT_TURN_STATE } from '../contracts/context';
import type { ChatTurnState } from '../contracts/context';
import type { ChatEventBus } from '../contracts/event-bus';
import { describeFailure } from '../contracts/failure';
import { CHAT_MESSAGE_ROLE } from '../contracts/services';
import type { ChatMarkdown, ChatMarkdownStream, ChatState } from '../contracts/services';
import { STREAM_EVENT_TYPE } from '../contracts/stream';
import type { ChatStreamEvent, ChatStreamParser } from '../contracts/stream';
import type { ChatTranscript } from '../transcript/transcript';

const SOURCE_FILE = 'Shared/AiCommerce/Theme/default/Chat/core/runtime/turn.ts';
const ANSWER_SEPARATOR = '\n\n';

export interface ChatTurnParts {
    readonly api: ChatApi;
    readonly parser: ChatStreamParser;
    readonly markdown: ChatMarkdown;
    readonly state: ChatState;
    readonly transcript: ChatTranscript;
    readonly events: ChatEventBus;
    readonly onStateChange: (state: ChatTurnState) => void;
    readonly onRetry: (payload: ChatPromptPayload) => boolean;
}

export interface ChatTurnRequest {
    readonly payload: ChatPromptPayload;
    readonly renderUserMessage: boolean;
}

export interface ChatTurn {
    run(): Promise<ChatTurnState>;
    abort(): void;
    closeAssistantMessage(): void;
}

interface StreamingAssistantMessage {
    readonly root: HTMLElement;
    readonly stream: ChatMarkdownStream;
    text: string;
}

export const createTurn = (parts: ChatTurnParts, request: ChatTurnRequest): ChatTurn => {
    const controller = new AbortController();
    const message = request.payload.message;
    const answerParts: string[] = [];
    const unrecordedAssistantRoots: HTMLElement[] = [];
    let state: ChatTurnState = CHAT_TURN_STATE.idle;
    let assistantMessage: StreamingAssistantMessage | null = null;
    let hasError = false;
    let hasFinished = false;
    let hasText = false;
    let hasFeatureContent = false;

    const setState = (nextState: ChatTurnState): void => {
        if (nextState !== state) {
            state = nextState;
            parts.onStateChange(nextState);
        }
    };

    const closeAssistantMessage = (shouldRecord: boolean): void => {
        if (assistantMessage === null) {
            return;
        }

        assistantMessage.stream.finish();
        answerParts.push(assistantMessage.text);

        if (shouldRecord && !hasError && assistantMessage.text !== '') {
            parts.state.record({ role: CHAT_MESSAGE_ROLE.assistant, content: assistantMessage.text });
        } else {
            unrecordedAssistantRoots.push(assistantMessage.root);
        }

        assistantMessage = null;
    };

    const appendText = (delta: string): void => {
        if (delta === '') {
            return;
        }

        parts.transcript.hideLoading();

        if (assistantMessage === null) {
            const rendered = parts.transcript.renderAssistantMessage();
            let follow: (() => void) | null = null;

            assistantMessage = {
                root: rendered.root,
                stream: parts.markdown.stream(rendered.content, {
                    onBeforeRender: () => {
                        follow = parts.transcript.captureFollow();
                    },
                    onRender: () => follow?.(),
                }),
                text: '',
            };
        }

        hasText = true;
        assistantMessage.text += delta;
        assistantMessage.stream.append(delta);
    };

    const renderError = (errorText: string | undefined): void => {
        hasError = true;
        parts.transcript.hideLoading();

        const errorElement = parts.transcript.renderErrorMessage(errorText);
        const retryElement = parts.transcript.renderRetry(() => {
            if (!parts.onRetry(request.payload)) {
                return false;
            }

            errorElement.remove();
            retryElement.remove();

            for (const root of unrecordedAssistantRoots.splice(0)) {
                root.remove();
            }

            return true;
        });
    };

    const handleEvent = (event: ChatStreamEvent): void => {
        setState(CHAT_TURN_STATE.streaming);

        switch (event.type) {
            case STREAM_EVENT_TYPE.start: {
                const conversationReference = event.messageMetadata?.conversationReference;

                if (typeof conversationReference === 'string') {
                    parts.state.adoptServerReference(conversationReference);
                }

                break;
            }
            case STREAM_EVENT_TYPE.textDelta:
                appendText(event.delta ?? '');
                break;
            case STREAM_EVENT_TYPE.error:
                renderError(event.errorText);
                break;
            case STREAM_EVENT_TYPE.finish:
                hasFinished = true;
                parts.transcript.hideLoading();
                closeAssistantMessage(true);
                break;
        }

        parts.events.emit('stream', event);
    };

    const failCutStream = (): void => {
        hasError = true;
        closeAssistantMessage(false);
        reportError(
            new Error(
                `The prompt stream ended without a finish event or [DONE] (reported by ${SOURCE_FILE}): the server ` +
                    `stopped in the middle of the answer. The partial answer stays visible with a retry; check the ` +
                    `server log for the prompt request that was cut off.`,
            ),
        );
        renderError(undefined);
    };

    const failEmptyTurn = (): void => {
        reportError(
            new Error(
                `The prompt endpoint (ChatApi.prompt(), the prompt-url of <spryker-chat>) answered, but the server ` +
                    `closed the turn without any content (reported by ${SOURCE_FILE}): no text, no tool result and no ` +
                    `error arrived before the stream ended. The customer sees the error block with retry; check the ` +
                    `prompt request in the network panel and the server log for why the answer was empty.`,
            ),
        );
        renderError(undefined);
    };

    const failTurn = (failure: unknown): void => {
        hasError = true;
        closeAssistantMessage(false);

        if (failure instanceof ChatSessionExpiredError) {
            return;
        }

        reportError(
            new Error(
                `The chat turn failed: ${describeFailure(failure)} (reported by ${SOURCE_FILE}). The customer sees ` +
                    `the error block with retry; check the prompt request in the network panel and the server log.`,
                { cause: failure },
            ),
        );
        renderError(undefined);
    };

    const run = async (): Promise<ChatTurnState> => {
        setState(CHAT_TURN_STATE.sending);
        parts.transcript.setBusy(true);

        if (request.renderUserMessage) {
            parts.transcript.renderUserMessage(message);
            parts.state.record({ role: CHAT_MESSAGE_ROLE.user, content: message });
        }

        parts.transcript.showLoading();

        try {
            const stream = await parts.api.prompt(request.payload, controller.signal);
            const outcome = await parts.parser.read(stream, handleEvent);

            if (outcome === 'ended' && !hasFinished) {
                failCutStream();
            } else {
                closeAssistantMessage(true);

                if (!hasError && !hasText && !hasFeatureContent) {
                    failEmptyTurn();
                }
            }

            setState(hasError ? CHAT_TURN_STATE.error : CHAT_TURN_STATE.done);
        } catch (failure) {
            if (controller.signal.aborted) {
                closeAssistantMessage(false);
                setState(CHAT_TURN_STATE.idle);
            } else {
                failTurn(failure);
                setState(CHAT_TURN_STATE.error);
            }
        } finally {
            parts.transcript.hideLoading();
            parts.transcript.setBusy(false);
        }

        parts.events.emit('turn:end', { state: state, message: message, answer: answerParts.join(ANSWER_SEPARATOR) });

        return state;
    };

    return {
        run: run,
        abort: (): void => controller.abort(),
        closeAssistantMessage: (): void => {
            hasFeatureContent = true;
            closeAssistantMessage(true);
        },
    };
};
