export class BackofficeAssistantFormContext {
    static #EXCLUDED_TYPES = new Set(['hidden', 'password', 'submit', 'reset', 'button', 'image', 'file']);

    #containerEl;
    #suggestionTemplate;
    #chipTemplate;
    #i18n;
    #excludedFormNames;
    #capturedForm = null;
    #activeForm = null;
    #chipEl = null;
    #suggestionEl = null;

    constructor(containerEl, panelEl, i18n, excludedFormNames = []) {
        this.#containerEl = containerEl;
        this.#suggestionTemplate = panelEl.querySelector('[data-id="backoffice-assistant-form-context-suggestion"]');
        this.#chipTemplate = panelEl.querySelector('[data-id="backoffice-assistant-form-context-chip"]');
        this.#i18n = i18n;
        this.#excludedFormNames = new Set(excludedFormNames);
        this.#capturedForm = this.#capturePageForm();
    }

    showSuggestion() {
        if (!this.#capturedForm || this.#activeForm || this.#suggestionEl) {
            return;
        }

        const fragment = this.#suggestionTemplate.content.cloneNode(true);
        const suggestion = fragment.firstElementChild;

        suggestion.querySelector('.js-backoffice-assistant__form-context-suggestion-label').textContent =
            this.#getFormLabel();
        suggestion.addEventListener('click', () => this.#attachContext());

        this.#containerEl.appendChild(suggestion);
        this.#suggestionEl = suggestion;
    }

    getContextPrefix() {
        if (!this.#activeForm) {
            return '';
        }

        return `[Form: ${JSON.stringify(this.#activeForm)}]\n\n`;
    }

    hasActiveContext() {
        return this.#activeForm !== null;
    }

    clear() {
        this.#activeForm = null;

        if (this.#chipEl) {
            this.#chipEl.remove();
            this.#chipEl = null;
        }
    }

    reset() {
        this.clear();

        if (this.#suggestionEl) {
            this.#suggestionEl.remove();
            this.#suggestionEl = null;
        }

        this.#capturedForm = this.#capturePageForm();
        this.showSuggestion();
    }

    #capturePageForm() {
        const forms = document.querySelectorAll('form:not(.js-backoffice-assistant__panel form)');

        if (forms.length === 0) {
            return null;
        }

        let bestForm = null;
        let bestFields = [];

        for (const form of forms) {
            if (!form.name) {
                continue;
            }

            const fields = this.#extractFields(form);

            if (fields.length > bestFields.length) {
                bestForm = form;
                bestFields = fields;
            }
        }

        if (!bestForm || bestFields.length === 0) {
            return null;
        }

        const formName = bestForm.name;
        const isExcluded = this.#isFormExcluded(formName);

        return { name: formName, fields: isExcluded ? this.#stripValues(bestFields) : bestFields };
    }

    #isFormExcluded(formName) {
        if (!formName || this.#excludedFormNames.size === 0) {
            return false;
        }

        const normalizedName = formName.toLowerCase();
        const nameParts = normalizedName.split(/[\s_\-[\]]+/).filter(Boolean);

        for (const excluded of this.#excludedFormNames) {
            const lowerExcluded = excluded.toLowerCase();

            if (normalizedName === lowerExcluded || nameParts.includes(lowerExcluded)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param {Array} fields
     * @returns {Array}
     */
    #stripValues(fields) {
        return fields.map((field) => ({ ...field, value: null }));
    }

    #getFormLabel() {
        const formName = this.#capturedForm?.name;

        return formName ? `${this.#i18n.formContextLabel} #${formName}` : this.#i18n.formContextLabel;
    }

    #extractFields(form) {
        const labelMap = this.#buildLabelMap(form);
        const fields = [];
        const seen = new Set();

        for (const element of form.elements) {
            if (BackofficeAssistantFormContext.#EXCLUDED_TYPES.has(element.type)) {
                continue;
            }

            if (!element.name || seen.has(element.name)) {
                continue;
            }

            seen.add(element.name);

            const field = this.#describeField(element, labelMap);

            if (field) {
                fields.push(field);
            }
        }

        return fields;
    }

    #buildLabelMap(form) {
        const map = new Map();

        for (const label of form.querySelectorAll('label[for]')) {
            const target = form.querySelector(`#${CSS.escape(label.htmlFor)}`);

            if (target) {
                map.set(
                    target,
                    label.textContent
                        .trim()
                        .replace(/\s*\*\s*$/, '')
                        .trim(),
                );
            }
        }

        return map;
    }

    #describeField(element, labelMap) {
        const label =
            labelMap.get(element) || element.getAttribute('aria-label') || element.getAttribute('placeholder') || null;

        if (element.tagName === 'SELECT') {
            return this.#describeSelect(element, label);
        }

        if (element.tagName === 'TEXTAREA') {
            return {
                name: element.name,
                label,
                type: 'textarea',
                value: element.value || null,
                required: element.required,
            };
        }

        if (element.type === 'checkbox' || element.type === 'radio') {
            return {
                name: element.name,
                label,
                type: element.type,
                value: element.checked,
                required: element.required,
            };
        }

        return {
            name: element.name,
            label,
            type: element.type ?? 'text',
            placeholder: element.placeholder || null,
            value: element.value || null,
            required: element.required,
        };
    }

    #describeSelect(select, label) {
        const options = [];

        for (const option of select.options) {
            if (option.value === '') {
                continue;
            }

            options.push({ value: option.value, label: option.text.trim() });
        }

        return {
            name: select.name,
            label,
            type: 'select',
            value: select.value || null,
            options,
            required: select.required,
        };
    }

    #attachContext() {
        if (this.#suggestionEl) {
            this.#suggestionEl.remove();
            this.#suggestionEl = null;
        }

        this.#activeForm = this.#capturedForm;

        const fragment = this.#chipTemplate.content.cloneNode(true);
        const chip = fragment.firstElementChild;

        chip.querySelector('.js-backoffice-assistant__context-chip-name').textContent = this.#getFormLabel();
        chip.querySelector('.js-backoffice-assistant__context-chip-remove').addEventListener('click', () => {
            this.clear();
            this.showSuggestion();
        });

        this.#containerEl.appendChild(chip);
        this.#chipEl = chip;
    }
}
