const KEY = {
    escape: 'Escape',
    arrowDown: 'ArrowDown',
    arrowUp: 'ArrowUp',
    home: 'Home',
    end: 'End',
    tab: 'Tab',
} as const;

export interface ChatMenuParts {
    readonly toggle: HTMLElement;
    readonly menu: HTMLElement;
    readonly itemSelector: string;
}

export interface ChatMenu {
    isOpen(): boolean;
    open(shouldFocusFirstItem: boolean): void;
    close(shouldRestoreFocus: boolean): void;
    dispose(): void;
}

export const createMenu = (parts: ChatMenuParts): ChatMenu => {
    const { toggle, menu, itemSelector } = parts;

    const isOpen = (): boolean => !menu.hidden;

    const items = (): HTMLElement[] =>
        Array.from(menu.querySelectorAll<HTMLElement>(itemSelector)).filter(
            (item) => !item.hidden && !item.hasAttribute('disabled'),
        );

    const closeOnOutsideClick = (event: MouseEvent): void => {
        const target = event.target;

        if (target instanceof Node && (menu.contains(target) || toggle.contains(target))) {
            return;
        }

        close(false);
    };

    const open = (shouldFocusFirstItem: boolean): void => {
        if (isOpen()) {
            return;
        }

        menu.hidden = false;
        toggle.setAttribute('aria-expanded', 'true');
        document.addEventListener('click', closeOnOutsideClick, true);

        if (shouldFocusFirstItem) {
            items()[0]?.focus();
        }
    };

    const close = (shouldRestoreFocus: boolean): void => {
        if (!isOpen()) {
            return;
        }

        menu.hidden = true;
        toggle.setAttribute('aria-expanded', 'false');
        document.removeEventListener('click', closeOnOutsideClick, true);

        if (shouldRestoreFocus) {
            toggle.focus();
        }
    };

    const consumeEscape = (event: KeyboardEvent): void => {
        event.preventDefault();
        event.stopPropagation();
        close(true);
    };

    const onToggleClick = (): void => {
        if (isOpen()) {
            close(false);
        } else {
            open(false);
        }
    };

    const onToggleKeydown = (event: KeyboardEvent): void => {
        if (event.key === KEY.arrowDown) {
            event.preventDefault();
            open(true);
        } else if (event.key === KEY.escape && isOpen()) {
            consumeEscape(event);
        }
    };

    const onMenuClick = (event: MouseEvent): void => {
        if (event.target instanceof Element && event.target.closest(itemSelector) !== null) {
            close(false);
        }
    };

    const nextIndex = (key: string, currentIndex: number, count: number): number | null => {
        switch (key) {
            case KEY.arrowDown:
                return (currentIndex + 1) % count;
            case KEY.arrowUp:
                return (currentIndex - 1 + count) % count;
            case KEY.home:
                return 0;
            case KEY.end:
                return count - 1;
            default:
                return null;
        }
    };

    const onMenuKeydown = (event: KeyboardEvent): void => {
        if (event.key === KEY.escape) {
            consumeEscape(event);

            return;
        }

        if (event.key === KEY.tab) {
            close(false);

            return;
        }

        const choosable = items();

        if (choosable.length === 0) {
            return;
        }

        const index = nextIndex(event.key, choosable.indexOf(document.activeElement as HTMLElement), choosable.length);

        if (index !== null) {
            event.preventDefault();
            choosable[index]?.focus();
        }
    };

    menu.hidden = true;
    toggle.setAttribute('aria-expanded', 'false');
    toggle.addEventListener('click', onToggleClick);
    toggle.addEventListener('keydown', onToggleKeydown);
    menu.addEventListener('click', onMenuClick);
    menu.addEventListener('keydown', onMenuKeydown);

    return {
        isOpen: isOpen,
        open: open,
        close: close,
        dispose: (): void => {
            close(false);
            toggle.removeEventListener('click', onToggleClick);
            toggle.removeEventListener('keydown', onToggleKeydown);
            menu.removeEventListener('click', onMenuClick);
            menu.removeEventListener('keydown', onMenuKeydown);
        },
    };
};
