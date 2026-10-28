import type { ChatFeature, ChatFeatureContext } from '@shared/ai-commerce/Chat/core/contracts/context';
import { announceFeature } from '@shared/ai-commerce/Chat/core/contracts/access';

const TRIGGER_ATTRIBUTE = 'data-chat-trigger';
const TRIGGER_VISIBLE_ATTRIBUTE = 'data-trigger-visible';
const PRESSED_ATTRIBUTE = 'aria-pressed';
const HIDDEN_ATTRIBUTE = 'hidden';

export class ChatTrigger extends HTMLElement implements ChatFeature {
    private context: ChatFeatureContext | null = null;
    private triggers: HTMLElement[] = [];
    private wasHidden = new Map<HTMLElement, boolean>();
    private readonly visibleTriggers = new Set<HTMLElement>();
    private observer: IntersectionObserver | null = null;

    private readonly toggle = (): void => this.context?.panel.toggle();

    connectedCallback(): void {
        announceFeature(this);
    }

    initialize(context: ChatFeatureContext): void {
        this.context = context;
        this.triggers = this.findTriggers(context.chat);
        this.wasHidden = new Map(this.triggers.map((trigger) => [trigger, trigger.hasAttribute(HIDDEN_ATTRIBUTE)]));
        this.observer =
            typeof IntersectionObserver === 'undefined' ? null : new IntersectionObserver(this.trackVisibility);

        for (const trigger of this.triggers) {
            trigger.removeAttribute(HIDDEN_ATTRIBUTE);
            trigger.addEventListener('click', this.toggle);

            if (this.observer !== null) {
                this.visibleTriggers.add(trigger);
                this.observer.observe(trigger);
            }
        }

        this.syncPressed(context.panel.isOpen());
        this.syncLauncher();
        context.events.on('panel:toggle', ({ isOpen }) => {
            this.syncPressed(isOpen);

            if (!isOpen) {
                this.focusVisibleTrigger();
            }
        });
    }

    dispose(): void {
        this.observer?.disconnect();

        for (const trigger of this.triggers) {
            trigger.removeEventListener('click', this.toggle);
            trigger.setAttribute(PRESSED_ATTRIBUTE, 'false');
            trigger.toggleAttribute(HIDDEN_ATTRIBUTE, this.wasHidden.get(trigger) ?? false);
        }

        this.context?.chat.removeAttribute(TRIGGER_VISIBLE_ATTRIBUTE);
        this.visibleTriggers.clear();
        this.wasHidden.clear();
        this.triggers = [];
        this.observer = null;
        this.context = null;
    }

    private readonly trackVisibility = (entries: IntersectionObserverEntry[]): void => {
        for (const entry of entries) {
            if (entry.isIntersecting) {
                this.visibleTriggers.add(entry.target as HTMLElement);
            } else {
                this.visibleTriggers.delete(entry.target as HTMLElement);
            }
        }

        this.syncLauncher();
    };

    private findTriggers(chat: HTMLElement): HTMLElement[] {
        return Array.from(chat.ownerDocument.querySelectorAll<HTMLElement>(`[${TRIGGER_ATTRIBUTE}]`)).filter(
            (trigger) => {
                const chatId = trigger.getAttribute(TRIGGER_ATTRIBUTE) ?? '';

                return chatId === '' || chatId === chat.id;
            },
        );
    }

    private syncPressed(isOpen: boolean): void {
        for (const trigger of this.triggers) {
            trigger.setAttribute(PRESSED_ATTRIBUTE, String(isOpen));
        }
    }

    private syncLauncher(): void {
        this.context?.chat.toggleAttribute(TRIGGER_VISIBLE_ATTRIBUTE, this.visibleTriggers.size > 0);
    }

    // Runs a microtask after the core focuses its launcher, which is a no-op while a trigger in view hides it.
    private focusVisibleTrigger(): void {
        const trigger = this.triggers.find((element) => this.visibleTriggers.has(element));

        if (trigger !== undefined) {
            queueMicrotask(() => trigger.focus());
        }
    }
}
