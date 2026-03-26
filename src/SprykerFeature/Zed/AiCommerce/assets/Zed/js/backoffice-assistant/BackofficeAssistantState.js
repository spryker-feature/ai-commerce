import { STORAGE_KEY } from './constants';

export class BackofficeAssistantState {
    #conversationReference = null;
    #isWaiting = false;
    #greetingShown = false;
    #pendingAttachments = [];
    #abortController = null;

    get conversationReference() {
        return this.#conversationReference;
    }
    set conversationReference(value) {
        this.#conversationReference = value;
    }

    get isWaiting() {
        return this.#isWaiting;
    }
    set isWaiting(value) {
        this.#isWaiting = value;
    }

    get greetingShown() {
        return this.#greetingShown;
    }
    set greetingShown(value) {
        this.#greetingShown = value;
    }

    get pendingAttachments() {
        return this.#pendingAttachments;
    }
    set pendingAttachments(value) {
        this.#pendingAttachments = value;
    }

    get abortController() {
        return this.#abortController;
    }
    set abortController(value) {
        this.#abortController = value;
    }

    save(isOpen) {
        try {
            localStorage.setItem(
                STORAGE_KEY,
                JSON.stringify({
                    isOpen: isOpen,
                    conversationReference: this.#conversationReference,
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
        this.#conversationReference = null;
        this.#greetingShown = false;
        this.#pendingAttachments = [];
    }
}
