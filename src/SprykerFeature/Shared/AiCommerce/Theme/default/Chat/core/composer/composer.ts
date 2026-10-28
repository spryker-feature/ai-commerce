import { CORE_BLOCK, ChatBlockError } from '../runtime/blocks';
import type { ChatBlockInstance } from '../contracts/blocks';
import type { ChatComposerControl } from '../contracts/context';

const SOURCE_FILE = 'Shared/AiCommerce/Theme/default/Chat/core/composer/composer.ts';
const LIMIT_REFERENCE = 'limit';
const DISCLOSURE_REFERENCE = 'disclosure';
const DISCLOSURE_ID_PREFIX = 'spryker-chat-ai-disclosure-';

let describedComposerCount = 0;

export interface ChatComposer extends ChatComposerControl {
    setStreaming(isStreaming: boolean): void;
    setBusy(isBusy: boolean): void;
}

export interface ChatComposerParts {
    readonly block: ChatBlockInstance<'input' | 'send' | 'stop'>;
    readonly maxMessageLength: number;
    readonly canSend: () => boolean;
    readonly onSend: (message: string) => void;
    readonly onStop: () => void;
}

const requireTextarea = (element: HTMLElement): HTMLTextAreaElement => {
    if (element.localName !== 'textarea') {
        throw new ChatBlockError(
            `Chat block "${CORE_BLOCK.composer}" required by <spryker-chat> marks a <${element.localName}> with ` +
                `data-ref="input" (reported by ${SOURCE_FILE}). The message field must be a <textarea>, so Shift+Enter ` +
                `can add a line; change the element in the twig block "${CORE_BLOCK.composer}".`,
        );
    }

    return element as HTMLTextAreaElement;
};

const describeInputWith = (input: HTMLTextAreaElement, description: HTMLElement): void => {
    if (description.id === '') {
        describedComposerCount += 1;
        description.id = `${DISCLOSURE_ID_PREFIX}${describedComposerCount}`;
    }

    const describedBy = new Set((input.getAttribute('aria-describedby') ?? '').split(' ').filter(Boolean));

    describedBy.add(description.id);
    input.setAttribute('aria-describedby', [...describedBy].join(' '));
};

export const createComposer = (parts: ChatComposerParts): ChatComposer => {
    const input = requireTextarea(parts.block.references.input);
    const { send, stop } = parts.block.references;
    const limitNotice = parts.block.find(LIMIT_REFERENCE);
    const disclosure = parts.block.find(DISCLOSURE_REFERENCE);

    if (disclosure !== null) {
        describeInputWith(input, disclosure);
    }

    const showLimitNotice = (isShown: boolean): void => {
        // An empty aria-invalid reads as "false", so the attribute is set to "true" or removed.
        if (isShown) {
            input.setAttribute('aria-invalid', 'true');
        } else {
            input.removeAttribute('aria-invalid');
        }

        if (limitNotice !== null) {
            limitNotice.hidden = !isShown;
        }
    };

    const submit = (): void => {
        const message = input.value.trim();

        if (message === '' || !parts.canSend()) {
            return;
        }

        if (parts.maxMessageLength > 0 && message.length > parts.maxMessageLength) {
            showLimitNotice(true);

            return;
        }

        input.value = '';
        showLimitNotice(false);
        parts.onSend(message);
    };

    send.addEventListener('click', submit);
    stop.addEventListener('click', parts.onStop);
    input.addEventListener('input', () => showLimitNotice(false));
    input.addEventListener('keydown', (event: KeyboardEvent) => {
        // Enter confirms an IME composition first; only a plain Enter sends.
        if (event.key === 'Enter' && !event.shiftKey && !event.isComposing) {
            event.preventDefault();
            submit();
        }
    });

    stop.hidden = true;
    showLimitNotice(false);

    return {
        input: input,
        read: (): string => input.value.trim(),
        write: (text: string): void => {
            input.value = text;
            input.focus();
        },
        clear: (): void => {
            input.value = '';
            showLimitNotice(false);
        },
        focus: (): void => input.focus(),
        setStreaming: (isStreaming: boolean): void => {
            send.hidden = isStreaming;
            stop.hidden = !isStreaming;
        },
        setBusy: (isBusy: boolean): void => {
            input.disabled = isBusy;
            send.toggleAttribute('disabled', isBusy);
        },
    };
};
