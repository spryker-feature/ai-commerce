import type { ChatFeature, ChatFeatureContext } from '@shared/ai-commerce/Chat/core/contracts/context';
import { announceFeature } from '@shared/ai-commerce/Chat/core/contracts/access';
import { CORE_ZONE } from '@shared/ai-commerce/Chat/core/contracts/blocks';

const DISTANCE_PROPERTY = '--chat-scroll-latest-distance';
// Must equal the core's default follow distance; used only without the feature stylesheet.
const DEFAULT_DISTANCE_PIXELS = 48;
const REDUCED_MOTION_QUERY = '(prefers-reduced-motion: reduce)';

const SCROLL_LATEST_BLOCK = { name: 'scrollLatest', references: ['button'] } as const;

export class ChatScrollLatest extends HTMLElement implements ChatFeature {
    private context: ChatFeatureContext | null = null;
    private scroller: HTMLElement | null = null;
    private button: HTMLElement | null = null;
    private observer: MutationObserver | null = null;
    private frameHandle: number | null = null;
    private distance: number | null = null;

    private readonly scheduleSync = (): void => {
        if (this.frameHandle !== null) {
            return;
        }

        this.frameHandle = requestAnimationFrame(() => {
            this.frameHandle = null;
            this.sync();
        });
    };

    private readonly scrollToLatest = (): void => {
        const prefersReducedMotion =
            typeof window.matchMedia === 'function' && window.matchMedia(REDUCED_MOTION_QUERY).matches;

        this.scroller?.scrollTo({
            top: this.scroller.scrollHeight,
            behavior: prefersReducedMotion ? 'auto' : 'smooth',
        });
    };

    connectedCallback(): void {
        announceFeature(this);
    }

    initialize(context: ChatFeatureContext): void {
        const block = context.blocks.require(SCROLL_LATEST_BLOCK).create();

        this.context = context;
        this.scroller = context.transcript.scroller;
        this.button = block.references.button;
        this.button.hidden = true;
        this.button.addEventListener('click', this.scrollToLatest);
        this.scroller.addEventListener('scroll', this.scheduleSync, { passive: true });
        this.observer = new MutationObserver(this.scheduleSync);
        this.observer.observe(this.scroller, { childList: true, subtree: true, characterData: true });
        context.events.on('panel:toggle', this.scheduleSync);
        context.zones.insert(CORE_ZONE.footer, block.root, 'start');
    }

    dispose(): void {
        this.button?.removeEventListener('click', this.scrollToLatest);
        this.scroller?.removeEventListener('scroll', this.scheduleSync);
        this.observer?.disconnect();

        if (this.frameHandle !== null) {
            cancelAnimationFrame(this.frameHandle);
        }

        this.frameHandle = null;
        this.observer = null;
        this.context = null;
        this.scroller = null;
        this.button = null;
        this.distance = null;
    }

    private sync(): void {
        if (this.scroller === null || this.button === null) {
            return;
        }

        const remaining = this.scroller.scrollHeight - this.scroller.scrollTop - this.scroller.clientHeight;
        const isHidden = remaining <= this.readDistance(this.scroller);

        // A hidden button drops the keyboard focus; the reader who pressed it continues in the composer.
        if (isHidden && !this.button.hidden && this.button.contains(document.activeElement)) {
            this.context?.composer.focus();
        }

        this.button.hidden = isHidden;
    }

    private readDistance(scroller: HTMLElement): number {
        if (this.distance === null) {
            const value = Number.parseFloat(getComputedStyle(scroller).getPropertyValue(DISTANCE_PROPERTY));

            this.distance = Number.isNaN(value) ? DEFAULT_DISTANCE_PIXELS : value;
        }

        return this.distance;
    }
}
