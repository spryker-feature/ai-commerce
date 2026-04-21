import '../../scss/main.scss';
import { AiProductManagement } from './ai-product-management';

export class AiImageAltText extends AiProductManagement {
    _triggerSelector = '.js-ai-alt-image-trigger';
    altTextData = { locale: '' };

    preparePayload(trigger) {
        const fieldName = this._fieldElement?.name ?? '';
        const localePart = fieldName.split('[')[1]?.split(']')[0] ?? '';
        const inputLocale = localePart.replace('image_set_', '');

        this.altTextData = {
            locale: inputLocale === 'default' ? 'en_US' : inputLocale,
        };

        const urlFieldName = fieldName
            .replace('[alt_text_small]', '[external_url_small]')
            .replace('[alt_text_large]', '[external_url_large]');
        const urlValue = document.querySelector(`[name="${urlFieldName}"]`)?.value;

        if (urlValue) {
            this.altTextData.imageUrl = urlValue;
        }
    }

    async processAiAction() {
        if (!this.altTextData.imageUrl) {
            this._modal?.classList.add(this._states.empty);
            this._modal?.classList.remove(this._states.loading);

            return;
        }

        const input = this._modal?.querySelector('.js-ai-alt-text-input');
        if (input) {
            input.value = '';
        }

        const formData = new FormData();
        formData.append('imageUrl', this.altTextData.imageUrl);
        formData.append('locale', this.altTextData.locale);

        try {
            const response = await fetch(this._url, { method: 'POST', body: formData });
            const responseData = await response.json();

            if (!response.ok) {
                this.onError(responseData.errors[0].message);

                return;
            }

            if (input) {
                input.value = decodeURI(responseData.altText ?? '');
            }

            this.altTextData.cache = true;
        } catch (error) {
            console.error(error);
        } finally {
            this._modal?.classList.remove(this._states.loading);
        }
    }

    onApply() {
        const input = this._modal?.querySelector('.js-ai-alt-text-input');
        if (this._fieldElement && input) {
            this._fieldElement.value = input.value;
        }
    }
}

export class AiImageAltTextInjector {
    _wrapperSelector = '.js-image-alt-text-wrapper';
    _templateId = 'ai-alt-text-trigger-template';
    _triggerClass = 'js-ai-alt-image-trigger';
    _affixClass = 'form-wrapper-clickable-affix';

    init() {
        this.#injectAll(document);
        this.#observeMutations();
    }

    #injectAll(root) {
        root.querySelectorAll(this._wrapperSelector).forEach((wrapper) => {
            this.#injectTrigger(wrapper);
        });
    }

    #injectTrigger(wrapper) {
        if (wrapper.querySelector(`.${this._triggerClass}`)) {
            return;
        }

        const template = document.getElementById(this._templateId);

        if (!template) {
            return;
        }

        wrapper.classList.add(this._affixClass);
        wrapper.appendChild(template.content.cloneNode(true));
    }

    #observeMutations() {
        const observer = new MutationObserver((mutations) => {
            for (const mutation of mutations) {
                for (const node of mutation.addedNodes) {
                    if (!(node instanceof Element)) {
                        continue;
                    }

                    if (node.matches(this._wrapperSelector)) {
                        this.#injectTrigger(node);
                    }

                    this.#injectAll(node);
                }
            }
        });

        observer.observe(document.body, { childList: true, subtree: true });
    }
}
