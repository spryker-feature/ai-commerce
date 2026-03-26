export class BackofficeAssistantStreamParser {
    buffer = '';

    constructor(onEvent) {
        this.onEvent = onEvent;
    }

    feed(chunk) {
        this.buffer += chunk;

        let boundary = this.buffer.indexOf('\n\n');

        while (boundary !== -1) {
            const block = this.buffer.substring(0, boundary);
            this.buffer = this.buffer.substring(boundary + 2);

            const lines = block.split('\n');

            for (let i = 0; i < lines.length; i++) {
                if (!lines[i].startsWith('data: ')) {
                    continue;
                }

                try {
                    this.onEvent(JSON.parse(lines[i].slice(6)));
                } catch {
                    // Skip malformed JSON
                }
            }

            boundary = this.buffer.indexOf('\n\n');
        }
    }
}
