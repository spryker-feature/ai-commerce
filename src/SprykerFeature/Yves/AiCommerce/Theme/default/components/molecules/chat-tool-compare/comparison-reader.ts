export const MIN_COMPARED_PRODUCTS = 2;
// Same-origin paths only (no `//host`, `/\\host`, whitespace, backslash); kept in sync with product-reader.ts.
const SAFE_PATH_PATTERN = /^\/(?![/\\])[^\s\\]*$/;

export interface ComparedProduct {
    readonly name: string;
    readonly sku: string;
    readonly priceFormatted: string;
    readonly rating: number | null;
    readonly reviewCount: number | null;
    readonly isAvailable: boolean | null;
}

export interface ComparisonRow {
    readonly attribute: string;
    readonly values: readonly string[];
    readonly isDifferent: boolean;
}

export interface Comparison {
    readonly products: readonly ComparedProduct[];
    readonly rows: readonly ComparisonRow[];
    readonly comparisonUrl: string;
    readonly rejectedRowCount: number;
    readonly isComparisonUrlRejected: boolean;
}

const isRecord = (value: unknown): value is Record<string, unknown> =>
    typeof value === 'object' && value !== null && !Array.isArray(value);

const readString = (value: unknown): string => (typeof value === 'string' ? value.trim() : '');

const parseComparedProduct = (candidate: unknown): ComparedProduct => {
    const record = isRecord(candidate) ? candidate : {};

    return {
        name: readString(record.name),
        sku: readString(record.sku),
        priceFormatted: readString(record.priceFormatted),
        rating: typeof record.rating === 'number' ? record.rating : null,
        reviewCount: typeof record.reviewCount === 'number' ? record.reviewCount : null,
        isAvailable: typeof record.isAvailable === 'boolean' ? record.isAvailable : null,
    };
};

const parseRow = (candidate: unknown, productCount: number): ComparisonRow | null => {
    if (!isRecord(candidate)) {
        return null;
    }

    const attribute = readString(candidate.attribute);
    const values = Array.isArray(candidate.values) ? candidate.values : [];

    if (attribute === '') {
        return null;
    }

    return {
        attribute: attribute,
        values: Array.from({ length: productCount }, (_value, index) => readString(values[index])),
        isDifferent: candidate.isDifferent === true,
    };
};

const isModelCorrection = (decoded: Record<string, unknown>): boolean =>
    (typeof decoded.error === 'string' || 'alreadyShown' in decoded) && !('comparedProducts' in decoded);

export const parseComparison = (result: string): Comparison | string | null => {
    let decoded: unknown;

    try {
        decoded = JSON.parse(result);
    } catch {
        return 'it is not JSON';
    }

    if (isRecord(decoded) && isModelCorrection(decoded)) {
        return null;
    }

    if (!isRecord(decoded) || !Array.isArray(decoded.comparedProducts)) {
        return 'it has no "comparedProducts" list';
    }

    if (decoded.comparedProducts.length === 0) {
        return null;
    }

    const products = decoded.comparedProducts.map(parseComparedProduct);
    const namelessCount = products.filter((product) => product.name === '').length;

    if (namelessCount > 0) {
        return `${namelessCount} of its ${products.length} compared products have no name`;
    }

    if (products.length < MIN_COMPARED_PRODUCTS) {
        return `it compares ${products.length} product, at least ${MIN_COMPARED_PRODUCTS} are needed`;
    }

    const comparisonUrl = readString(decoded.comparisonUrl);
    const rowCandidates = Array.isArray(decoded.attributeRows) ? decoded.attributeRows : [];
    const rows = rowCandidates
        .map((row) => parseRow(row, products.length))
        .filter((row): row is ComparisonRow => row !== null);
    const isComparisonUrlSafe = SAFE_PATH_PATTERN.test(comparisonUrl);

    return {
        products: products,
        rows: rows,
        comparisonUrl: isComparisonUrlSafe ? comparisonUrl : '',
        rejectedRowCount: rowCandidates.length - rows.length,
        isComparisonUrlRejected: comparisonUrl !== '' && !isComparisonUrlSafe,
    };
};
