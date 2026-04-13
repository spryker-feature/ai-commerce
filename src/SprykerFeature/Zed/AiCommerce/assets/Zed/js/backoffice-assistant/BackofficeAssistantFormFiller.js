export class BackofficeAssistantFormFiller {
    static #DENIED_TYPES = new Set(['hidden', 'password', 'file']);
    static #DENIED_NAME_PATTERNS = ['_token', 'csrf'];

    /**
     * Fills form fields on the current page using the provided field name → value mapping.
     *
     * @param {Object} fields - An object mapping field names to their desired values.
     */
    fill(fields) {
        for (const [name, value] of Object.entries(fields)) {
            const elements = document.querySelectorAll(
                `form:not(.js-backoffice-assistant__panel form) [name="${CSS.escape(name)}"]`,
            );

            for (const element of elements) {
                if (this.#isDenied(element, name)) {
                    continue;
                }

                this.#fillElement(element, value);
            }
        }
    }

    #isDenied(element, name) {
        if (BackofficeAssistantFormFiller.#DENIED_TYPES.has(element.type)) {
            return true;
        }

        const lowerName = name.toLowerCase();

        for (const pattern of BackofficeAssistantFormFiller.#DENIED_NAME_PATTERNS) {
            if (lowerName.includes(pattern)) {
                return true;
            }
        }

        return false;
    }

    #fillElement(element, value) {
        if (element.tagName === 'SELECT') {
            this.#fillSelect(element, value);

            return;
        }

        if (element.type === 'checkbox' || element.type === 'radio') {
            this.#fillCheckableInput(element, value);

            return;
        }

        element.value = String(value ?? '');
        element.dispatchEvent(new Event('input', { bubbles: true }));
        element.dispatchEvent(new Event('change', { bubbles: true }));
    }

    #fillSelect(select, value) {
        const stringValue = String(value ?? '');

        for (const option of select.options) {
            if (option.value === stringValue) {
                select.value = stringValue;
                select.dispatchEvent(new Event('change', { bubbles: true }));

                return;
            }
        }

        // Try matching by label text when value does not match directly
        for (const option of select.options) {
            if (option.text.trim().toLowerCase() === stringValue.toLowerCase()) {
                select.value = option.value;
                select.dispatchEvent(new Event('change', { bubbles: true }));

                return;
            }
        }
    }

    #fillCheckableInput(element, value) {
        const shouldCheck = value === true || value === 'true' || value === '1' || value === 1;
        element.checked = shouldCheck;
        element.dispatchEvent(new Event('change', { bubbles: true }));
    }
}
