import Component from 'ShopUi/models/component';
import MainPopup, { EVENT_POPUP_OPENED } from 'ShopUi/components/molecules/main-popup/main-popup';
import { EVENT_REQUEST_FILE_INPUT, EVENT_SHOW_ERROR, EVENT_HIDE_ERROR } from '../search-by-image/search-by-image';

export default class SearchByFileImage extends Component {
    protected errorContainer: HTMLElement | null = null;
    protected errorMessage: HTMLElement | null = null;
    protected errorItemTemplate: HTMLTemplateElement | null = null;
    protected parent: HTMLElement | null = null;
    protected popup: MainPopup | null = null;

    protected readyCallback(): void {}

    protected init(): void {
        this.popup = this.closest('main-popup');

        if (this.popup) {
            this.popup.addEventListener(EVENT_POPUP_OPENED, () => this.toggleError(false));

            return;
        }

        this.parent = document.querySelector(`.${this.dataset.parent}`);
        this.popup = document.querySelector(`.${this.dataset.parentPopup}`);
        this.errorContainer = this.querySelector(`.${this.jsName}__error`);
        this.errorMessage = this.querySelector(`.${this.jsName}__error-message`);
        this.errorItemTemplate = this.querySelector<HTMLTemplateElement>(`.${this.jsName}__error-item-template`);

        this.mapEvents();
    }

    protected mapEvents(): void {
        const uploadButton = this.querySelector<HTMLButtonElement>(`.${this.jsName}__upload-file-button`);
        uploadButton?.addEventListener('click', () => this.onUploadButtonClick());

        this.parent?.addEventListener(EVENT_SHOW_ERROR, (event: Event) =>
            this.showError([(event as CustomEvent<{ message: string }>).detail.message]),
        );

        this.parent?.addEventListener(EVENT_HIDE_ERROR, () => this.toggleError(true));
        this.popup?.addEventListener(EVENT_POPUP_OPENED, () => this.toggleError(true));
    }

    protected onUploadButtonClick(): void {
        this.parent?.dispatchEvent(new CustomEvent(EVENT_REQUEST_FILE_INPUT));
    }

    protected showError(messages: string[]): void {
        this.errorContainer.innerHTML = '';

        messages.forEach((message) => {
            const errorListItem = this.errorItemTemplate?.content.cloneNode(true) as DocumentFragment;
            const placeholderClass = this.errorItemTemplate?.dataset.textPlaceholder;
            const textElement = errorListItem.querySelector(`.${placeholderClass}`);
            if (textElement) {
                textElement.textContent = message;
            }
            this.errorContainer.appendChild(errorListItem);
        });

        this.toggleError(false);
    }

    protected toggleError(isHidden: boolean): void {
        this.errorContainer?.classList.toggle('is-hidden', isHidden);
    }
}
