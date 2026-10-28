import { CHAT_TAG_NAME } from '../contracts/access';
import type { ChatBlockInstance, ChatBlockResolver, ChatBlockSpec, ChatBlockTemplate } from '../contracts/blocks';

const SOURCE_FILE = 'Shared/AiCommerce/Theme/default/Chat/core/runtime/blocks.ts';
const BLOCK_ATTRIBUTE = 'data-block';
const REFERENCE_ATTRIBUTE = 'data-ref';

export const CORE_BLOCK = {
    messageUser: 'messageUser',
    messageAssistant: 'messageAssistant',
    messageError: 'messageError',
    loading: 'loading',
    retry: 'retry',
    composer: 'composer',
    launcher: 'launcher',
    panel: 'panel',
} as const;

export const CORE_BLOCK_SPECS = {
    messageUser: { name: CORE_BLOCK.messageUser, references: ['text'] },
    messageAssistant: { name: CORE_BLOCK.messageAssistant, references: ['content'] },
    messageError: { name: CORE_BLOCK.messageError, references: ['text'] },
    loading: { name: CORE_BLOCK.loading, references: [] },
    retry: { name: CORE_BLOCK.retry, references: [] },
    composer: { name: CORE_BLOCK.composer, references: ['input', 'send', 'stop'] },
    launcher: { name: CORE_BLOCK.launcher, references: [] },
    panel: { name: CORE_BLOCK.panel, references: ['close', 'scroller', 'transcript', 'composer'] },
} as const satisfies Record<keyof typeof CORE_BLOCK, ChatBlockSpec>;

const CORE_BLOCK_TWIG: Readonly<Record<string, string>> = {
    launcher: '@AiCommerce/Chat/core/panel/panel.twig',
    panel: '@AiCommerce/Chat/core/panel/panel.twig',
    composer: '@AiCommerce/Chat/core/composer/composer.twig',
    messageUser: '@AiCommerce/Chat/core/transcript/transcript.twig',
    messageAssistant: '@AiCommerce/Chat/core/transcript/transcript.twig',
    messageError: '@AiCommerce/Chat/core/transcript/transcript.twig',
    loading: '@AiCommerce/Chat/core/transcript/transcript.twig',
    retry: '@AiCommerce/Chat/core/transcript/transcript.twig',
};

export class ChatBlockError extends Error {
    name = 'ChatBlockError';
}

const describeTwigSource = (name: string): string =>
    name in CORE_BLOCK_TWIG
        ? `the twig block "${name}" (default markup in ${CORE_BLOCK_TWIG[name]})`
        : `the twig block "${name}" of the feature that renders it`;

const findTemplate = (chat: HTMLElement, name: string, owner: string): HTMLTemplateElement => {
    const templates = Array.from(chat.querySelectorAll<HTMLTemplateElement>(`template[${BLOCK_ATTRIBUTE}]`)).filter(
        (template) => template.getAttribute(BLOCK_ATTRIBUTE) === name && template.closest(CHAT_TAG_NAME) === chat,
    );

    if (templates.length === 0) {
        throw new ChatBlockError(
            `Chat block "${name}" required by <${owner}> is missing: no <template ${BLOCK_ATTRIBUTE}="${name}"> is ` +
                `rendered inside <${CHAT_TAG_NAME}> (reported by ${SOURCE_FILE}). Render ${describeTwigSource(name)} ` +
                `inside the chat, or make sure an override of that block still outputs the <template ` +
                `${BLOCK_ATTRIBUTE}="${name}"> wrapper.`,
        );
    }

    if (templates.length > 1) {
        throw new ChatBlockError(
            `Chat block "${name}" required by <${owner}> is rendered ${templates.length} times inside ` +
                `<${CHAT_TAG_NAME}> (reported by ${SOURCE_FILE}). Keep exactly one <template ${BLOCK_ATTRIBUTE}="${name}">: ` +
                `override ${describeTwigSource(name)} in the host embed instead of rendering a second template.`,
        );
    }

    return templates[0];
};

const findRoot = (template: HTMLTemplateElement, name: string, owner: string): Element => {
    const rootCount = template.content.childElementCount;
    const root = template.content.firstElementChild;

    if (rootCount !== 1 || root === null) {
        throw new ChatBlockError(
            `Chat block "${name}" required by <${owner}> must have exactly one root element inside its <template ` +
                `${BLOCK_ATTRIBUTE}="${name}">, found ${rootCount} (reported by ${SOURCE_FILE}). Wrap the markup of ` +
                `${describeTwigSource(name)} in a single element.`,
        );
    }

    return root;
};

const collectReferences = (root: Element): Map<string, HTMLElement> => {
    const references = new Map<string, HTMLElement>();

    for (const element of [root, ...Array.from(root.querySelectorAll(`[${REFERENCE_ATTRIBUTE}]`))]) {
        const reference = element.getAttribute(REFERENCE_ATTRIBUTE);

        if (reference !== null && reference !== '' && !references.has(reference)) {
            references.set(reference, element as HTMLElement);
        }
    }

    return references;
};

const assertReferences = (root: Element, spec: ChatBlockSpec, owner: string): void => {
    const references = collectReferences(root);

    for (const reference of spec.references) {
        if (!references.has(reference)) {
            throw new ChatBlockError(
                `Chat block "${spec.name}" required by <${owner}> has no element with ` +
                    `${REFERENCE_ATTRIBUTE}="${reference}" (reported by ${SOURCE_FILE}). Add ` +
                    `${REFERENCE_ATTRIBUTE}="${reference}" to the element that plays that part in ` +
                    `${describeTwigSource(spec.name)}.`,
            );
        }
    }
};

const bindInstance = <Reference extends string>(root: HTMLElement): ChatBlockInstance<Reference> => {
    const references = collectReferences(root);

    return Object.freeze({
        root: root,
        references: Object.freeze(Object.fromEntries(references)) as Readonly<Record<Reference, HTMLElement>>,
        find: (reference: string): HTMLElement | null => references.get(reference) ?? null,
    });
};

export const createBlockResolver = (chat: HTMLElement, owner: string): ChatBlockResolver =>
    Object.freeze({
        require: <Reference extends string>(spec: ChatBlockSpec<Reference>): ChatBlockTemplate<Reference> => {
            const template = findTemplate(chat, spec.name, owner);

            assertReferences(findRoot(template, spec.name, owner), spec, owner);

            return Object.freeze({
                name: spec.name,
                create: (): ChatBlockInstance<Reference> =>
                    // importNode adopts the clone into the page document, so custom elements inside it upgrade.
                    bindInstance<Reference>(
                        chat.ownerDocument.importNode(findRoot(template, spec.name, owner), true) as HTMLElement,
                    ),
            });
        },
    });
