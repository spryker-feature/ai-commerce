const HTTPS_PROTOCOL = 'https:';
const MAX_REFINEMENT_TEXT_LENGTH = 80;
const PRODUCT_COLLECTION_KEYS = ['products', 'items'];
// Same-origin paths only (no `//host`, `/\\host`, whitespace, backslash); kept in sync with comparison-reader.ts.
const SAFE_PATH_PATTERN = /^\/(?![/\\])[^\s\\]*$/;
const FILTER_ARGUMENT_PATTERN = /^[A-Za-z][\w.]*(?:, [A-Za-z][\w.]*)*$/;
export interface ChatProduct {
    readonly name: string;
    readonly sku: string;
    readonly url: string;
    readonly imageUrl: string;
    readonly description: string;
    readonly priceFormatted: string;
    readonly originalPriceFormatted: string;
    readonly reason: string;
    readonly isPriceOutlier: boolean;
    readonly rating: number | null;
    readonly reviewCount: number | null;
    readonly labels: readonly string[];
}

export interface RefinementChip {
    readonly label: string;
    readonly filterArgument: string;
    readonly value: string;
    readonly matchCount: number;
}

export interface ActiveFilter {
    readonly label: string;
    readonly filterArgument: string;
    readonly value: string;
}

export interface SeeAll {
    readonly totalResults: number;
    readonly queryString: string;
    readonly categoryUrl: string;
}

export interface ProductReading {
    readonly products: ChatProduct[];
    readonly problem: string;
    readonly rejectedProductCount: number;
    readonly rejectedImageCount: number;
}

export const isRecord = (value: unknown): value is Record<string, unknown> =>
    typeof value === 'object' && value !== null && !Array.isArray(value);

export const decodeResult = (result: string): unknown => {
    try {
        return JSON.parse(result);
    } catch {
        return null;
    }
};

const readString = (value: unknown): string => (typeof value === 'string' ? value.trim() : '');

const readNumber = (value: unknown): number | null =>
    typeof value === 'number' && Number.isFinite(value) ? value : null;

export const isSafePath = (path: unknown): path is string => typeof path === 'string' && SAFE_PATH_PATTERN.test(path);

const isSafeImageUrl = (url: string, origins: readonly string[]): boolean => {
    if (isSafePath(url)) {
        return true;
    }

    try {
        const parsed = new URL(url);

        return parsed.protocol === HTTPS_PROTOCOL && (origins.length === 0 || origins.includes(parsed.origin));
    } catch {
        return false;
    }
};

// Brackets would break the refinement hint the message ends with, so they never reach it.
const readRefinementText = (value: unknown): string =>
    typeof value === 'string'
        ? value
              .replace(/[[\]\s]+/g, ' ')
              .trim()
              .slice(0, MAX_REFINEMENT_TEXT_LENGTH)
        : '';

const findProductCollection = (decoded: unknown): unknown[] | null => {
    if (Array.isArray(decoded)) {
        return decoded;
    }

    if (!isRecord(decoded)) {
        return null;
    }

    for (const key of PRODUCT_COLLECTION_KEYS) {
        const collection = decoded[key];

        if (Array.isArray(collection)) {
            return collection;
        }
    }

    return null;
};

const parseProduct = (candidate: unknown, imageOrigins: readonly string[]): ChatProduct | null => {
    if (!isRecord(candidate)) {
        return null;
    }

    const name = readString(candidate.name);
    const sku = readString(candidate.sku);
    const url = isSafePath(readString(candidate.url)) ? readString(candidate.url) : '';
    const imageUrl = readString(candidate.imageUrl);

    if (name === '' || (sku === '' && url === '')) {
        return null;
    }

    return {
        name: name,
        sku: sku,
        url: url,
        imageUrl: isSafeImageUrl(imageUrl, imageOrigins) ? imageUrl : '',
        description: readString(candidate.description),
        priceFormatted: readString(candidate.priceFormatted),
        originalPriceFormatted: readString(candidate.originalPriceFormatted),
        reason: readString(candidate.reason),
        isPriceOutlier: candidate.isPriceOutlier === true,
        rating: readNumber(candidate.rating),
        reviewCount: readNumber(candidate.reviewCount),
        labels: Array.isArray(candidate.labels) ? candidate.labels.map(readString).filter((label) => label !== '') : [],
    };
};

export const readProductCollection = (decoded: unknown, imageOrigins: readonly string[]): ProductReading => {
    const collection = findProductCollection(decoded);
    const products: ChatProduct[] = [];
    let rejectedImageCount = 0;

    if (collection === null) {
        return {
            products: products,
            problem: `it is neither a list of products nor an object with a ${PRODUCT_COLLECTION_KEYS.join(' or ')} list`,
            rejectedProductCount: 0,
            rejectedImageCount: 0,
        };
    }

    for (const candidate of collection) {
        const product = parseProduct(candidate, imageOrigins);

        if (product === null) {
            continue;
        }

        if (product.imageUrl === '' && isRecord(candidate) && readString(candidate.imageUrl) !== '') {
            rejectedImageCount += 1;
        }

        products.push(product);
    }

    return {
        products: products,
        problem:
            collection.length > 0 && products.length === 0
                ? `none of its ${collection.length} entries has a name and either a SKU or a same-origin URL`
                : '',
        rejectedProductCount: collection.length - products.length,
        rejectedImageCount: rejectedImageCount,
    };
};

export const parseActiveFilter = (candidate: unknown): ActiveFilter | null => {
    if (!isRecord(candidate)) {
        return null;
    }

    const label = readRefinementText(candidate.label);
    const filterArgument = readRefinementText(candidate.filterArgument);
    const value = readRefinementText(candidate.value);

    if (label === '' || value === '' || !FILTER_ARGUMENT_PATTERN.test(filterArgument)) {
        return null;
    }

    return { label: label, filterArgument: filterArgument, value: value };
};

export const parseRefinementChip = (candidate: unknown): RefinementChip | null => {
    const filter = parseActiveFilter(candidate);
    const matchCount = isRecord(candidate) ? candidate.matchCount : null;

    if (filter === null || typeof matchCount !== 'number' || !Number.isInteger(matchCount) || matchCount <= 0) {
        return null;
    }

    return { ...filter, matchCount: matchCount };
};

export const parseList = <Item>(value: unknown, parse: (candidate: unknown) => Item | null): Item[] =>
    (Array.isArray(value) ? value : []).map(parse).filter((item): item is Item => item !== null);

export const parseSeeAll = (decoded: unknown): SeeAll | null => {
    const seeAll = isRecord(decoded) ? decoded.seeAll : null;

    if (!isRecord(seeAll)) {
        return null;
    }

    const { totalResults, queryString, categoryUrl } = seeAll;

    if (typeof totalResults !== 'number' || !Number.isInteger(totalResults) || totalResults <= 0) {
        return null;
    }

    if (typeof queryString !== 'string' || (categoryUrl !== undefined && !isSafePath(categoryUrl))) {
        return null;
    }

    return {
        totalResults: totalResults,
        queryString: queryString,
        categoryUrl: typeof categoryUrl === 'string' ? categoryUrl : '',
    };
};
