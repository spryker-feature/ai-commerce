import type { ChatRequestLock } from '../contracts/services';

export class SingleRequestLock implements ChatRequestLock {
    private busy = false;
    private readonly listeners = new Set<(isBusy: boolean) => void>();

    isBusy(): boolean {
        return this.busy;
    }

    async run(operation: () => Promise<void>): Promise<boolean> {
        if (this.busy) {
            return false;
        }

        this.setBusy(true);

        try {
            await operation();
        } finally {
            this.setBusy(false);
        }

        return true;
    }

    subscribe(listener: (isBusy: boolean) => void): () => void {
        this.listeners.add(listener);

        return () => {
            this.listeners.delete(listener);
        };
    }

    private setBusy(isBusy: boolean): void {
        this.busy = isBusy;

        for (const listener of this.listeners) {
            listener(isBusy);
        }
    }
}
