import { AiProductManagement } from './ai-product-management';

export class AiCategorySuggestion extends AiProductManagement {
    dataFieldNames = ['description', 'name'];
    _triggerSelector = '.js-ai-category-trigger';
    suggestionData = {};

    preparePayload(trigger) {
        const fieldSelector = trigger.getAttribute('data-product-info-field') ?? '';
        const dataFields = Array.from(document.querySelectorAll(fieldSelector));

        this.suggestionData = {};

        for (const { name, value } of dataFields) {
            const matchedName = this.dataFieldNames.find((part) => name.includes(`[${part}]`));

            if (!matchedName || !value) {
                continue;
            }

            if (!(`product_${matchedName}` in this.suggestionData)) {
                this.suggestionData[`product_${matchedName}`] = value;
            }

            if (Object.keys(this.suggestionData).length === this.dataFieldNames.length) {
                return;
            }
        }
    }

    async processAiAction() {
        if (Object.keys(this.suggestionData).length !== this.dataFieldNames.length) {
            this._modal?.classList.add(this._states.empty);
            this._modal?.classList.remove(this._states.loading);

            return;
        }

        const select = this._modal?.querySelector('.js-ai-category-select');
        select?.replaceChildren();

        try {
            const response = await fetch(this._url, {
                method: 'POST',
                body: new URLSearchParams(this.suggestionData),
            });

            const responseData = await response.json();

            if (!response.ok) {
                this.onError(responseData.errors[0].message);

                return;
            }

            const { categories } = responseData;
            const fragment = document.createDocumentFragment();

            for (const [text, id] of Object.entries(categories)) {
                fragment.append(new Option(text, id, true, true));
            }

            select?.append(fragment);
        } catch (error) {
            console.error(error);
        } finally {
            this._modal?.classList.remove(this._states.loading);
            select?.dispatchEvent(new Event('change'));
        }
    }

    onApply() {
        const previewSelect = this._modal?.querySelector('.js-ai-category-select');
        if (!previewSelect || !this._fieldElement) {
            return;
        }

        const selectedValues = Array.from(previewSelect.selectedOptions).map((option) => option.value);
        const targetSelect = this._fieldElement;

        for (const option of Array.from(targetSelect.options)) {
            option.selected = selectedValues.includes(option.value);
        }

        targetSelect.dispatchEvent(new Event('change'));
    }
}
