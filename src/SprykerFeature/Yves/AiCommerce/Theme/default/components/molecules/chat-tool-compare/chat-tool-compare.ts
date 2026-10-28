import './chat-tool-compare.scss';
/* eslint-disable max-lines -- the element owns the tray, the open view and the cards it rendered; the stateless reading and table parts already live in comparison-reader.ts and comparison-table.ts. */
import type { ChatFeature, ChatFeatureContext } from '@shared/ai-commerce/Chat/core/contracts/context';
import { CHAT_TURN_STATE } from '@shared/ai-commerce/Chat/core/contracts/context';
import type { ChatBlockTemplate } from '@shared/ai-commerce/Chat/core/contracts/blocks';
import { CORE_ZONE } from '@shared/ai-commerce/Chat/core/contracts/blocks';
import { announceFeature } from '@shared/ai-commerce/Chat/core/contracts/access';
import { REQUEST_LOCK } from '@shared/ai-commerce/Chat/core/contracts/tokens';
import type { ChatToolCall, ChatToolCalls } from '@shared/ai-commerce/Chat/features/tool-calls/tool-calls';
import type ChatToolProducts from '../chat-tool-products/chat-tool-products';
import type { ChatProduct, ChatProductCard } from '../chat-tool-products/chat-tool-products';
import { MIN_COMPARED_PRODUCTS, parseComparison, type Comparison } from './comparison-reader';
import { COUNT_PLACEHOLDER, TABLE_TEXT, createComparisonTable, showImage } from './comparison-table';

type ProductLookup = (sku: string) => ChatProduct | null;

const readComparedProductCandidates = (result: string): unknown[] | null => {
    try {
        const decoded: unknown = JSON.parse(result);
        const candidates =
            typeof decoded === 'object' && decoded !== null
                ? (decoded as Record<string, unknown>).comparedProducts
                : null;

        return Array.isArray(candidates) ? candidates : null;
    } catch {
        return null;
    }
};

export const TOOL_COMPARE_TAG_NAME = 'chat-tool-compare';

const SOURCE_FILE = 'Yves/AiCommerce/Theme/default/components/molecules/chat-tool-compare/chat-tool-compare.ts';
const TOOL_CALLS_TAG_NAME = 'chat-tool-calls';
const TOOL_PRODUCTS_TAG_NAME = 'chat-tool-products';
// Must match CompareProductsToolPlugin::TOOL_NAME.
const COMPARE_PRODUCTS_TOOL_NAME = 'compare_products';
const MAX_PRODUCTS_ATTRIBUTE = 'max-products';
const DEFAULT_MAX_PRODUCTS = 4;
const DECIMAL_RADIX = 10;
const DIFFERENCES_ONLY_ATTRIBUTE = 'data-differences-only';
const NAME_PLACEHOLDER = '%name%';
const SKU_PLACEHOLDER = '%sku%';
const PRODUCTS_PLACEHOLDER = '%products%';

const TRAY_TEXT = {
    selectedOne: 'data-selected-one',
    selectedMany: 'data-selected-many',
    removeLabel: 'data-remove-label',
    message: 'data-message',
    productFormat: 'data-product-format',
    productSeparator: 'data-product-separator',
} as const;
const COUNT_TEXT_ATTRIBUTE = 'data-count-text';

const TRAY_BLOCK = { name: 'compareTray', references: ['count', 'clear', 'chips', 'submit'] } as const;
const CHIP_BLOCK = { name: 'compareChip', references: ['image', 'name', 'remove'] } as const;
const CARD_BLOCK = { name: 'comparisonCard', references: ['count', 'products', 'open', 'link'] } as const;
const CARD_PRODUCT_BLOCK = { name: 'comparisonCardProduct', references: ['image', 'name', 'price'] } as const;
const VIEW_BLOCK = { name: 'comparisonView', references: ['back', 'count', 'differences', 'body'] } as const;
const TABLE_BLOCK = { name: 'comparisonTable', references: ['head', 'body'] } as const;
const HEAD_CELL_BLOCK = {
    name: 'comparisonHeadCell',
    references: ['image', 'name', 'sku', 'skuValue', 'price', 'link'],
} as const;
const SECTION_BLOCK = { name: 'comparisonSection', references: ['label'] } as const;
const ROW_BLOCK = { name: 'comparisonRow', references: ['label'] } as const;
const VALUE_BLOCK = { name: 'comparisonValue', references: [] } as const;
const EMPTY_VALUE_BLOCK = { name: 'comparisonEmptyValue', references: [] } as const;

type Reference<Spec extends { readonly references: readonly string[] }> = Spec['references'][number];

interface CompareTemplates {
    readonly chip: ChatBlockTemplate<Reference<typeof CHIP_BLOCK>>;
    readonly card: ChatBlockTemplate<Reference<typeof CARD_BLOCK>>;
    readonly cardProduct: ChatBlockTemplate<Reference<typeof CARD_PRODUCT_BLOCK>>;
    readonly table: ChatBlockTemplate<Reference<typeof TABLE_BLOCK>>;
    readonly headCell: ChatBlockTemplate<Reference<typeof HEAD_CELL_BLOCK>>;
    readonly section: ChatBlockTemplate<'label'>;
    readonly row: ChatBlockTemplate<'label'>;
    readonly value: ChatBlockTemplate<never>;
    readonly emptyValue: ChatBlockTemplate<never>;
}

interface Tray {
    readonly root: HTMLElement;
    readonly references: Readonly<Record<Reference<typeof TRAY_BLOCK>, HTMLElement>>;
}

interface ComparisonView {
    readonly root: HTMLElement;
    readonly references: Readonly<Record<Reference<typeof VIEW_BLOCK>, HTMLElement>>;
    readonly differences: HTMLInputElement;
}

const fillCount = (element: HTMLElement, count: number): string =>
    (element.getAttribute(COUNT_TEXT_ATTRIBUTE) ?? '').replace(COUNT_PLACEHOLDER, () => String(count));

const requireCheckbox = (element: HTMLElement): HTMLInputElement => {
    if (!(element instanceof HTMLInputElement) || element.type !== 'checkbox') {
        throw new Error(
            `Chat block "${VIEW_BLOCK.name}" required by <${TOOL_COMPARE_TAG_NAME}> marks a <${element.localName}> ` +
                `with data-ref="differences" (reported by ${SOURCE_FILE}). It must be an <input type="checkbox">, ` +
                `which holds the "only differences" switch; change the element in the twig block "${VIEW_BLOCK.name}".`,
        );
    }

    return element;
};

const requireText = (root: HTMLElement, blockName: string, attribute: string, mayBeBlank: boolean = false): void => {
    const text = root.getAttribute(attribute);

    if (text === null || (!mayBeBlank && text.trim() === '')) {
        throw new Error(
            `Chat block "${blockName}" required by <${TOOL_COMPARE_TAG_NAME}> has no "${attribute}" text on its root ` +
                `element (reported by ${SOURCE_FILE}); the feature stays inactive. Keep the ${attribute} attribute ` +
                `with the translated text in the twig block "${blockName}".`,
        );
    }
};

const readMaxProducts = (element: HTMLElement): number => {
    const value = (element.getAttribute(MAX_PRODUCTS_ATTRIBUTE) ?? '').trim();

    if (value === '') {
        return DEFAULT_MAX_PRODUCTS;
    }

    if (!/^[0-9]+$/.test(value) || Number.parseInt(value, DECIMAL_RADIX) < MIN_COMPARED_PRODUCTS) {
        throw new Error(
            `<${TOOL_COMPARE_TAG_NAME}> has ${MAX_PRODUCTS_ATTRIBUTE}="${value}", which is not a whole number of at ` +
                `least ${MIN_COMPARED_PRODUCTS} (reported by ${SOURCE_FILE}); the feature stays inactive. Pass ` +
                `toolCompare.maxProducts as a number in the host's include of tool-compare.twig, or leave it out for ` +
                `${DEFAULT_MAX_PRODUCTS}.`,
        );
    }

    return Number.parseInt(value, DECIMAL_RADIX);
};

const findFeature = <Feature>(
    context: ChatFeatureContext,
    tagName: string,
    isUsable: (feature: Feature) => boolean,
): Feature => {
    const feature = context.features.find(tagName) as Feature | null;

    if (feature === null || !isUsable(feature)) {
        throw new Error(
            `<${TOOL_COMPARE_TAG_NAME}> builds on <${tagName}>, which is not initialized (reported by ` +
                `${SOURCE_FILE}). ` +
                `Render <${tagName}> in the same chat; tool-compare is listed after it in the host's include of tool-compare.twig.`,
        );
    }

    return feature;
};

export default class ChatToolCompare extends HTMLElement implements ChatFeature {
    readonly requires = [TOOL_CALLS_TAG_NAME, TOOL_PRODUCTS_TAG_NAME];

    private context: ChatFeatureContext | null = null;
    private toolProducts: ChatToolProducts | null = null;
    private templates: CompareTemplates | null = null;
    private tray: Tray | null = null;
    private view: ComparisonView | null = null;
    private viewOpener: HTMLElement | null = null;
    private maxProducts = DEFAULT_MAX_PRODUCTS;
    private readonly releases: (() => void)[] = [];
    private readonly selected = new Map<string, ChatProduct>();
    private readonly compareButtons = new Map<HTMLElement, string>();
    private readonly renderedCards = new Set<HTMLElement>();
    private chipRemoveButtons: HTMLElement[] = [];
    private isBusy = false;

    connectedCallback(): void {
        announceFeature(this);
    }

    initialize(context: ChatFeatureContext): void {
        const toolCalls = findFeature<ChatToolCalls>(
            context,
            TOOL_CALLS_TAG_NAME,
            (feature) => typeof feature.tools?.register === 'function',
        );
        const toolProducts = findFeature<ChatToolProducts>(
            context,
            TOOL_PRODUCTS_TAG_NAME,
            (feature) => typeof feature.products?.decorate === 'function',
        );
        const tray = context.blocks.require(TRAY_BLOCK).create();
        const view = context.blocks.require(VIEW_BLOCK).create();

        this.maxProducts = readMaxProducts(this);
        this.requireTexts(tray.root, view.root, context);
        this.context = context;
        this.toolProducts = toolProducts;
        this.templates = {
            chip: context.blocks.require(CHIP_BLOCK),
            card: context.blocks.require(CARD_BLOCK),
            cardProduct: context.blocks.require(CARD_PRODUCT_BLOCK),
            table: context.blocks.require(TABLE_BLOCK),
            headCell: context.blocks.require(HEAD_CELL_BLOCK),
            section: context.blocks.require(SECTION_BLOCK),
            row: context.blocks.require(ROW_BLOCK),
            value: context.blocks.require<never>(VALUE_BLOCK),
            emptyValue: context.blocks.require<never>(EMPTY_VALUE_BLOCK),
        };
        this.tray = { root: tray.root, references: tray.references };
        this.view = {
            root: view.root,
            references: view.references,
            differences: requireCheckbox(view.references.differences),
        };

        this.isBusy = context.services.get(REQUEST_LOCK).isBusy();
        this.bindTray(this.tray);
        this.bindView(this.view);
        this.releases.push(
            toolCalls.tools.register(COMPARE_PRODUCTS_TOOL_NAME, (call) => this.renderComparisonCard(call)),
            toolProducts.products.decorate((card) => this.decorateCard(card)),
        );
        context.events.on('conversation:reset', () => this.resetShopping());
        context.events.on('conversation:loaded', () => this.resetShopping());
        context.events.on('turn:state', (change) => {
            if (change.state === CHAT_TURN_STATE.sending) {
                this.forgetDetached();
            }
        });
        context.events.on('busy', (change) => {
            this.isBusy = change.isBusy;
            this.syncSubmit();
        });
        this.placeTrayAboveComposer(context, tray.root);
        context.zones.insert(CORE_ZONE.views, view.root);
        this.renderTray();
    }

    dispose(): void {
        this.closeView();

        for (const release of this.releases.splice(0)) {
            release();
        }

        for (const card of this.renderedCards) {
            card.remove();
        }

        for (const button of this.compareButtons.keys()) {
            button.hidden = true;
        }

        this.renderedCards.clear();
        this.selected.clear();
        this.compareButtons.clear();
        this.chipRemoveButtons = [];
        this.isBusy = false;
        this.context = null;
        this.toolProducts = null;
        this.templates = null;
        this.tray = null;
        this.view = null;
    }

    private requireTexts(tray: HTMLElement, view: HTMLElement, context: ChatFeatureContext): void {
        for (const attribute of Object.values(TRAY_TEXT)) {
            requireText(tray, TRAY_BLOCK.name, attribute, attribute === TRAY_TEXT.productSeparator);
        }

        requireText(context.blocks.require(CARD_BLOCK).create().root, CARD_BLOCK.name, COUNT_TEXT_ATTRIBUTE);
        requireText(view, VIEW_BLOCK.name, COUNT_TEXT_ATTRIBUTE);

        const table = context.blocks.require(TABLE_BLOCK).create().root;

        for (const attribute of Object.values(TABLE_TEXT)) {
            requireText(table, TABLE_BLOCK.name, attribute);
        }
    }

    private placeTrayAboveComposer(context: ChatFeatureContext, tray: HTMLElement): void {
        context.zones.insert(CORE_ZONE.footer, tray);

        let composerRoot: Element | null = context.composer.input;

        while (composerRoot !== null && composerRoot.parentElement !== tray.parentElement) {
            composerRoot = composerRoot.parentElement;
        }

        composerRoot?.before(tray);
    }

    private bindTray(tray: Tray): void {
        tray.references.clear.addEventListener('click', () => this.clearTray());
        tray.references.submit.addEventListener('click', () => {
            this.submitTray().catch((error: unknown) => reportError(error));
        });
        // Pressing a tray button must not move the focus away from where the customer is typing.
        tray.root.addEventListener('mousedown', (event: MouseEvent) => {
            if (event.target instanceof Element && event.target.closest('button') !== null) {
                event.preventDefault();
            }
        });
    }

    private bindView(view: ComparisonView): void {
        view.root.hidden = true;
        view.references.back.addEventListener('click', () => this.closeView());
        view.differences.addEventListener('change', () =>
            view.root.toggleAttribute(DIFFERENCES_ONLY_ATTRIBUTE, view.differences.checked),
        );
    }

    private decorateCard(card: ChatProductCard): void {
        const button = card.compare;

        if (button === null || card.product.sku === '') {
            return;
        }

        button.hidden = false;
        this.compareButtons.set(button, card.product.sku);
        button.addEventListener('click', () => this.toggle(card.product));
        this.syncCompareButton(button, card.product.sku);
    }

    private toggle(product: ChatProduct): void {
        if (this.selected.has(product.sku)) {
            this.selected.delete(product.sku);
        } else if (this.selected.size < this.maxProducts) {
            this.selected.set(product.sku, product);
        }

        this.renderTray();
    }

    private removeFromTray(product: ChatProduct): void {
        const tray = this.tray;
        const position = [...this.selected.keys()].indexOf(product.sku);

        this.toggle(product);

        if (tray === null) {
            return;
        }

        const nextRemove = this.chipRemoveButtons[position] ?? null;

        if (nextRemove !== null) {
            nextRemove.focus();
        } else if (!tray.root.hidden) {
            tray.references.clear.focus();
        } else {
            this.context?.composer.focus();
        }
    }

    private clearTray(): void {
        this.moveFocusOutOfTray();
        this.selected.clear();
        this.renderTray();
    }

    private moveFocusOutOfTray(): void {
        if (this.tray?.root.contains(document.activeElement)) {
            this.context?.composer.focus();
        }
    }

    private async submitTray(): Promise<void> {
        const tray = this.tray;
        const context = this.context;
        const products = [...this.selected.values()];

        if (tray === null || context === null || this.isBusy || products.length < MIN_COMPARED_PRODUCTS) {
            return;
        }

        const wasFocusInTray = tray.root.contains(document.activeElement);
        const format = tray.root.getAttribute(TRAY_TEXT.productFormat) ?? '';
        const productList = products
            .map((product) =>
                format.replace(NAME_PLACEHOLDER, () => product.name).replace(SKU_PLACEHOLDER, () => product.sku),
            )
            .join(tray.root.getAttribute(TRAY_TEXT.productSeparator) ?? '');
        const message = (tray.root.getAttribute(TRAY_TEXT.message) ?? '').replace(
            PRODUCTS_PLACEHOLDER,
            () => productList,
        );

        this.clearTray();

        if ((await context.conversation.send(message)) || this.tray === null || this.selected.size > 0) {
            return;
        }

        for (const product of products) {
            this.selected.set(product.sku, product);
        }

        this.renderTray();

        if (wasFocusInTray) {
            this.tray.references.submit.focus();
        }

        reportError(
            new Error(
                `<chat-tool-compare> could not send the comparison of ${products.length} products (reported by ` +
                    `${SOURCE_FILE}): the chat refused it because another request holds the request lock. The ` +
                    `selection is restored; submit it once the running request has finished.`,
            ),
        );
    }

    private syncSubmit(): void {
        this.tray?.references.submit.toggleAttribute(
            'disabled',
            this.isBusy || this.selected.size < MIN_COMPARED_PRODUCTS,
        );
    }

    private renderTray(): void {
        const tray = this.tray;
        const templates = this.templates;

        if (tray === null || templates === null) {
            return;
        }

        const count = this.selected.size;
        const { count: countElement, chips } = tray.references;
        const text = (attribute: string): string => tray.root.getAttribute(attribute) ?? '';

        tray.root.hidden = count === 0;
        countElement.textContent =
            count === 1
                ? text(TRAY_TEXT.selectedOne)
                : text(TRAY_TEXT.selectedMany).replace(COUNT_PLACEHOLDER, () => String(count));
        this.syncSubmit();
        this.chipRemoveButtons = [];
        chips.replaceChildren(
            ...[...this.selected.values()].map((product) => {
                const chip = templates.chip.create();
                const removeLabel = text(TRAY_TEXT.removeLabel).replace(NAME_PLACEHOLDER, () => product.name);

                showImage(chip.references.image, product.imageUrl);
                chip.references.name.textContent = product.name;
                chip.references.name.setAttribute('title', product.name);
                chip.references.remove.setAttribute('aria-label', removeLabel);
                chip.references.remove.setAttribute('title', removeLabel);
                chip.references.remove.addEventListener('click', () => this.removeFromTray(product));
                this.chipRemoveButtons.push(chip.references.remove);

                return chip.root;
            }),
        );
        this.syncCompareButtons();
    }

    private syncCompareButtons(): void {
        for (const [button, sku] of this.compareButtons) {
            this.syncCompareButton(button, sku);
        }
    }

    private syncCompareButton(button: HTMLElement, sku: string): void {
        const isSelected = this.selected.has(sku);

        button.setAttribute('aria-pressed', String(isSelected));
        button.toggleAttribute('disabled', !isSelected && this.selected.size >= this.maxProducts);
    }

    private renderComparisonCard(call: ChatToolCall): Element | null {
        const comparison = parseComparison(call.result);
        const templates = this.templates;

        if (comparison === null || templates === null) {
            return null;
        }

        if (typeof comparison === 'string') {
            reportError(
                new Error(
                    `The "${COMPARE_PRODUCTS_TOOL_NAME}" tool result could not be shown as a comparison: ` +
                        `${comparison} (reported by ${SOURCE_FILE}). It falls back to the generic tool-call block ` +
                        `(hidden with hide-unclaimed-results); check what CompareProductsToolPlugin returned for this ` +
                        `turn.`,
                ),
            );

            return null;
        }

        this.reportRejectedParts(comparison);

        const card = templates.card.create();
        const { count, products, open, link } = card.references;

        const findProduct = this.createProductLookup(call.result);

        count.textContent = fillCount(card.root, comparison.products.length);

        for (const product of comparison.products) {
            const item = templates.cardProduct.create();

            showImage(item.references.image, findProduct(product.sku)?.imageUrl ?? '');
            item.references.name.textContent = product.name;
            item.references.price.textContent = product.priceFormatted;
            products.append(item.root);
        }

        open.addEventListener('click', () => this.openView(comparison, open, findProduct));
        link.hidden = comparison.comparisonUrl === '';

        if (comparison.comparisonUrl !== '') {
            link.setAttribute('href', comparison.comparisonUrl);
        }

        this.renderedCards.add(card.root);

        return card.root;
    }

    private reportRejectedParts(comparison: Comparison): void {
        const parts = [
            comparison.rejectedRowCount > 0
                ? `${comparison.rejectedRowCount} attribute row(s) without an "attribute" name were left out`
                : '',
            comparison.isComparisonUrlRejected
                ? 'the "Full comparison" link was left out because comparisonUrl is not a same-origin path'
                : '',
        ].filter((part) => part !== '');

        if (parts.length > 0) {
            reportError(
                new Error(
                    `The "${COMPARE_PRODUCTS_TOOL_NAME}" tool result was shown, but ${parts.join('; ')} (reported by ` +
                        `${SOURCE_FILE}). Check what CompareProductsToolPlugin returned for this turn.`,
                ),
            );
        }
    }

    private createProductLookup(result: string): ProductLookup {
        const toolProducts = this.toolProducts;
        const comparedProducts = new Map<string, ChatProduct>();
        const candidates = readComparedProductCandidates(result);

        if (toolProducts !== null && candidates !== null) {
            for (const product of toolProducts.products.read(JSON.stringify(candidates))) {
                comparedProducts.set(product.sku, product);
            }
        }

        return (sku: string): ChatProduct | null => {
            const compared = comparedProducts.get(sku) ?? null;
            const displayed = sku === '' ? null : (toolProducts?.products.find(sku) ?? null);

            if (compared === null || displayed === null) {
                return compared ?? displayed;
            }

            return {
                ...compared,
                url: compared.url || displayed.url,
                imageUrl: compared.imageUrl || displayed.imageUrl,
            };
        };
    }

    private openView(comparison: Comparison, opener: HTMLElement, findProduct: ProductLookup): void {
        const view = this.view;
        const context = this.context;

        if (view === null || context === null) {
            return;
        }

        view.references.count.textContent = fillCount(view.root, comparison.products.length);
        view.references.body.replaceChildren(createComparisonTable(this.requireTemplates(), comparison, findProduct));
        view.references.body.scrollTop = 0;
        view.references.body.scrollLeft = 0;
        view.root.toggleAttribute(DIFFERENCES_ONLY_ATTRIBUTE, view.differences.checked);
        context.panel.showView(view.root);
        this.viewOpener = opener;
        view.references.back.focus();
    }

    private closeView(): void {
        const view = this.view;

        if (view === null) {
            return;
        }

        const opener = this.viewOpener;

        this.viewOpener = null;

        if (!view.root.hidden) {
            this.context?.panel.hideView();

            if (opener?.isConnected) {
                opener.focus();
            }
        }

        view.references.body.replaceChildren();
    }

    private resetShopping(): void {
        this.closeView();
        this.clearTray();
        this.forgetDetached();
    }

    private forgetDetached(): void {
        for (const card of [...this.renderedCards]) {
            if (!card.isConnected) {
                this.renderedCards.delete(card);
            }
        }

        for (const button of [...this.compareButtons.keys()]) {
            if (!button.isConnected) {
                this.compareButtons.delete(button);
            }
        }
    }

    private requireTemplates(): CompareTemplates {
        if (this.templates === null) {
            throw new Error(
                `<${TOOL_COMPARE_TAG_NAME}> was asked to render a comparison before initialize() or after dispose() ` +
                    `(reported by ${SOURCE_FILE}). Keep the feature element in the chat while its comparison is open.`,
            );
        }

        return this.templates;
    }
}
