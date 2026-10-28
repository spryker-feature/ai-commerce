import type { ChatFeature, ChatFeatureContext } from '@shared/ai-commerce/Chat/core/contracts/context';
import { CHAT_TURN_STATE } from '@shared/ai-commerce/Chat/core/contracts/context';
import type { ChatStoredMessage } from '@shared/ai-commerce/Chat/core/contracts/api';
import type { ChatBlockTemplate } from '@shared/ai-commerce/Chat/core/contracts/blocks';
import type { ChatStreamEvent } from '@shared/ai-commerce/Chat/core/contracts/stream';
import { STREAM_EVENT_TYPE } from '@shared/ai-commerce/Chat/core/contracts/stream';
import { CHAT_MESSAGE_ROLE } from '@shared/ai-commerce/Chat/core/contracts/services';
import { announceFeature } from '@shared/ai-commerce/Chat/core/contracts/access';
import { describeFailure } from '@shared/ai-commerce/Chat/core/contracts/failure';

export const TOOL_CALLS_TAG_NAME = 'chat-tool-calls';

const SOURCE_FILE = 'Shared/AiCommerce/Theme/default/Chat/features/tool-calls/tool-calls.ts';
const TOOL_LABELS_ATTRIBUTE = 'tool-labels';
const HIDE_UNCLAIMED_RESULTS_ATTRIBUTE = 'hide-unclaimed-results';
const JSON_INDENTATION = 2;
const RESULT_ID_PREFIX = 'chat-tool-call-result-';
let resultIdSequence = 0;

const TOOL_CALL_BLOCK = {
    name: 'toolCall',
    references: ['name', 'toggle', 'showLabel', 'hideLabel', 'result'],
} as const;
const TOOL_CALL_DENIED_BLOCK = { name: 'toolCallDenied', references: [] } as const;

type ToolCallReference = (typeof TOOL_CALL_BLOCK.references)[number];

export interface ChatToolCall {
    readonly toolName: string;
    readonly toolCallId: string;
    readonly result: string;
}

export type ChatToolRenderer = (call: ChatToolCall) => Element | null;

export interface ChatToolRegistry {
    /** Returns the unregister function. Callers list this tag in `requires` and use `import type` only. */
    register(toolName: string, renderer: ChatToolRenderer): () => void;
    labelFor(toolName: string): string;
}

const isRecord = (value: unknown): value is Record<string, unknown> =>
    typeof value === 'object' && value !== null && !Array.isArray(value);

const humanizeToolName = (toolName: string): string => {
    const words = toolName.replace(/[_-]+/g, ' ').trim();

    return words === '' ? toolName : words.replace(/\b\w/g, (character: string) => character.toUpperCase());
};

const prettyPrint = (result: string): string => {
    try {
        return JSON.stringify(JSON.parse(result), null, JSON_INDENTATION);
    } catch {
        return result;
    }
};

const createResultId = (toolCallId: string): string => {
    const readableId = toolCallId.replace(/[^\w-]+/g, '');

    resultIdSequence += 1;

    return `${RESULT_ID_PREFIX}${readableId === '' ? '' : `${readableId}-`}${resultIdSequence}`;
};

const readResult = (output: unknown): string => {
    const result = isRecord(output) ? output.result : output;

    if (result === undefined || result === null) {
        return '';
    }

    return typeof result === 'string' ? result : JSON.stringify(result);
};

export class ChatToolCalls extends HTMLElement implements ChatFeature {
    private readonly renderersByToolName = new Map<string, ChatToolRenderer[]>();
    private readonly toolNamesByCallId = new Map<string, string>();
    private readonly renderedElements = new Set<Element>();
    private context: ChatFeatureContext | null = null;
    private toolCallTemplate: ChatBlockTemplate<ToolCallReference> | null = null;
    private deniedTemplate: ChatBlockTemplate<never> | null = null;
    private toolLabels: Readonly<Record<string, string>> = {};
    private isDeniedShown = false;

    readonly tools: ChatToolRegistry = Object.freeze({
        register: (toolName: string, renderer: ChatToolRenderer): (() => void) => {
            const renderers = this.renderersByToolName.get(toolName) ?? [];

            renderers.push(renderer);
            this.renderersByToolName.set(toolName, renderers);

            return () => {
                const remaining = (this.renderersByToolName.get(toolName) ?? []).filter(
                    (registered) => registered !== renderer,
                );

                this.renderersByToolName.set(toolName, remaining);
            };
        },
        labelFor: (toolName: string): string => {
            const name = toolName.trim();

            return name === '' ? '' : (this.toolLabels[name] ?? humanizeToolName(name));
        },
    });

    connectedCallback(): void {
        announceFeature(this);
    }

    initialize(context: ChatFeatureContext): void {
        this.context = context;
        this.toolLabels = this.readToolLabels();
        this.toolCallTemplate = context.blocks.require(TOOL_CALL_BLOCK);
        this.deniedTemplate = context.blocks.require<never>(TOOL_CALL_DENIED_BLOCK);
        context.events.on('stream', (event: ChatStreamEvent) => this.handleStreamEvent(event));
        context.events.on('turn:state', (change) => {
            if (change.state === CHAT_TURN_STATE.sending) {
                this.isDeniedShown = false;
                this.toolNamesByCallId.clear();
                this.forgetDetachedElements();
            }
        });
        context.events.on('message:stored', (stored) => this.replayStoredMessage(stored.message));
        context.events.on('conversation:reset', () => this.renderedElements.clear());
        context.events.on('conversation:loaded', () => this.forgetDetachedElements());
    }

    dispose(): void {
        for (const element of this.renderedElements) {
            element.remove();
        }

        this.renderedElements.clear();
        this.toolNamesByCallId.clear();
        this.context = null;
        this.toolCallTemplate = null;
        this.deniedTemplate = null;
    }

    private handleStreamEvent(event: ChatStreamEvent): void {
        switch (event.type) {
            case STREAM_EVENT_TYPE.toolInputStart:
            case STREAM_EVENT_TYPE.toolInputAvailable:
                this.toolNamesByCallId.set(event.toolCallId ?? '', event.toolName ?? '');
                break;
            case STREAM_EVENT_TYPE.toolOutputAvailable: {
                const toolCallId = event.toolCallId ?? '';

                this.reportUnknownCall(event.type, toolCallId);
                this.renderResult({
                    toolName: this.toolNamesByCallId.get(toolCallId) ?? '',
                    toolCallId: toolCallId,
                    result: readResult(event.output),
                });
                this.toolNamesByCallId.delete(toolCallId);
                break;
            }
            case STREAM_EVENT_TYPE.toolOutputDenied:
                this.reportUnknownCall(event.type, event.toolCallId ?? '');
                this.toolNamesByCallId.delete(event.toolCallId ?? '');
                this.showDenied();
                break;
        }
    }

    private reportUnknownCall(eventType: string, toolCallId: string): void {
        if (this.toolNamesByCallId.has(toolCallId)) {
            return;
        }

        reportError(
            new Error(
                `<${TOOL_CALLS_TAG_NAME}> got "${eventType}" for the tool call "${toolCallId}", whose tool-input-start ` +
                    `or tool-input-available never arrived in this turn (reported by ${SOURCE_FILE}). The result is ` +
                    `shown without a tool name (generic block, or hidden with ${HIDE_UNCLAIMED_RESULTS_ATTRIBUTE}); ` +
                    `check that the server's pre-tool-call plugin emits the input events with the same toolCallId.`,
            ),
        );
    }

    private replayStoredMessage(message: ChatStoredMessage): void {
        if (message.role !== CHAT_MESSAGE_ROLE.toolResult) {
            return;
        }

        for (const invocation of message.toolInvocations ?? []) {
            this.renderResult({ toolName: invocation.name, toolCallId: '', result: invocation.result });
        }
    }

    private renderResult(call: ChatToolCall): void {
        if (call.result === '' || this.context === null) {
            return;
        }

        const element = this.renderWithRegisteredRenderers(call) ?? this.renderGenericBlock(call);

        if (element !== null) {
            this.context.transcript.append(element);
            this.renderedElements.add(element);
        }
    }

    private renderWithRegisteredRenderers(call: ChatToolCall): Element | null {
        for (const renderer of [...(this.renderersByToolName.get(call.toolName) ?? [])]) {
            try {
                const element = renderer(call);

                if (element !== null) {
                    return element;
                }
            } catch (error) {
                reportError(
                    new Error(
                        `The renderer registered for the tool "${call.toolName}" threw: ${describeFailure(error)} ` +
                            `(reported by ${SOURCE_FILE}). The result falls through to the next renderer or the ` +
                            `generic tool-call block; fix the renderer the feature registered through ` +
                            `<${TOOL_CALLS_TAG_NAME}>.tools.register().`,
                        { cause: error },
                    ),
                );
            }
        }

        return null;
    }

    private renderGenericBlock(call: ChatToolCall): Element | null {
        if (this.hasAttribute(HIDE_UNCLAIMED_RESULTS_ATTRIBUTE) || this.toolCallTemplate === null) {
            return null;
        }

        const block = this.toolCallTemplate.create();
        const { name, toggle, showLabel, hideLabel, result } = block.references;
        const label = this.tools.labelFor(call.toolName);

        if (label !== '') {
            name.textContent = label;
        }

        result.textContent = prettyPrint(call.result);
        result.hidden = true;
        result.id = createResultId(call.toolCallId);
        hideLabel.hidden = true;
        toggle.setAttribute('aria-controls', result.id);
        toggle.setAttribute('aria-expanded', 'false');
        toggle.addEventListener('click', () => {
            const isExpanded = result.hidden;

            result.hidden = !isExpanded;
            showLabel.hidden = isExpanded;
            hideLabel.hidden = !isExpanded;
            toggle.setAttribute('aria-expanded', String(isExpanded));
        });

        return block.root;
    }

    private forgetDetachedElements(): void {
        for (const element of [...this.renderedElements]) {
            if (!element.isConnected) {
                this.renderedElements.delete(element);
            }
        }
    }

    private showDenied(): void {
        if (this.isDeniedShown || this.context === null || this.deniedTemplate === null) {
            return;
        }

        const notice = this.deniedTemplate.create().root;

        this.isDeniedShown = true;
        this.context.transcript.append(notice);
        this.renderedElements.add(notice);
    }

    private readToolLabels(): Readonly<Record<string, string>> {
        const value = this.getAttribute(TOOL_LABELS_ATTRIBUTE);
        let parsed: unknown;

        if (value === null) {
            return {};
        }

        if (value.trim() === '') {
            reportError(
                new Error(
                    `<${TOOL_CALLS_TAG_NAME}> has an empty "${TOOL_LABELS_ATTRIBUTE}" attribute (reported by ` +
                        `${SOURCE_FILE}); tool names are shown as words instead. Pass features.toolCalls.toolLabels ` +
                        `as a map of tool name to translated label in the host embed (the feature twig encodes it).`,
                ),
            );

            return {};
        }

        try {
            parsed = JSON.parse(value);
        } catch (error) {
            reportError(
                new Error(
                    `<${TOOL_CALLS_TAG_NAME}> has a "${TOOL_LABELS_ATTRIBUTE}" attribute that is not JSON (reported ` +
                        `by ${SOURCE_FILE}); tool names are shown as words instead. Pass ` +
                        `features.toolCalls.toolLabels as a map of tool name to translated label in the host embed.`,
                    { cause: error },
                ),
            );

            return {};
        }

        const labels: Record<string, string> = {};

        // An empty map arrives as `[]` from json_encode, which simply holds no labels.
        for (const [toolName, label] of Object.entries(isRecord(parsed) ? parsed : {})) {
            if (typeof label === 'string' && label.trim() !== '') {
                labels[toolName] = label;
            }
        }

        return labels;
    }
}
