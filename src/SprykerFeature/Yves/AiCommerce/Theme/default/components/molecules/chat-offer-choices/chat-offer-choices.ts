import './chat-offer-choices.scss';
import type { ChatFeature, ChatFeatureContext } from '@shared/ai-commerce/Chat/core/contracts/context';
import { CHAT_TURN_STATE } from '@shared/ai-commerce/Chat/core/contracts/context';
import type { ChatBlockTemplate } from '@shared/ai-commerce/Chat/core/contracts/blocks';
import { announceFeature } from '@shared/ai-commerce/Chat/core/contracts/access';
import { REQUEST_LOCK } from '@shared/ai-commerce/Chat/core/contracts/tokens';
import type { ChatToolCall, ChatToolCalls } from '@shared/ai-commerce/Chat/features/tool-calls/tool-calls';

export const OFFER_CHOICES_TAG_NAME = 'chat-offer-choices';

const SOURCE_FILE = 'Yves/AiCommerce/Theme/default/components/molecules/chat-offer-choices/chat-offer-choices.ts';
const TOOL_CALLS_TAG_NAME = 'chat-tool-calls';
// Must match OfferChoicesToolPlugin::TOOL_NAME.
const OFFER_CHOICES_TOOL_NAME = 'offer_choices';
const MIN_CHOICES = 2;
const MAX_CHOICES = 6;
const MAX_CHOICE_LENGTH = 40;
const MAX_QUESTION_LENGTH = 160;

const CHOICES_BLOCK = { name: 'offerChoices', references: ['question', 'choices'] } as const;
const CHOICE_BLOCK = { name: 'offerChoice', references: ['text'] } as const;

interface ChoiceOffer {
    readonly question: string;
    readonly choices: readonly string[];
}

interface RenderedRow {
    readonly buttons: readonly HTMLElement[];
    isChosen: boolean;
}

const isRecord = (value: unknown): value is Record<string, unknown> =>
    typeof value === 'object' && value !== null && !Array.isArray(value);

// Brackets are dropped because the backend reads a trailing `[...]` of a message as a refinement hint.
const readText = (value: unknown, maxLength: number): string =>
    typeof value === 'string'
        ? value
              .replace(/[[\]\s]+/g, ' ')
              .trim()
              .slice(0, maxLength)
        : '';

const isModelCorrection = (decoded: Record<string, unknown>): boolean =>
    (typeof decoded.error === 'string' || 'alreadyShown' in decoded) &&
    !('choices' in decoded) &&
    !('question' in decoded);

const parseChoiceOffer = (result: string): ChoiceOffer | string | null => {
    let decoded: unknown;

    try {
        decoded = JSON.parse(result);
    } catch {
        return 'it is not JSON';
    }

    if (isRecord(decoded) && isModelCorrection(decoded)) {
        return null;
    }

    if (!isRecord(decoded) || !Array.isArray(decoded.choices)) {
        return 'it has no "choices" list';
    }

    const choices = [...new Set(decoded.choices.map((choice) => readText(choice, MAX_CHOICE_LENGTH)))]
        .filter((choice) => choice !== '')
        .slice(0, MAX_CHOICES);

    if (choices.length < MIN_CHOICES) {
        return `it has ${choices.length} distinct non-empty choices, at least ${MIN_CHOICES} are needed`;
    }

    return { question: readText(decoded.question, MAX_QUESTION_LENGTH), choices: choices };
};

export default class ChatOfferChoices extends HTMLElement implements ChatFeature {
    readonly requires = [TOOL_CALLS_TAG_NAME];

    private context: ChatFeatureContext | null = null;
    private choicesTemplate: ChatBlockTemplate<'question' | 'choices'> | null = null;
    private choiceTemplate: ChatBlockTemplate<'text'> | null = null;
    private unregisterRenderer: (() => void) | null = null;
    private readonly renderedRows = new Map<HTMLElement, RenderedRow>();
    private isBusy = false;

    connectedCallback(): void {
        announceFeature(this);
    }

    initialize(context: ChatFeatureContext): void {
        const toolCalls = context.features.find(TOOL_CALLS_TAG_NAME) as ChatToolCalls | null;

        if (toolCalls === null || typeof toolCalls.tools?.register !== 'function') {
            throw new Error(
                `<${OFFER_CHOICES_TAG_NAME}> registers its renderer on <${TOOL_CALLS_TAG_NAME}>, which is not ` +
                    `initialized (reported by ${SOURCE_FILE}). Keep features.toolCalls enabled in the host embed.`,
            );
        }

        this.context = context;
        this.choicesTemplate = context.blocks.require(CHOICES_BLOCK);
        this.choiceTemplate = context.blocks.require(CHOICE_BLOCK);
        this.unregisterRenderer = toolCalls.tools.register(OFFER_CHOICES_TOOL_NAME, (call) => this.render(call));
        this.isBusy = context.services.get(REQUEST_LOCK).isBusy();
        context.events.on('busy', (change) => this.setBusy(change.isBusy));
        context.events.on('conversation:reset', () => this.forgetDetachedRows());
        context.events.on('conversation:loaded', () => this.forgetDetachedRows());
        context.events.on('turn:state', (change) => {
            if (change.state === CHAT_TURN_STATE.sending) {
                this.forgetDetachedRows();
            }
        });
    }

    dispose(): void {
        this.unregisterRenderer?.();
        this.unregisterRenderer = null;

        for (const row of this.renderedRows.keys()) {
            row.remove();
        }

        this.renderedRows.clear();
        this.isBusy = false;
        this.context = null;
        this.choicesTemplate = null;
        this.choiceTemplate = null;
    }

    private render(call: ChatToolCall): Element | null {
        const offer = parseChoiceOffer(call.result);
        const choicesTemplate = this.choicesTemplate;
        const choiceTemplate = this.choiceTemplate;

        if (offer === null || choicesTemplate === null || choiceTemplate === null) {
            return null;
        }

        if (typeof offer === 'string') {
            reportError(
                new Error(
                    `The "${OFFER_CHOICES_TOOL_NAME}" tool result could not be shown as choices: ${offer} (reported ` +
                        `by ${SOURCE_FILE}). It falls back to the generic tool-call block; check what ` +
                        `OfferChoicesToolPlugin returned for this turn.`,
                ),
            );

            return null;
        }

        const block = choicesTemplate.create();
        const { question, choices } = block.references;
        const buttons = offer.choices.map((choice) => {
            const button = choiceTemplate.create();

            button.references.text.textContent = choice;
            button.root.toggleAttribute('disabled', this.isBusy);
            button.root.addEventListener('click', () => {
                this.choose(row, button.root, choice).catch((error: unknown) => reportError(error));
            });

            return button.root;
        });
        const row: RenderedRow = { buttons: buttons, isChosen: false };

        question.textContent = offer.question;
        question.hidden = offer.question === '';
        // An empty name would hide the group from assistive technology instead of leaving it unnamed.
        if (offer.question === '') {
            choices.removeAttribute('aria-label');
        } else {
            choices.setAttribute('aria-label', offer.question);
        }
        choices.append(...buttons);
        this.renderedRows.set(block.root, row);

        return block.root;
    }

    private forgetDetachedRows(): void {
        for (const row of [...this.renderedRows.keys()]) {
            if (!row.isConnected) {
                this.renderedRows.delete(row);
            }
        }
    }

    private setBusy(isBusy: boolean): void {
        this.isBusy = isBusy;

        for (const row of this.renderedRows.values()) {
            if (!row.isChosen) {
                this.setRowDisabled(row, isBusy);
            }
        }
    }

    private setRowDisabled(row: RenderedRow, isDisabled: boolean): void {
        for (const button of row.buttons) {
            button.toggleAttribute('disabled', isDisabled);
        }
    }

    private async choose(row: RenderedRow, chosen: HTMLElement, choice: string): Promise<void> {
        const context = this.context;

        if (context === null || row.isChosen || this.isBusy) {
            return;
        }

        row.isChosen = true;
        this.setRowDisabled(row, true);
        chosen.setAttribute('aria-pressed', 'true');

        if (await context.conversation.send(choice)) {
            return;
        }

        row.isChosen = false;
        chosen.setAttribute('aria-pressed', 'false');
        this.setRowDisabled(row, this.isBusy);
        reportError(
            new Error(
                `<chat-offer-choices> could not send the choice "${choice}" (reported by ${SOURCE_FILE}): the chat ` +
                    `refused it because another request holds the request lock. The row is offered again; choose ` +
                    `once the running request has finished.`,
            ),
        );
    }
}
