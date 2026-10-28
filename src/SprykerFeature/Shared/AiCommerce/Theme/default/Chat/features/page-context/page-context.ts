import type { ChatFeature, ChatFeatureContext } from '@shared/ai-commerce/Chat/core/contracts/context';
import { CHAT_TURN_STATE } from '@shared/ai-commerce/Chat/core/contracts/context';
import type { ChatBlockInstance } from '@shared/ai-commerce/Chat/core/contracts/blocks';
import { CORE_ZONE } from '@shared/ai-commerce/Chat/core/contracts/blocks';
import { announceFeature } from '@shared/ai-commerce/Chat/core/contracts/access';
import { describeFailure } from '@shared/ai-commerce/Chat/core/contracts/failure';
import type { ChatSuggestedPrompts } from '@shared/ai-commerce/Chat/features/suggested-prompts/suggested-prompts';

export const PAGE_CONTEXT_TAG_NAME = 'chat-page-context';

const SOURCE_FILE = 'Shared/AiCommerce/Theme/default/Chat/features/page-context/page-context.ts';
// Written out because a feature never imports another feature at runtime.
const SUGGESTED_PROMPTS_TAG_NAME = 'chat-suggested-prompts';
const RULES_ATTRIBUTE = 'rules';
const OPT_IN_ATTRIBUTE = 'opt-in';
const SINGLE_TURN_ATTRIBUTE = 'single-turn';
const ATTACHED_ATTRIBUTE = 'data-attached';
const ATTACH_REFERENCE = 'attach';
const PAYLOAD_FIELD = 'pageContext';
const PAGE_TYPE_FIELD = 'pageType';
const MAX_VALUE_LENGTH = 120;
const MAX_LIST_LENGTH = 10;
const LIST_NAME_SUFFIX = /\[\]$/;

const CHIP_BLOCK = { name: 'pageContextChip', references: ['label', 'remove'] } as const;

const FIELD_READ = {
    text: 'text',
    value: 'value',
    attribute: 'attribute',
    nameValue: 'nameValue',
    queryParameter: 'queryParameter',
    location: 'location',
    fixed: 'fixed',
} as const;

type FieldRead = (typeof FIELD_READ)[keyof typeof FIELD_READ];
type FieldValue = string | readonly string[];

interface PageContextField {
    readonly read: FieldRead;
    readonly selector: string;
    readonly attribute: string;
    readonly parameter: string;
    readonly value: string;
    readonly isRequired: boolean;
    readonly isList: boolean;
}

interface PageContextRule {
    readonly pageType: string;
    readonly label: string;
    readonly when: string;
    readonly unless: string;
    readonly pathPrefix: string;
    readonly minimumPathSegments: number;
    readonly scope: string;
    readonly fields: ReadonlyMap<string, PageContextField>;
}

interface DetectedContext {
    readonly pageType: string;
    readonly label: string;
    readonly values: Readonly<Record<string, FieldValue>>;
}

const isRecord = (value: unknown): value is Record<string, unknown> =>
    typeof value === 'object' && value !== null && !Array.isArray(value);

const readString = (record: Record<string, unknown>, key: string): string =>
    typeof record[key] === 'string' ? record[key].trim() : '';

const capValue = (value: string | null | undefined): string =>
    typeof value === 'string' ? value.trim().slice(0, MAX_VALUE_LENGTH) : '';

const describeRule = (index: number): string => `rule ${index + 1}`;

type FieldSetting = 'selector' | 'attribute' | 'parameter';

const REQUIRED_SETTINGS: Readonly<Record<FieldRead, readonly FieldSetting[]>> = {
    [FIELD_READ.text]: ['selector'],
    [FIELD_READ.value]: ['selector'],
    [FIELD_READ.attribute]: ['selector', 'attribute'],
    [FIELD_READ.nameValue]: ['selector'],
    [FIELD_READ.queryParameter]: ['parameter'],
    [FIELD_READ.location]: [],
    [FIELD_READ.fixed]: [],
};

const parseField = (candidate: unknown, name: string, ruleIndex: number, problems: string[]): PageContextField => {
    const record = isRecord(candidate) ? candidate : {};
    const read = readString(record, 'read');
    const field = {
        read: read as FieldRead,
        selector: readString(record, 'selector'),
        attribute: readString(record, 'attribute'),
        parameter: readString(record, 'parameter'),
        value: readString(record, 'value'),
        isRequired: record.required === true,
        isList: record.list === true,
    };
    const describeField = `${describeRule(ruleIndex)}, field "${name}"`;

    if (!Object.values<string>(FIELD_READ).includes(read)) {
        problems.push(`${describeField}: read "${read}" is not one of ${Object.values(FIELD_READ).join(', ')}`);

        return field;
    }

    for (const setting of REQUIRED_SETTINGS[field.read]) {
        if (field[setting] === '') {
            problems.push(`${describeField}: read "${read}" needs a non-empty "${setting}"`);
        }
    }

    return field;
};

const parseRule = (candidate: unknown, index: number, problems: string[]): PageContextRule => {
    const record = isRecord(candidate) ? candidate : {};
    const pageType = readString(record, PAGE_TYPE_FIELD);
    const fields = isRecord(record.fields) ? record.fields : {};
    const minimumPathSegments = record.minimumPathSegments ?? 0;

    if (pageType === '') {
        problems.push(`${describeRule(index)}: "pageType" is missing`);
    }

    if (!isRecord(record.fields)) {
        problems.push(`${describeRule(index)}: "fields" is not a map of field name to read settings`);
    }

    if (typeof minimumPathSegments !== 'number' || !Number.isInteger(minimumPathSegments) || minimumPathSegments < 0) {
        problems.push(`${describeRule(index)}: "minimumPathSegments" is not a whole number of 0 or more`);
    }

    return {
        pageType: pageType,
        label: readString(record, 'label'),
        when: readString(record, 'when'),
        unless: readString(record, 'unless'),
        pathPrefix: readString(record, 'pathPrefix'),
        minimumPathSegments: typeof minimumPathSegments === 'number' ? minimumPathSegments : 0,
        scope: readString(record, 'scope'),
        fields: new Map(
            Object.entries(fields).map(([name, field]) => [name, parseField(field, name, index, problems)]),
        ),
    };
};

const hasLabel = (rule: PageContextRule, index: number): boolean => {
    if (rule.label !== '') {
        return true;
    }

    reportError(
        new Error(
            `<${PAGE_CONTEXT_TAG_NAME}> skips ${describeRule(index)} (pageType "${rule.pageType}"): its "label" is ` +
                `not a non-empty string (reported by ${SOURCE_FILE}). Give the rule the chip text in ` +
                `pageContext.rules in the host's include of page-context.twig, with %field% placeholders for its values.`,
        ),
    );

    return false;
};

const readRules = (element: HTMLElement): PageContextRule[] => {
    const value = element.getAttribute(RULES_ATTRIBUTE);
    const problems: string[] = [];
    let parsed: unknown;

    if (value === null) {
        return [];
    }

    if (value.trim() === '') {
        reportError(
            new Error(
                `<${PAGE_CONTEXT_TAG_NAME}> has an empty "${RULES_ATTRIBUTE}" attribute (reported by ${SOURCE_FILE}); ` +
                    `no page context is sent. Pass pageContext.rules as a list of rules (the feature twig ` +
                    `encodes it), or as non-empty JSON text from the host's rules block.`,
            ),
        );

        return [];
    }

    try {
        parsed = JSON.parse(value);
    } catch (error) {
        throw new Error(
            `<${PAGE_CONTEXT_TAG_NAME}> has a "${RULES_ATTRIBUTE}" attribute that is not JSON (reported by ` +
                `${SOURCE_FILE}); no page context is sent. Pass pageContext.rules as a list of rules in the ` +
                `host's include of page-context.twig; the feature twig encodes it.`,
            { cause: error },
        );
    }

    if (!Array.isArray(parsed)) {
        problems.push('the attribute is not a list of rules');
    }

    const rules = (Array.isArray(parsed) ? parsed : []).map((rule, index) => parseRule(rule, index, problems));

    if (problems.length > 0) {
        throw new Error(
            `<${PAGE_CONTEXT_TAG_NAME}> has an invalid "${RULES_ATTRIBUTE}" attribute: ${problems.join('; ')} ` +
                `(reported by ${SOURCE_FILE}); the feature stays inactive. Fix these rules in ` +
                `pageContext.rules in the host's include of page-context.twig (keys and read modes: Chat/README.md, "Page context").`,
        );
    }

    return rules.filter(hasLabel);
};

const readNameValue = (element: Element): string => {
    if (!(element instanceof HTMLInputElement || element instanceof HTMLSelectElement)) {
        return '';
    }

    const name = element.name.replace(LIST_NAME_SUFFIX, '').trim();
    const value = element.value.trim();

    return name === '' || value === '' ? '' : capValue(`${name}: ${value}`);
};

const readElement = (element: Element, field: PageContextField): string => {
    switch (field.read) {
        case FIELD_READ.text:
            return capValue(element.textContent);
        case FIELD_READ.value:
            return element instanceof HTMLInputElement || element instanceof HTMLSelectElement
                ? capValue(element.value)
                : '';
        case FIELD_READ.attribute:
            return capValue(element.getAttribute(field.attribute));
        default:
            return readNameValue(element);
    }
};

const readField = (scope: ParentNode, field: PageContextField): FieldValue => {
    switch (field.read) {
        case FIELD_READ.queryParameter:
            return capValue(new URLSearchParams(window.location.search).get(field.parameter));
        case FIELD_READ.location:
            return capValue(`${window.location.pathname}${window.location.search}`);
        case FIELD_READ.fixed:
            return capValue(field.value);
    }

    if (!field.isList) {
        const element = scope.querySelector(field.selector);

        return element === null ? '' : readElement(element, field);
    }

    const values: string[] = [];

    for (const element of Array.from(scope.querySelectorAll(field.selector))) {
        const value = readElement(element, field);

        if (value !== '' && !values.includes(value)) {
            values.push(value);
        }

        if (values.length === MAX_LIST_LENGTH) {
            break;
        }
    }

    return values;
};

const isEmpty = (value: FieldValue): boolean => (typeof value === 'string' ? value === '' : value.length === 0);

const matchesPage = (rule: PageContextRule): boolean => {
    const pathSegments = window.location.pathname.split('/').filter((segment) => segment !== '');

    return (
        (rule.when === '' || document.querySelector(rule.when) !== null) &&
        (rule.unless === '' || document.querySelector(rule.unless) === null) &&
        window.location.pathname.startsWith(rule.pathPrefix) &&
        pathSegments.length >= rule.minimumPathSegments
    );
};

const readValues = (scope: ParentNode, rule: PageContextRule): Record<string, FieldValue> | null => {
    const values: Record<string, FieldValue> = {};

    for (const [name, field] of rule.fields) {
        const value = readField(scope, field);

        if (field.isRequired && isEmpty(value)) {
            return null;
        }

        values[name] = value;
    }

    return values;
};

const fillLabel = (label: string, values: Readonly<Record<string, FieldValue>>): string =>
    label.replace(/%(\w+)%/g, (placeholder: string, name: string) => {
        const value = values[name];

        return typeof value === 'string' ? value : placeholder;
    });

const detect = (rules: readonly PageContextRule[]): DetectedContext | null => {
    for (const [index, rule] of rules.entries()) {
        try {
            if (!matchesPage(rule)) {
                continue;
            }

            const scopes: ParentNode[] =
                rule.scope === '' ? [document] : Array.from(document.querySelectorAll(rule.scope));

            for (const scope of scopes) {
                const values = readValues(scope, rule);

                if (values !== null) {
                    return { pageType: rule.pageType, label: fillLabel(rule.label, values), values: values };
                }
            }
        } catch (error) {
            reportError(
                new Error(
                    `<${PAGE_CONTEXT_TAG_NAME}> could not apply ${describeRule(index)}: ${describeFailure(error)} ` +
                        `(reported by ${SOURCE_FILE}). The rule is skipped; fix its selectors in ` +
                        `pageContext.rules in the host's include of page-context.twig.`,
                    { cause: error },
                ),
            );
        }
    }

    return null;
};

export class ChatPageContext extends HTMLElement implements ChatFeature {
    private detected: DetectedContext | null = null;
    private chip: ChatBlockInstance<'label' | 'remove'> | null = null;
    private isAttached = false;

    connectedCallback(): void {
        announceFeature(this);
    }

    initialize(context: ChatFeatureContext): void {
        const chip = context.blocks.require(CHIP_BLOCK).create();
        const attach = chip.find(ATTACH_REFERENCE);
        const detected = detect(readRules(this));

        this.detected = detected;
        this.chip = chip;
        this.isAttached = detected !== null && !this.hasAttribute(OPT_IN_ATTRIBUTE);
        chip.references.remove.addEventListener('click', () => {
            this.setAttached(false);
            this.focusAfterToggle(attach, context);
        });
        attach?.addEventListener('click', () => {
            this.setAttached(true);
            this.focusAfterToggle(chip.references.remove, context);
        });
        context.conversation.contribute(() => this.createContribution());
        context.events.on('turn:state', (change) => {
            // The payload of the turn was built before it reported `sending`, so this detaches for the next one.
            if (change.state === CHAT_TURN_STATE.sending && this.hasAttribute(SINGLE_TURN_ATTRIBUTE)) {
                this.setAttached(false);
            }
        });
        context.events.on('conversation:reset', () => {
            if (this.hasAttribute(OPT_IN_ATTRIBUTE)) {
                this.setAttached(false);
            }
        });
        context.zones.insert(CORE_ZONE.composerStart, chip.root, 'start');
        this.render();
        this.showPagePrompts(context);
    }

    dispose(): void {
        this.detected = null;
        this.chip = null;
        this.isAttached = false;
    }

    private createContribution(): Readonly<Record<string, unknown>> {
        if (this.detected === null || !this.isAttached) {
            return {};
        }

        return { [PAYLOAD_FIELD]: { [PAGE_TYPE_FIELD]: this.detected.pageType, ...this.detected.values } };
    }

    private setAttached(isAttached: boolean): void {
        if (this.detected === null) {
            return;
        }

        this.isAttached = isAttached;
        this.render();
    }

    // The pressed button is now hidden, so focus must move or it falls to <body>.
    private focusAfterToggle(counterpart: HTMLElement | null, context: ChatFeatureContext): void {
        if (counterpart !== null && !counterpart.hidden && this.chip?.root.hidden === false) {
            counterpart.focus();
        } else {
            context.composer.focus();
        }
    }

    private render(): void {
        const chip = this.chip;

        if (chip === null) {
            return;
        }

        const label = this.detected?.label ?? '';
        const attach = chip.find(ATTACH_REFERENCE);
        const isOffered = this.hasAttribute(OPT_IN_ATTRIBUTE) && attach !== null;

        chip.references.label.textContent = label;
        chip.root.toggleAttribute(ATTACHED_ATTRIBUTE, this.isAttached);
        chip.root.hidden = label === '' || (!this.isAttached && !isOffered);
        chip.references.remove.hidden = !this.isAttached;

        if (attach !== null) {
            attach.hidden = this.isAttached;
        }
    }

    private showPagePrompts(context: ChatFeatureContext): void {
        const detected = this.detected;
        const suggestedPrompts = context.features.find(SUGGESTED_PROMPTS_TAG_NAME) as ChatSuggestedPrompts | null;

        if (detected === null || suggestedPrompts === null) {
            return;
        }

        if (typeof suggestedPrompts.showGroup !== 'function') {
            reportError(
                new Error(
                    `<${PAGE_CONTEXT_TAG_NAME}> found <${SUGGESTED_PROMPTS_TAG_NAME}> without a showGroup() method ` +
                        `(reported by ${SOURCE_FILE}); the starter prompts stay on their default group instead of ` +
                        `"${detected.pageType}". Keep showGroup(group, values) in a project class that replaces ` +
                        `ChatSuggestedPrompts.`,
                ),
            );

            return;
        }

        const values: Record<string, string> = {};

        for (const [name, value] of Object.entries(detected.values)) {
            if (typeof value === 'string') {
                values[name] = value;
            }
        }

        suggestedPrompts.showGroup(detected.pageType, values);
    }
}
