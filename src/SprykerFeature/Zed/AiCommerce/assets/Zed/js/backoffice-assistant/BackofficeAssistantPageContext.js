export class BackofficeAssistantPageContext {
    static #PAGE_PATTERNS = [
        { pathPrefix: '/sales/detail', parameterName: 'id-sales-order', labelTemplate: 'Order #%s' },
        { pathPrefix: '/discount/index/view', parameterName: 'id-discount', labelTemplate: 'Discount #%s' },
        { pathPrefix: '/discount/index/edit', parameterName: 'id-discount', labelTemplate: 'Discount #%s' },
    ];

    #containerEl;
    #suggestionTemplate;
    #chipTemplate;
    #i18n;
    #detectedContext = null;
    #activeContext = null;
    #chipEl = null;
    #suggestionEl = null;

    constructor(containerEl, panelEl, i18n) {
        this.#containerEl = containerEl;
        this.#suggestionTemplate = panelEl.querySelector('[data-id="backoffice-assistant-page-context-suggestion"]');
        this.#chipTemplate = panelEl.querySelector('[data-id="backoffice-assistant-page-context-chip"]');
        this.#i18n = i18n;
        this.#detectedContext = this.#detectPageContext();
    }

    showSuggestion() {
        if (!this.#detectedContext || this.#activeContext || this.#suggestionEl) {
            return;
        }

        const fragment = this.#suggestionTemplate.content.cloneNode(true);
        const suggestion = fragment.firstElementChild;

        suggestion.querySelector('.backoffice-assistant__page-context-suggestion-label').textContent =
            this.#detectedContext.label;
        suggestion.addEventListener('click', () => this.#attachContext());

        this.#containerEl.appendChild(suggestion);
        this.#suggestionEl = suggestion;
    }

    getContextPrefix() {
        if (!this.#activeContext) {
            return '';
        }

        return `[Context: ${this.#activeContext.label} — ${this.#activeContext.url}]\n\n`;
    }

    hasActiveContext() {
        return this.#activeContext !== null;
    }

    clear() {
        this.#activeContext = null;

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

        this.showSuggestion();
    }

    #detectPageContext() {
        const pathname = window.location.pathname;
        const searchParams = new URLSearchParams(window.location.search);

        for (const pattern of BackofficeAssistantPageContext.#PAGE_PATTERNS) {
            if (!pathname.startsWith(pattern.pathPrefix)) {
                continue;
            }

            const parameterValue = searchParams.get(pattern.parameterName);

            if (!parameterValue) {
                continue;
            }

            return {
                label: pattern.labelTemplate.replace('%s', parameterValue),
                url: pathname + window.location.search,
            };
        }

        return null;
    }

    #attachContext() {
        if (this.#suggestionEl) {
            this.#suggestionEl.remove();
            this.#suggestionEl = null;
        }

        this.#activeContext = this.#detectedContext;

        const fragment = this.#chipTemplate.content.cloneNode(true);
        const chip = fragment.firstElementChild;

        chip.querySelector('.backoffice-assistant__context-chip-name').textContent = this.#activeContext.label;
        chip.querySelector('.backoffice-assistant__context-chip-remove').addEventListener('click', () => {
            this.clear();
            this.showSuggestion();
        });

        this.#containerEl.appendChild(chip);
        this.#chipEl = chip;
    }
}
