import { isSafePath } from '@shared/ai-commerce/Chat/core/contracts/links';

const HTTPS_PROTOCOL = 'https:';
const SOURCES_KEY = 'sources';
const MAX_DOCUMENT_NAME_LENGTH = 160;

export interface KnowledgeSource {
    readonly documentName: string;
    readonly url: string;
    readonly isUrlRejected: boolean;
}

export type KnowledgeSourceReading = KnowledgeSource[] | string | null;

const isRecord = (value: unknown): value is Record<string, unknown> =>
    typeof value === 'object' && value !== null && !Array.isArray(value);

const readString = (value: unknown): string => (typeof value === 'string' ? value.trim() : '');

const resolveLinkUrl = (url: string, linkOrigins: readonly string[]): string => {
    if (isSafePath(url)) {
        return url;
    }

    let parsed: URL;

    try {
        parsed = new URL(url);
    } catch {
        return '';
    }

    if (parsed.username !== '' || parsed.password !== '') {
        return '';
    }

    const isSameOrigin = parsed.origin === window.location.origin;
    const isAllowedOrigin = parsed.protocol === HTTPS_PROTOCOL && linkOrigins.includes(parsed.origin);

    return isSameOrigin || isAllowedOrigin ? parsed.href : '';
};

const parseSource = (candidate: unknown, linkOrigins: readonly string[]): KnowledgeSource | null => {
    if (!isRecord(candidate)) {
        return null;
    }

    const documentName = readString(candidate.documentName).slice(0, MAX_DOCUMENT_NAME_LENGTH);

    if (documentName === '') {
        return null;
    }

    const sourceUrl = readString(candidate.url);
    const url = resolveLinkUrl(sourceUrl, linkOrigins);

    return {
        documentName: documentName,
        url: url,
        isUrlRejected: sourceUrl !== '' && url === '',
    };
};

export const readKnowledgeSources = (result: string, linkOrigins: readonly string[]): KnowledgeSourceReading => {
    let decoded: unknown;

    try {
        decoded = JSON.parse(result);
    } catch {
        return 'it is not JSON';
    }

    if (!isRecord(decoded)) {
        return 'it is not a JSON object';
    }

    if (!(SOURCES_KEY in decoded)) {
        return null;
    }

    const candidates = decoded[SOURCES_KEY];

    if (!Array.isArray(candidates)) {
        return `its "${SOURCES_KEY}" value is not a list`;
    }

    if (candidates.length === 0) {
        return null;
    }

    const sources = candidates
        .map((candidate) => parseSource(candidate, linkOrigins))
        .filter((source): source is KnowledgeSource => source !== null);

    if (sources.length === 0) {
        return `none of its ${candidates.length} sources has a document name`;
    }

    return sources;
};
