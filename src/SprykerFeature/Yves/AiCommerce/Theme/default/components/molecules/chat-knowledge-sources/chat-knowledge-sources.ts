import './chat-knowledge-sources.scss';
import type { ChatFeature, ChatFeatureContext } from '@shared/ai-commerce/Chat/core/contracts/context';
import type { ChatBlockTemplate } from '@shared/ai-commerce/Chat/core/contracts/blocks';
import { announceFeature } from '@shared/ai-commerce/Chat/core/contracts/access';
import { readOriginList } from '@shared/ai-commerce/Chat/core/contracts/links';
import type { ChatToolCall, ChatToolCalls } from '@shared/ai-commerce/Chat/features/tool-calls/tool-calls';
import { readKnowledgeSources, type KnowledgeSource } from './knowledge-source-reader';
import { KnowledgeSourcesPlacement } from './knowledge-sources-placement';

export const KNOWLEDGE_SOURCES_TAG_NAME = 'chat-knowledge-sources';

const SOURCE_FILE =
    'Yves/AiCommerce/Theme/default/components/molecules/chat-knowledge-sources/chat-knowledge-sources.ts';
const TOOL_CALLS_TAG_NAME = 'chat-tool-calls';
const DISPLAY_KNOWLEDGE_SOURCES_TOOL_NAME = 'display_knowledge_sources';
const LINK_ORIGINS_ATTRIBUTE = 'link-origins';

const SOURCES_BLOCK = { name: 'knowledgeSources', references: ['list'] } as const;
const SOURCE_BLOCK = { name: 'knowledgeSource', references: ['link', 'linkName', 'name'] } as const;

type SourceReference = (typeof SOURCE_BLOCK.references)[number];

export default class ChatKnowledgeSources extends HTMLElement implements ChatFeature {
    readonly requires = [TOOL_CALLS_TAG_NAME];

    private sourcesTemplate: ChatBlockTemplate<'list'> | null = null;
    private sourceTemplate: ChatBlockTemplate<SourceReference> | null = null;
    private linkOrigins: readonly string[] = [];
    private unregisterRenderer: (() => void) | null = null;
    private placement: KnowledgeSourcesPlacement | null = null;

    connectedCallback(): void {
        announceFeature(this);
    }

    initialize(context: ChatFeatureContext): void {
        const toolCalls = context.features.find(TOOL_CALLS_TAG_NAME) as ChatToolCalls | null;

        if (toolCalls === null || typeof toolCalls.tools?.register !== 'function') {
            throw new Error(
                `<${KNOWLEDGE_SOURCES_TAG_NAME}> registers its renderer on <${TOOL_CALLS_TAG_NAME}>, which is not ` +
                    `initialized (reported by ${SOURCE_FILE}). Keep features.toolCalls enabled in the host embed.`,
            );
        }

        this.linkOrigins = readOriginList(this, LINK_ORIGINS_ATTRIBUTE, {
            httpsOnly: true,
            fix: "Return hosts such as 'media.example.com' from AiCommerceConfig::getKnowledgeSourceLinkOrigins().",
        });
        this.sourcesTemplate = context.blocks.require(SOURCES_BLOCK);
        this.sourceTemplate = context.blocks.require(SOURCE_BLOCK);
        this.placement = new KnowledgeSourcesPlacement(context);
        this.unregisterRenderer = toolCalls.tools.register(DISPLAY_KNOWLEDGE_SOURCES_TOOL_NAME, (call) =>
            this.render(call),
        );
    }

    dispose(): void {
        this.unregisterRenderer?.();
        this.unregisterRenderer = null;
        this.placement?.dispose();
        this.placement = null;
        this.sourcesTemplate = null;
        this.sourceTemplate = null;
        this.linkOrigins = [];
    }

    private render(call: ChatToolCall): Element | null {
        const sourcesTemplate = this.sourcesTemplate;
        const sourceTemplate = this.sourceTemplate;
        const placement = this.placement;
        const reading = readKnowledgeSources(call.result, this.linkOrigins);

        if (reading === null || sourcesTemplate === null || sourceTemplate === null || placement === null) {
            return null;
        }

        if (typeof reading === 'string') {
            reportError(
                new Error(
                    `The "${DISPLAY_KNOWLEDGE_SOURCES_TOOL_NAME}" tool result could not be shown as sources: ` +
                        `${reading} (reported by ${SOURCE_FILE}). It falls back to the generic tool-call block; ` +
                        `check what DisplayKnowledgeSourcesToolPlugin returned for this turn.`,
                ),
            );

            return null;
        }

        this.reportRejectedUrls(reading);

        const block = sourcesTemplate.create();

        block.references.list.append(...reading.map((source) => this.renderSource(sourceTemplate, source)));
        placement.defer(block.root);

        return block.root;
    }

    private reportRejectedUrls(sources: readonly KnowledgeSource[]): void {
        const rejectedCount = sources.filter((source) => source.isUrlRejected).length;

        if (rejectedCount === 0) {
            return;
        }

        reportError(
            new Error(
                `${rejectedCount} of ${sources.length} "${DISPLAY_KNOWLEDGE_SOURCES_TOOL_NAME}" sources have a URL ` +
                    `that is not on the shop origin or on an https origin of "${LINK_ORIGINS_ATTRIBUTE}" ` +
                    `(reported by ${SOURCE_FILE}); they show the document name without a link. Add the document ` +
                    `host to AiCommerceConfig::getKnowledgeSourceLinkOrigins() when these documents must be links.`,
            ),
        );
    }

    private renderSource(sourceTemplate: ChatBlockTemplate<SourceReference>, source: KnowledgeSource): HTMLElement {
        const item = sourceTemplate.create();
        const { link, linkName, name } = item.references;

        if (source.url === '') {
            name.textContent = source.documentName;
            link.remove();

            return item.root;
        }

        linkName.textContent = source.documentName;
        link.setAttribute('href', source.url);
        name.remove();

        return item.root;
    }
}
