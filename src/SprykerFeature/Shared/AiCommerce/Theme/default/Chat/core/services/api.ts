import { raiseSessionExpired } from '../contracts/access';
import { ChatSessionExpiredError, createChatHttpError } from '../contracts/api';
import type {
    ChatApi,
    ChatConversationList,
    ChatConversationSummary,
    ChatPromptPayload,
    ChatStoredMessage,
} from '../contracts/api';
import type { ChatTransport, ChatTransportRequest } from '../contracts/services';
import { TRANSPORT } from '../contracts/tokens';
import type { ChatService, ChatServiceEnvironment } from '../contracts/tokens';

const SOURCE_FILE = 'Shared/AiCommerce/Theme/default/Chat/core/services/api.ts';
const UNAUTHORIZED_STATUS = 401;
const FORBIDDEN_STATUS = 403;
const EVENT_STREAM_CONTENT_TYPE = 'text/event-stream';
const CONTENT_TYPE_HEADER = 'content-type';
const CSRF_TOKEN_ATTRIBUTE = 'csrf-token';

const URL_ATTRIBUTE = {
    prompt: 'prompt-url',
    conversations: 'conversations-url',
    conversationMessages: 'conversation-messages-url',
    deleteConversation: 'delete-conversation-url',
} as const;

type ChatApiUrlAttribute = (typeof URL_ATTRIBUTE)[keyof typeof URL_ATTRIBUTE];

const isRecord = (value: unknown): value is Record<string, unknown> =>
    typeof value === 'object' && value !== null && !Array.isArray(value);

const describeJsonValue = (value: unknown): string => {
    if (value === undefined) {
        return 'no such key';
    }

    if (value === null) {
        return 'null';
    }

    return Array.isArray(value) ? 'a list' : `a ${typeof value}`;
};

const createMissingKeyError = (url: string, status: number, key: string, expected: string, value: unknown): Error =>
    createChatHttpError(
        `The chat endpoint ${url} answered HTTP ${status} without ${expected} under "${key}" (it holds ` +
            `${describeJsonValue(value)}) (reported by ${SOURCE_FILE}). Check the controller behind that URL in the ` +
            `network panel; the chat expects the storefront response shape with "${key}" as ${expected}.`,
        status,
    );

const requireList = (payload: Record<string, unknown>, key: string, url: string, status: number): unknown[] => {
    const value = payload[key];

    if (!Array.isArray(value)) {
        throw createMissingKeyError(url, status, key, 'a list', value);
    }

    return value;
};

export class HttpChatApi implements ChatApi, ChatService {
    private chat: HTMLElement | null = null;
    private transport: ChatTransport | null = null;

    attach(environment: ChatServiceEnvironment): void {
        this.chat = environment.chat;
        this.transport = environment.services.get(TRANSPORT);
    }

    async prompt(payload: ChatPromptPayload, signal: AbortSignal): Promise<ReadableStream<Uint8Array>> {
        const url = this.readUrl(URL_ATTRIBUTE.prompt);
        const response = await this.request({ url: url, method: 'POST', body: payload, signal: signal });
        const contentType = response.headers.get(CONTENT_TYPE_HEADER) ?? '';

        if (!contentType.includes(EVENT_STREAM_CONTENT_TYPE) || response.body === null) {
            throw createChatHttpError(
                `The prompt endpoint ${url} answered HTTP ${response.status} with content type ` +
                    `"${contentType || 'none'}"${response.body === null ? ' and no body' : ''} instead of ` +
                    `${EVENT_STREAM_CONTENT_TYPE} (reported by ${SOURCE_FILE}). Check that the prompt-url attribute ` +
                    `points at the streaming prompt action and what that action returned in the network panel.`,
                response.status,
            );
        }

        return response.body;
    }

    async conversations(): Promise<ChatConversationList> {
        const url = this.readUrl(URL_ATTRIBUTE.conversations);
        const response = await this.request({ url: url, method: 'GET' });
        const payload = await this.readJson(response, url);
        const conversations = requireList(payload, 'conversations', url, response.status);

        return {
            conversations: conversations as ChatConversationSummary[],
            availableAgents: payload.availableAgents,
            userSelectedAgent: typeof payload.userSelectedAgent === 'string' ? payload.userSelectedAgent : null,
        };
    }

    async messages(conversationReference: string): Promise<readonly ChatStoredMessage[]> {
        const url = this.readUrl(URL_ATTRIBUTE.conversationMessages);
        const response = await this.request({
            url: url,
            method: 'GET',
            query: { conversationReference: conversationReference },
        });
        const messages = requireList(await this.readJson(response, url), 'messages', url, response.status);

        return messages as ChatStoredMessage[];
    }

    async delete(conversationReferences: readonly string[]): Promise<number> {
        const url = this.readUrl(URL_ATTRIBUTE.deleteConversation);
        const response = await this.request({
            url: url,
            method: 'DELETE',
            body: { conversationReferences: [...conversationReferences] },
        });
        const deletedCount = (await this.readJson(response, url)).deletedCount;

        if (typeof deletedCount !== 'number') {
            throw createMissingKeyError(url, response.status, 'deletedCount', 'a number', deletedCount);
        }

        return deletedCount;
    }

    private async request(request: ChatTransportRequest): Promise<Response> {
        const chat = this.requireChat();
        const csrfToken = chat.getAttribute(CSRF_TOKEN_ATTRIBUTE) ?? '';
        const headers: Record<string, string> = csrfToken === '' ? {} : { 'X-CSRF-Token': csrfToken };
        const response = await this.requireTransport().send({ ...request, headers: headers });
        const target = `${request.method} ${request.url}`;

        // fetch follows the Back Office firewall's redirect to the login page, which answers 200 with HTML.
        if (response.status === UNAUTHORIZED_STATUS || response.redirected) {
            const answer = response.redirected ? `was redirected to ${response.url}` : 'was rejected with HTTP 401';

            if (raiseSessionExpired(chat)) {
                throw createChatHttpError(
                    `The chat request ${target} ${answer}: the session has expired (reported by ${SOURCE_FILE}). The ` +
                        `chat dispatched spryker-chat:session-expired; the page reloads unless a host listener ` +
                        `cancelled that event.`,
                    response.status,
                    ChatSessionExpiredError,
                );
            }

            throw createChatHttpError(
                `The chat request ${target} ${answer} again right after a reload (reported by ${SOURCE_FILE}). The ` +
                    `chat does not reload twice within 30 seconds, to avoid a reload loop; sign in again, or check why ` +
                    `the session is not accepted by that endpoint.`,
                response.status,
            );
        }

        if (response.status === FORBIDDEN_STATUS) {
            throw createChatHttpError(
                `The chat request ${target} was refused with HTTP 403 (reported by ${SOURCE_FILE}). That is either ` +
                    `a missing permission or a rejected CSRF token; reload the page to renew the token, and if it ` +
                    `persists check the customer's access to that route and the token id the host renders.`,
                response.status,
            );
        }

        if (!response.ok) {
            throw createChatHttpError(
                `The chat request ${target} failed with HTTP ${response.status} (reported by ${SOURCE_FILE}). ` +
                    `Check the endpoint's response in the network panel and the server log.`,
                response.status,
            );
        }

        return response;
    }

    private async readJson(response: Response, url: string): Promise<Record<string, unknown>> {
        let payload: unknown;

        try {
            payload = await response.json();
        } catch {
            throw createChatHttpError(
                `The chat endpoint ${url} answered HTTP ${response.status}, but the response is not JSON (reported ` +
                    `by ${SOURCE_FILE}). Check the controller behind that URL in the network panel; an HTML page ` +
                    `here usually means a redirect to the login page or an error page.`,
                response.status,
            );
        }

        if (!isRecord(payload)) {
            throw createChatHttpError(
                `The chat endpoint ${url} answered with JSON that is not an object (reported by ${SOURCE_FILE}). ` +
                    `Check the controller behind that URL; the chat expects the storefront response shape.`,
                response.status,
            );
        }

        return payload;
    }

    private readUrl(attribute: ChatApiUrlAttribute): string {
        const url = this.requireChat().getAttribute(attribute) ?? '';

        if (url === '') {
            throw new Error(
                `<spryker-chat> has no "${attribute}" attribute, so the chat cannot reach that endpoint (reported by ` +
                    `${SOURCE_FILE}). Pass the URL in the "chat" variable of the host's ` +
                    `{% embed '@AiCommerce/Chat/core/chat.twig' %}, or provide another ChatApi under the API token.`,
            );
        }

        return url;
    }

    private requireChat(): HTMLElement {
        if (this.chat === null) {
            throw new Error(
                `HttpChatApi was used before the chat attached it (reported by ${SOURCE_FILE}). Get the API ` +
                    `through context.services.get(API) inside initialize(), not by constructing the class yourself.`,
            );
        }

        return this.chat;
    }

    private requireTransport(): ChatTransport {
        if (this.transport === null) {
            throw new Error(
                `HttpChatApi has no transport because the chat has not attached it yet (reported by ` +
                    `${SOURCE_FILE}). Get the API through context.services.get(API) inside initialize().`,
            );
        }

        return this.transport;
    }
}
