export class BackofficeAssistantApi {
    static #endpoints = {
        prompt: '/ai-commerce/backoffice-assistant-prompt/index',
        histories: '/ai-commerce/backoffice-assistant-conversation/index',
        detail: '/ai-commerce/backoffice-assistant-conversation/detail',
        delete: '/ai-commerce/backoffice-assistant-conversation/delete',
    };

    async fetchHistories() {
        const response = await fetch(BackofficeAssistantApi.#endpoints.histories, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
        });

        return await response.json();
    }

    async fetchConversationDetail(conversationReference) {
        const url = `${BackofficeAssistantApi.#endpoints.detail}?conversationReference=${encodeURIComponent(conversationReference)}`;
        const response = await fetch(url, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
        });

        return await response.json();
    }

    async deleteConversation(conversationReference) {
        const csrfToken = window.BackofficeAssistantConfig?.csrfToken ?? '';
        const response = await fetch(BackofficeAssistantApi.#endpoints.delete, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({ conversation_reference: conversationReference, _token: csrfToken }),
        });

        if (!response.ok) {
            throw new Error('Delete failed');
        }
    }

    async sendPrompt(body, signal) {
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

        return fetch(BackofficeAssistantApi.#endpoints.prompt, options);
    }
}
