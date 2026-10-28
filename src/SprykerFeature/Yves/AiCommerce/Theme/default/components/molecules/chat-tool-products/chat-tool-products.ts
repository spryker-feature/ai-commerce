import './chat-tool-products.scss';
/* eslint-disable max-lines -- the element owns the results, observers and refinement controls it rendered; the stateless reading and card parts already live in product-reader.ts and product-card.ts. */
import type {
    ChatFeature,
    ChatFeatureContext,
    ChatRenderedMessage,
} from '@shared/ai-commerce/Chat/core/contracts/context';
import { CHAT_TURN_STATE } from '@shared/ai-commerce/Chat/core/contracts/context';
import type { ChatBlockInstance, ChatBlockTemplate } from '@shared/ai-commerce/Chat/core/contracts/blocks';
import { CHAT_MESSAGE_ROLE } from '@shared/ai-commerce/Chat/core/contracts/services';
import { announceFeature } from '@shared/ai-commerce/Chat/core/contracts/access';
import { REQUEST_LOCK } from '@shared/ai-commerce/Chat/core/contracts/tokens';
import { describeFailure } from '@shared/ai-commerce/Chat/core/contracts/failure';
import type { ChatToolCall, ChatToolCalls } from '@shared/ai-commerce/Chat/features/tool-calls/tool-calls';
import type { ChatSuggestedPrompts } from '@shared/ai-commerce/Chat/features/suggested-prompts/suggested-prompts';
import {
    decodeResult,
    isRecord,
    isSafePath,
    parseActiveFilter,
    parseList,
    parseRefinementChip,
    parseSeeAll,
    readProductCollection,
    type ChatProduct,
    type ProductReading,
} from './product-reader';

import {
    CARD_BLOCK,
    CAROUSEL_BLOCK,
    bindProductCarousel,
    fillProductCard,
    type CardReference,
    type CarouselReference,
} from './product-card';

export type { ChatProduct } from './product-reader';

export const TOOL_PRODUCTS_TAG_NAME = 'chat-tool-products';

const SOURCE_FILE = 'Yves/AiCommerce/Theme/default/components/molecules/chat-tool-products/chat-tool-products.ts';
const TOOL_CALLS_TAG_NAME = 'chat-tool-calls';
const SUGGESTED_PROMPTS_TAG_NAME = 'chat-suggested-prompts';
// Must match DisplayProductsToolPlugin::TOOL_NAME.
const DISPLAY_PRODUCTS_TOOL_NAME = 'display_products';
const SEARCH_URL_ATTRIBUTE = 'search-url';
const IMAGE_ORIGINS_ATTRIBUTE = 'image-origins';
const URL_SCHEME_SEPARATOR = '://';
const MAX_REMEMBERED_PRODUCTS = 200;
const PROMPT_GROUP_ATTRIBUTE = 'prompt-group';
const DEFAULT_PROMPT_GROUP = 'product_results';
const SEE_ALL_TEXT_ATTRIBUTE = 'data-text';
const SELECT_TEXT_ATTRIBUTE = 'data-select-text';
const REMOVE_TEXT_ATTRIBUTE = 'data-remove-text';
const TEXT_REFERENCE = 'text';
const COUNT_PLACEHOLDER = '%count%';
const LABEL_PLACEHOLDER = '%label%';

const REFINEMENT_HINT_PATTERN = /\s*\[[A-Za-z][\w.]*(?:, [A-Za-z][\w.]*)*(?:=[^\]]*)?\]$/;

const RESULT_BLOCK = { name: 'productResult', references: [] } as const;
const PROMPTS_BLOCK = { name: 'productPrompts', references: [] } as const;
const PROMPT_BLOCK = { name: 'productPrompt', references: [TEXT_REFERENCE] } as const;
const SEE_ALL_BLOCK = { name: 'productSeeAll', references: [] } as const;
const REFINEMENTS_BLOCK = { name: 'productRefinements', references: ['pills', 'chips'] } as const;
const REFINEMENT_CHIP_BLOCK = { name: 'productRefinementChip', references: ['label', 'count'] } as const;
const FILTER_PILL_BLOCK = { name: 'productFilterPill', references: ['label'] } as const;

type RefinementsReference = (typeof REFINEMENTS_BLOCK.references)[number];
type RefinementChipReference = (typeof REFINEMENT_CHIP_BLOCK.references)[number];

export interface ChatProductCard {
    readonly product: ChatProduct;
    readonly root: HTMLElement;
    readonly compare: HTMLElement | null;
}

export type ChatProductCardDecorator = (card: ChatProductCard) => void;

export interface ChatProductResults {
    read(result: string): ChatProduct[];
    find(sku: string): ChatProduct | null;
    render(products: readonly ChatProduct[], footer: readonly Element[]): HTMLElement;
    decorate(decorator: ChatProductCardDecorator): () => void;
}

interface ProductTemplates {
    readonly result: ChatBlockTemplate<never>;
    readonly carousel: ChatBlockTemplate<CarouselReference>;
    readonly card: ChatBlockTemplate<CardReference>;
    readonly prompts: ChatBlockTemplate<never>;
    readonly prompt: ChatBlockTemplate<typeof TEXT_REFERENCE>;
    readonly seeAll: ChatBlockTemplate<never>;
    readonly refinements: ChatBlockTemplate<RefinementsReference>;
    readonly refinementChip: ChatBlockTemplate<RefinementChipReference>;
    readonly filterPill: ChatBlockTemplate<'label'>;
}

const readImageOrigins = (element: HTMLElement): string[] =>
    (element.getAttribute(IMAGE_ORIGINS_ATTRIBUTE) ?? '')
        .split(',')
        .map((entry) => entry.trim())
        .filter((entry) => entry !== '')
        .map((entry) => {
            try {
                return new URL(entry.includes(URL_SCHEME_SEPARATOR) ? entry : `https${URL_SCHEME_SEPARATOR}${entry}`)
                    .origin;
            } catch (error) {
                throw new Error(
                    `<${TOOL_PRODUCTS_TAG_NAME}> has "${entry}" in its "${IMAGE_ORIGINS_ATTRIBUTE}" attribute, which ` +
                        `is not a host or an origin (reported by ${SOURCE_FILE}); the feature stays inactive. Pass ` +
                        `toolProducts.imageOrigins as a list such as ['media.example.com'] in the host's include of tool-products.twig.`,
                    { cause: error },
                );
            }
        });

const requireText = <Reference extends string>(template: ChatBlockTemplate<Reference>, attribute: string): string => {
    const text = template.create().root.getAttribute(attribute) ?? '';

    if (text.trim() === '') {
        throw new Error(
            `Chat block "${template.name}" required by <${TOOL_PRODUCTS_TAG_NAME}> has no "${attribute}" text on its ` +
                `root element (reported by ${SOURCE_FILE}). Keep the ${attribute} attribute with the translated text ` +
                `in the twig block "${template.name}".`,
        );
    }

    return text;
};

const findToolCalls = (context: ChatFeatureContext): ChatToolCalls => {
    const toolCalls = context.features.find(TOOL_CALLS_TAG_NAME) as ChatToolCalls | null;

    if (toolCalls === null || typeof toolCalls.tools?.register !== 'function') {
        throw new Error(
            `<${TOOL_PRODUCTS_TAG_NAME}> registers its renderer on <${TOOL_CALLS_TAG_NAME}>, which is not ` +
                `initialized (reported by ${SOURCE_FILE}). Keep features.toolCalls enabled in the host's include of tool-products.twig.`,
        );
    }

    return toolCalls;
};

export default class ChatToolProducts extends HTMLElement implements ChatFeature {
    readonly requires = [TOOL_CALLS_TAG_NAME];

    private context: ChatFeatureContext | null = null;
    private templates: ProductTemplates | null = null;
    private unregisterRenderer: (() => void) | null = null;
    private readonly productsBySku = new Map<string, ChatProduct>();
    private imageOrigins: readonly string[] = [];
    private readonly decorators: ChatProductCardDecorator[] = [];
    private readonly renderedResults = new Set<HTMLElement>();
    private readonly carouselObservers = new Map<ResizeObserver, HTMLElement>();
    private readonly refinementControls = new Set<HTMLElement>();
    private isBusy = false;

    readonly products: ChatProductResults = Object.freeze({
        read: (result: string): ChatProduct[] => this.readProducts(decodeResult(result)),
        find: (sku: string): ChatProduct | null => this.productsBySku.get(sku) ?? null,
        render: (products: readonly ChatProduct[], footer: readonly Element[]): HTMLElement =>
            this.renderResult(products, footer),
        decorate: (decorator: ChatProductCardDecorator): (() => void) => {
            this.decorators.push(decorator);

            return () => {
                const index = this.decorators.indexOf(decorator);

                if (index >= 0) {
                    this.decorators.splice(index, 1);
                }
            };
        },
    });

    connectedCallback(): void {
        announceFeature(this);
    }

    initialize(context: ChatFeatureContext): void {
        const toolCalls = findToolCalls(context);
        const imageOrigins = readImageOrigins(this);
        const templates: ProductTemplates = {
            result: context.blocks.require<never>(RESULT_BLOCK),
            carousel: context.blocks.require(CAROUSEL_BLOCK),
            card: context.blocks.require(CARD_BLOCK),
            prompts: context.blocks.require<never>(PROMPTS_BLOCK),
            prompt: context.blocks.require(PROMPT_BLOCK),
            seeAll: context.blocks.require<never>(SEE_ALL_BLOCK),
            refinements: context.blocks.require(REFINEMENTS_BLOCK),
            refinementChip: context.blocks.require(REFINEMENT_CHIP_BLOCK),
            filterPill: context.blocks.require(FILTER_PILL_BLOCK),
        };

        requireText(templates.seeAll, SEE_ALL_TEXT_ATTRIBUTE);
        requireText(templates.refinements, SELECT_TEXT_ATTRIBUTE);
        requireText(templates.refinements, REMOVE_TEXT_ATTRIBUTE);

        this.context = context;
        this.templates = templates;
        this.imageOrigins = imageOrigins;
        this.unregisterRenderer = toolCalls.tools.register(DISPLAY_PRODUCTS_TOOL_NAME, (call) =>
            this.renderDisplayProducts(call),
        );
        this.isBusy = context.services.get(REQUEST_LOCK).isBusy();
        context.events.on('busy', (change) => this.setBusy(change.isBusy));
        context.events.on('message:rendered', (rendered) => this.hideRefinementHint(rendered));
        context.events.on('conversation:reset', () => {
            this.productsBySku.clear();
            this.forgetDetachedResults();
        });
        context.events.on('conversation:loaded', () => this.forgetDetachedResults());
        context.events.on('turn:state', (change) => {
            if (change.state === CHAT_TURN_STATE.sending) {
                this.forgetDetachedResults();
            }
        });
    }

    dispose(): void {
        this.unregisterRenderer?.();
        this.unregisterRenderer = null;

        for (const result of this.renderedResults) {
            result.remove();
        }

        this.forgetDetachedResults();
        this.productsBySku.clear();
        this.refinementControls.clear();
        this.isBusy = false;
        this.context = null;
        this.templates = null;
        this.imageOrigins = [];
    }

    private readProducts(decoded: unknown): ChatProduct[] {
        return readProductCollection(decoded, this.imageOrigins).products;
    }

    private renderDisplayProducts(call: ChatToolCall): Element | null {
        let decoded: unknown;

        try {
            decoded = JSON.parse(call.result);
        } catch {
            this.reportUnreadableResult('it is not JSON');

            return null;
        }

        const reading = readProductCollection(decoded, this.imageOrigins);

        if (reading.problem !== '') {
            this.reportUnreadableResult(reading.problem);

            return null;
        }

        if (reading.products.length === 0) {
            return null;
        }

        const seeAll = this.createSeeAll(decoded);
        const isSeeAllRejected = seeAll === null && isRecord(decoded) && isRecord(decoded.seeAll);
        const footer = [seeAll, this.createRefinements(decoded)].filter(
            (element): element is HTMLElement => element !== null,
        );

        this.reportRejectedParts(reading, isSeeAllRejected);

        return this.renderResult(reading.products, footer);
    }

    private reportUnreadableResult(problem: string): void {
        reportError(
            new Error(
                `The "${DISPLAY_PRODUCTS_TOOL_NAME}" tool result could not be shown as products: ${problem} ` +
                    `(reported by ${SOURCE_FILE}). It falls back to the generic tool-call block (hidden with ` +
                    `hide-unclaimed-results); check what DisplayProductsToolPlugin returned for this turn.`,
            ),
        );
    }

    private reportRejectedParts(reading: ProductReading, isSeeAllRejected: boolean): void {
        const parts = [
            reading.rejectedProductCount > 0
                ? `${reading.rejectedProductCount} product(s) without a name and either a SKU or a same-origin URL ` +
                  `were left out`
                : '',
            reading.rejectedImageCount > 0
                ? `${reading.rejectedImageCount} image URL(s) that are neither a same-origin path nor an https URL ` +
                  `from "${IMAGE_ORIGINS_ATTRIBUTE}" are hidden`
                : '',
            isSeeAllRejected
                ? `the "see all" link was left out: its seeAll entry has no positive totalResults, no queryString, ` +
                  `or a categoryUrl (else the "${SEARCH_URL_ATTRIBUTE}") that is not a same-origin path`
                : '',
        ].filter((part) => part !== '');

        if (parts.length === 0) {
            return;
        }

        reportError(
            new Error(
                `The "${DISPLAY_PRODUCTS_TOOL_NAME}" tool result was shown with ${reading.products.length} product(s), ` +
                    `but ${parts.join('; ')} (reported by ${SOURCE_FILE}). Check what DisplayProductsToolPlugin ` +
                    `returned for this turn, or list the media host in toolProducts.imageOrigins.`,
            ),
        );
    }

    private renderResult(products: readonly ChatProduct[], footer: readonly Element[]): HTMLElement {
        const templates = this.requireTemplates();
        const result = templates.result.create().root;
        const carousel = templates.carousel.create();
        const prompts = this.createPrompts(products);

        for (const product of products) {
            this.rememberProduct(product);
            carousel.references.track.append(this.createCard(product));
        }

        this.bindCarousel(carousel);
        result.append(carousel.root);

        if (prompts !== null) {
            result.append(prompts);
        }

        result.append(...footer);
        this.renderedResults.add(result);

        return result;
    }

    private rememberProduct(product: ChatProduct): void {
        if (product.sku === '') {
            return;
        }

        this.productsBySku.delete(product.sku);
        this.productsBySku.set(product.sku, product);

        for (const sku of this.productsBySku.keys()) {
            if (this.productsBySku.size <= MAX_REMEMBERED_PRODUCTS) {
                break;
            }

            this.productsBySku.delete(sku);
        }
    }

    private createCard(product: ChatProduct): HTMLElement {
        const block = this.requireTemplates().card.create();
        const compare = fillProductCard(block, product);

        this.decorateCard({ product: product, root: block.root, compare: compare });

        return block.root;
    }

    private decorateCard(card: ChatProductCard): void {
        for (const decorator of [...this.decorators]) {
            try {
                decorator(card);
            } catch (error) {
                reportError(
                    new Error(
                        `A product card decorator threw for "${card.product.name}": ${describeFailure(error)} ` +
                            `(reported by ${SOURCE_FILE}). The card is shown without that decoration; fix the ` +
                            `decorator the feature registered through <${TOOL_PRODUCTS_TAG_NAME}>.products.decorate().`,
                        { cause: error },
                    ),
                );
            }
        }
    }

    private bindCarousel(carousel: ChatBlockInstance<CarouselReference>): void {
        const observer = bindProductCarousel(carousel);

        if (observer !== null) {
            this.carouselObservers.set(observer, carousel.references.track);
        }
    }

    private createPrompts(products: readonly ChatProduct[]): HTMLElement | null {
        const context = this.context;
        const suggestedPrompts = context?.features.find(SUGGESTED_PROMPTS_TAG_NAME) as ChatSuggestedPrompts | null;

        if (context === null || suggestedPrompts === null || typeof suggestedPrompts.promptsFor !== 'function') {
            return null;
        }

        const prompts = suggestedPrompts.promptsFor(this.getAttribute(PROMPT_GROUP_ATTRIBUTE) || DEFAULT_PROMPT_GROUP, {
            productName: products[0]?.name ?? '',
            productCount: String(products.length),
        });

        if (prompts.length === 0) {
            return null;
        }

        const templates = this.requireTemplates();
        const rail = templates.prompts.create().root;

        for (const prompt of prompts) {
            const button = templates.prompt.create();

            button.references.text.textContent = prompt;
            button.root.addEventListener('click', () => context.composer.write(prompt));
            rail.append(button.root);
        }

        return rail;
    }

    private createSeeAll(decoded: unknown): HTMLElement | null {
        const seeAll = parseSeeAll(decoded);
        const path = seeAll?.categoryUrl || this.getAttribute(SEARCH_URL_ATTRIBUTE);

        if (seeAll === null || !isSafePath(path)) {
            return null;
        }

        const link = this.requireTemplates().seeAll.create().root;
        const target = new URL(path, window.location.origin);

        new URLSearchParams(seeAll.queryString).forEach((value, name) => target.searchParams.append(name, value));
        link.setAttribute('href', `${target.pathname}${target.search}${target.hash}`);
        link.textContent = (link.getAttribute(SEE_ALL_TEXT_ATTRIBUTE) ?? '').replace(COUNT_PLACEHOLDER, () =>
            String(seeAll.totalResults),
        );

        return link;
    }

    private createRefinements(decoded: unknown): HTMLElement | null {
        const record = isRecord(decoded) ? decoded : {};
        const chips = parseList(record.refinementChips, parseRefinementChip);
        const filters = parseList(record.activeFilters, parseActiveFilter);

        if (chips.length === 0 && filters.length === 0) {
            return null;
        }

        const templates = this.requireTemplates();
        const block = templates.refinements.create();
        const selectText = block.root.getAttribute(SELECT_TEXT_ATTRIBUTE) ?? '';
        const removeText = block.root.getAttribute(REMOVE_TEXT_ATTRIBUTE) ?? '';

        for (const filter of filters) {
            const pill = templates.filterPill.create();
            const removeMessage = removeText.replace(LABEL_PLACEHOLDER, () => filter.label);

            pill.references.label.textContent = filter.label;
            pill.root.setAttribute('aria-label', removeMessage);
            pill.root.addEventListener('click', () =>
                this.send(pill.root, `${removeMessage} [${filter.filterArgument}]`),
            );
            this.addRefinementControl(pill.root);
            block.references.pills.append(pill.root);
        }

        for (const chip of chips) {
            const button = templates.refinementChip.create();
            const selectMessage = selectText.replace(LABEL_PLACEHOLDER, () => chip.label);

            button.references.label.textContent = chip.label;
            button.references.count.textContent = String(chip.matchCount);
            button.root.addEventListener('click', () =>
                this.send(button.root, `${selectMessage} [${chip.filterArgument}=${chip.value}]`),
            );
            this.addRefinementControl(button.root);
            block.references.chips.append(button.root);
        }

        block.references.pills.hidden = filters.length === 0;
        block.references.chips.hidden = chips.length === 0;

        return block.root;
    }

    private addRefinementControl(control: HTMLElement): void {
        control.toggleAttribute('disabled', this.isBusy);
        this.refinementControls.add(control);
    }

    private setBusy(isBusy: boolean): void {
        this.isBusy = isBusy;

        for (const control of this.refinementControls) {
            control.toggleAttribute('disabled', isBusy);
        }
    }

    private send(control: HTMLElement, message: string): void {
        const context = this.context;

        if (context === null || this.isBusy) {
            return;
        }

        control.toggleAttribute('disabled', true);
        context.conversation
            .send(message)
            .then((isSent: boolean) => {
                if (!isSent) {
                    this.reportRefusedRefinement(message);
                }
            })
            .catch((error: unknown) => reportError(error))
            .finally(() => control.toggleAttribute('disabled', this.isBusy));
    }

    private reportRefusedRefinement(message: string): void {
        reportError(
            new Error(
                `<${TOOL_PRODUCTS_TAG_NAME}> could not send the refinement "${message}" (reported by ${SOURCE_FILE}): ` +
                    `the chat refused it because another request holds the request lock. The control is enabled ` +
                    `again; choose it once the running request has finished.`,
            ),
        );
    }

    // Only the rendered text loses the hint; the recorded message keeps it, so regenerate sends the hint again.
    private hideRefinementHint(rendered: ChatRenderedMessage): void {
        if (rendered.role !== CHAT_MESSAGE_ROLE.user || !REFINEMENT_HINT_PATTERN.test(rendered.text)) {
            return;
        }

        const text = rendered.element.querySelector(`[data-ref="${TEXT_REFERENCE}"]`);

        if (text !== null) {
            text.textContent = rendered.text.replace(REFINEMENT_HINT_PATTERN, '');
        }
    }

    private forgetDetachedResults(): void {
        for (const [observer, track] of [...this.carouselObservers]) {
            if (!track.isConnected) {
                observer.disconnect();
                this.carouselObservers.delete(observer);
            }
        }

        for (const result of [...this.renderedResults]) {
            if (!result.isConnected) {
                this.renderedResults.delete(result);
            }
        }

        for (const control of [...this.refinementControls]) {
            if (!control.isConnected) {
                this.refinementControls.delete(control);
            }
        }
    }

    private requireTemplates(): ProductTemplates {
        if (this.templates === null) {
            throw new Error(
                `<${TOOL_PRODUCTS_TAG_NAME}> was asked to render products before initialize() or after dispose() ` +
                    `(reported by ${SOURCE_FILE}). Call its products API only while the feature is initialized, ` +
                    `between your own initialize() and dispose().`,
            );
        }

        return this.templates;
    }
}
