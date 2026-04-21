export class AiProductManagement {
    _states = {
        loading: 'is-loading',
        empty: 'is-empty',
    };

    _data = {};
    _modal = null;
    _fieldElement = null;
    _triggerSelector = '';
    _url = '';
    #errorHolder = null;

    #onAgain = () => {
        this._modal?.classList.add(this._states.loading);
        this.#cleanError();
        this.processAiAction();
    };

    constructor() {
        this.onApply = this.onApply.bind(this);
    }

    init() {
        document.addEventListener('click', (event) => {
            const targetElement = event.target.closest(this._triggerSelector);

            if (targetElement) {
                this.#onTriggerClick(targetElement);
            }
        });
    }

    #refreshElements() {
        this._data = {};
        this._modal.classList.remove(this._states.loading, this._states.empty);

        const applyBtn = this._modal.querySelector('.js-ai-product-management-apply');
        const againBtn = this._modal.querySelector('.js-ai-product-management-again');

        applyBtn?.removeEventListener('click', this.onApply);
        againBtn?.removeEventListener('click', this.#onAgain);

        applyBtn?.addEventListener('click', this.onApply);
        againBtn?.addEventListener('click', this.#onAgain);
    }

    #onTriggerClick(trigger) {
        const modalId = trigger.getAttribute('popovertarget') ?? '';
        this._modal = document.getElementById(modalId);
        this.#errorHolder = this._modal?.querySelector('.js-ai-product-management-modal__error') ?? null;
        this.#cleanError();

        const fieldSelector = trigger.getAttribute('data-field-selector') ?? '';
        this._fieldElement = trigger.parentElement?.querySelector(fieldSelector) ?? null;
        this._url = trigger.dataset['url'] ?? '';

        this.#refreshElements();
        this._modal?.classList.add(this._states.loading);
        this.preparePayload(trigger);
        this.processAiAction();
    }

    #cleanError() {
        if (!this.#errorHolder) {
            return;
        }

        this.#errorHolder.style.display = 'none';
        this.#errorHolder.innerText = '';
    }

    onError(error) {
        if (this.#errorHolder) {
            this.#errorHolder.innerText = error;
            this.#errorHolder.style.display = 'block';
        }

        this._modal?.classList.remove(this._states.loading);
    }

    preparePayload(trigger) {
        throw new Error('preparePayload() must be implemented');
    }
    processAiAction() {
        throw new Error('processAiAction() must be implemented');
    }
    onApply() {
        throw new Error('onApply() must be implemented');
    }
}
