import type {
    ChatConversationLoaded,
    ChatFeature,
    ChatFeatureContext,
    ChatRenderedMessage,
} from '@shared/ai-commerce/Chat/core/contracts/context';
import { CHAT_TURN_STATE } from '@shared/ai-commerce/Chat/core/contracts/context';
import type { ChatBlockTemplate } from '@shared/ai-commerce/Chat/core/contracts/blocks';
import type { ChatStoredAttachment } from '@shared/ai-commerce/Chat/core/contracts/api';
import { CHAT_MESSAGE_ROLE } from '@shared/ai-commerce/Chat/core/contracts/services';
import { announceFeature } from '@shared/ai-commerce/Chat/core/contracts/access';
import { CORE_ZONE } from '@shared/ai-commerce/Chat/core/contracts/blocks';
import { REQUEST_LOCK } from '@shared/ai-commerce/Chat/core/contracts/tokens';

const ACCEPT_ATTRIBUTE = 'accept';
const MAX_SIZE_ATTRIBUTE = 'max-size-bytes';
const MAX_COUNT_ATTRIBUTE = 'max-count';
const DATA_URL_SEPARATOR = ',';
const DECIMAL_RADIX = 10;
const NAME_PLACEHOLDER = '%name%';

const CONTROL_BLOCK = { name: 'attachmentsControl', references: ['button', 'input'] } as const;
const CHIP_BLOCK = { name: 'attachmentsChip', references: ['name', 'remove'] } as const;
const PILLS_BLOCK = { name: 'attachmentsPills', references: [] } as const;
const PILL_BLOCK = { name: 'attachmentsPill', references: ['name'] } as const;
const NOTICE_BLOCK = {
    name: 'attachmentsNotice',
    references: ['unsupportedType', 'tooLarge', 'tooMany', 'readFailed', 'fileName'],
} as const;

type NoticeReason = 'unsupportedType' | 'tooLarge' | 'tooMany' | 'readFailed';
const NOTICE_REASONS: readonly NoticeReason[] = ['unsupportedType', 'tooLarge', 'tooMany', 'readFailed'];

interface PendingAttachment {
    readonly name: string;
    readonly mediaType: string;
    readonly content: string;
    readonly chip: HTMLElement;
}

interface AttachmentTemplates {
    readonly chip: ChatBlockTemplate<'name' | 'remove'>;
    readonly pills: ChatBlockTemplate<never>;
    readonly pill: ChatBlockTemplate<'name'>;
    readonly notice: ChatBlockTemplate<NoticeReason | 'fileName'>;
}

const SOURCE_FILE = 'Shared/AiCommerce/Theme/default/Chat/features/attachments/attachments.ts';

const requireFileInput = (element: HTMLElement): HTMLInputElement => {
    if (!(element instanceof HTMLInputElement) || element.type !== 'file') {
        throw new Error(
            `Chat block "${CONTROL_BLOCK.name}" required by <chat-attachments> marks a <${element.localName}> with ` +
                `data-ref="input" (reported by ${SOURCE_FILE}). It must be an <input type="file">, which opens the ` +
                `file picker; change the element in the twig block "${CONTROL_BLOCK.name}".`,
        );
    }

    return element;
};

interface AttachmentLimits {
    readonly accepted: readonly string[];
    readonly maxSize: number;
    readonly maxCount: number;
}

// Fails closed: a typo must not quietly lift the limit.
const readLimit = (element: HTMLElement, attribute: string): number => {
    const value = (element.getAttribute(attribute) ?? '').trim();

    if (value === '') {
        return 0;
    }

    if (!/^[0-9]+$/.test(value)) {
        throw new Error(
            `<${element.localName}> has ${attribute}="${value}", which is not a whole number of 0 or more (reported ` +
                `by ${SOURCE_FILE}); the feature stays inactive rather than accept files without that limit. Pass ` +
                `a number in features.attachments of the host embed, 0 for no limit.`,
        );
    }

    return Number.parseInt(value, DECIMAL_RADIX);
};

const readLimits = (element: HTMLElement): AttachmentLimits => ({
    accepted: (element.getAttribute(ACCEPT_ATTRIBUTE) ?? '')
        .split(',')
        .map((mediaType) => mediaType.trim())
        .filter((mediaType) => mediaType !== ''),
    maxSize: readLimit(element, MAX_SIZE_ATTRIBUTE),
    maxCount: readLimit(element, MAX_COUNT_ATTRIBUTE),
});

export class ChatAttachments extends HTMLElement implements ChatFeature {
    private context: ChatFeatureContext | null = null;
    private templates: AttachmentTemplates | null = null;
    private limits: AttachmentLimits = { accepted: [], maxSize: 0, maxCount: 0 };
    // Bumped by initialize() and dispose(), so a read that finishes late is ignored.
    private generation = 0;
    private pending: PendingAttachment[] = [];
    private readingCount = 0;
    private replayedUserMessages: HTMLElement[] = [];
    private readonly addedElements = new Set<HTMLElement>();
    private attachButton: HTMLElement | null = null;

    connectedCallback(): void {
        announceFeature(this);
    }

    initialize(context: ChatFeatureContext): void {
        const control = context.blocks.require(CONTROL_BLOCK).create();
        const input = requireFileInput(control.references.input);

        this.limits = readLimits(this);
        this.generation += 1;
        this.readingCount = 0;
        this.context = context;
        this.templates = {
            chip: context.blocks.require(CHIP_BLOCK),
            pills: context.blocks.require<never>(PILLS_BLOCK),
            pill: context.blocks.require(PILL_BLOCK),
            notice: context.blocks.require(NOTICE_BLOCK),
        };

        this.attachButton = control.references.button;
        control.references.button.addEventListener('click', () => input.click());
        input.addEventListener('change', () => {
            this.addFiles(Array.from(input.files ?? []));
            input.value = '';
        });

        context.conversation.contribute(() => ({
            attachments: this.pending.map((attachment) => ({
                name: attachment.name,
                mediaType: attachment.mediaType,
                content: attachment.content,
            })),
        }));
        context.events.on('message:rendered', (rendered) => this.handleRenderedMessage(context, rendered));
        context.events.on('conversation:reset', () => this.forgetConversation());
        context.events.on('conversation:loaded', (loaded) => this.addReplayPills(loaded));
        // A file picked while a conversation loads would be dropped when the load clears the pending files.
        context.events.on('busy', (change) => control.references.button.toggleAttribute('disabled', change.isBusy));
        control.references.button.toggleAttribute('disabled', context.services.get(REQUEST_LOCK).isBusy());
        context.zones.insert(CORE_ZONE.composerActions, control.root, 'start');
    }

    dispose(): void {
        for (const element of this.addedElements) {
            element.remove();
        }

        this.addedElements.clear();
        this.generation += 1;
        this.pending = [];
        this.readingCount = 0;
        this.replayedUserMessages = [];
        this.attachButton = null;
        this.context = null;
        this.templates = null;
    }

    private addFiles(files: readonly File[]): void {
        const { accepted, maxSize, maxCount } = this.limits;

        for (const file of files) {
            if (accepted.length > 0 && !accepted.includes(file.type)) {
                this.showNotice('unsupportedType', file.name);
            } else if (maxSize > 0 && file.size > maxSize) {
                this.showNotice('tooLarge', file.name);
            } else if (maxCount > 0 && this.pending.length + this.readingCount >= maxCount) {
                this.showNotice('tooMany', file.name);
            } else if (file.size === 0) {
                this.showNotice('readFailed', file.name);
            } else {
                this.read(file);
            }
        }
    }

    private read(file: File): void {
        const reader = new FileReader();
        const generation = this.generation;
        const finish = (): boolean => {
            if (generation !== this.generation) {
                return false;
            }

            this.readingCount -= 1;

            return this.context !== null;
        };

        this.readingCount += 1;
        reader.addEventListener('load', () => {
            if (!finish()) {
                return;
            }

            const result = typeof reader.result === 'string' ? reader.result : '';
            const content = result.split(DATA_URL_SEPARATOR)[1] ?? '';

            if (content === '') {
                this.showNotice('readFailed', file.name);

                return;
            }

            this.addPending(file, content);
        });

        for (const failure of ['error', 'abort'] as const) {
            reader.addEventListener(failure, () => {
                if (finish()) {
                    this.failRead(file, reader.error?.name ?? failure);
                }
            });
        }

        reader.readAsDataURL(file);
    }

    private failRead(file: File, reason: string): void {
        reportError(
            new Error(
                `<chat-attachments> could not read the file "${file.name}" (${reason}) (reported by ${SOURCE_FILE}); ` +
                    `the customer sees the "could not be read" notice and the file is not attached. Ask the ` +
                    `customer to pick the file again; NotReadableError usually means it was moved or is locked.`,
            ),
        );
        this.showNotice('readFailed', file.name);
    }

    private addPending(file: File, content: string): void {
        const chip = this.templates?.chip.create();

        if (chip === undefined || this.context === null) {
            return;
        }

        const attachment: PendingAttachment = {
            name: file.name,
            mediaType: file.type,
            content: content,
            chip: chip.root,
        };

        chip.references.name.textContent = file.name;
        chip.references.name.title = file.name;
        chip.references.remove.addEventListener('click', () => {
            // The removed button must not take the keyboard focus with it.
            if (chip.root.contains(document.activeElement)) {
                this.attachButton?.focus();
            }

            this.pending = this.pending.filter((pending) => pending !== attachment);
            chip.root.remove();
        });
        this.pending.push(attachment);
        this.context.zones.insert(CORE_ZONE.composerStart, chip.root);
    }

    private showNotice(reason: NoticeReason, fileName: string): void {
        if (this.context === null || this.templates === null) {
            return;
        }

        const notice = this.templates.notice.create();
        const { readFailed, fileName: fileNameElement } = notice.references;

        for (const candidate of NOTICE_REASONS) {
            notice.references[candidate].hidden = candidate !== reason;
        }

        readFailed.textContent = (readFailed.textContent ?? '').replace(NAME_PLACEHOLDER, () => fileName);
        fileNameElement.textContent = reason === 'readFailed' ? '' : fileName;
        fileNameElement.hidden = reason === 'readFailed';
        this.context.transcript.append(notice.root);
        this.addedElements.add(notice.root);
    }

    private handleRenderedMessage(context: ChatFeatureContext, rendered: ChatRenderedMessage): void {
        if (rendered.role !== CHAT_MESSAGE_ROLE.user) {
            return;
        }

        if (context.conversation.turnState !== CHAT_TURN_STATE.sending) {
            this.replayedUserMessages.push(rendered.element);

            return;
        }

        this.appendPills(rendered.element, this.pending);
        this.clearPending();
    }

    private addReplayPills(loaded: ChatConversationLoaded): void {
        const rendered = this.replayedUserMessages.filter((element) => element.isConnected);
        const stored = loaded.messages.filter((message) => message.role === CHAT_MESSAGE_ROLE.user);

        this.addedElements.clear();
        this.replayedUserMessages = [];
        this.clearPending();
        rendered.forEach((element, index) => this.appendPills(element, stored[index]?.attachments ?? []));
    }

    private appendPills(message: HTMLElement, attachments: readonly ChatStoredAttachment[]): void {
        if (this.templates === null || attachments.length === 0) {
            return;
        }

        const pills = this.templates.pills.create().root;

        for (const attachment of attachments) {
            const pill = this.templates.pill.create();

            pill.references.name.textContent = attachment.name;
            pills.append(pill.root);
        }

        message.append(pills);
        this.addedElements.add(pills);
    }

    private clearPending(): void {
        for (const attachment of this.pending) {
            attachment.chip.remove();
        }

        this.pending = [];
    }

    private forgetConversation(): void {
        this.clearPending();
        this.addedElements.clear();
        this.replayedUserMessages = [];
    }
}
