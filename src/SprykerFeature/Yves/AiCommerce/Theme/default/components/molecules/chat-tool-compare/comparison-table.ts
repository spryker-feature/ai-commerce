import type { ChatBlockTemplate } from '@shared/ai-commerce/Chat/core/contracts/blocks';
import type { ChatProduct } from '../chat-tool-products/chat-tool-products';
import type { ComparedProduct, Comparison, ComparisonRow } from './comparison-reader';

const DIFFERENT_ATTRIBUTE = 'data-different';
const SAME_ATTRIBUTE = 'data-same';
const RATING_PLACEHOLDER = '%rating%';
const RATING_FRACTION_DIGITS = 1;

export const COUNT_PLACEHOLDER = '%count%';

export const TABLE_TEXT = {
    overview: 'data-overview',
    specifications: 'data-specifications',
    rating: 'data-rating',
    availability: 'data-availability',
    available: 'data-available',
    unavailable: 'data-unavailable',
    ratingFormat: 'data-rating-format',
    ratingWithReviewsFormat: 'data-rating-with-reviews-format',
} as const;

export interface ComparisonTableTemplates {
    readonly table: ChatBlockTemplate<'head' | 'body'>;
    readonly headCell: ChatBlockTemplate<'image' | 'name' | 'sku' | 'skuValue' | 'price' | 'link'>;
    readonly section: ChatBlockTemplate<'label'>;
    readonly row: ChatBlockTemplate<'label'>;
    readonly value: ChatBlockTemplate<never>;
    readonly emptyValue: ChatBlockTemplate<never>;
}

const setText = (element: HTMLElement, text: string): void => {
    element.textContent = text;
    element.hidden = text === '';
};

export const showImage = (image: HTMLElement, url: string): void => {
    image.hidden = url === '';
    image.addEventListener(
        'error',
        () => {
            image.hidden = true;
        },
        { once: true },
    );

    if (url !== '') {
        image.setAttribute('src', url);
    }
};

export const createComparisonTable = (
    templates: ComparisonTableTemplates,
    comparison: Comparison,
    findProduct: (sku: string) => ChatProduct | null,
): HTMLElement => {
    const table = templates.table.create();
    const text = (attribute: string): string => table.root.getAttribute(attribute) ?? '';

    for (const product of comparison.products) {
        table.references.head.append(createHeadCell(templates, product, findProduct));
    }

    appendSection(
        templates,
        table.references.body,
        text(TABLE_TEXT.overview),
        createOverviewRows(comparison, text),
        comparison,
    );
    appendSection(templates, table.references.body, text(TABLE_TEXT.specifications), comparison.rows, comparison);

    return table.root;
};

const createHeadCell = (
    templates: ComparisonTableTemplates,
    product: ComparedProduct,
    findProduct: (sku: string) => ChatProduct | null,
): HTMLElement => {
    const cell = templates.headCell.create();
    const { image, name, sku, skuValue, price, link } = cell.references;
    const knownProduct = findProduct(product.sku);
    const url = knownProduct?.url ?? '';

    showImage(image, knownProduct?.imageUrl ?? '');
    image.setAttribute('alt', product.name);
    setText(name, product.name);
    setText(skuValue, product.sku);
    sku.hidden = product.sku === '';
    setText(price, product.priceFormatted);
    link.hidden = url === '';

    if (url !== '') {
        link.setAttribute('href', url);
    }

    return cell.root;
};

const createOverviewRows = (comparison: Comparison, text: (attribute: string) => string): ComparisonRow[] => {
    const formatRating = (product: ComparedProduct): string => {
        if (product.rating === null) {
            return '';
        }

        const format =
            product.reviewCount === null ? text(TABLE_TEXT.ratingFormat) : text(TABLE_TEXT.ratingWithReviewsFormat);

        return format
            .replace(RATING_PLACEHOLDER, () => (product.rating ?? 0).toFixed(RATING_FRACTION_DIGITS))
            .replace(COUNT_PLACEHOLDER, () => String(product.reviewCount));
    };
    const formatAvailability = (product: ComparedProduct): string => {
        if (product.isAvailable === null) {
            return '';
        }

        return text(product.isAvailable ? TABLE_TEXT.available : TABLE_TEXT.unavailable);
    };
    const rows = [
        { attribute: text(TABLE_TEXT.rating), values: comparison.products.map(formatRating) },
        { attribute: text(TABLE_TEXT.availability), values: comparison.products.map(formatAvailability) },
    ];

    return rows
        .filter((row) => row.values.some((value) => value !== ''))
        .map((row) => ({ ...row, isDifferent: new Set(row.values).size > 1 }));
};

const appendSection = (
    templates: ComparisonTableTemplates,
    body: HTMLElement,
    label: string,
    rows: readonly ComparisonRow[],
    comparison: Comparison,
): void => {
    if (rows.length === 0) {
        return;
    }

    const section = templates.section.create();

    section.references.label.textContent = label;
    section.references.label.setAttribute('colspan', String(comparison.products.length + 1));
    section.root.toggleAttribute(
        SAME_ATTRIBUTE,
        rows.every((row) => !row.isDifferent),
    );
    body.append(section.root);

    for (const row of rows) {
        const rowBlock = templates.row.create();

        rowBlock.references.label.textContent = row.attribute;
        rowBlock.root.toggleAttribute(DIFFERENT_ATTRIBUTE, row.isDifferent);

        for (const value of row.values) {
            const cell = value === '' ? templates.emptyValue.create().root : templates.value.create().root;

            if (value !== '') {
                cell.textContent = value;
            }

            rowBlock.root.append(cell);
        }

        body.append(rowBlock.root);
    }
};
