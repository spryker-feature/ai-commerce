import { STORAGE_KEY } from './constants';

export class BackofficeAssistantState {
    conversationReference = null;
    isWaiting = false;
    greetingShown = false;
    pendingAttachments = [];
    abortController = null;

    save(isOpen) {
        try {
            localStorage.setItem(
                STORAGE_KEY,
                JSON.stringify({
                    isOpen: isOpen,
                    conversationReference: this.conversationReference,
                }),
            );
        } catch {
            // localStorage may be unavailable
        }
    }

    load() {
        try {
            const raw = localStorage.getItem(STORAGE_KEY);

            return raw ? JSON.parse(raw) : null;
        } catch {
            return null;
        }
    }

    reset() {
        this.conversationReference = null;
        this.greetingShown = false;
        this.pendingAttachments = [];
    }
}
