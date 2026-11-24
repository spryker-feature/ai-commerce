import type { ChatFeatureContext } from '@shared/ai-commerce/Chat/core/contracts/context';

export class KnowledgeSourcesPlacement {
    private pendingBlocks: HTMLElement[] = [];
    private readonly unsubscribers: Array<() => void>;

    constructor(private readonly context: ChatFeatureContext) {
        this.unsubscribers = [
            context.events.on('turn:end', () => this.showPendingBlocks()),
            context.events.on('conversation:loaded', () => this.showPendingBlocks()),
            context.events.on('conversation:reset', () => {
                this.pendingBlocks = [];
            }),
        ];
    }

    defer(block: HTMLElement): void {
        block.hidden = true;
        this.pendingBlocks.push(block);
    }

    dispose(): void {
        this.unsubscribers.splice(0).forEach((unsubscribe) => unsubscribe());
        this.pendingBlocks = [];
    }

    private showPendingBlocks(): void {
        const follow = this.context.transcript.captureFollow();

        for (const block of this.pendingBlocks.splice(0).filter((pending) => pending.isConnected)) {
            this.context.transcript.appendToAnswer(block, block);
            block.hidden = false;
        }

        follow();
    }
}
