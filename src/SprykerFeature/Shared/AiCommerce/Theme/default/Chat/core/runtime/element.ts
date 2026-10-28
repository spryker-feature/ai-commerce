import { createChatRuntime } from './runtime';
import type { ChatRuntime } from './runtime';

export class ChatElement extends HTMLElement {
    private runtime: ChatRuntime | null = null;

    connectedCallback(): void {
        this.runtime?.stop();
        this.runtime = createChatRuntime(this);
        this.runtime.start();
    }

    disconnectedCallback(): void {
        this.runtime?.stop();
        this.runtime = null;
    }
}
