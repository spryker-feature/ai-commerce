import type { ChatBlockTemplate } from '@shared/ai-commerce/Chat/core/contracts/blocks';
import type { ChatFeature, ChatFeatureContext } from '@shared/ai-commerce/Chat/core/contracts/context';
import type { ChatStreamEvent } from '@shared/ai-commerce/Chat/core/contracts/stream';
import { STREAM_DATA_EVENT_PREFIX } from '@shared/ai-commerce/Chat/core/contracts/stream';
import { announceFeature } from '@shared/ai-commerce/Chat/core/contracts/access';

const SOURCE_FILE = 'Zed/AiCommerce/assets/Zed/chat/features/form-fill/form-fill.ts';
// StreamDataEventName::FORM_FILL, emitted by FillFormToolPlugin.
const FORM_FILL_DATA_EVENT = `${STREAM_DATA_EVENT_PREFIX}form-fill` as const;
const FIELDS_KEY = 'fields';
const SKIPPED_BLOCK = { name: 'formFillSkipped', references: ['fields'] } as const;
// Same lists as form-context.ts: a field the assistant never sees is a field it never fills.
const DENIED_INPUT_TYPES = new Set(['hidden', 'password', 'file', 'submit', 'reset', 'button', 'image']);
const DENIED_NAME_PARTS = ['password', 'token', 'secret', 'api_key', 'apikey', 'csrf'];
const CHECKED_VALUES = new Set<unknown>([true, 'true', '1', 1]);
const UNCHECKED_VALUES = new Set<unknown>([false, 'false', '0', 0, null, '']);

const SKIP_REASON = {
    missing: 'not on this page',
    noOption: 'no matching option',
    disabled: 'disabled',
    denied: 'denied',
    notScalar: 'the value is not text, a number or a boolean',
} as const;

type SkipReason = (typeof SKIP_REASON)[keyof typeof SKIP_REASON];
type FillableElement = HTMLInputElement | HTMLSelectElement | HTMLTextAreaElement;

interface SkippedField {
    readonly name: string;
    readonly reason: SkipReason;
}

const isRecord = (value: unknown): value is Record<string, unknown> =>
    typeof value === 'object' && value !== null && !Array.isArray(value);

const isFillable = (element: Element): element is FillableElement =>
    element instanceof HTMLInputElement ||
    element instanceof HTMLSelectElement ||
    element instanceof HTMLTextAreaElement;

const isDeniedName = (name: string): boolean => {
    const lowerCaseName = name.toLowerCase();

    return DENIED_NAME_PARTS.some((part) => lowerCaseName.includes(part));
};

const isDeniedType = (element: FillableElement): boolean =>
    element instanceof HTMLInputElement && DENIED_INPUT_TYPES.has(element.type);

const isRadio = (element: FillableElement): element is HTMLInputElement =>
    element instanceof HTMLInputElement && element.type === 'radio';

const dispatchFieldEvents = (element: FillableElement, eventNames: readonly string[]): void => {
    for (const eventName of eventNames) {
        element.dispatchEvent(new Event(eventName, { bubbles: true }));
    }
};

const fillSelect = (select: HTMLSelectElement, value: string): boolean => {
    const options = Array.from(select.options);
    const option =
        options.find((candidate) => candidate.value === value) ??
        options.find((candidate) => candidate.text.trim().toLowerCase() === value.toLowerCase());

    if (option === undefined) {
        return false;
    }

    select.value = option.value;
    dispatchFieldEvents(select, ['change']);

    return true;
};

const fillRadioGroup = (radios: readonly HTMLInputElement[], value: string): boolean => {
    const radio = radios.find((candidate) => candidate.value === value);

    if (radio === undefined) {
        return false;
    }

    radio.checked = true;
    dispatchFieldEvents(radio, ['change']);

    return true;
};

const fillElement = (element: Exclude<FillableElement, HTMLSelectElement>, value: unknown, text: string): boolean => {
    if (element instanceof HTMLInputElement && element.type === 'checkbox') {
        if (!CHECKED_VALUES.has(value) && !UNCHECKED_VALUES.has(value)) {
            return false;
        }

        element.checked = CHECKED_VALUES.has(value);
        dispatchFieldEvents(element, ['change']);

        return true;
    }

    element.value = text;
    dispatchFieldEvents(element, ['input', 'change']);

    return true;
};

export class ChatFormFill extends HTMLElement implements ChatFeature {
    private context: ChatFeatureContext | null = null;
    private skippedTemplate: ChatBlockTemplate<'fields'> | null = null;

    connectedCallback(): void {
        announceFeature(this);
    }

    initialize(context: ChatFeatureContext): void {
        this.context = context;
        this.skippedTemplate = context.blocks.require(SKIPPED_BLOCK);
        context.events.on('stream', (event: ChatStreamEvent) => this.handleStreamEvent(event));
    }

    dispose(): void {
        this.context = null;
        this.skippedTemplate = null;
    }

    private handleStreamEvent(event: ChatStreamEvent): void {
        if (event.type !== FORM_FILL_DATA_EVENT) {
            return;
        }

        if (!('data' in event) || !isRecord(event.data)) {
            reportError(
                new Error(
                    `The ${FORM_FILL_DATA_EVENT} stream event carries no "data" map (reported by ${SOURCE_FILE}), so ` +
                        `no field was filled. Check what FillFormToolPlugin emitted in the Zed log.`,
                ),
            );

            return;
        }

        const fields = event.data[FIELDS_KEY];

        // PHP encodes an empty field map as a list.
        if (Array.isArray(fields) && fields.length === 0) {
            return;
        }

        if (!isRecord(fields)) {
            reportError(
                new Error(
                    `The ${FORM_FILL_DATA_EVENT} stream event carries "${FIELDS_KEY}" that is not a map of field ` +
                        `names to values (reported by ${SOURCE_FILE}), so no field was filled. Check the arguments ` +
                        `FillFormToolPlugin received from the model in the Zed log.`,
                ),
            );

            return;
        }

        this.reportSkipped(
            Object.entries(fields)
                .map(([name, value]) => this.fillField(name, value))
                .filter((skipped): skipped is SkippedField => skipped !== null),
        );
    }

    private fillField(name: string, value: unknown): SkippedField | null {
        const elements = this.findFields(name);

        if (elements.length === 0) {
            return { name: name, reason: SKIP_REASON.missing };
        }

        if (isDeniedName(name)) {
            return { name: name, reason: SKIP_REASON.denied };
        }

        if (typeof value === 'object' && value !== null) {
            return { name: name, reason: SKIP_REASON.notScalar };
        }

        const allowed = elements.filter((element) => !isDeniedType(element));
        const enabled = allowed.filter((element) => !element.disabled);

        if (allowed.length === 0) {
            return { name: name, reason: SKIP_REASON.denied };
        }

        if (enabled.length === 0) {
            return { name: name, reason: SKIP_REASON.disabled };
        }

        const text = value === null || value === undefined ? '' : String(value);
        const radios = enabled.filter(isRadio);
        let isMatched = radios.length === 0 || fillRadioGroup(radios, text);

        for (const element of enabled) {
            if (element instanceof HTMLSelectElement) {
                isMatched = fillSelect(element, text) && isMatched;
            } else if (!isRadio(element)) {
                isMatched = fillElement(element, value, text) && isMatched;
            }
        }

        return isMatched ? null : { name: name, reason: SKIP_REASON.noOption };
    }

    private reportSkipped(skipped: readonly SkippedField[]): void {
        if (skipped.length === 0) {
            return;
        }

        if (this.context !== null && this.skippedTemplate !== null) {
            const notice = this.skippedTemplate.create();

            notice.references.fields.textContent = skipped.map((field) => field.name).join(', ');
            this.context.transcript.append(notice.root);
        }

        reportError(
            new Error(
                `The assistant could not fill ${skipped.length} field(s): ` +
                    `${skipped.map((field) => `"${field.name}" (${field.reason})`).join(', ')} (reported by ` +
                    `${SOURCE_FILE}); the other fields were filled. Attach the form with the form context chip ` +
                    `(chat-form-context) so the prompt carries the exact field names, options and states; a disabled ` +
                    `field has to be enabled on the page before the assistant can fill it, and a denied one (a ` +
                    `secret-looking name, or a hidden, password, file or button input) is left for the user to fill.`,
            ),
        );
    }

    private findFields(name: string): FillableElement[] {
        const chat = this.context?.chat ?? null;

        return Array.from(document.querySelectorAll(`[name="${CSS.escape(name)}"]`))
            .filter(isFillable)
            .filter((element) => element.form !== null && (chat === null || !chat.contains(element)));
    }
}
