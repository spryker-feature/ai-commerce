import { CHAT_EVENT, CHAT_TAG_NAME } from '../contracts/access';
import type { ChatReadyDetail, ChatRequestDetail } from '../contracts/access';
import { createBlockResolver } from './blocks';
import type {
    ChatConversationControl,
    ChatEventSubscriber,
    ChatFeature,
    ChatFeatureContext,
    ChatPromptContributor,
} from '../contracts/context';
import { createEventBus } from '../contracts/event-bus';
import { describeFailure } from '../contracts/failure';
import { createFeatureRegistry } from './feature-registry';
import { mountCore, requireCoreTemplates } from './mount';
import type { MountedCore } from './mount';
import { createServiceRegistry } from './service-registry';
import { STATE } from '../contracts/tokens';
import { createZoneLedger } from './zones';

const SOURCE_FILE = 'Shared/AiCommerce/Theme/default/Chat/core/runtime/runtime.ts';
const STATE_ATTRIBUTE = 'data-state';

const CHAT_STATE = {
    starting: 'starting',
    ready: 'ready',
    failed: 'failed',
} as const;

export interface ChatRuntime {
    start(): void;
    stop(): void;
}

const whenDocumentParsed = (): Promise<void> =>
    document.readyState !== 'loading'
        ? Promise.resolve()
        : new Promise((resolve) => document.addEventListener('DOMContentLoaded', () => resolve(), { once: true }));

const readEventDetail = (event: Event): unknown => (event instanceof CustomEvent ? (event.detail as unknown) : null);

const callSafely = (callback: () => void): void => {
    try {
        callback();
    } catch (error) {
        reportError(error);
    }
};

const createFailedError = (): Error =>
    new Error(
        `<${CHAT_TAG_NAME}> failed to start, so a requestChat() request cannot be served (reported by ` +
            `${SOURCE_FILE}): the start-up errors reported before this one name the cause. Fix them and reload the ` +
            `page; until then every requestChat() call on this chat is rejected at once.`,
    );

const createStoppedError = (): Error =>
    new Error(
        `<${CHAT_TAG_NAME}> stopped before a requestChat() request was served (reported by ${SOURCE_FILE}): the ` +
            `element was removed from the page or failed to start. Call requestChat() again once the chat is ` +
            `connected and ready, or check the start-up error reported before this one.`,
    );

export const createChatRuntime = (chat: HTMLElement): ChatRuntime => {
    const services = createServiceRegistry();
    const events = createEventBus();
    const zones = createZoneLedger(chat);
    const releaseByFeature = new Map<ChatFeature, () => void>();
    const pendingRequests: ChatRequestDetail[] = [];
    const featuresWithCollectedProvisions = new WeakSet<ChatFeature>();
    let core: MountedCore | null = null;
    let isStopped = false;

    const requireCore = (): MountedCore => {
        if (core === null) {
            throw new Error(
                `A chat feature was initialized before <${CHAT_TAG_NAME}> mounted its core (reported by ` +
                    `${SOURCE_FILE}). This is a defect of the core start-up order; report it with the page markup.`,
            );
        }

        return core;
    };

    const createFeatureContext = (feature: ChatFeature, mounted: MountedCore): ChatFeatureContext => {
        const subscriber = events.scope();
        const conversation: ChatConversationControl = Object.freeze({
            get turnState() {
                return mounted.conversation.turnState;
            },
            send: mounted.conversation.send,
            stop: mounted.conversation.stop,
            reset: mounted.conversation.reset,
            load: mounted.conversation.load,
            createPayload: mounted.conversation.createPayload,
            regenerate: mounted.conversation.regenerate,
            contribute: (contributor: ChatPromptContributor): void =>
                mounted.conversation.contributeFor(feature, contributor),
        });
        const featureEvents: ChatEventSubscriber = Object.freeze({ on: subscriber.on });

        releaseByFeature.set(feature, () => {
            subscriber.release();
            mounted.conversation.release(feature);
            zones.release(feature);
        });

        return Object.freeze({
            chat: chat,
            features: registry.directory,
            services: mounted.services,
            blocks: createBlockResolver(chat, feature.localName),
            zones: zones.forOwner(feature, feature.localName),
            state: mounted.services.get(STATE),
            events: featureEvents,
            conversation: conversation,
            transcript: mounted.transcript,
            composer: mounted.composer,
            panel: mounted.panel,
        });
    };

    const releaseFeature = (feature: ChatFeature): void => {
        releaseByFeature.get(feature)?.();
        releaseByFeature.delete(feature);
    };

    const disposeFeature = (feature: ChatFeature): void => {
        try {
            feature.dispose();
        } catch (error) {
            reportError(
                new Error(
                    `Chat feature <${feature.localName}> threw in dispose(): ${describeFailure(error)} (reported by ` +
                        `${SOURCE_FILE}). The core still released its subscriptions and zone elements; make ` +
                        `dispose() undo initialize() without throwing.`,
                    { cause: error },
                ),
            );
        } finally {
            releaseFeature(feature);
        }
    };

    const collectProvisions = (feature: ChatFeature): void => {
        if (featuresWithCollectedProvisions.has(feature)) {
            return;
        }

        featuresWithCollectedProvisions.add(feature);

        for (const provision of Array.isArray(feature.provides) ? feature.provides : []) {
            services.provide(provision, feature);
        }
    };

    const initializeFeature = (feature: ChatFeature): boolean => {
        collectProvisions(feature);

        try {
            feature.initialize(createFeatureContext(feature, requireCore()));

            return true;
        } catch (error) {
            reportError(
                new Error(
                    `Chat feature <${feature.localName}> failed to initialize and stays inactive: ` +
                        `${describeFailure(error)} (reported by ${SOURCE_FILE}).`,
                    { cause: error },
                ),
            );
            disposeFeature(feature);

            return false;
        }
    };

    const registry = createFeatureRegistry({
        chat: chat,
        initializeFeature: initializeFeature,
        disposeFeature: disposeFeature,
    });

    const rejectRequest = (request: ChatRequestDetail, error: Error): void => {
        const reject = request.reject;

        if (reject === undefined) {
            reportError(error);
        } else {
            callSafely(() => reject(error));
        }
    };

    const fail = (errors: readonly unknown[]): void => {
        for (const error of errors) {
            reportError(error);
        }

        chat.setAttribute(STATE_ATTRIBUTE, CHAT_STATE.failed);

        for (const request of pendingRequests.splice(0)) {
            rejectRequest(request, createFailedError());
        }
    };

    const startUp = async (): Promise<void> => {
        // A chat defined while the parser is still inside it would not see its templates yet.
        await whenDocumentParsed();

        if (isStopped) {
            return;
        }

        const { templates, errors } = requireCoreTemplates(createBlockResolver(chat, CHAT_TAG_NAME));

        if (templates === null) {
            fail(errors);

            return;
        }

        await registry.settle();

        if (isStopped) {
            return;
        }

        for (const feature of registry.registeredFeatures()) {
            collectProvisions(feature);
        }

        // Host listeners may still provide services synchronously; resolution happens right after this dispatch.
        chat.dispatchEvent(new CustomEvent(CHAT_EVENT.init, { bubbles: true, composed: true }));

        const mounted = mountCore({
            chat: chat,
            templates: templates,
            services: services.resolve(chat),
            events: events,
            takeBrokenRequirements: () => registry.takeBrokenRequirements(),
        });

        core = mounted;
        registry.ignoreSubtree(mounted.transcript.element);
        registry.initializeFeatures();
        chat.setAttribute(STATE_ATTRIBUTE, CHAT_STATE.ready);

        const detail: ChatReadyDetail = {
            handle: mounted.handle,
            features: registry.directory.list().map((feature) => feature.localName),
        };

        chat.dispatchEvent(new CustomEvent(CHAT_EVENT.ready, { bubbles: true, composed: true, detail: detail }));

        for (const request of pendingRequests.splice(0)) {
            callSafely(() => request.respond(mounted.handle));
        }
    };

    const handleProvide = (event: Event): void => {
        // A nested chat resolves its own services.
        event.stopPropagation();
        services.provide(readEventDetail(event), event.target);
    };

    const handleRequest = (event: Event): void => {
        const detail = readEventDetail(event);
        const respond = typeof detail === 'object' && detail !== null && 'respond' in detail ? detail.respond : null;
        const reject = typeof detail === 'object' && detail !== null && 'reject' in detail ? detail.reject : null;

        if (typeof respond !== 'function') {
            reportError(
                new Error(
                    `<${CHAT_TAG_NAME}> received ${CHAT_EVENT.request} without a respond callback (reported by ` +
                        `${SOURCE_FILE}). Use requestChat(target, callback) from Chat/core/contracts/access.ts.`,
                ),
            );

            return;
        }

        const request: ChatRequestDetail = {
            respond: respond as ChatRequestDetail['respond'],
            reject: typeof reject === 'function' ? (reject as NonNullable<ChatRequestDetail['reject']>) : undefined,
        };
        const mounted = core;

        if (mounted !== null && chat.getAttribute(STATE_ATTRIBUTE) === CHAT_STATE.ready) {
            callSafely(() => request.respond(mounted.handle));
        } else if (chat.getAttribute(STATE_ATTRIBUTE) === CHAT_STATE.failed) {
            rejectRequest(request, createFailedError());
        } else {
            pendingRequests.push(request);
        }
    };

    return {
        start: (): void => {
            chat.setAttribute(STATE_ATTRIBUTE, CHAT_STATE.starting);
            chat.addEventListener(CHAT_EVENT.provide, handleProvide);
            chat.addEventListener(CHAT_EVENT.request, handleRequest);
            registry.connect();
            startUp().catch((error: unknown) => fail([error]));
        },
        stop: (): void => {
            isStopped = true;
            chat.removeEventListener(CHAT_EVENT.provide, handleProvide);
            chat.removeEventListener(CHAT_EVENT.request, handleRequest);
            registry.disconnect();
            core?.unmount();
            core = null;
            events.clear();

            for (const request of pendingRequests.splice(0)) {
                rejectRequest(request, createStoppedError());
            }

            chat.removeAttribute(STATE_ATTRIBUTE);
        },
    };
};
