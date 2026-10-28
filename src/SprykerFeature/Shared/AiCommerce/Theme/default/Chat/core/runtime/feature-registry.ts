import { CHAT_EVENT, CHAT_TAG_NAME, FEATURE_TAG_PREFIX } from '../contracts/access';
import type { ChatFeature, ChatFeatureDirectory } from '../contracts/context';

const SOURCE_FILE = 'Shared/AiCommerce/Theme/default/Chat/core/runtime/feature-registry.ts';
const FEATURE_DEFINITION_TIMEOUT_MILLISECONDS = 5000;

export interface ChatFeatureRegistryHooks {
    readonly chat: HTMLElement;
    initializeFeature(feature: ChatFeature): boolean;
    disposeFeature(feature: ChatFeature): void;
}

export interface ChatFeatureRegistry {
    readonly directory: ChatFeatureDirectory;
    registeredFeatures(): readonly ChatFeature[];
    connect(): void;
    ignoreSubtree(element: Element): void;
    settle(): Promise<void>;
    initializeFeatures(): void;
    takeBrokenRequirements(): readonly Error[];
    disconnect(): void;
}

const isChatFeature = (element: Element): element is ChatFeature =>
    element instanceof HTMLElement &&
    typeof (element as Partial<ChatFeature>).initialize === 'function' &&
    typeof (element as Partial<ChatFeature>).dispose === 'function';

const describeTags = (tagNames: readonly string[]): string => tagNames.map((tagName) => `<${tagName}>`).join(', ');

const createFeatureDefinitionTimeoutError = (tagName: string): Error =>
    new Error(
        `Chat feature <${tagName}> is rendered inside <${CHAT_TAG_NAME}> but no bundle has defined it after ` +
            `${FEATURE_DEFINITION_TIMEOUT_MILLISECONDS} milliseconds (reported by ${SOURCE_FILE}; the file to check ` +
            `is the host twig that renders <${tagName}>). Import the module that calls ` +
            `customElements.define('${tagName}') in the host entry (Yves organism index.ts or the Back Office ` +
            `*.entry.js), or remove the tag from the twig. The chat starts without it and initializes the feature ` +
            `as soon as it is defined.`,
    );

export const createFeatureRegistry = (hooks: ChatFeatureRegistryHooks): ChatFeatureRegistry => {
    const { chat } = hooks;
    const pending = new Map<Element, Promise<void>>();
    const registered = new Set<ChatFeature>();
    const initialized = new Set<ChatFeature>();
    const failed = new Set<ChatFeature>();
    const reportedAsBlocked = new Set<ChatFeature>();
    const brokenRequirements: Error[] = [];
    let isListening = false;
    let ignoredSubtree: Element | null = null;
    const definitionTimers = new Set<ReturnType<typeof setTimeout>>();

    const waitForFeatureDefinition = async (tagName: string): Promise<void> => {
        const definition = customElements.whenDefined(tagName).then(() => 'defined' as const);
        let timeoutHandle: ReturnType<typeof setTimeout> | undefined;

        const timeout = new Promise<'timed-out'>((resolve) => {
            timeoutHandle = setTimeout(() => resolve('timed-out'), FEATURE_DEFINITION_TIMEOUT_MILLISECONDS);
            definitionTimers.add(timeoutHandle);
        });

        try {
            const outcome = await Promise.race([definition, timeout]);

            if (outcome === 'timed-out') {
                reportError(createFeatureDefinitionTimeoutError(tagName));
                await definition;
            }
        } finally {
            if (timeoutHandle !== undefined) {
                clearTimeout(timeoutHandle);
                definitionTimers.delete(timeoutHandle);
            }
        }
    };
    let isReady = false;

    const isOwnFeatureElement = (element: Element): boolean =>
        element.localName.startsWith(FEATURE_TAG_PREFIX) &&
        element.isConnected &&
        element.closest(CHAT_TAG_NAME) === chat;

    const requirementsOf = (feature: ChatFeature): readonly string[] =>
        Array.isArray(feature.requires) ? feature.requires : [];

    const hasInitializedTag = (tagName: string): boolean =>
        [...initialized].some((feature) => feature.localName === tagName);

    const hasRegisteredTag = (tagName: string): boolean =>
        [...registered].some((feature) => feature.localName === tagName);

    const findCycle = (start: ChatFeature): string[] | null => {
        const visit = (tagName: string, path: readonly string[]): string[] | null => {
            if (path.includes(tagName)) {
                return tagName === start.localName ? [...path, tagName] : null;
            }

            const candidates = [...registered].filter(
                (feature) => feature.localName === tagName && !initialized.has(feature),
            );

            for (const candidate of candidates) {
                for (const requirement of requirementsOf(candidate)) {
                    const cycle = visit(requirement, [...path, tagName]);

                    if (cycle !== null) {
                        return cycle;
                    }
                }
            }

            return null;
        };

        return visit(start.localName, []);
    };

    // Bundle order decides when a tag gets defined; walking the DOM order keeps the initialization order stable.
    const inDocumentOrder = (features: Iterable<ChatFeature>): ChatFeature[] =>
        [...features].sort((first, second) =>
            first.compareDocumentPosition(second) & Node.DOCUMENT_POSITION_FOLLOWING ? -1 : 1,
        );

    const reportBlockedFeature = (feature: ChatFeature): void => {
        const tagName = feature.localName;
        const missing = requirementsOf(feature).filter((requirement) => !hasRegisteredTag(requirement));

        reportedAsBlocked.add(feature);

        if (missing.length > 0) {
            reportError(
                new Error(
                    `Chat feature <${tagName}> requires ${describeTags(missing)}, which is not rendered or not ` +
                        `defined inside <${CHAT_TAG_NAME}> (reported by ${SOURCE_FILE}). Render the missing tag in ` +
                        `the chat twig (the hostFeatures block or the default features) and import its bundle; ` +
                        `<${tagName}> is initialized as soon as its requirements are.`,
                ),
            );

            return;
        }

        const cycle = findCycle(feature);

        if (cycle !== null) {
            for (const member of registered) {
                if (cycle.includes(member.localName) && !initialized.has(member)) {
                    reportedAsBlocked.add(member);
                }
            }

            reportError(
                new Error(
                    `Chat features require each other in a cycle: ${cycle.map((name) => `<${name}>`).join(' -> ')} ` +
                        `(reported by ${SOURCE_FILE}). None of them is initialized; remove one requirement from the ` +
                        `"requires" list of these features.`,
                ),
            );

            return;
        }

        const blockers = requirementsOf(feature).filter((requirement) => !hasInitializedTag(requirement));

        reportError(
            new Error(
                `Chat feature <${tagName}> is not initialized because ${describeTags(blockers)} failed to ` +
                    `initialize (reported by ${SOURCE_FILE}). Fix the error reported for ${describeTags(blockers)} ` +
                    `first.`,
            ),
        );
    };

    const reportBlockedFeatures = (): void => {
        for (const feature of inDocumentOrder(registered)) {
            if (!initialized.has(feature) && !failed.has(feature) && !reportedAsBlocked.has(feature)) {
                reportBlockedFeature(feature);
            }
        }
    };

    const initializeSatisfiedFeatures = (): void => {
        let hasProgress = true;

        while (hasProgress) {
            hasProgress = false;

            for (const feature of inDocumentOrder(registered)) {
                if (
                    initialized.has(feature) ||
                    failed.has(feature) ||
                    !requirementsOf(feature).every(hasInitializedTag)
                ) {
                    continue;
                }

                hasProgress = true;
                reportedAsBlocked.delete(feature);

                if (hooks.initializeFeature(feature)) {
                    initialized.add(feature);
                } else {
                    failed.add(feature);
                }
            }
        }

        reportBlockedFeatures();
    };

    const accept = (element: Element): void => {
        if (!isListening || !isOwnFeatureElement(element)) {
            return;
        }

        if (!isChatFeature(element)) {
            reportError(
                new Error(
                    `Chat feature <${element.localName}> is defined but does not implement initialize(context) and ` +
                        `dispose() (reported by ${SOURCE_FILE}). Extend HTMLElement and implement both methods as ` +
                        `declared by ChatFeature in Chat/core/contracts/context.ts, or rename the tag so it does not start ` +
                        `with "${FEATURE_TAG_PREFIX}".`,
                ),
            );

            return;
        }

        registered.add(element);

        if (isReady) {
            initializeSatisfiedFeatures();
        }
    };

    const register = (element: Element): void => {
        if (
            !isListening ||
            !isOwnFeatureElement(element) ||
            pending.has(element) ||
            (isChatFeature(element) && registered.has(element))
        ) {
            return;
        }

        const registration = waitForFeatureDefinition(element.localName)
            .then(
                () => accept(element),
                (error: unknown) => reportError(error),
            )
            .finally(() => pending.delete(element));

        pending.set(element, registration);
    };

    const recordBrokenRequirements = (removed: ChatFeature): void => {
        if (hasInitializedTag(removed.localName)) {
            return;
        }

        for (const dependent of initialized) {
            if (requirementsOf(dependent).includes(removed.localName)) {
                brokenRequirements.push(
                    new Error(
                        `Chat feature <${dependent.localName}> requires <${removed.localName}>, which was removed ` +
                            `from <${CHAT_TAG_NAME}> after start-up (reported by ${SOURCE_FILE}). ` +
                            `<${dependent.localName}> keeps running without it; insert <${removed.localName}> again ` +
                            `or remove <${dependent.localName}> as well.`,
                    ),
                );
            }
        }
    };

    const pruneDetachedFeatures = (): void => {
        for (const feature of [...registered]) {
            if (isOwnFeatureElement(feature)) {
                continue;
            }

            registered.delete(feature);
            failed.delete(feature);
            reportedAsBlocked.delete(feature);

            if (initialized.delete(feature)) {
                hooks.disposeFeature(feature);
                recordBrokenRequirements(feature);
            }
        }
    };

    const registerTree = (root: Element): void => {
        register(root);

        for (const element of Array.from(root.querySelectorAll('*'))) {
            register(element);
        }
    };

    const isIgnored = (record: MutationRecord): boolean =>
        ignoredSubtree !== null && ignoredSubtree.contains(record.target);

    const observer = new MutationObserver((records: MutationRecord[]) => {
        const relevantRecords = records.filter((record) => !isIgnored(record));

        if (relevantRecords.length === 0) {
            return;
        }

        for (const record of relevantRecords) {
            for (const node of Array.from(record.addedNodes)) {
                if (node instanceof Element) {
                    registerTree(node);
                }
            }
        }

        pruneDetachedFeatures();
    });

    const handleFeatureConnected = (event: Event): void => {
        const detail: unknown = event instanceof CustomEvent ? event.detail : null;

        if (!(detail instanceof Element)) {
            return;
        }

        // A nested chat must not be claimed by an outer one.
        event.stopPropagation();
        register(detail);
    };

    return {
        directory: Object.freeze({
            list: (): readonly ChatFeature[] => Object.freeze([...initialized]),
            find: (tagName: string): ChatFeature | null =>
                [...initialized].find((feature) => feature.localName === tagName) ?? null,
        }),
        registeredFeatures: (): readonly ChatFeature[] => Object.freeze(inDocumentOrder(registered)),
        ignoreSubtree: (element: Element): void => {
            ignoredSubtree = element;
        },
        connect: (): void => {
            isListening = true;
            chat.addEventListener(CHAT_EVENT.featureConnected, handleFeatureConnected);
            observer.observe(chat, { childList: true, subtree: true });

            for (const element of Array.from(chat.children)) {
                registerTree(element);
            }
        },
        settle: async (): Promise<void> => {
            let timeoutHandle: ReturnType<typeof setTimeout> | undefined;
            const timeout = new Promise<void>((resolve) => {
                timeoutHandle = setTimeout(resolve, FEATURE_DEFINITION_TIMEOUT_MILLISECONDS);
            });

            try {
                await Promise.race([Promise.all([...pending.values()]), timeout]);
            } finally {
                clearTimeout(timeoutHandle);
            }
        },
        initializeFeatures: (): void => {
            isReady = true;
            initializeSatisfiedFeatures();
        },
        takeBrokenRequirements: (): readonly Error[] => brokenRequirements.splice(0),
        disconnect: (): void => {
            isListening = false;
            isReady = false;
            observer.disconnect();

            for (const timer of definitionTimers) {
                clearTimeout(timer);
            }

            definitionTimers.clear();
            ignoredSubtree = null;
            chat.removeEventListener(CHAT_EVENT.featureConnected, handleFeatureConnected);

            for (const feature of [...initialized].reverse()) {
                hooks.disposeFeature(feature);
            }

            initialized.clear();
            registered.clear();
            failed.clear();
            reportedAsBlocked.clear();
            pending.clear();
            brokenRequirements.length = 0;
        },
    };
};
