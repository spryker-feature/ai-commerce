import type {
    ChatFeature,
    ChatFeatureContext,
    ChatTranscriptControl,
} from '@shared/ai-commerce/Chat/core/contracts/context';
import type { ChatBlockTemplate } from '@shared/ai-commerce/Chat/core/contracts/blocks';
import type { ChatMarkdown, ChatMarkdownStream } from '@shared/ai-commerce/Chat/core/contracts/services';
import type { ChatStreamEvent } from '@shared/ai-commerce/Chat/core/contracts/stream';
import { STREAM_EVENT_TYPE } from '@shared/ai-commerce/Chat/core/contracts/stream';
import { announceFeature } from '@shared/ai-commerce/Chat/core/contracts/access';
import { MARKDOWN } from '@shared/ai-commerce/Chat/core/contracts/tokens';

const REASONING_BLOCK = { name: 'reasoningBlock', references: ['content'] } as const;
const REASONING_PART_BLOCK = { name: 'reasoningPart', references: [] } as const;

interface OpenReasoning {
    readonly id: string;
    readonly stream: ChatMarkdownStream;
}

interface ReasoningBlock {
    readonly root: HTMLElement;
    readonly content: HTMLElement;
}

export class ChatReasoning extends HTMLElement implements ChatFeature {
    private context: ChatFeatureContext | null = null;
    private template: ChatBlockTemplate<'content'> | null = null;
    private partTemplate: ChatBlockTemplate<never> | null = null;
    private markdown: ChatMarkdown | null = null;
    private openReasoning: OpenReasoning | null = null;
    private turnBlock: ReasoningBlock | null = null;
    private readonly renderedBlocks = new Set<HTMLElement>();
    private readonly unfoldedBlocks = new Set<HTMLElement>();

    connectedCallback(): void {
        announceFeature(this);
    }

    initialize(context: ChatFeatureContext): void {
        this.context = context;
        this.template = context.blocks.require(REASONING_BLOCK);
        this.partTemplate = context.blocks.require<never>(REASONING_PART_BLOCK);
        this.markdown = context.services.get(MARKDOWN);
        context.events.on('stream', (event: ChatStreamEvent) => this.handleStreamEvent(event));
        context.events.on('turn:end', () => {
            this.closeReasoning();
            this.foldBlocks();
            this.turnBlock = null;
        });
        context.events.on('conversation:reset', () => this.forgetBlocks());
        context.events.on('conversation:loaded', () => this.forgetBlocks());
    }

    dispose(): void {
        this.closeReasoning();

        for (const block of this.renderedBlocks) {
            block.remove();
        }

        this.renderedBlocks.clear();
        this.unfoldedBlocks.clear();
        this.context = null;
        this.template = null;
        this.partTemplate = null;
        this.markdown = null;
    }

    private handleStreamEvent(event: ChatStreamEvent): void {
        switch (event.type) {
            case STREAM_EVENT_TYPE.reasoningDelta:
                this.appendReasoning(event.id ?? '', event.delta ?? '');
                break;
            case STREAM_EVENT_TYPE.reasoningEnd:
                this.closeReasoning();
                break;
            case STREAM_EVENT_TYPE.textStart:
                this.foldBlocks();
                break;
        }
    }

    private appendReasoning(id: string, delta: string): void {
        if (
            delta === '' ||
            this.context === null ||
            this.template === null ||
            this.partTemplate === null ||
            this.markdown === null
        ) {
            return;
        }

        if (this.openReasoning === null || this.openReasoning.id !== id) {
            this.closeReasoning();

            const transcript = this.context.transcript;
            const part = this.partTemplate.create().root;
            let follow: (() => void) | null = null;

            this.requireTurnBlock(this.template, transcript).content.append(part);
            // Measured before each render, so a reader who scrolled up is not pulled down.
            this.openReasoning = {
                id: id,
                stream: this.markdown.stream(part, {
                    onBeforeRender: () => {
                        follow = transcript.captureFollow();
                    },
                    onRender: () => follow?.(),
                }),
            };
        }

        this.openReasoning.stream.append(delta);
    }

    private requireTurnBlock(
        template: ChatBlockTemplate<'content'>,
        transcript: ChatTranscriptControl,
    ): ReasoningBlock {
        if (this.turnBlock !== null) {
            return this.turnBlock;
        }

        const block = template.create();

        transcript.append(block.root);
        this.renderedBlocks.add(block.root);
        this.unfoldedBlocks.add(block.root);
        this.turnBlock = { root: block.root, content: block.references.content };

        return this.turnBlock;
    }

    private closeReasoning(): void {
        this.openReasoning?.stream.finish();
        this.openReasoning = null;
    }

    private foldBlocks(): void {
        for (const block of this.unfoldedBlocks) {
            block.removeAttribute('open');
        }

        this.unfoldedBlocks.clear();
    }

    private forgetBlocks(): void {
        this.closeReasoning();
        this.turnBlock = null;
        this.renderedBlocks.clear();
        this.unfoldedBlocks.clear();
    }
}
