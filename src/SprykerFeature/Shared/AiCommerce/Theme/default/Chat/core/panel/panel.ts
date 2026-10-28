import type { ChatPanelControl } from '../contracts/context';
import type { ChatEventBus } from '../contracts/event-bus';

const OPEN_ATTRIBUTE = 'data-open';
const EXPANDED_CLASS = 'spryker-chat--expanded';
const LABEL_EXPAND_ATTRIBUTE = 'data-label-expand';
const LABEL_COLLAPSE_ATTRIBUTE = 'data-label-collapse';
const INERT_ATTRIBUTE = 'inert';
const FOCUSABLE_SELECTOR = [
    'a[href]',
    'button:not([disabled])',
    'textarea:not([disabled])',
    'input:not([disabled])',
    'select:not([disabled])',
    '[tabindex]:not([tabindex="-1"])',
].join(',');

export interface ChatPanel extends ChatPanelControl {
    dispose(): void;
}

export interface ChatPanelParts {
    readonly chat: HTMLElement;
    readonly launcher: HTMLElement;
    readonly panel: HTMLElement;
    readonly closeButton: HTMLElement;
    readonly expandButton: HTMLElement | null;
    readonly scroller: HTMLElement;
    readonly footer: HTMLElement;
    readonly events: ChatEventBus;
    readonly focusComposer: () => void;
}

export const createPanel = (parts: ChatPanelParts): ChatPanel => {
    const { chat, launcher, panel } = parts;

    const isOpen = (): boolean => chat.hasAttribute(OPEN_ATTRIBUTE);

    const focusableElements = (): HTMLElement[] =>
        Array.from(panel.querySelectorAll<HTMLElement>(FOCUSABLE_SELECTOR)).filter(
            (element) => !element.hidden && element.offsetParent !== null,
        );

    const trapFocus = (event: KeyboardEvent): void => {
        if (event.key !== 'Tab' || !isOpen()) {
            return;
        }

        const focusable = focusableElements();

        if (focusable.length === 0) {
            event.preventDefault();

            return;
        }

        const first = focusable[0];
        const last = focusable[focusable.length - 1];
        const active = document.activeElement;
        const isOutside = !panel.contains(active);

        if (event.shiftKey && (active === first || isOutside)) {
            event.preventDefault();
            last.focus();

            return;
        }

        if (!event.shiftKey && (active === last || isOutside)) {
            event.preventDefault();
            first.focus();
        }
    };

    const setOpen = (shouldOpen: boolean, shouldMoveFocus: boolean = true): void => {
        if (isOpen() === shouldOpen) {
            return;
        }

        chat.toggleAttribute(OPEN_ATTRIBUTE, shouldOpen);
        // inert keeps a closed panel out of the tab order even where the host styles do not hide it.
        panel.toggleAttribute(INERT_ATTRIBUTE, !shouldOpen);
        launcher.hidden = shouldOpen;
        parts.events.emit('panel:toggle', { isOpen: shouldOpen });

        if (!shouldMoveFocus) {
            return;
        }

        if (shouldOpen) {
            parts.focusComposer();
        } else {
            launcher.focus();
        }
    };

    const closeOnEscape = (event: KeyboardEvent): void => {
        if (event.key === 'Escape' && !event.defaultPrevented && isOpen() && panel.contains(document.activeElement)) {
            setOpen(false);
        }
    };

    let shownView: HTMLElement | null = null;
    let replacedVisibility: { readonly element: HTMLElement; readonly wasHidden: boolean }[] = [];

    const hideView = (): void => {
        if (shownView === null) {
            return;
        }

        shownView.hidden = true;
        shownView = null;

        for (const { element, wasHidden } of replacedVisibility) {
            element.toggleAttribute('hidden', wasHidden);
        }

        replacedVisibility = [];
    };

    const showView = (view: HTMLElement): void => {
        if (shownView === view) {
            return;
        }

        hideView();
        replacedVisibility = [parts.scroller, parts.footer].map((element) => ({
            element: element,
            wasHidden: element.hasAttribute('hidden'),
        }));

        for (const { element } of replacedVisibility) {
            element.hidden = true;
        }

        view.hidden = false;
        shownView = view;
    };

    const isExpanded = (): boolean => chat.classList.contains(EXPANDED_CLASS);

    const setExpanded = (shouldExpand: boolean): void => {
        if (isExpanded() === shouldExpand) {
            return;
        }

        chat.classList.toggle(EXPANDED_CLASS, shouldExpand);

        if (parts.expandButton !== null) {
            const label =
                parts.expandButton.getAttribute(shouldExpand ? LABEL_COLLAPSE_ATTRIBUTE : LABEL_EXPAND_ATTRIBUTE) ?? '';

            parts.expandButton.setAttribute('aria-pressed', String(shouldExpand));
            parts.expandButton.setAttribute('aria-label', label);
            parts.expandButton.setAttribute('title', label);
        }

        parts.events.emit('panel:expand', { isExpanded: shouldExpand });
    };

    const toggle = (): void => setOpen(!isOpen());
    const close = (): void => setOpen(false);
    const toggleExpanded = (): void => setExpanded(!isExpanded());

    panel.toggleAttribute(INERT_ATTRIBUTE, true);
    launcher.addEventListener('click', toggle);
    parts.closeButton.addEventListener('click', close);
    parts.expandButton?.addEventListener('click', toggleExpanded);
    document.addEventListener('keydown', trapFocus);
    document.addEventListener('keydown', closeOnEscape);

    return {
        isOpen: isOpen,
        open: (): void => setOpen(true),
        restore: (): void => setOpen(true, false),
        close: close,
        toggle: toggle,
        isExpanded: isExpanded,
        setExpanded: setExpanded,
        showView: showView,
        hideView: hideView,
        dispose: (): void => {
            hideView();
            parts.expandButton?.removeEventListener('click', toggleExpanded);
            document.removeEventListener('keydown', trapFocus);
            document.removeEventListener('keydown', closeOnEscape);
            chat.removeAttribute(OPEN_ATTRIBUTE);
            chat.classList.remove(EXPANDED_CLASS);
        },
    };
};
