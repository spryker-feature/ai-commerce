import type { ChatFeature, ChatFeatureContext } from '@shared/ai-commerce/Chat/core/contracts/context';
import { announceFeature } from '@shared/ai-commerce/Chat/core/contracts/access';

const SOURCE_FILE = 'Shared/AiCommerce/Theme/default/Chat/features/docking/docking.ts';
const TARGET_ATTRIBUTE = 'target';
const DOCK_TARGETS = ['body', 'none'] as const;
const DEFAULT_DOCK_TARGET = 'body';
const DOCKED_ATTRIBUTE = 'data-chat-docked';
const EXPANDED_ATTRIBUTE = 'data-chat-expanded';
const DOCK_TARGET_ATTRIBUTE = 'data-chat-dock-target';
const ANIMATED_ATTRIBUTE = 'data-chat-dock-animated';
const WIDTH_PROPERTY = '--chat-dock-width';
const DURATION_PROPERTY = '--chat-dock-duration';
const EASING_PROPERTY = '--chat-dock-easing';
const PANEL_DURATION_TOKEN = '--chat-panel-transition-duration';
const EASING_TOKEN = '--chat-easing';

type ChatDockTarget = (typeof DOCK_TARGETS)[number];

const isDockTarget = (value: string): value is ChatDockTarget => (DOCK_TARGETS as readonly string[]).includes(value);

export class ChatDocking extends HTMLElement implements ChatFeature {
    private context: ChatFeatureContext | null = null;
    private frameHandles: number[] = [];
    private resizeFrameHandle: number | null = null;

    private readonly scheduleSync = (): void => {
        if (this.resizeFrameHandle !== null) {
            return;
        }

        this.resizeFrameHandle = requestAnimationFrame(() => {
            this.resizeFrameHandle = null;
            this.sync();
        });
    };

    connectedCallback(): void {
        announceFeature(this);
    }

    initialize(context: ChatFeatureContext): void {
        const root = document.documentElement;
        const target = this.readTarget();
        const tokens = getComputedStyle(context.chat);

        this.context = context;
        root.setAttribute(DOCK_TARGET_ATTRIBUTE, target);
        root.style.setProperty(DURATION_PROPERTY, tokens.getPropertyValue(PANEL_DURATION_TOKEN).trim());
        root.style.setProperty(EASING_PROPERTY, tokens.getPropertyValue(EASING_TOKEN).trim());
        context.events.on('panel:toggle', () => this.sync());
        context.events.on('panel:expand', () => this.sync());
        window.addEventListener('resize', this.scheduleSync);
        this.sync();
        // Two frames: the first state must be styled before the transition is switched on, or it would animate too.
        this.frameHandles.push(
            requestAnimationFrame(() => {
                this.frameHandles.push(requestAnimationFrame(() => root.setAttribute(ANIMATED_ATTRIBUTE, '')));
            }),
        );
    }

    dispose(): void {
        const root = document.documentElement;

        window.removeEventListener('resize', this.scheduleSync);

        for (const handle of [...this.frameHandles, this.resizeFrameHandle]) {
            if (handle !== null) {
                cancelAnimationFrame(handle);
            }
        }

        for (const attribute of [DOCKED_ATTRIBUTE, EXPANDED_ATTRIBUTE, DOCK_TARGET_ATTRIBUTE, ANIMATED_ATTRIBUTE]) {
            root.removeAttribute(attribute);
        }

        for (const property of [WIDTH_PROPERTY, DURATION_PROPERTY, EASING_PROPERTY]) {
            root.style.removeProperty(property);
        }

        this.frameHandles = [];
        this.resizeFrameHandle = null;
        this.context = null;
    }

    private readTarget(): ChatDockTarget {
        const target = this.getAttribute(TARGET_ATTRIBUTE) ?? DEFAULT_DOCK_TARGET;

        if (!isDockTarget(target)) {
            throw new Error(
                `<${this.localName}> has ${TARGET_ATTRIBUTE}="${target}", which is not a docking target (reported by ` +
                    `${SOURCE_FILE}). Pass features.docking.target as one of: ${DOCK_TARGETS.join(', ')}.`,
            );
        }

        return target;
    }

    private sync(): void {
        if (this.context === null) {
            return;
        }

        const root = document.documentElement;
        const isOpen = this.context.panel.isOpen();
        const width = this.getBoundingClientRect().width;
        const isDocked = isOpen && width > 0 && width < root.clientWidth;

        root.style.setProperty(WIDTH_PROPERTY, `${width}px`);
        root.toggleAttribute(DOCKED_ATTRIBUTE, isDocked);
        root.toggleAttribute(EXPANDED_ATTRIBUTE, isOpen && this.context.panel.isExpanded());
    }
}
