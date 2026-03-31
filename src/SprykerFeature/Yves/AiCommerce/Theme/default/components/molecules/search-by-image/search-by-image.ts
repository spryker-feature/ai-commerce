import Component from 'ShopUi/models/component';
import AjaxProvider from 'ShopUi/components/molecules/ajax-provider/ajax-provider';
import { processImage } from './image-processor';

const BYTES_PER_KILOBYTE = 1024;
const BYTES_PER_MEGABYTE = BYTES_PER_KILOBYTE * BYTES_PER_KILOBYTE;
const DEFAULT_MAX_FILE_SIZE_MB = 5;
const DEFAULT_MAX_FILE_SIZE_BYTES = DEFAULT_MAX_FILE_SIZE_MB * BYTES_PER_MEGABYTE;
const DECIMAL_RADIX = 10;

export const EVENT_FILE_READY = 'search-by-image:file-ready';
export const EVENT_REQUEST_FILE_INPUT = 'search-by-image:request-file-input';
export const EVENT_SHOW_ERROR = 'search-by-image:show-error';
export const EVENT_HIDE_ERROR = 'search-by-image:hide-error';

interface SearchByImageResponse {
    isSuccessful: boolean;
    errors: string[];
    redirectUrl: string;
}

export default class SearchByImage extends Component {
    protected fileInput: HTMLInputElement | null = null;
    protected tokenField: HTMLInputElement | null = null;
    protected ajaxProvider: AjaxProvider | null = null;
    protected lastActiveSource: Element | null = null;

    protected readyCallback(): void {}

    protected init(): void {
        if (this.closest('main-popup')) {
            return;
        }

        this.fileInput = this.querySelector(`.${this.jsName}__file-input`);
        this.tokenField = this.querySelector(`.${this.jsName}__token`);
        this.ajaxProvider = this.querySelector(`.${this.jsName}__ajax-provider`);

        this.toggleTriggerButtons();
        this.mapEvents();
    }

    protected mapEvents(): void {
        this.fileInput?.addEventListener('change', () => this.onFileInputChange());
        this.addEventListener(EVENT_FILE_READY, (event: Event) =>
            this.onFileReady(event as CustomEvent<{ file: File }>),
        );
        this.addEventListener(EVENT_REQUEST_FILE_INPUT, (event: Event) => this.onRequestFileInput(event));
    }

    toggleTriggerButtons(): void {
        const photoTriggerButton = this.querySelector(`.${this.jsName}__btn-search-by-photo`);
        const fileTriggerButton = this.querySelector(`.${this.jsName}__btn-search-by-file`);

        if (this.isMobileWithCamera) {
            photoTriggerButton.classList.remove('is-hidden');
            fileTriggerButton.classList.add('is-hidden');

            return;
        }

        photoTriggerButton.classList.add('is-hidden');
        fileTriggerButton.classList.remove('is-hidden');
    }

    protected onRequestFileInput(event: Event): void {
        this.lastActiveSource = event.target as Element;
        this.fileInput?.click();
    }

    protected async onFileReady(event: CustomEvent<{ file: File }>): Promise<void> {
        this.lastActiveSource = event.target as Element;
        await this.processAndSubmitFile(event.detail.file);
    }

    protected async onFileInputChange(): Promise<void> {
        if (!this.fileInput) {
            return;
        }

        const file = this.fileInput.files[0];

        if (!file) {
            return;
        }

        this.fileInput.value = '';
        await this.processAndSubmitFile(file);
    }

    protected async processAndSubmitFile(file: File): Promise<void> {
        this.dispatchToSource(EVENT_HIDE_ERROR);

        try {
            const processedFile = await processImage(file, this.maxFileSizeBytes);
            await this.sendFile(processedFile);
        } catch (error) {
            this.dispatchToSource(EVENT_SHOW_ERROR, {
                message: error instanceof Error ? error.message : 'An unexpected error occurred.',
            });
        }
    }

    protected async sendFile(file: File): Promise<void> {
        if (!this.fileInput || !this.tokenField || !this.ajaxProvider) {
            return;
        }

        const formData = new FormData();
        formData.append(this.fileInput.getAttribute('name'), file);
        formData.append(this.tokenField.getAttribute('name'), this.tokenField.value);

        const response: SearchByImageResponse = await this.ajaxProvider.fetch(formData);

        if (!response.isSuccessful) {
            this.dispatchToSource(EVENT_SHOW_ERROR, {
                message: response.errors,
            });

            return;
        }

        if (response?.redirectUrl) {
            window.location.href = response.redirectUrl;
        }
    }

    protected dispatchToSource(eventName: string, detail?: Record<string, unknown>): void {
        this.dispatchEvent(new CustomEvent(eventName, { detail, bubbles: false }));
    }

    protected get maxFileSizeBytes(): number {
        return parseInt(this.getAttribute('max-file-size-bytes') ?? String(DEFAULT_MAX_FILE_SIZE_BYTES), DECIMAL_RADIX);
    }

    protected get maxFileSizeMb(): number {
        return Math.round(this.maxFileSizeBytes / BYTES_PER_MEGABYTE);
    }

    protected get isMobileWithCamera(): boolean {
        const hasTouchScreen = navigator.maxTouchPoints > 0;
        const isMobileUserAgent = /Android|iPhone|iPad|iPod|Mobile/i.test(navigator.userAgent);
        const hasMediaDevices = typeof navigator.mediaDevices?.getUserMedia === 'function';

        return (hasTouchScreen || isMobileUserAgent) && hasMediaDevices;
    }
}
