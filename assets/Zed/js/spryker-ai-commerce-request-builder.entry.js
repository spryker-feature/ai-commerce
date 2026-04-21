import '../scss/main.scss';

class AiRequestBuilder {
    requestMethod = 'POST';
    requestUrl = '';
    requestBody = {};
    currentTargetFieldSelector = '';
    sourceFields = [];
    response = '';
    responseField = null;
    loadingPopover = null;
    responsePopover = null;
    againBtn = null;
    applyBtn = null;
    errorBlock = null;

    constructor() {
        this.responseField = document.querySelector('#response-field');
        this.loadingPopover = document.querySelector('.loading-popover');
        this.responsePopover = document.querySelector('.response-popover');
        this.againBtn = document.querySelector('.js-ai-builder-again');
        this.applyBtn = document.querySelector('.js-ai-builder-apply');
        this.errorBlock = document.querySelector('.js-ai-product-management-modal__error-block');

        if (this.errorBlock) {
            this.errorBlock.innerHTML = '';
            this.errorBlock.style.display = 'none';
        }

        this.bindEvents();
    }

    bindEvents() {
        document.querySelectorAll('[data-ai-start]').forEach((button) => {
            button.addEventListener('click', () => {
                this.requestBody = {};
            });
        });

        document.querySelectorAll('[data-current-locale]').forEach((button) => {
            button.addEventListener('click', (event) => {
                this.requestBody['currentLocale'] = event.target.dataset['currentLocale'] ?? '';
            });
        });

        document.querySelectorAll('[data-target-popover]').forEach((button) => {
            button.addEventListener('click', (event) => {
                this.showTargetPopover(event);
            });
        });

        document.querySelectorAll('[data-request-action]').forEach((button) => {
            button.addEventListener('click', (event) => {
                const target = event.target;
                this.requestBody['action'] = target.getAttribute('data-request-action') ?? '';
                this.requestUrl = target.getAttribute('data-request-url') ?? '';
            });
        });

        document.querySelectorAll('[data-locale]').forEach((button) => {
            button.addEventListener('click', (event) => {
                this.requestBody['locale'] = event.target.getAttribute('data-locale') ?? '';
            });
        });

        document.querySelectorAll('[data-source-field]').forEach((button) => {
            button.addEventListener('click', (event) => {
                const sources = (event.target.dataset['sourceField'] ?? '').split(',').map((field) => field.trim());

                this.sourceFields = sources.map((field) => document.querySelector(field)).filter((el) => el !== null);

                this.requestBody['text'] = this.sourceFields.map((field) => field.value ?? '').join('');
            });
        });

        document.querySelectorAll('[data-target-field]').forEach((button) => {
            button.addEventListener('click', (event) => {
                this.currentTargetFieldSelector = event.target.getAttribute('data-target-field') ?? '';
            });
        });

        document.querySelectorAll('[data-request-ready]').forEach((button) => {
            button.addEventListener('click', async () => {
                await this.sendRequest();
            });
        });

        document.querySelectorAll('[data-close-popover]').forEach((button) => {
            button.addEventListener('click', () => {
                this.closePopovers();
                this.requestBody = {};
            });
        });

        this.responseField?.addEventListener('input', () => {
            this.response = this.responseField.value;
        });

        this.againBtn?.addEventListener('click', async () => {
            await this.sendRequest();
        });

        this.applyBtn?.addEventListener('click', () => {
            const targetSelector =
                this.requestBody['action'] === 'translate-to'
                    ? this.currentTargetFieldSelector.replaceAll(
                          this.requestBody['currentLocale'] ?? '',
                          this.requestBody['locale'] ?? '',
                      )
                    : this.currentTargetFieldSelector;

            const targetField = document.querySelector(targetSelector);
            if (targetField) {
                targetField.value = this.response;
            }

            this.closePopovers();
        });
    }

    async sendRequest() {
        this.closePopovers();
        this.toggleLoadingPopover(true);

        switch (this.requestBody['action']) {
            case 'translate-to':
                await this.sendTranslateAllRequest();
                break;
            default:
                await this.sendSingleRequest();
        }
    }

    async sendSingleRequest() {
        const formData = new FormData();
        formData.append('text', this.requestBody['text'] ?? '');
        formData.append('locale', this.requestBody['locale'] ?? '');

        if (this.responseField) {
            this.responseField.value = '';
        }

        const originalField = document.querySelector('#original-field');
        if (originalField) {
            originalField.value = '';
        }

        try {
            const response = await fetch(this.requestUrl, { method: this.requestMethod, body: formData });
            const data = await response.json();

            if (response.status === 400 || response.status === 422) {
                this.handleError(data);
                return;
            }

            this.handleSuccess(data);
        } catch (error) {
            console.error('Error:', error);
            this.toggleLoadingPopover(false);
        }
    }

    async sendTranslateAllRequest() {
        const targetLocales = [this.requestBody['locale'] ?? ''].filter(Boolean);

        const formData = new FormData();
        formData.append('text', this.requestBody['text'] ?? '');

        targetLocales.forEach((locale) => {
            formData.append('locales[]', locale);
        });

        try {
            const response = await fetch(this.requestUrl, { method: this.requestMethod, body: formData });
            const data = await response.json();

            if (response.status === 400 || response.status === 422) {
                this.handleError(data);
            } else {
                this.handleSingleTranslateSuccess(data);
            }
        } catch (error) {
            console.error('Error:', error);
            this.toggleLoadingPopover(false);
        }
    }

    handleSingleTranslateSuccess(data) {
        const locale = this.requestBody['locale'] ?? '';
        const translations = data['translations'] ?? {};

        this.handleSuccess({ improvedText: translations[locale] ?? '' });
    }

    handleSuccess(data) {
        this.toggleLoadingPopover(false);
        this.toggleResponsePopover(true);

        if (this.errorBlock) {
            this.errorBlock.style.display = 'none';
        }

        const originalField = document.querySelector('#original-field');
        if (originalField) {
            originalField.value = this.requestBody['text'] ?? '';
        }

        this.response = data['improvedText'] ?? '';

        if (this.responseField) {
            this.responseField.value = this.response;
        }
    }

    handleError(error) {
        const errorMessage = error['message'] ?? error['errors']?.[0]?.message ?? '';

        if (this.errorBlock) {
            this.errorBlock.textContent = errorMessage;
            this.errorBlock.style.display = 'block';
        }

        this.toggleLoadingPopover(false);
        this.toggleResponsePopover(true);
    }

    toggleLoadingPopover(isVisible) {
        isVisible ? this.loadingPopover?.showPopover() : this.loadingPopover?.hidePopover();
    }

    toggleResponsePopover(isVisible) {
        isVisible ? this.responsePopover?.showPopover() : this.responsePopover?.hidePopover();
    }

    showTargetPopover(event) {
        this.closePopovers();
        const targetSelector = event.currentTarget.dataset['targetPopover'] ?? '';
        const target = document.querySelector(targetSelector);

        target?.querySelectorAll('[data-locale]').forEach((button) => {
            button.hidden = button.getAttribute('data-locale') === (this.requestBody['currentLocale'] ?? '');
        });

        target?.showPopover();
    }

    closePopovers() {
        document.querySelectorAll('[popover]').forEach((popover) => {
            popover.hidePopover();
        });
    }
}

document.addEventListener('DOMContentLoaded', () => {
    new AiRequestBuilder();
});
