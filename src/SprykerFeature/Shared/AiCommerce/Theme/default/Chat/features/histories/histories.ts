import type { ChatFeature, ChatFeatureContext } from '@shared/ai-commerce/Chat/core/contracts/context';
import type { ChatBlockTemplate } from '@shared/ai-commerce/Chat/core/contracts/blocks';
import type { ChatConversationSummary } from '@shared/ai-commerce/Chat/core/contracts/api';
import { ChatSessionExpiredError } from '@shared/ai-commerce/Chat/core/contracts/api';
import { announceFeature } from '@shared/ai-commerce/Chat/core/contracts/access';
import { API, REQUEST_LOCK } from '@shared/ai-commerce/Chat/core/contracts/tokens';
import { formatWithPageLocale } from '@shared/ai-commerce/Chat/core/contracts/locale';
import { CORE_ZONE } from '@shared/ai-commerce/Chat/core/contracts/blocks';

const SOURCE_FILE = 'Shared/AiCommerce/Theme/default/Chat/features/histories/histories.ts';
const NAME_PLACEHOLDER = '%name%';
const LABEL_ATTRIBUTE = 'data-label';
const CONFIRM_DELETE_ATTRIBUTE = 'data-confirm-delete';
const CONFIRM_DELETE_SELECTED_ATTRIBUTE = 'data-confirm-delete-selected';
const REFERENCE_ATTRIBUTE = 'data-conversation-reference';
const SELECTING_ATTRIBUTE = 'data-selecting';
const ITEM_STATE_ATTRIBUTE = 'data-state';
const ITEM_STATE = { deleting: 'deleting', deleted: 'deleted' } as const;
const REMOVAL_FALLBACK_MILLISECONDS = 250;
const MILLISECONDS_IN_SECOND = 1000;
const SECONDS_IN_MINUTE = 60;
const SECONDS_IN_HOUR = 3600;
const SECONDS_IN_DAY = 86400;
const DAYS_IN_WEEK = 7;

const TOGGLE_BLOCK = { name: 'historiesToggle', references: [] } as const;
const MENU_ITEM_BLOCK = { name: 'historiesMenuItem', references: [] } as const;
const VIEW_BLOCK = {
    name: 'historiesView',
    references: [
        'close',
        'startNew',
        'list',
        'loading',
        'empty',
        'count',
        'select',
        'selectLabel',
        'cancelLabel',
        'footer',
        'selectAll',
        'deleteSelected',
        'deleteError',
    ],
} as const;
const ITEM_BLOCK = {
    name: 'historiesItem',
    references: ['checkbox', 'open', 'name', 'current', 'time', 'delete'],
} as const;
const ERROR_BLOCK = { name: 'historiesError', references: [] } as const;
// Optional in the item block, so an item override without it keeps working.
const AGENT_REFERENCE = 'agent';

type ViewReference = (typeof VIEW_BLOCK.references)[number];
type ItemReference = (typeof ITEM_BLOCK.references)[number];
type ConfirmAttribute = typeof CONFIRM_DELETE_ATTRIBUTE | typeof CONFIRM_DELETE_SELECTED_ATTRIBUTE;

const readConfirmTexts = (view: HTMLElement): Readonly<Record<ConfirmAttribute, string>> => {
    const texts = {
        [CONFIRM_DELETE_ATTRIBUTE]: view.getAttribute(CONFIRM_DELETE_ATTRIBUTE) ?? '',
        [CONFIRM_DELETE_SELECTED_ATTRIBUTE]: view.getAttribute(CONFIRM_DELETE_SELECTED_ATTRIBUTE) ?? '',
    };

    for (const [attribute, text] of Object.entries(texts)) {
        if (text.trim() === '') {
            throw new Error(
                `Chat block "${VIEW_BLOCK.name}" required by <chat-histories> has no "${attribute}" text on its root ` +
                    `element (reported by ${SOURCE_FILE}), so deleting could not ask for confirmation. Keep the ` +
                    `${attribute} attribute with the translated question in the twig block "${VIEW_BLOCK.name}".`,
            );
        }
    }

    return texts;
};

const formatRelativeTime = (epochSeconds: number, locale: string | undefined): string => {
    if (!Number.isFinite(epochSeconds) || epochSeconds <= 0) {
        return '';
    }

    const elapsedSeconds = Math.max(0, Math.round(Date.now() / MILLISECONDS_IN_SECOND - epochSeconds));
    const formatter = new Intl.RelativeTimeFormat(locale, { numeric: 'auto', style: 'narrow' });

    if (elapsedSeconds < SECONDS_IN_MINUTE) {
        return formatter.format(0, 'second');
    }

    if (elapsedSeconds < SECONDS_IN_HOUR) {
        return formatter.format(-Math.floor(elapsedSeconds / SECONDS_IN_MINUTE), 'minute');
    }

    if (elapsedSeconds < SECONDS_IN_DAY) {
        return formatter.format(-Math.floor(elapsedSeconds / SECONDS_IN_HOUR), 'hour');
    }

    const elapsedDays = Math.floor(elapsedSeconds / SECONDS_IN_DAY);

    if (elapsedDays < DAYS_IN_WEEK) {
        return formatter.format(-elapsedDays, 'day');
    }

    return new Date(epochSeconds * MILLISECONDS_IN_SECOND).toLocaleDateString(locale, {
        day: 'numeric',
        month: 'short',
    });
};

const fillName = (element: HTMLElement, name: string): string =>
    (element.getAttribute(LABEL_ATTRIBUTE) ?? '').replace(NAME_PLACEHOLDER, () => name);

const requireCheckbox = (element: HTMLElement): HTMLInputElement => {
    if (!(element instanceof HTMLInputElement) || element.type !== 'checkbox') {
        throw new Error(
            `Chat block "${ITEM_BLOCK.name}" required by <chat-histories> marks a <${element.localName}> with ` +
                `data-ref="checkbox" (reported by ${SOURCE_FILE}). It must be an <input type="checkbox">, which ` +
                `holds the selection; change the element in the twig block "${ITEM_BLOCK.name}".`,
        );
    }

    return element;
};

const readAgentLabels = (availableAgents: unknown): ReadonlyMap<string, string> => {
    const labels = new Map<string, string>();

    for (const agent of Array.isArray(availableAgents) ? availableAgents : []) {
        const record: Record<string, unknown> = typeof agent === 'object' && agent !== null ? agent : {};
        const name = typeof record.name === 'string' ? record.name : '';
        const label = typeof record.label === 'string' ? record.label : '';

        if (name !== '' && label !== '') {
            labels.set(name, label);
        }
    }

    return labels;
};

const humanizeAgentName = (name: string): string =>
    name
        .replace(/[_-]+/g, ' ')
        .trim()
        .replace(/\b\w/g, (character: string) => character.toUpperCase());

const showAgent = (element: HTMLElement | null, agent: string, agentLabels: ReadonlyMap<string, string>): void => {
    if (element === null) {
        return;
    }

    element.textContent = agent === '' ? '' : (agentLabels.get(agent) ?? humanizeAgentName(agent));
    element.hidden = agent === '';
};

export class ChatHistories extends HTMLElement implements ChatFeature {
    private context: ChatFeatureContext | null = null;
    private toggle: HTMLElement | null = null;
    private view: HTMLElement | null = null;
    private references: Readonly<Record<ViewReference, HTMLElement>> | null = null;
    private itemTemplate: ChatBlockTemplate<ItemReference> | null = null;
    private errorTemplate: ChatBlockTemplate<never> | null = null;
    private readonly errorNotices = new Set<HTMLElement>();
    private readonly removalTimers = new Set<ReturnType<typeof setTimeout>>();
    private confirmTexts: Readonly<Record<ConfirmAttribute, string>> | null = null;
    private isSelecting = false;
    private isDiscardingCurrent = false;
    private isBusy = false;

    connectedCallback(): void {
        announceFeature(this);
    }

    initialize(context: ChatFeatureContext): void {
        const isMenuItem = context.zones.has(CORE_ZONE.moreMenu);
        const toggle = context.blocks.require<never>(isMenuItem ? MENU_ITEM_BLOCK : TOGGLE_BLOCK).create().root;
        const view = context.blocks.require(VIEW_BLOCK).create();

        this.confirmTexts = readConfirmTexts(view.root);
        this.context = context;
        this.toggle = toggle;
        this.view = view.root;
        this.references = view.references;
        this.itemTemplate = context.blocks.require(ITEM_BLOCK);
        this.errorTemplate = context.blocks.require<never>(ERROR_BLOCK);
        this.bindView(view.references);

        view.root.hidden = true;
        toggle.setAttribute('aria-expanded', 'false');
        toggle.addEventListener('click', () => {
            if (this.isOpen()) {
                this.hide();

                return;
            }

            context.services
                .get(REQUEST_LOCK)
                .run(() => this.show())
                .catch((error: unknown) => reportError(error));
        });
        context.events.on('conversation:reset', () => {
            this.errorNotices.clear();

            if (!this.isDiscardingCurrent) {
                this.hide();
            }
        });
        context.events.on('conversation:loaded', () => this.errorNotices.clear());
        context.events.on('busy', (change) => this.setBusy(change.isBusy));
        this.setBusy(context.services.get(REQUEST_LOCK).isBusy());
        context.zones.insert(isMenuItem ? CORE_ZONE.moreMenu : CORE_ZONE.headerActions, toggle, 'start');
        context.zones.insert(CORE_ZONE.views, view.root);
    }

    dispose(): void {
        this.hide();

        for (const notice of this.errorNotices) {
            notice.remove();
        }

        for (const timer of this.removalTimers) {
            clearTimeout(timer);
        }

        this.errorNotices.clear();
        this.removalTimers.clear();
        this.context = null;
        this.toggle = null;
        this.view = null;
        this.references = null;
        this.itemTemplate = null;
        this.errorTemplate = null;
        this.confirmTexts = null;
        this.isBusy = false;
    }

    private isOpen(): boolean {
        return this.view !== null && !this.view.hidden;
    }

    private bindView(references: Readonly<Record<ViewReference, HTMLElement>>): void {
        references.close.addEventListener('click', () => this.hide());
        references.startNew.addEventListener('click', () => {
            this.hide();
            this.context?.conversation.reset();
        });
        references.select.addEventListener('click', () => this.setSelecting(!this.isSelecting));
        references.selectAll.addEventListener('click', () => this.toggleAll());
        references.deleteSelected.addEventListener('click', () => {
            const selected = this.checkboxes()
                .filter((checkbox) => checkbox.checked)
                .map(
                    (checkbox) => checkbox.closest(`[${REFERENCE_ATTRIBUTE}]`)?.getAttribute(REFERENCE_ATTRIBUTE) ?? '',
                )
                .filter((reference) => reference !== '');

            if (selected.length > 0) {
                this.deleteConversations(selected, CONFIRM_DELETE_SELECTED_ATTRIBUTE).catch((error: unknown) =>
                    reportError(error),
                );
            }
        });
    }

    private async show(): Promise<void> {
        const context = this.context;

        if (context === null || this.view === null || this.toggle === null) {
            return;
        }

        context.panel.showView(this.view);
        this.toggle.setAttribute('aria-expanded', 'true');
        await this.load(context);
    }

    private hide(): void {
        this.setSelecting(false);
        this.toggle?.setAttribute('aria-expanded', 'false');

        if (!this.isOpen()) {
            return;
        }

        // Read before hiding: a hidden list drops the focus. Focus moves after hideView(), because the composer is
        // hidden while the view is shown and focus() on a hidden element does nothing.
        const hadFocus = this.view?.contains(document.activeElement) ?? false;

        this.context?.panel.hideView();

        if (!hadFocus) {
            return;
        }

        if (this.toggle !== null && this.toggle.closest('[hidden]') === null) {
            this.toggle.focus();
        } else {
            this.context?.composer.focus();
        }
    }

    private async load(context: ChatFeatureContext): Promise<void> {
        const references = this.references;

        if (references === null) {
            return;
        }

        references.list.replaceChildren();
        references.empty.hidden = true;
        references.deleteError.hidden = true;
        references.loading.hidden = false;

        let conversations: readonly ChatConversationSummary[];
        let agentLabels: ReadonlyMap<string, string>;

        try {
            const list = await context.services.get(API).conversations();

            conversations = list.conversations;
            agentLabels = readAgentLabels(list.availableAgents);
        } catch (error) {
            if (this.context !== context) {
                return;
            }

            references.loading.hidden = true;
            this.hide();

            if (
                this.reportFailure(
                    error,
                    'The conversation list could not be loaded',
                    'the error notice in the transcript',
                    'conversations',
                )
            ) {
                this.showTranscriptNotice();
            }

            return;
        }

        if (this.context !== context) {
            return;
        }

        references.loading.hidden = true;
        references.list.append(
            ...conversations.map((conversation) => this.createItem(context, conversation, agentLabels)),
        );
        this.renderSummary();
    }

    private createItem(
        context: ChatFeatureContext,
        conversation: ChatConversationSummary,
        agentLabels: ReadonlyMap<string, string>,
    ): HTMLElement {
        if (this.itemTemplate === null) {
            throw new Error(
                `<chat-histories> rendered a conversation after it was disposed (reported by ${SOURCE_FILE}). This ` +
                    `is a defect of the feature; report it with the steps that led to it.`,
            );
        }

        const item = this.itemTemplate.create();
        const { open, name, current, time } = item.references;
        const checkbox = requireCheckbox(item.references.checkbox);
        const reference = conversation.conversationReference;
        const isCurrent = reference === context.state.conversationReference;

        if (conversation.name !== '') {
            name.textContent = conversation.name;
        }

        const shownName = name.textContent ?? '';

        name.title = shownName;
        item.root.setAttribute(REFERENCE_ATTRIBUTE, reference);
        if (isCurrent) {
            item.root.setAttribute('aria-current', 'true');
        }

        current.hidden = !isCurrent;
        showAgent(item.find(AGENT_REFERENCE), conversation.agent ?? '', agentLabels);
        time.textContent = formatWithPageLocale((locale) => formatRelativeTime(conversation.updatedAt, locale));
        checkbox.setAttribute('aria-label', fillName(checkbox, shownName));
        checkbox.addEventListener('change', () => this.syncSelection());
        item.references.delete.toggleAttribute('disabled', this.isBusy);
        open.toggleAttribute('disabled', this.isBusy);
        item.references.delete.setAttribute('aria-label', fillName(item.references.delete, shownName));
        item.references.delete.title = fillName(item.references.delete, shownName);
        item.references.delete.addEventListener('click', () => {
            this.deleteConversations([reference], CONFIRM_DELETE_ATTRIBUTE).catch((error: unknown) =>
                reportError(error),
            );
        });
        open.addEventListener('click', () => {
            if (this.isSelecting) {
                checkbox.checked = !checkbox.checked;
                this.syncSelection();

                return;
            }

            if (context.services.get(REQUEST_LOCK).isBusy()) {
                return;
            }

            this.hide();
            context.conversation.load(reference).catch((error: unknown) => reportError(error));
        });

        return item.root;
    }

    private renderSummary(): void {
        const references = this.references;

        if (references === null) {
            return;
        }

        const count = references.list.childElementCount;

        references.empty.hidden = count > 0;
        references.count.textContent = String(count);
        references.count.hidden = count === 0;
        references.select.hidden = count === 0;

        if (count === 0) {
            this.setSelecting(false);
        }

        this.syncSelection();
    }

    private setSelecting(isSelecting: boolean): void {
        const references = this.references;

        this.isSelecting = isSelecting;

        if (references === null || this.view === null) {
            return;
        }

        this.view.toggleAttribute(SELECTING_ATTRIBUTE, isSelecting);
        references.select.setAttribute('aria-pressed', String(isSelecting));
        references.selectLabel.hidden = isSelecting;
        references.cancelLabel.hidden = !isSelecting;
        references.footer.hidden = !isSelecting;

        if (!isSelecting) {
            for (const checkbox of this.checkboxes()) {
                checkbox.checked = false;
            }
        }

        this.syncSelection();
    }

    private syncSelection(): void {
        const references = this.references;

        if (references === null) {
            return;
        }

        const checkboxes = this.checkboxes();
        const selectedCount = checkboxes.filter((checkbox) => checkbox.checked).length;

        references.selectAll.setAttribute(
            'aria-pressed',
            String(checkboxes.length > 0 && selectedCount === checkboxes.length),
        );
        references.deleteSelected.toggleAttribute('disabled', selectedCount === 0 || this.isBusy);
    }

    private toggleAll(): void {
        const checkboxes = this.checkboxes();
        const isAllSelected = checkboxes.every((checkbox) => checkbox.checked);

        for (const checkbox of checkboxes) {
            checkbox.checked = !isAllSelected;
        }

        this.syncSelection();
    }

    private checkboxes(): HTMLInputElement[] {
        return Array.from(this.references?.list.querySelectorAll<HTMLElement>(`[${REFERENCE_ATTRIBUTE}]`) ?? [])
            .map((item) => item.querySelector('[data-ref="checkbox"]'))
            .filter((checkbox): checkbox is HTMLInputElement => checkbox instanceof HTMLInputElement);
    }

    private findItem(reference: string): HTMLElement | null {
        return (
            Array.from(this.references?.list.children ?? []).find(
                (item): item is HTMLElement =>
                    item instanceof HTMLElement && item.getAttribute(REFERENCE_ATTRIBUTE) === reference,
            ) ?? null
        );
    }

    private async deleteConversations(
        references: readonly string[],
        confirmAttribute: ConfirmAttribute,
    ): Promise<void> {
        const context = this.context;
        const viewReferences = this.references;

        if (context === null || viewReferences === null || this.confirmTexts === null) {
            return;
        }

        if (!window.confirm(this.confirmTexts[confirmAttribute])) {
            return;
        }

        viewReferences.deleteError.hidden = true;

        const items = references
            .map((reference) => this.findItem(reference))
            .filter((item): item is HTMLElement => item !== null);
        // Read before the lock disables the buttons, which drops their focus in the browser.
        const focusedItem = items.find((item) => item.contains(document.activeElement)) ?? null;
        let isDeleted = false;

        this.setItemsState(items, ITEM_STATE.deleting);

        try {
            isDeleted = await context.services.get(REQUEST_LOCK).run(async () => {
                await context.services.get(API).delete(references);
            });
        } catch (error) {
            if (this.context !== context) {
                return;
            }

            this.setItemsState(items, null);
            viewReferences.deleteError.hidden = !this.reportFailure(
                error,
                'The conversations could not be deleted',
                'the delete error inside the open list',
                'delete',
            );

            return;
        }

        if (this.context !== context) {
            return;
        }

        if (!isDeleted) {
            this.setItemsState(items, null);

            return;
        }

        // Read before the reset below, which focuses the composer hidden behind the list.
        const shouldMoveFocus = focusedItem !== null && this.isFocusLeftWith(focusedItem);

        if (references.includes(context.state.conversationReference)) {
            this.isDiscardingCurrent = true;
            context.conversation.reset();
            this.isDiscardingCurrent = false;
        }

        if (shouldMoveFocus && focusedItem !== null) {
            this.moveFocusFrom(focusedItem, items);
        }

        for (const item of items) {
            this.removeItem(item);
        }
    }

    private isFocusLeftWith(item: HTMLElement): boolean {
        const focused = document.activeElement;

        return focused === null || focused === document.body || item.contains(focused);
    }

    private moveFocusFrom(item: HTMLElement, removedItems: readonly HTMLElement[]): void {
        const isRemaining = (candidate: Element | null): candidate is HTMLElement =>
            candidate instanceof HTMLElement && !removedItems.includes(candidate);
        let next: Element | null = item.nextElementSibling;
        let previous: Element | null = item.previousElementSibling;

        while (next !== null && !isRemaining(next)) {
            next = next.nextElementSibling;
        }

        while (previous !== null && !isRemaining(previous)) {
            previous = previous.previousElementSibling;
        }

        const target = isRemaining(next) ? next : isRemaining(previous) ? previous : null;
        const open = target?.querySelector<HTMLElement>('[data-ref="open"]') ?? null;

        (open ?? this.references?.startNew ?? null)?.focus();
    }

    private setItemsState(items: readonly HTMLElement[], state: string | null): void {
        for (const item of items) {
            if (state === null) {
                item.removeAttribute(ITEM_STATE_ATTRIBUTE);
            } else {
                item.setAttribute(ITEM_STATE_ATTRIBUTE, state);
            }
        }
    }

    private removeItem(item: HTMLElement): void {
        let timer: ReturnType<typeof setTimeout> | null = null;
        let isRemoved = false;
        const remove = (): void => {
            if (isRemoved) {
                return;
            }

            isRemoved = true;
            item.removeEventListener('transitionend', handleTransitionEnd);

            if (timer !== null) {
                clearTimeout(timer);
                this.removalTimers.delete(timer);
            }

            item.remove();
            this.renderSummary();
        };
        // Transitions of the item's children bubble up too; only the item's own one ends the removal.
        const handleTransitionEnd = (event: TransitionEvent): void => {
            if (event.target === item) {
                remove();
            }
        };

        timer = setTimeout(remove, REMOVAL_FALLBACK_MILLISECONDS);
        this.removalTimers.add(timer);
        item.setAttribute(ITEM_STATE_ATTRIBUTE, ITEM_STATE.deleted);
        item.addEventListener('transitionend', handleTransitionEnd);
    }

    private setBusy(isBusy: boolean): void {
        this.isBusy = isBusy;
        this.toggle?.toggleAttribute('disabled', isBusy);

        for (const item of Array.from(this.references?.list.children ?? [])) {
            item.querySelector('[data-ref="delete"]')?.toggleAttribute('disabled', isBusy);
            item.querySelector('[data-ref="open"]')?.toggleAttribute('disabled', isBusy);
        }

        this.syncSelection();
    }

    private reportFailure(error: unknown, reason: string, shownAs: string, request: string): boolean {
        if (error instanceof ChatSessionExpiredError) {
            return false;
        }

        reportError(
            new Error(
                `${reason} (reported by ${SOURCE_FILE}); the customer sees ${shownAs}. Check the ${request} request ` +
                    `in the network panel and the server log.`,
                { cause: error },
            ),
        );

        return true;
    }

    private showTranscriptNotice(): void {
        if (this.context !== null && this.errorTemplate !== null) {
            const notice = this.errorTemplate.create().root;

            this.context.transcript.append(notice);
            this.errorNotices.add(notice);
        }
    }
}
