// Returns nodes, never a string, so no model HTML reaches an innerHTML sink; never use a weaker profile elsewhere.
// No images, no forms a prompt injection could aim elsewhere, no `data-*` or `<template>` (the core's zones/blocks).

import DOMPurify from 'dompurify';
import type { ChatSanitizer } from '../contracts/services';

const TARGET_ATTRIBUTE = 'target';
const RELATION_ATTRIBUTE = 'rel';
const REQUIRED_RELATION = 'noopener';
const ALLOWED_TAGS = [
    'p',
    'br',
    'hr',
    'h1',
    'h2',
    'h3',
    'h4',
    'h5',
    'h6',
    'blockquote',
    'pre',
    'code',
    'em',
    'strong',
    'del',
    's',
    'a',
    'ul',
    'ol',
    'li',
    'table',
    'thead',
    'tbody',
    'tr',
    'th',
    'td',
    'span',
    'sup',
    'sub',
];
const ALLOWED_ATTRIBUTES = ['href', 'title', 'start', 'align', 'class'];

// Without rel="noopener" a link opened in a new tab could reach back through window.opener.
const enforceNoopener = (element: Element): void => {
    if (!element.hasAttribute(TARGET_ATTRIBUTE)) {
        return;
    }

    const relations = new Set(
        (element.getAttribute(RELATION_ATTRIBUTE) ?? '').split(/\s+/).filter((relation) => relation !== ''),
    );

    relations.add(REQUIRED_RELATION);
    element.setAttribute(RELATION_ATTRIBUTE, [...relations].join(' '));
};

// A private instance keeps the hook away from every other DOMPurify user on the page.
const createPurifier = (): ReturnType<typeof DOMPurify> => {
    const purifier = DOMPurify(window);

    purifier.addHook('afterSanitizeAttributes', enforceNoopener);

    return purifier;
};

export class DomPurifyChatSanitizer implements ChatSanitizer {
    private readonly purifier = createPurifier();

    sanitize(html: string): DocumentFragment {
        return this.purifier.sanitize(html, {
            ALLOWED_TAGS: ALLOWED_TAGS,
            ALLOWED_ATTR: [...ALLOWED_ATTRIBUTES, TARGET_ATTRIBUTE],
            ALLOW_DATA_ATTR: false,
            RETURN_DOM_FRAGMENT: true,
        });
    }
}
