import type { ChatTransport, ChatTransportRequest } from '../contracts/services';

const withQuery = (url: string, query: Readonly<Record<string, string>> | undefined): string => {
    if (query === undefined || Object.keys(query).length === 0) {
        return url;
    }

    const separator = url.includes('?') ? '&' : '?';

    return `${url}${separator}${new URLSearchParams(query).toString()}`;
};

export class FetchChatTransport implements ChatTransport {
    send(request: ChatTransportRequest): Promise<Response> {
        // The Yves controllers treat X-Requested-With as the marker of a same-origin script request.
        const headers: Record<string, string> = { 'X-Requested-With': 'XMLHttpRequest', ...request.headers };
        const hasBody = request.body !== undefined;

        if (hasBody) {
            headers['Content-Type'] = 'application/json';
        }

        return fetch(withQuery(request.url, request.query), {
            method: request.method,
            headers: headers,
            body: hasBody ? JSON.stringify(request.body) : undefined,
            credentials: 'same-origin',
            signal: request.signal,
        });
    }
}
