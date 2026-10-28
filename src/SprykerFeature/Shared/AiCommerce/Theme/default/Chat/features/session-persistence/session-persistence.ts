import type { ChatFeature, ChatFeatureContext, ChatTurnEnd } from '@shared/ai-commerce/Chat/core/contracts/context';
import { CHAT_TURN_STATE } from '@shared/ai-commerce/Chat/core/contracts/context';
import { announceFeature } from '@shared/ai-commerce/Chat/core/contracts/access';

const SOURCE_FILE = 'Shared/AiCommerce/Theme/default/Chat/features/session-persistence/session-persistence.ts';
const STORAGE_KEY_ATTRIBUTE = 'storage-key';

interface StoredSession {
    readonly conversationReference: string | null;
    readonly isOpen: boolean;
    readonly isExpanded: boolean;
}

const EMPTY_SESSION: StoredSession = { conversationReference: null, isOpen: false, isExpanded: false };

let isStorageFailureReported = false;

const reportStorageFailure = (storageKey: string, operation: string, error: unknown): void => {
    if (isStorageFailureReported) {
        return;
    }

    isStorageFailureReported = true;
    reportError(
        new Error(
            `<chat-session-persistence> could not ${operation} localStorage under "${storageKey}" (reported by ` +
                `${SOURCE_FILE}); the next page starts a new conversation with the panel closed. Check whether the ` +
                `browser blocks or filled the site storage, or remove a broken value under that key; reported once ` +
                `per page.`,
            { cause: error },
        ),
    );
};

const isRecord = (value: unknown): value is Record<string, unknown> =>
    typeof value === 'object' && value !== null && !Array.isArray(value);

export class ChatSessionPersistence extends HTMLElement implements ChatFeature {
    private session: StoredSession = EMPTY_SESSION;

    connectedCallback(): void {
        announceFeature(this);
    }

    initialize(context: ChatFeatureContext): void {
        this.session = this.read();

        context.events.on('turn:end', (end: ChatTurnEnd) => {
            if (end.state === CHAT_TURN_STATE.done) {
                this.write({ ...this.session, conversationReference: context.state.conversationReference });
            }
        });
        context.events.on('conversation:loaded', (loaded) =>
            this.write({ ...this.session, conversationReference: loaded.conversationReference }),
        );
        context.events.on('conversation:reset', () => this.write({ ...this.session, conversationReference: null }));
        context.events.on('panel:toggle', (toggle) => this.write({ ...this.session, isOpen: toggle.isOpen }));
        context.events.on('panel:expand', (expand) => this.write({ ...this.session, isExpanded: expand.isExpanded }));

        const { conversationReference, isOpen, isExpanded } = this.session;

        if (isExpanded) {
            context.panel.setExpanded(true);
        }

        if (isOpen) {
            context.panel.restore();
        }

        if (conversationReference !== null) {
            context.conversation.load(conversationReference).catch((error: unknown) => reportError(error));
        }
    }

    dispose(): void {
        this.session = EMPTY_SESSION;
    }

    private requireStorageKey(): string {
        const storageKey = this.getAttribute(STORAGE_KEY_ATTRIBUTE) ?? '';

        if (storageKey === '') {
            throw new Error(
                `<chat-session-persistence> has no "${STORAGE_KEY_ATTRIBUTE}" attribute, so it does not know where ` +
                    `to keep the session (reported by ${SOURCE_FILE}). Render the feature through the ` +
                    `featureSessionPersistence block of chat.twig, or pass features.sessionPersistence.storageKey.`,
            );
        }

        return storageKey;
    }

    private read(): StoredSession {
        const storageKey = this.requireStorageKey();
        let stored: unknown;

        try {
            stored = JSON.parse(localStorage.getItem(storageKey) ?? 'null');
        } catch (error) {
            reportStorageFailure(storageKey, 'read', error);

            return EMPTY_SESSION;
        }

        if (!isRecord(stored)) {
            return EMPTY_SESSION;
        }

        const conversationReference = stored.conversationReference;

        return {
            conversationReference:
                typeof conversationReference === 'string' && conversationReference !== ''
                    ? conversationReference
                    : null,
            isOpen: stored.isOpen === true,
            isExpanded: stored.isExpanded === true,
        };
    }

    private write(session: StoredSession): void {
        const storageKey = this.requireStorageKey();

        this.session = session;

        try {
            localStorage.setItem(storageKey, JSON.stringify(session));
        } catch (error) {
            reportStorageFailure(storageKey, 'write', error);
        }
    }
}
