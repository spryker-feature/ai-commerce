import type { ChatFeature, ChatFeatureContext } from '@shared/ai-commerce/Chat/core/contracts/context';
import { CHAT_TURN_STATE } from '@shared/ai-commerce/Chat/core/contracts/context';
import type { ChatStreamEvent } from '@shared/ai-commerce/Chat/core/contracts/stream';
import { STREAM_EVENT_TYPE } from '@shared/ai-commerce/Chat/core/contracts/stream';
import { announceFeature } from '@shared/ai-commerce/Chat/core/contracts/access';
import type { ChatToolCalls } from '@shared/ai-commerce/Chat/features/tool-calls/tool-calls';

export const LOADING_LABEL_TAG_NAME = 'chat-loading-label';

const SOURCE_FILE = 'Shared/AiCommerce/Theme/default/Chat/features/loading-label/loading-label.ts';
const TOOL_CALLS_TAG_NAME = 'chat-tool-calls';
const LABEL_REFERENCE = 'label';
const STATUS_REFERENCE = 'status';
const RUNNING_TOOL_TEXT_ATTRIBUTE = 'running-tool-text';
const TOOL_NAME_PLACEHOLDER = '%toolName%';

export class ChatLoadingLabel extends HTMLElement implements ChatFeature {
    readonly requires = [TOOL_CALLS_TAG_NAME];

    private context: ChatFeatureContext | null = null;
    private toolCalls: ChatToolCalls | null = null;
    private runningToolText = '';
    private readonly defaultStatuses = new WeakMap<HTMLElement, string>();

    connectedCallback(): void {
        announceFeature(this);
    }

    initialize(context: ChatFeatureContext): void {
        const toolCalls = context.features.find(TOOL_CALLS_TAG_NAME) as ChatToolCalls | null;
        const runningToolText = this.getAttribute(RUNNING_TOOL_TEXT_ATTRIBUTE) ?? '';

        if (toolCalls === null || typeof toolCalls.tools?.labelFor !== 'function') {
            throw new Error(
                `<${LOADING_LABEL_TAG_NAME}> takes the tool labels from <${TOOL_CALLS_TAG_NAME}>, which is not ` +
                    `initialized (reported by ${SOURCE_FILE}). Keep features.toolCalls enabled in the host embed.`,
            );
        }

        if (!runningToolText.includes(TOOL_NAME_PLACEHOLDER)) {
            throw new Error(
                `<${LOADING_LABEL_TAG_NAME}> has no "${RUNNING_TOOL_TEXT_ATTRIBUTE}" text with ` +
                    `${TOOL_NAME_PLACEHOLDER} (reported by ${SOURCE_FILE}). Render the element through the ` +
                    `featureLoadingLabel block, or keep the attribute with the translated text in its override.`,
            );
        }

        this.context = context;
        this.toolCalls = toolCalls;
        this.runningToolText = runningToolText;
        context.events.on('turn:state', (change) => {
            // The core shows the indicator right after it reports `sending`, in the same task.
            if (change.state === CHAT_TURN_STATE.sending) {
                queueMicrotask(() => this.setLabel(null));
            }
        });
        context.events.on('stream', (event: ChatStreamEvent) => this.handleStreamEvent(event));
    }

    dispose(): void {
        this.context = null;
        this.toolCalls = null;
        this.runningToolText = '';
    }

    private handleStreamEvent(event: ChatStreamEvent): void {
        switch (event.type) {
            case STREAM_EVENT_TYPE.toolInputStart:
            case STREAM_EVENT_TYPE.toolInputAvailable:
                this.setLabel(this.toolCalls?.tools.labelFor(event.toolName ?? '') ?? '');
                break;
            case STREAM_EVENT_TYPE.toolOutputAvailable:
            case STREAM_EVENT_TYPE.toolOutputDenied:
                this.setLabel(null);
                break;
        }
    }

    private setLabel(toolLabel: string | null): void {
        const loading = this.context?.transcript.currentLoading() ?? null;

        if (loading === null) {
            return;
        }

        const label = loading.querySelector<HTMLElement>(`[data-ref="${LABEL_REFERENCE}"]`);
        const status = loading.querySelector<HTMLElement>(`[data-ref="${STATUS_REFERENCE}"]`);
        const text =
            toolLabel === null || toolLabel === ''
                ? ''
                : this.runningToolText.replace(TOOL_NAME_PLACEHOLDER, () => toolLabel);

        if (label !== null) {
            label.textContent = text;
            label.hidden = text === '';
        }

        if (status !== null) {
            if (!this.defaultStatuses.has(loading)) {
                this.defaultStatuses.set(loading, status.textContent ?? '');
            }

            status.textContent = text === '' ? (this.defaultStatuses.get(loading) ?? '') : text;
        }
    }
}
