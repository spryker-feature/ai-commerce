import { createParser } from 'eventsource-parser';
import type { EventSourceMessage } from 'eventsource-parser';
import { STREAM_DATA_EVENT_PREFIX, STREAM_DONE_SENTINEL, STREAM_EVENT_TYPE } from '../contracts/stream';
import type { ChatStreamEvent, ChatStreamEventType, ChatStreamOutcome, ChatStreamParser } from '../contracts/stream';

const SOURCE_FILE = 'Shared/AiCommerce/Theme/default/Chat/core/services/stream-parser.ts';
const FRAME_EXCERPT_LENGTH = 120;

const KNOWN_EVENT_TYPES: ReadonlySet<string> = new Set<ChatStreamEventType>(Object.values(STREAM_EVENT_TYPE));

const isRecord = (value: unknown): value is Record<string, unknown> =>
    typeof value === 'object' && value !== null && !Array.isArray(value);

const reportMalformedFrame = (data: string, reason: string): void => {
    reportError(
        new Error(
            `The chat stream sent a frame the core cannot read: ${reason} (reported by ${SOURCE_FILE}). Frame ` +
                `data: ${data.slice(0, FRAME_EXCERPT_LENGTH)}. Check that the prompt endpoint writes only through ` +
                `StreamEventEmitter (Shared/AiCommerce/Stream) and that nothing else prints into the response.`,
        ),
    );
};

const decodeEvent = (message: EventSourceMessage): ChatStreamEvent | null => {
    let decoded: unknown;

    try {
        decoded = JSON.parse(message.data);
    } catch {
        reportMalformedFrame(message.data, 'its data is not JSON');

        return { type: STREAM_EVENT_TYPE.error };
    }

    if (!isRecord(decoded) || typeof decoded.type !== 'string') {
        reportMalformedFrame(message.data, 'it has no string "type" field');

        return { type: STREAM_EVENT_TYPE.error };
    }

    // An unknown type is a newer server talking to an older client; data-* is the extension channel features use.
    if (!KNOWN_EVENT_TYPES.has(decoded.type) && !decoded.type.startsWith(STREAM_DATA_EVENT_PREFIX)) {
        return null;
    }

    return decoded as unknown as ChatStreamEvent;
};

const releaseReader = async (reader: ReadableStreamDefaultReader<Uint8Array>): Promise<void> => {
    // Closes a connection the server may hold open after [DONE]; the rejection is one the caller already handles.
    await reader.cancel().catch(() => undefined);
    reader.releaseLock();
};

export class EventSourceChatStreamParser implements ChatStreamParser {
    async read(
        stream: ReadableStream<Uint8Array>,
        onEvent: (event: ChatStreamEvent) => void,
    ): Promise<ChatStreamOutcome> {
        const reader = stream.getReader();
        const decoder = new TextDecoder();
        let isDone = false;

        const parser = createParser({
            onEvent: (message: EventSourceMessage) => {
                if (isDone) {
                    return;
                }

                if (message.data === STREAM_DONE_SENTINEL) {
                    isDone = true;

                    return;
                }

                const event = decodeEvent(message);

                if (event !== null) {
                    onEvent(event);
                }
            },
        });

        try {
            while (!isDone) {
                const { done, value } = await reader.read();

                if (done) {
                    parser.feed(decoder.decode());
                    parser.reset({ consume: true });

                    break;
                }

                parser.feed(decoder.decode(value, { stream: true }));
            }
        } finally {
            await releaseReader(reader);
        }

        return isDone ? 'done' : 'ended';
    }
}
