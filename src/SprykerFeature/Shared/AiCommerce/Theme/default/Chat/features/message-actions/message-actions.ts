import type {
    ChatConversationLoaded,
    ChatFeature,
    ChatFeatureContext,
    ChatRenderedMessage,
    ChatTurnEnd,
} from '@shared/ai-commerce/Chat/core/contracts/context';
import { CHAT_TURN_STATE } from '@shared/ai-commerce/Chat/core/contracts/context';
import type { ChatBlockTemplate } from '@shared/ai-commerce/Chat/core/contracts/blocks';
import { CHAT_MESSAGE_ROLE } from '@shared/ai-commerce/Chat/core/contracts/services';
import { announceFeature } from '@shared/ai-commerce/Chat/core/contracts/access';
import { describeFailure } from '@shared/ai-commerce/Chat/core/contracts/failure';
import { formatWithPageLocale } from '@shared/ai-commerce/Chat/core/contracts/locale';

const SOURCE_FILE = 'Shared/AiCommerce/Theme/default/Chat/features/message-actions/message-actions.ts';

const MESSAGE_ACTIONS_BLOCK = { name: 'messageActions', references: ['copy', 'regenerate', 'time'] } as const;
const COPY_BUFFER_REFERENCE = 'copyBuffer';
const COPY_STATUS_REFERENCE = 'copyStatus';
const COPIED_TEXT_ATTRIBUTE = 'data-copied-text';
const COPIED_ATTRIBUTE = 'data-copied';
const COPY_FAILED_ATTRIBUTE = 'data-copy-failed';
const COPIED_STATE_MILLISECONDS = 1500;
const ANSWER_SEPARATOR = '\n\n';
const CORE_PAYLOAD_FIELDS = ['message', 'conversationReference'];

type MessageActionsReference = (typeof MESSAGE_ACTIONS_BLOCK.references)[number];

interface ActionBarOptions {
    readonly text: string;
    readonly timestamp: Date | null;
    readonly onRegenerate: ((button: HTMLElement) => void) | null;
}

interface LatestTurn {
    readonly userMessage: HTMLElement;
    readonly fields: Readonly<Record<string, unknown>>;
}

const withoutCoreFields = (payload: Readonly<Record<string, unknown>>): Record<string, unknown> =>
    Object.fromEntries(Object.entries(payload).filter(([field]) => !CORE_PAYLOAD_FIELDS.includes(field)));

const isTurnRunning = (context: ChatFeatureContext): boolean =>
    context.conversation.turnState === CHAT_TURN_STATE.sending ||
    context.conversation.turnState === CHAT_TURN_STATE.streaming;

export class ChatMessageActions extends HTMLElement implements ChatFeature {
    private context: ChatFeatureContext | null = null;
    private template: ChatBlockTemplate<MessageActionsReference> | null = null;
    private latestTurn: LatestTurn | null = null;
    private startingFields: Readonly<Record<string, unknown>> = {};
    private replayedMessages: ChatRenderedMessage[] = [];
    private readonly bars = new Map<HTMLElement, HTMLElement>();
    private readonly copiedTimers = new Set<ReturnType<typeof setTimeout>>();
    private isBusy = false;

    connectedCallback(): void {
        announceFeature(this);
    }

    initialize(context: ChatFeatureContext): void {
        this.context = context;
        this.template = context.blocks.require(MESSAGE_ACTIONS_BLOCK);

        context.events.on('busy', (change) => this.setBusy(change.isBusy));

        context.events.on('turn:state', (change) => {
            if (change.state === CHAT_TURN_STATE.sending) {
                this.startingFields = withoutCoreFields(context.conversation.createPayload(change.message));
            }
        });
        context.events.on('message:rendered', (rendered) => this.trackRenderedMessage(context, rendered));
        context.events.on('turn:end', (end) => this.addTurnBar(end));
        context.events.on('conversation:loaded', (loaded) => this.addReplayBars(loaded));
        context.events.on('conversation:reset', () => this.forgetConversation());
    }

    dispose(): void {
        for (const bar of this.bars.keys()) {
            bar.remove();
        }

        for (const timer of this.copiedTimers) {
            clearTimeout(timer);
        }

        this.copiedTimers.clear();
        this.forgetConversation();
        this.context = null;
        this.template = null;
    }

    private trackRenderedMessage(context: ChatFeatureContext, rendered: ChatRenderedMessage): void {
        if (!isTurnRunning(context)) {
            this.replayedMessages.push(rendered);

            return;
        }

        if (rendered.role === CHAT_MESSAGE_ROLE.user) {
            this.latestTurn = { userMessage: rendered.element, fields: this.startingFields };
            this.hideRegenerateButtons();
        }
    }

    private addTurnBar(end: ChatTurnEnd): void {
        const turn = this.latestTurn;

        if (end.state === CHAT_TURN_STATE.error || turn === null || turn.userMessage.nextElementSibling === null) {
            return;
        }

        this.appendBar(
            {
                text: end.answer.trim(),
                timestamp: new Date(),
                onRegenerate: (button: HTMLElement) => {
                    this.regenerate(turn, button).catch((error: unknown) => reportError(error));
                },
            },
            null,
        );
    }

    private addReplayBars(loaded: ChatConversationLoaded): void {
        const rendered = this.replayedMessages.filter((message) => message.element.isConnected);
        const stored = loaded.messages.filter(
            (message) => message.role === CHAT_MESSAGE_ROLE.user || message.role === CHAT_MESSAGE_ROLE.assistant,
        );
        let answerParts: string[] = [];

        this.bars.clear();
        this.replayedMessages = [];
        this.latestTurn = null;

        rendered.forEach((message, index) => {
            if (message.role === CHAT_MESSAGE_ROLE.user) {
                this.flushReplayAnswer(answerParts, message.element);
                answerParts = [];
            } else if (message.role === CHAT_MESSAGE_ROLE.assistant) {
                answerParts.push((stored[index]?.content ?? '').trim());
            }
        });

        this.flushReplayAnswer(answerParts, null);
    }

    private flushReplayAnswer(answerParts: readonly string[], nextUserMessage: HTMLElement | null): void {
        const text = answerParts.filter((part) => part !== '').join(ANSWER_SEPARATOR);

        if (text !== '') {
            this.appendBar({ text: text, timestamp: null, onRegenerate: null }, nextUserMessage);
        }
    }

    private appendBar(options: ActionBarOptions, before: HTMLElement | null): void {
        if (this.context === null || this.template === null) {
            return;
        }

        const block = this.template.create();
        const { copy, regenerate, time } = block.references;
        const onRegenerate = options.onRegenerate;

        copy.hidden = options.text === '';
        copy.addEventListener('click', () => {
            this.copy(options.text, copy, block.find(COPY_BUFFER_REFERENCE), block.find(COPY_STATUS_REFERENCE)).catch(
                (error: unknown) => reportError(error),
            );
        });
        regenerate.hidden = onRegenerate === null;

        if (onRegenerate !== null) {
            this.hideRegenerateButtons();
            regenerate.addEventListener('click', () => onRegenerate(regenerate));
        }

        time.hidden = options.timestamp === null;

        if (options.timestamp !== null) {
            time.setAttribute('datetime', options.timestamp.toISOString());
            const timestamp = options.timestamp;

            time.textContent = formatWithPageLocale((locale) =>
                timestamp.toLocaleTimeString(locale, { hour: '2-digit', minute: '2-digit' }),
            );
        }

        if (before === null) {
            this.context.transcript.append(block.root);
        } else {
            before.before(block.root);
        }

        regenerate.toggleAttribute('disabled', this.isBusy);
        this.bars.set(block.root, regenerate);
    }

    private setBusy(isBusy: boolean): void {
        this.isBusy = isBusy;

        for (const regenerate of this.bars.values()) {
            regenerate.toggleAttribute('disabled', isBusy);
        }
    }

    private async regenerate(turn: LatestTurn, button: HTMLElement): Promise<void> {
        const context = this.context;

        if (context === null || button.hasAttribute('disabled')) {
            return;
        }

        button.toggleAttribute('disabled', true);

        const regenerating = context.conversation.regenerate(turn.userMessage, { fields: turn.fields });

        for (const bar of [...this.bars.keys()]) {
            if (!bar.isConnected) {
                this.bars.delete(bar);
            }
        }

        if (await regenerating) {
            return;
        }

        button.toggleAttribute('disabled', this.isBusy);
        reportError(
            new Error(
                `<chat-message-actions> could not regenerate the latest answer (reported by ${SOURCE_FILE}): the chat ` +
                    `refused it because another request holds the request lock, or the message left the ` +
                    `conversation (reported separately). The button is enabled again; press it once the running ` +
                    `request has finished.`,
            ),
        );
    }

    private hideRegenerateButtons(): void {
        for (const regenerate of this.bars.values()) {
            regenerate.hidden = true;
        }
    }

    private async copy(
        text: string,
        button: HTMLElement,
        buffer: HTMLElement | null,
        status: HTMLElement | null,
    ): Promise<void> {
        const failure = await this.writeToClipboard(text, buffer);

        if (failure !== null) {
            reportError(
                new Error(
                    `<chat-message-actions> could not copy the answer: ${failure} (reported by ${SOURCE_FILE}). The ` +
                        `copy button shows ${COPY_FAILED_ATTRIBUTE}; allow clipboard access for the shop, serve it ` +
                        `over https, or keep the copyBuffer textarea in the messageActions block.`,
                ),
            );
            this.showState(button, COPY_FAILED_ATTRIBUTE, null);

            return;
        }

        if (status !== null) {
            status.textContent = status.getAttribute(COPIED_TEXT_ATTRIBUTE) ?? '';
        }

        this.showState(button, COPIED_ATTRIBUTE, status);
    }

    private showState(button: HTMLElement, attribute: string, status: HTMLElement | null): void {
        button.removeAttribute(COPIED_ATTRIBUTE);
        button.removeAttribute(COPY_FAILED_ATTRIBUTE);
        button.setAttribute(attribute, '');

        const timer = setTimeout(() => {
            button.removeAttribute(attribute);

            if (status !== null) {
                status.replaceChildren();
            }

            this.copiedTimers.delete(timer);
        }, COPIED_STATE_MILLISECONDS);

        this.copiedTimers.add(timer);
    }

    // The Clipboard API exists only in secure contexts and may be refused; the hidden buffer is the fallback.
    private async writeToClipboard(text: string, buffer: HTMLElement | null): Promise<string | null> {
        let clipboardFailure = 'the Clipboard API is unavailable (no secure context)';

        if (typeof navigator.clipboard?.writeText === 'function') {
            try {
                await navigator.clipboard.writeText(text);

                return null;
            } catch (error) {
                clipboardFailure = `the Clipboard API refused it (${describeFailure(error)})`;
            }
        }

        if (!(buffer instanceof HTMLTextAreaElement)) {
            return `${clipboardFailure} and the block has no copyBuffer textarea to fall back to`;
        }

        buffer.value = text;
        buffer.select();

        let isCopied = false;

        try {
            isCopied = document.execCommand('copy');
        } catch (error) {
            clipboardFailure = `${clipboardFailure}; execCommand threw (${describeFailure(error)})`;
        } finally {
            buffer.value = '';
        }

        return isCopied ? null : `${clipboardFailure} and copying through the copyBuffer textarea failed too`;
    }

    private forgetConversation(): void {
        this.bars.clear();
        this.latestTurn = null;
        this.replayedMessages = [];
        this.startingFields = {};
    }
}
