import { ENDPOINTS } from './constants';

export class BackofficeAssistantApi {
    fetchHistories() {
        return fetch(ENDPOINTS.histories, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
        }).then((response) => response.json());
    }

    fetchConversationDetail(conversationReference) {
        const url = ENDPOINTS.detail + '?conversationReference=' + encodeURIComponent(conversationReference);

        return fetch(url, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
        }).then((response) => response.json());
    }

    deleteConversation(conversationReference) {
        const csrfToken = window.BackofficeAssistantConfig?.csrfToken ?? '';

        return fetch(ENDPOINTS.delete, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({ conversation_reference: conversationReference, _token: csrfToken }),
        }).then((response) => {
            if (!response.ok) {
                throw new Error('Delete failed');
            }
        });
    }

    sendPrompt(body, signal) {
        const csrfToken = window.BackofficeAssistantConfig?.csrfToken ?? '';
        const options = {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({ ...body, _token: csrfToken }),
        };

        if (signal) {
            options.signal = signal;
        }

        return fetch(ENDPOINTS.prompt, options);
    }
}
