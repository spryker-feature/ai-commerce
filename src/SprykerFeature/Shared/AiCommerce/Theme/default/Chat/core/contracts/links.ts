// Tool results come from an AI turn, so features trust a link only as a same-origin path or a URL from a listed origin.

const SOURCE_FILE = 'Shared/AiCommerce/Theme/default/Chat/core/contracts/links.ts';
const SAFE_PATH_PATTERN = /^\/(?![/\\])[^\s\\]*$/;
const HTTPS_PROTOCOL = 'https:';
const URL_SCHEME_SEPARATOR = '://';

export interface ChatOriginListOptions {
    readonly httpsOnly: boolean;
    readonly fix: string;
}

export const isSafePath = (path: unknown): path is string => typeof path === 'string' && SAFE_PATH_PATTERN.test(path);

const createOriginError = (
    element: HTMLElement,
    attribute: string,
    entry: string,
    reason: string,
    fix: string,
    cause?: unknown,
): Error =>
    new Error(
        `<${element.localName}> has "${entry}" in its "${attribute}" attribute, which ${reason} ` +
            `(reported by ${SOURCE_FILE}); the feature stays inactive. ${fix}`,
        { cause: cause },
    );

export const readOriginList = (element: HTMLElement, attribute: string, options: ChatOriginListOptions): string[] =>
    (element.getAttribute(attribute) ?? '')
        .split(',')
        .map((entry) => entry.trim())
        .filter((entry) => entry !== '')
        .map((entry) => {
            let origin: string;

            try {
                origin = new URL(entry.includes(URL_SCHEME_SEPARATOR) ? entry : `${HTTPS_PROTOCOL}//${entry}`).origin;
            } catch (error) {
                throw createOriginError(element, attribute, entry, 'is not a host or an origin', options.fix, error);
            }

            if (options.httpsOnly && !origin.startsWith(`${HTTPS_PROTOCOL}//`)) {
                throw createOriginError(element, attribute, entry, 'is not an https origin', options.fix);
            }

            return origin;
        });
