import { CHAT_EVENT } from '../contracts/access';
import { describeFailure } from '../contracts/failure';
import { HttpChatApi } from '../services/api';
import { MarkedChatMarkdown } from '../markdown/markdown';
import { SingleRequestLock } from '../services/request-lock';
import { DomPurifyChatSanitizer } from '../services/sanitizer';
import { ChatConversationState } from '../services/state';
import { EventSourceChatStreamParser } from '../services/stream-parser';
import { FetchChatTransport } from '../services/transport';
import {
    API,
    MARKDOWN,
    REQUEST_LOCK,
    SANITIZER,
    SERVICE_TOKENS,
    STATE,
    STREAM_PARSER,
    TRANSPORT,
} from '../contracts/tokens';
import type {
    ChatService,
    ChatServiceClass,
    ChatServiceDirectory,
    ChatServiceProvision,
    ChatServiceToken,
} from '../contracts/tokens';

const SOURCE_FILE = 'Shared/AiCommerce/Theme/default/Chat/core/runtime/service-registry.ts';

const DEFAULT_SERVICE_CLASSES: Readonly<Record<string, ChatServiceClass<unknown>>> = {
    [TRANSPORT.name]: FetchChatTransport,
    [STREAM_PARSER.name]: EventSourceChatStreamParser,
    [SANITIZER.name]: DomPurifyChatSanitizer,
    [MARKDOWN.name]: MarkedChatMarkdown,
    [STATE.name]: ChatConversationState,
    [REQUEST_LOCK.name]: SingleRequestLock,
    [API.name]: HttpChatApi,
};

const KNOWN_TOKEN_NAMES = SERVICE_TOKENS.map((token) => token.name).join(', ');

export interface ChatServiceRegistry {
    provide(detail: unknown, origin: EventTarget | null): void;
    resolve(chat: HTMLElement): ChatServiceDirectory;
}

const isRecord = (value: unknown): value is Record<string, unknown> => typeof value === 'object' && value !== null;

const isProvision = (detail: unknown): detail is ChatServiceProvision =>
    isRecord(detail) &&
    isRecord(detail.token) &&
    typeof detail.token.name === 'string' &&
    typeof detail.class === 'function';

const describeOrigin = (origin: EventTarget | null): string =>
    origin instanceof Element ? `<${origin.localName}>` : 'a script';

export const createServiceRegistry = (): ChatServiceRegistry => {
    const providedClasses = new Map<string, ChatServiceClass<unknown>>();
    const providedBy = new Map<string, string>();
    let isResolved = false;

    const provide = (detail: unknown, origin: EventTarget | null): void => {
        if (!isProvision(detail)) {
            reportError(
                new Error(
                    `${describeOrigin(origin)} provided a chat service that is not { token, class } (reported by ` +
                        `${SOURCE_FILE}). Use provideChatService(target, TOKEN, ServiceClass) from ` +
                        `Chat/core/contracts/access.ts, or list { token: TOKEN, class: ServiceClass } in the feature's provides.`,
                ),
            );

            return;
        }

        const tokenName = detail.token.name;

        if (isResolved) {
            reportError(
                new Error(
                    `${describeOrigin(origin)} provided the chat service "${tokenName}" after <spryker-chat> had ` +
                        `resolved its services (reported by ${SOURCE_FILE}). The default service stays; a feature ` +
                        `declares it in provides and must be defined at start-up, host code provides it from a ` +
                        `${CHAT_EVENT.init} listener.`,
                ),
            );

            return;
        }

        if (!Object.hasOwn(DEFAULT_SERVICE_CLASSES, tokenName)) {
            reportError(
                new Error(
                    `${describeOrigin(origin)} provided a chat service under the unknown token "${tokenName}" ` +
                        `(reported by ${SOURCE_FILE}). Use one of the tokens exported by Chat/core/contracts/tokens.ts: ` +
                        `${KNOWN_TOKEN_NAMES}.`,
                ),
            );

            return;
        }

        const previousOrigin = providedBy.get(tokenName);

        if (previousOrigin !== undefined) {
            reportError(
                new Error(
                    `The chat service "${tokenName}" is provided twice, by ${previousOrigin} and by ` +
                        `${describeOrigin(origin)} (reported by ${SOURCE_FILE}); the one from ` +
                        `${describeOrigin(origin)} is used. Remove one of the two provisions.`,
                ),
            );
        }

        providedClasses.set(tokenName, detail.class);
        providedBy.set(tokenName, describeOrigin(origin));
    };

    const createService = (token: ChatServiceToken<unknown>): unknown => {
        const serviceClass = providedClasses.get(token.name) ?? DEFAULT_SERVICE_CLASSES[token.name];

        try {
            return new serviceClass();
        } catch (error) {
            throw new Error(
                `The chat service "${token.name}" (${serviceClass.name}) could not be created: ` +
                    `${describeFailure(error)} (reported by ${SOURCE_FILE}). Check the class provided under that ` +
                    `token; service classes take no constructor arguments and receive the chat in attach().`,
                { cause: error },
            );
        }
    };

    const resolve = (chat: HTMLElement): ChatServiceDirectory => {
        const instances = new Map<string, unknown>(SERVICE_TOKENS.map((token) => [token.name, createService(token)]));

        const directory: ChatServiceDirectory = Object.freeze({
            get: <Service>(token: ChatServiceToken<Service>): Service => {
                if (!instances.has(token.name)) {
                    throw new Error(
                        `No chat service is registered under the token "${token.name}" (reported by ` +
                            `${SOURCE_FILE}). Use one of the tokens exported by Chat/core/contracts/tokens.ts: ` +
                            `${KNOWN_TOKEN_NAMES}.`,
                    );
                }

                return instances.get(token.name) as Service;
            },
        });

        isResolved = true;

        // Every service exists before any attaches, so attach() may look up any other service.
        for (const token of SERVICE_TOKENS) {
            (instances.get(token.name) as ChatService).attach?.({ chat: chat, services: directory });
        }

        return directory;
    };

    return { provide: provide, resolve: resolve };
};
