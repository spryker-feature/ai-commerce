/* eslint-disable max-lines -- one form's description, its secret filter and the composer control share the attached-form state. */
import type { ChatFeature, ChatFeatureContext } from '@shared/ai-commerce/Chat/core/contracts/context';
import { CHAT_TURN_STATE } from '@shared/ai-commerce/Chat/core/contracts/context';
import { announceFeature } from '@shared/ai-commerce/Chat/core/contracts/access';
import { CORE_ZONE } from '@shared/ai-commerce/Chat/core/contracts/blocks';

const SOURCE_FILE = 'Zed/AiCommerce/assets/Zed/chat/features/form-context/form-context.ts';
const EXCLUDED_FORM_NAMES_ATTRIBUTE = 'excluded-form-names';
const LABEL_ATTRIBUTE = 'data-label';
const ATTACH_LABEL_ATTRIBUTE = 'data-attach-label';
const NAME_PLACEHOLDER = '%name%';
const FORM_CONTEXT_FIELD = 'formContext';
const EXCLUDED_INPUT_TYPES = new Set(['hidden', 'password', 'submit', 'reset', 'button', 'image', 'file']);
// A field whose name looks secret is never described, whatever its type; the same list as form-fill.ts.
const DENIED_NAME_PARTS = ['password', 'token', 'secret', 'api_key', 'apikey', 'csrf'];
const FORM_NAME_SEPARATOR = /[\s_\-[\]]+/;
const REQUIRED_MARKER = /\s*\*\s*$/;

const CONTROL_BLOCK = {
    name: 'formContext',
    references: ['attach', 'attachLabel', 'chip', 'chipLabel', 'remove'],
} as const;

type ControlReference = (typeof CONTROL_BLOCK.references)[number];
type LabelAttribute = typeof LABEL_ATTRIBUTE | typeof ATTACH_LABEL_ATTRIBUTE;

interface FormFieldOption {
    readonly value: string;
    readonly label: string;
}

interface FormFieldDescription {
    readonly name: string;
    readonly label: string | null;
    readonly type: string;
    readonly value: string | boolean | null;
    readonly required: boolean;
    readonly placeholder?: string | null;
    readonly options?: readonly FormFieldOption[];
}

interface FormDescription {
    readonly name: string;
    readonly fields: readonly FormFieldDescription[];
}

type FormField = HTMLInputElement | HTMLSelectElement | HTMLTextAreaElement;

const isDeniedName = (name: string): boolean => {
    const lowerCaseName = name.toLowerCase();

    return DENIED_NAME_PARTS.some((part) => lowerCaseName.includes(part));
};

const isFormField = (element: Element): element is FormField =>
    element instanceof HTMLInputElement ||
    element instanceof HTMLSelectElement ||
    element instanceof HTMLTextAreaElement;

// The attribute, not `form.name`: a field named "name" shadows that property on the form element.
const readFormName = (form: HTMLFormElement): string => form.getAttribute('name') ?? '';

const buildLabelMap = (form: HTMLFormElement): Map<Element, string> => {
    const labels = new Map<Element, string>();

    for (const label of Array.from(form.querySelectorAll('label[for]'))) {
        const target = form.querySelector(`#${CSS.escape(label.getAttribute('for') ?? '')}`);

        if (target !== null) {
            labels.set(target, (label.textContent ?? '').trim().replace(REQUIRED_MARKER, '').trim());
        }
    }

    return labels;
};

const describeField = (field: FormField, labels: Map<Element, string>): FormFieldDescription => {
    const label = labels.get(field) || field.getAttribute('aria-label') || field.getAttribute('placeholder') || null;

    if (field instanceof HTMLSelectElement) {
        return {
            name: field.name,
            label: label,
            type: 'select',
            value: field.value || null,
            options: Array.from(field.options)
                .filter((option) => option.value !== '')
                .map((option) => ({ value: option.value, label: option.text.trim() })),
            required: field.required,
        };
    }

    if (field instanceof HTMLTextAreaElement) {
        return {
            name: field.name,
            label: label,
            type: 'textarea',
            value: field.value || null,
            required: field.required,
        };
    }

    if (field.type === 'checkbox' || field.type === 'radio') {
        return { name: field.name, label: label, type: field.type, value: field.checked, required: field.required };
    }

    return {
        name: field.name,
        label: label,
        type: field.type || 'text',
        placeholder: field.placeholder || null,
        value: field.value || null,
        required: field.required,
    };
};

const describeFields = (form: HTMLFormElement): FormFieldDescription[] => {
    const labels = buildLabelMap(form);
    const seenNames = new Set<string>();
    const fields: FormFieldDescription[] = [];

    for (const element of Array.from(form.elements)) {
        if (!isFormField(element) || element.name === '' || seenNames.has(element.name)) {
            continue;
        }

        if (
            (element instanceof HTMLInputElement && EXCLUDED_INPUT_TYPES.has(element.type)) ||
            isDeniedName(element.name)
        ) {
            continue;
        }

        seenNames.add(element.name);
        fields.push(describeField(element, labels));
    }

    return fields;
};

const parseJson = (value: string): unknown => {
    try {
        return JSON.parse(value);
    } catch {
        return undefined;
    }
};

const withoutValues = (fields: readonly FormFieldDescription[]): FormFieldDescription[] =>
    fields.map((field) => ({ ...field, value: null }));

export class ChatFormContext extends HTMLElement implements ChatFeature {
    private context: ChatFeatureContext | null = null;
    private control: HTMLElement | null = null;
    private references: Readonly<Record<ControlReference, HTMLElement>> | null = null;
    private labels: Readonly<Record<LabelAttribute, string>> | null = null;
    private excludedFormNames: readonly string[] | null = [];
    private formName = '';
    private isAttached = false;

    connectedCallback(): void {
        announceFeature(this);
    }

    initialize(context: ChatFeatureContext): void {
        const control = context.blocks.require(CONTROL_BLOCK).create();

        this.context = context;
        this.control = control.root;
        this.references = control.references;
        this.labels = this.readLabels(control.root);
        this.excludedFormNames = this.readExcludedFormNames();
        this.formName = '';
        this.isAttached = false;

        control.references.attach.addEventListener('click', () => {
            this.setAttached(true);
            control.references.remove.focus();
        });
        control.references.remove.addEventListener('click', () => {
            this.setAttached(false);
            control.references.attach.focus();
        });
        context.conversation.contribute(() => this.contribute());
        context.events.on('panel:toggle', (toggle) => {
            if (toggle.isOpen) {
                this.refresh();
            }
        });
        context.events.on('conversation:reset', () => this.setAttached(false));
        context.events.on('turn:state', (change) => {
            if (change.state === CHAT_TURN_STATE.sending) {
                this.setAttached(false);
            }
        });
        this.refresh();
    }

    dispose(): void {
        this.context = null;
        this.control = null;
        this.references = null;
        this.labels = null;
        this.formName = '';
        this.isAttached = false;
    }

    private setAttached(isAttached: boolean): void {
        this.isAttached = isAttached && this.formName !== '';
        this.refresh();
    }

    private refresh(): void {
        const context = this.context;
        const control = this.control;
        const references = this.references;

        if (context === null || control === null || references === null) {
            return;
        }

        const form = this.findBestForm();

        if (form === null) {
            this.formName = '';
            this.isAttached = false;
            control.remove();

            return;
        }

        if (readFormName(form) !== this.formName) {
            this.formName = readFormName(form);
            this.isAttached = false;
        }

        const label = this.fillName(LABEL_ATTRIBUTE);

        references.attachLabel.textContent = label;
        references.chipLabel.textContent = label;
        references.attach.setAttribute('aria-label', this.fillName(ATTACH_LABEL_ATTRIBUTE));
        references.attach.title = this.fillName(ATTACH_LABEL_ATTRIBUTE);
        references.attach.hidden = this.isAttached;
        references.chip.hidden = !this.isAttached;

        if (!control.isConnected) {
            context.zones.insert(CORE_ZONE.composerStart, control, 'start');
        }
    }

    private contribute(): Readonly<Record<string, unknown>> {
        if (!this.isAttached) {
            return {};
        }

        const form = this.findForm(this.formName);

        if (form === null) {
            reportError(
                new Error(
                    `<chat-form-context> had the form "${this.formName}" attached, but it is no longer on the page ` +
                        `${window.location.pathname} when the message is sent (reported by ${SOURCE_FILE}); the ` +
                        `prompt goes without the form context. A page script replaced or removed the form; attach ` +
                        `the form again once it is back.`,
                ),
            );

            return {};
        }

        return { [FORM_CONTEXT_FIELD]: this.describeForm(form) };
    }

    private describeForm(form: HTMLFormElement): FormDescription {
        const name = readFormName(form);
        const fields = describeFields(form);

        return { name: name, fields: this.isExcluded(name) ? withoutValues(fields) : fields };
    }

    private findBestForm(): HTMLFormElement | null {
        let bestForm: HTMLFormElement | null = null;
        let bestFieldCount = 0;

        for (const form of this.pageForms()) {
            const fieldCount = readFormName(form) === '' ? 0 : describeFields(form).length;

            if (fieldCount > bestFieldCount) {
                bestForm = form;
                bestFieldCount = fieldCount;
            }
        }

        return bestForm;
    }

    private findForm(name: string): HTMLFormElement | null {
        return this.pageForms().find((form) => readFormName(form) === name) ?? null;
    }

    private pageForms(): HTMLFormElement[] {
        const chat = this.context?.chat ?? null;

        return Array.from(document.forms).filter((form) => chat === null || !chat.contains(form));
    }

    private isExcluded(formName: string): boolean {
        if (this.excludedFormNames === null) {
            return true;
        }

        const normalizedName = formName.toLowerCase();
        const nameParts = normalizedName.split(FORM_NAME_SEPARATOR).filter((part) => part !== '');

        return this.excludedFormNames.some((excludedName) => {
            const normalizedExcludedName = excludedName.toLowerCase();

            return normalizedName === normalizedExcludedName || nameParts.includes(normalizedExcludedName);
        });
    }

    private fillName(attribute: LabelAttribute): string {
        return (this.labels?.[attribute] ?? '').replace(NAME_PLACEHOLDER, () => this.formName);
    }

    private readLabels(root: HTMLElement): Readonly<Record<LabelAttribute, string>> {
        const labels = {
            [LABEL_ATTRIBUTE]: root.getAttribute(LABEL_ATTRIBUTE) ?? '',
            [ATTACH_LABEL_ATTRIBUTE]: root.getAttribute(ATTACH_LABEL_ATTRIBUTE) ?? '',
        };

        for (const [attribute, text] of Object.entries(labels)) {
            if (text.trim() === '') {
                throw new Error(
                    `Chat block "${CONTROL_BLOCK.name}" required by <chat-form-context> has no "${attribute}" text ` +
                        `on its root element (reported by ${SOURCE_FILE}), so the form could not be named. Keep the ` +
                        `${attribute} attribute with the translated text and ${NAME_PLACEHOLDER} in the twig block ` +
                        `"${CONTROL_BLOCK.name}".`,
                );
            }
        }

        return labels;
    }

    // Absent means nothing is excluded; empty or unparsable (a twig encoding failure) fails closed: no value is sent.
    private readExcludedFormNames(): readonly string[] | null {
        const value = this.getAttribute(EXCLUDED_FORM_NAMES_ATTRIBUTE);

        if (value === null) {
            return [];
        }

        const parsed = value.trim() === '' ? undefined : parseJson(value);

        if (Array.isArray(parsed) && parsed.every((name) => typeof name === 'string')) {
            return parsed;
        }

        reportError(
            new Error(
                `<chat-form-context> has an "${EXCLUDED_FORM_NAMES_ATTRIBUTE}" attribute that is empty or not a JSON ` +
                    `list of form names (reported by ${SOURCE_FILE}); every form is described without its values ` +
                    `until it is fixed. Pass formContext.excludedFormNames as a list in the host's include of form-context.twig.`,
            ),
        );

        return null;
    }
}
