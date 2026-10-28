// Mirrors Shared/AiCommerce/Stream; the only place TypeScript reads event names from.

export const STREAM_EVENT_TYPE = {
    start: 'start',
    finish: 'finish',
    startStep: 'start-step',
    finishStep: 'finish-step',
    textStart: 'text-start',
    textDelta: 'text-delta',
    textEnd: 'text-end',
    reasoningStart: 'reasoning-start',
    reasoningDelta: 'reasoning-delta',
    reasoningEnd: 'reasoning-end',
    toolInputStart: 'tool-input-start',
    toolInputAvailable: 'tool-input-available',
    toolOutputAvailable: 'tool-output-available',
    toolOutputDenied: 'tool-output-denied',
    error: 'error',
} as const;

export const STREAM_DATA_EVENT_PREFIX = 'data-';
export const STREAM_DONE_SENTINEL = '[DONE]';

export type ChatStreamEventType = (typeof STREAM_EVENT_TYPE)[keyof typeof STREAM_EVENT_TYPE];

export interface ChatStreamStartEvent {
    readonly type: typeof STREAM_EVENT_TYPE.start;
    readonly messageId?: string;
    readonly messageMetadata?: Readonly<Record<string, unknown>>;
}

export interface ChatStreamLifecycleEvent {
    readonly type:
        typeof STREAM_EVENT_TYPE.finish | typeof STREAM_EVENT_TYPE.startStep | typeof STREAM_EVENT_TYPE.finishStep;
}

export interface ChatStreamBlockBoundaryEvent {
    readonly type:
        | typeof STREAM_EVENT_TYPE.textStart
        | typeof STREAM_EVENT_TYPE.textEnd
        | typeof STREAM_EVENT_TYPE.reasoningStart
        | typeof STREAM_EVENT_TYPE.reasoningEnd;
    readonly id?: string;
}

export interface ChatStreamDeltaEvent {
    readonly type: typeof STREAM_EVENT_TYPE.textDelta | typeof STREAM_EVENT_TYPE.reasoningDelta;
    readonly id?: string;
    readonly delta?: string;
}

export interface ChatStreamToolInputEvent {
    readonly type: typeof STREAM_EVENT_TYPE.toolInputStart | typeof STREAM_EVENT_TYPE.toolInputAvailable;
    readonly toolCallId?: string;
    readonly toolName?: string;
    readonly input?: unknown;
}

export interface ChatStreamToolOutputEvent {
    readonly type: typeof STREAM_EVENT_TYPE.toolOutputAvailable | typeof STREAM_EVENT_TYPE.toolOutputDenied;
    readonly toolCallId?: string;
    readonly output?: unknown;
}

export interface ChatStreamErrorEvent {
    readonly type: typeof STREAM_EVENT_TYPE.error;
    readonly errorText?: string;
}

export interface ChatStreamDataEvent {
    readonly type: `${typeof STREAM_DATA_EVENT_PREFIX}${string}`;
    readonly id?: string;
    readonly data?: unknown;
}

export type ChatStreamEvent =
    | ChatStreamStartEvent
    | ChatStreamLifecycleEvent
    | ChatStreamBlockBoundaryEvent
    | ChatStreamDeltaEvent
    | ChatStreamToolInputEvent
    | ChatStreamToolOutputEvent
    | ChatStreamErrorEvent
    | ChatStreamDataEvent;

export type ChatStreamOutcome = 'done' | 'ended';

export interface ChatStreamParser {
    read(stream: ReadableStream<Uint8Array>, onEvent: (event: ChatStreamEvent) => void): Promise<ChatStreamOutcome>;
}
