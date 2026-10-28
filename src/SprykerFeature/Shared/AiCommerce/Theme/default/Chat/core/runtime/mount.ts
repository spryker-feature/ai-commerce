import { CORE_BLOCK_SPECS } from './blocks';
import type { ChatBlockResolver, ChatBlockSpec, ChatBlockTemplate } from '../contracts/blocks';
import { createComposer } from '../composer/composer';
import type { ChatComposer } from '../composer/composer';
import type { ChatComposerControl, ChatPanelControl, ChatTranscriptControl } from '../contracts/context';
import { createConversation } from './conversation';
import type { ChatConversation } from './conversation';
import type { ChatEventBus } from '../contracts/event-bus';
import { createChatHandle } from '../contracts/handle';
import type { ChatHandle } from '../contracts/handle';
import { createMenu } from '../menu/menu';
import type { ChatMenu } from '../menu/menu';
import { createPanel } from '../panel/panel';
import type { ChatPanel } from '../panel/panel';
import { API, MARKDOWN, REQUEST_LOCK, STATE, STREAM_PARSER } from '../contracts/tokens';
import type { ChatServiceDirectory } from '../contracts/tokens';
import { createTranscript } from '../transcript/transcript';
import type { ChatTranscriptTemplates } from '../transcript/transcript';

const MAX_MESSAGE_LENGTH_ATTRIBUTE = 'max-message-length';
const DECIMAL_RADIX = 10;
const WELCOME_ZONE_REFERENCE = 'welcomeZone';
const NEW_CONVERSATION_REFERENCE = 'newConversation';
const MORE_TOGGLE_REFERENCE = 'moreToggle';
const MORE_MENU_REFERENCE = 'moreMenu';
const EXPAND_REFERENCE = 'expand';
const MENU_ITEM_SELECTOR = '[role="menuitem"]';

export interface ChatCoreTemplates extends ChatTranscriptTemplates {
    readonly composer: ChatBlockTemplate<'input' | 'send' | 'stop'>;
    readonly launcher: ChatBlockTemplate<never>;
    readonly panel: ChatBlockTemplate<'close' | 'scroller' | 'transcript' | 'composer'>;
}

export interface MountedCore {
    readonly services: ChatServiceDirectory;
    readonly conversation: ChatConversation;
    readonly transcript: ChatTranscriptControl;
    readonly composer: ChatComposerControl;
    readonly panel: ChatPanelControl;
    readonly handle: ChatHandle;
    unmount(): void;
}

export type CoreTemplateResult =
    | { readonly templates: ChatCoreTemplates; readonly errors: readonly [] }
    | { readonly templates: null; readonly errors: readonly unknown[] };

export const requireCoreTemplates = (blocks: ChatBlockResolver): CoreTemplateResult => {
    const errors: unknown[] = [];
    const requireTemplate = <Reference extends string>(
        spec: ChatBlockSpec<Reference>,
    ): ChatBlockTemplate<Reference> | null => {
        try {
            return blocks.require(spec);
        } catch (error) {
            errors.push(error);

            return null;
        }
    };

    const messageUser = requireTemplate(CORE_BLOCK_SPECS.messageUser);
    const messageAssistant = requireTemplate(CORE_BLOCK_SPECS.messageAssistant);
    const messageError = requireTemplate(CORE_BLOCK_SPECS.messageError);
    const loading = requireTemplate<never>(CORE_BLOCK_SPECS.loading);
    const retry = requireTemplate<never>(CORE_BLOCK_SPECS.retry);
    const composer = requireTemplate(CORE_BLOCK_SPECS.composer);
    const launcher = requireTemplate<never>(CORE_BLOCK_SPECS.launcher);
    const panel = requireTemplate(CORE_BLOCK_SPECS.panel);

    if (
        messageUser === null ||
        messageAssistant === null ||
        messageError === null ||
        loading === null ||
        retry === null ||
        composer === null ||
        launcher === null ||
        panel === null
    ) {
        return { templates: null, errors: errors };
    }

    return {
        templates: { messageUser, messageAssistant, messageError, loading, retry, composer, launcher, panel },
        errors: [],
    };
};

const readMaxMessageLength = (chat: HTMLElement): number => {
    const parsed = Number.parseInt(chat.getAttribute(MAX_MESSAGE_LENGTH_ATTRIBUTE) ?? '', DECIMAL_RADIX);

    return Number.isNaN(parsed) || parsed < 0 ? 0 : parsed;
};

export interface ChatMountParts {
    readonly chat: HTMLElement;
    readonly templates: ChatCoreTemplates;
    readonly services: ChatServiceDirectory;
    readonly events: ChatEventBus;
    readonly takeBrokenRequirements: () => readonly Error[];
}

export const mountCore = (parts: ChatMountParts): MountedCore => {
    const { chat, templates, services: directory, events } = parts;
    const launcher = templates.launcher.create();
    const panel = templates.panel.create();
    const composerBlock = templates.composer.create();
    const lock = directory.get(REQUEST_LOCK);
    let conversation: ChatConversation | null = null;

    panel.references.composer.append(composerBlock.root);

    const transcript = createTranscript({
        elements: {
            transcript: panel.references.transcript,
            scroller: panel.references.scroller,
            welcomeZone: panel.find(WELCOME_ZONE_REFERENCE),
        },
        templates: templates,
        events: events,
        onFeatureContent: () => conversation?.closeAssistantMessage(),
    });
    const composer: ChatComposer = createComposer({
        block: composerBlock,
        maxMessageLength: readMaxMessageLength(chat),
        canSend: () => !lock.isBusy(),
        onSend: (message: string) => {
            conversation?.send(message).catch((error: unknown) => reportError(error));
        },
        onStop: () => conversation?.stop(),
    });
    const mountedConversation = createConversation({
        chat: chat,
        api: directory.get(API),
        parser: directory.get(STREAM_PARSER),
        markdown: directory.get(MARKDOWN),
        state: directory.get(STATE),
        lock: lock,
        transcript: transcript,
        composer: composer,
        events: events,
        takeBrokenRequirements: parts.takeBrokenRequirements,
    });

    conversation = mountedConversation;
    chat.append(launcher.root, panel.root);

    const moreToggle = panel.find(MORE_TOGGLE_REFERENCE);
    const moreMenu = panel.find(MORE_MENU_REFERENCE);
    const menu: ChatMenu | null =
        moreToggle !== null && moreMenu !== null
            ? createMenu({ toggle: moreToggle, menu: moreMenu, itemSelector: MENU_ITEM_SELECTOR })
            : null;

    const panelControl: ChatPanel = createPanel({
        chat: chat,
        launcher: launcher.root,
        panel: panel.root,
        closeButton: panel.references.close,
        expandButton: panel.find(EXPAND_REFERENCE),
        scroller: panel.references.scroller,
        footer: panel.references.composer,
        events: events,
        focusComposer: () => composer.focus(),
    });

    panel.find(NEW_CONVERSATION_REFERENCE)?.addEventListener('click', () => mountedConversation.reset());

    const featurePanel: ChatPanelControl = Object.freeze({
        isOpen: panelControl.isOpen,
        open: panelControl.open,
        restore: panelControl.restore,
        showView: panelControl.showView,
        hideView: panelControl.hideView,
        close: panelControl.close,
        toggle: panelControl.toggle,
        isExpanded: panelControl.isExpanded,
        setExpanded: panelControl.setExpanded,
    });

    return {
        services: directory,
        conversation: mountedConversation,
        transcript: Object.freeze({
            element: transcript.element,
            scroller: transcript.scroller,
            append: transcript.append,
            currentLoading: transcript.currentLoading,
            scrollToEnd: transcript.scrollToEnd,
            removeAfter: transcript.removeAfter,
            captureFollow: transcript.captureFollow,
        }),
        composer: Object.freeze({
            input: composer.input,
            read: composer.read,
            write: composer.write,
            clear: composer.clear,
            focus: composer.focus,
        }),
        panel: featurePanel,
        handle: createChatHandle({
            element: chat,
            conversation: mountedConversation,
            panel: featurePanel,
            events: events,
        }),
        unmount: (): void => {
            mountedConversation.dispose();
            menu?.dispose();
            panelControl.dispose();
            launcher.root.remove();
            panel.root.remove();
        },
    };
};
