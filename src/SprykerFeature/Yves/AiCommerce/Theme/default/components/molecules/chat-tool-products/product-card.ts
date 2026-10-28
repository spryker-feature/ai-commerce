import type { ChatBlockInstance } from '@shared/ai-commerce/Chat/core/contracts/blocks';
import type { ChatProduct } from './product-reader';

const SCROLLABLE_ATTRIBUTE = 'data-scrollable';
const COMPARE_REFERENCE = 'compare';
const SCROLL_TOLERANCE_PIXELS = 1;
const RATING_FRACTION_DIGITS = 1;

export const CAROUSEL_BLOCK = { name: 'productCarousel', references: ['track', 'previous', 'next'] } as const;
export const CARD_BLOCK = {
    name: 'productCard',
    references: [
        'image',
        'badge',
        'name',
        'sku',
        'skuValue',
        'price',
        'priceCurrent',
        'priceOriginal',
        'priceOutlier',
        'rating',
        'ratingValue',
        'reviews',
        'reviewCount',
        'reason',
        'description',
        'view',
    ],
} as const;

export type CarouselReference = (typeof CAROUSEL_BLOCK.references)[number];
export type CardReference = (typeof CARD_BLOCK.references)[number];

export const setText = (element: HTMLElement, text: string): void => {
    element.textContent = text;
    element.hidden = text === '';
};

const renderImage = (image: HTMLElement, product: ChatProduct): void => {
    image.hidden = product.imageUrl === '';
    image.addEventListener(
        'error',
        () => {
            image.hidden = true;
        },
        { once: true },
    );

    if (product.imageUrl !== '') {
        image.setAttribute('src', product.imageUrl);
        image.setAttribute('alt', product.name);
    }
};

const renderRating = (references: Readonly<Record<CardReference, HTMLElement>>, product: ChatProduct): void => {
    references.rating.hidden = product.rating === null;
    references.reviews.hidden = product.reviewCount === null;

    if (product.rating !== null) {
        references.ratingValue.textContent = product.rating.toFixed(RATING_FRACTION_DIGITS);
    }

    if (product.reviewCount !== null) {
        references.reviewCount.textContent = String(product.reviewCount);
    }
};

export const fillProductCard = (block: ChatBlockInstance<CardReference>, product: ChatProduct): HTMLElement | null => {
    const references = block.references;
    const compare = block.find(COMPARE_REFERENCE);
    const originalPrice =
        product.originalPriceFormatted === product.priceFormatted ? '' : product.originalPriceFormatted;

    setText(references.name, product.name);
    setText(references.skuValue, product.sku);
    references.sku.hidden = product.sku === '';
    setText(references.reason, product.reason);
    setText(references.description, product.reason === '' ? product.description : '');
    setText(references.badge, product.labels[0] ?? '');
    setText(references.priceCurrent, product.priceFormatted);
    setText(references.priceOriginal, originalPrice);
    references.price.hidden = product.priceFormatted === '';
    references.priceOutlier.hidden = !product.isPriceOutlier;
    renderImage(references.image, product);
    renderRating(references, product);
    references.view.hidden = product.url === '';

    if (product.url !== '') {
        references.view.setAttribute('href', product.url);
    }

    if (compare !== null) {
        compare.hidden = true;
    }

    return compare;
};

const measureStep = (track: HTMLElement): number => {
    const firstCard = track.firstElementChild;
    const gap = Number.parseFloat(getComputedStyle(track).columnGap) || 0;

    return firstCard instanceof HTMLElement ? firstCard.offsetWidth + gap : track.clientWidth;
};

export const bindProductCarousel = (carousel: ChatBlockInstance<CarouselReference>): ResizeObserver | null => {
    const { track, previous, next } = carousel.references;
    const update = (): void => {
        const maxScrollLeft = track.scrollWidth - track.clientWidth;

        carousel.root.toggleAttribute(SCROLLABLE_ATTRIBUTE, maxScrollLeft > SCROLL_TOLERANCE_PIXELS);
        previous.toggleAttribute('disabled', track.scrollLeft <= SCROLL_TOLERANCE_PIXELS);
        next.toggleAttribute('disabled', track.scrollLeft >= maxScrollLeft - SCROLL_TOLERANCE_PIXELS);
    };
    const scrollByCard = (direction: number): void =>
        track.scrollBy({ left: direction * measureStep(track), behavior: 'smooth' });

    previous.addEventListener('click', () => scrollByCard(-1));
    next.addEventListener('click', () => scrollByCard(1));
    track.addEventListener('scroll', update, { passive: true });

    for (const image of Array.from(track.querySelectorAll('img'))) {
        image.addEventListener('load', update, { once: true });
    }

    update();

    if (typeof ResizeObserver !== 'function') {
        return null;
    }

    const observer = new ResizeObserver(update);

    observer.observe(track);

    return observer;
};
