import { Marked } from 'marked';
import type { Token } from 'marked';
import { SANITIZER } from '../contracts/tokens';
import type { ChatService, ChatServiceEnvironment } from '../contracts/tokens';
import type { ChatMarkdown, ChatMarkdownStream, ChatMarkdownStreamOptions, ChatSanitizer } from '../contracts/services';

const SOURCE_FILE = 'Shared/AiCommerce/Theme/default/Chat/core/markdown/markdown.ts';
const SPACE_TOKEN_TYPE = 'space';

// marked.lexer() turns \r\n into \n; doing it first keeps the token lengths equal to the text they were cut from.
const normalizeLineBreaks = (text: string): string => text.replace(/\r\n?/g, '\n');

const sumTokenLength = (tokens: readonly Token[]): number =>
    tokens.reduce((length, token) => length + token.raw.length, 0);

const countClosedTokens = (tokens: readonly Token[]): number => {
    for (let index = tokens.length - 1; index >= 0; index--) {
        if (tokens[index].type !== SPACE_TOKEN_TYPE) {
            return index;
        }
    }

    return 0;
};

const createIncrementalStream = (
    target: HTMLElement,
    lex: (text: string) => Token[],
    render: (tokens: Token[]) => DocumentFragment,
    options: ChatMarkdownStreamOptions,
): ChatMarkdownStream => {
    let text = '';
    let closedLength = 0;
    let openBlockNodes: ChildNode[] = [];
    let frameHandle: number | null = null;
    let isFinished = false;

    const write = (isFinal: boolean): void => {
        options.onBeforeRender?.();

        const pendingText = text.slice(closedLength);
        const tokens = lex(pendingText);
        const isLosslessSplit = sumTokenLength(tokens) === pendingText.length;
        const closedCount = isFinal ? tokens.length : isLosslessSplit ? countClosedTokens(tokens) : 0;

        for (const node of openBlockNodes) {
            node.remove();
        }

        if (closedCount > 0) {
            const closedTokens = tokens.slice(0, closedCount);

            target.append(render(closedTokens));
            closedLength += sumTokenLength(closedTokens);
        }

        const openTokens = tokens.slice(closedCount);
        const openFragment = openTokens.length > 0 ? render(openTokens) : null;

        openBlockNodes = openFragment === null ? [] : Array.from(openFragment.childNodes);

        if (openFragment !== null) {
            target.append(openFragment);
        }

        options.onRender?.();
    };

    const cancelFrame = (): void => {
        if (frameHandle !== null) {
            cancelAnimationFrame(frameHandle);
            frameHandle = null;
        }
    };

    return {
        append: (delta: string): void => {
            if (isFinished || delta === '') {
                return;
            }

            text += normalizeLineBreaks(delta);

            if (frameHandle === null) {
                frameHandle = requestAnimationFrame(() => {
                    frameHandle = null;
                    write(false);
                });
            }
        },
        finish: (): void => {
            if (isFinished) {
                return;
            }

            isFinished = true;
            cancelFrame();
            write(true);
        },
    };
};

export class MarkedChatMarkdown implements ChatMarkdown, ChatService {
    private readonly marked = new Marked({ gfm: true, breaks: true });
    private sanitizer: ChatSanitizer | null = null;

    attach(environment: ChatServiceEnvironment): void {
        this.sanitizer = environment.services.get(SANITIZER);
    }

    render(target: HTMLElement, text: string): void {
        target.replaceChildren(this.renderTokens(this.lex(normalizeLineBreaks(text))));
    }

    stream(target: HTMLElement, options: ChatMarkdownStreamOptions = {}): ChatMarkdownStream {
        return createIncrementalStream(
            target,
            (text: string) => this.lex(text),
            (tokens: Token[]) => this.renderTokens(tokens),
            options,
        );
    }

    private lex(text: string): Token[] {
        return this.marked.lexer(text);
    }

    private renderTokens(tokens: Token[]): DocumentFragment {
        if (this.sanitizer === null) {
            throw new Error(
                `MarkedChatMarkdown rendered before the chat attached its sanitizer (reported by ${SOURCE_FILE}). ` +
                    `Get the service through context.services.get(MARKDOWN) inside initialize().`,
            );
        }

        return this.sanitizer.sanitize(this.marked.parser(tokens));
    }
}
