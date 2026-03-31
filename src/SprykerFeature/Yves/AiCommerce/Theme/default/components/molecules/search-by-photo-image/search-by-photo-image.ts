import Component from 'ShopUi/models/component';
import MainPopup, { EVENT_POPUP_OPENED, EVENT_POPUP_CLOSED } from 'ShopUi/components/molecules/main-popup/main-popup';
import { canvasToJpegFile } from '../search-by-image/image-processor';
import {
    EVENT_FILE_READY,
    EVENT_REQUEST_FILE_INPUT,
    EVENT_SHOW_ERROR,
    EVENT_HIDE_ERROR,
} from '../search-by-image/search-by-image';

export default class SearchByPhotoImage extends Component {
    protected cameraStream: HTMLVideoElement | null = null;
    protected cameraCanvas: HTMLCanvasElement | null = null;
    protected cameraCaptureButton: HTMLButtonElement | null = null;
    protected cameraUseFileButton: HTMLButtonElement | null = null;
    protected activeMediaStream: MediaStream | null = null;
    protected cameraButtonsAbortController: AbortController | null = null;
    protected errorContainer: HTMLElement | null = null;
    protected errorMessage: HTMLElement | null = null;
    protected parent: HTMLElement | null = null;
    protected popup: MainPopup | null = null;

    protected readyCallback(): void {}

    protected init(): void {
        if (this.closest('main-popup')) {
            return;
        }

        this.parent = document.querySelector(`.${this.dataset.parent}`);
        this.popup = document.querySelector(`.${this.dataset.parentPopup}`);
        this.errorContainer = this.querySelector(`.${this.jsName}__error`);
        this.errorMessage = this.querySelector(`.${this.jsName}__error-message`);
        this.errorItemTemplate = this.querySelector<HTMLTemplateElement>(`.${this.jsName}__error-item-template`);

        this.toggleError(true);
        this.mapEvents();
    }

    protected mapEvents(): void {
        const mainPopup = <MainPopup>document.querySelector(`main-popup.${this.getAttribute('data-parent-popup')}`);

        mainPopup?.addEventListener(EVENT_POPUP_OPENED, () => this.onPopupOpened());
        mainPopup?.addEventListener(EVENT_POPUP_CLOSED, () => this.onPopupClosed());

        this.parent?.addEventListener(EVENT_SHOW_ERROR, (event: Event) =>
            this.showError([(event as CustomEvent<{ message: string }>).detail.message]),
        );
        this.parent?.addEventListener(EVENT_HIDE_ERROR, () => this.toggleError(true));
        this.popup?.addEventListener(EVENT_POPUP_OPENED, () => this.toggleError(true));
        this.onPopupOpened();
    }

    protected async onPopupOpened(): Promise<void> {
        this.cameraStream = this.querySelector(`.${this.jsName}__camera-stream`);
        this.cameraCanvas = this.querySelector(`.${this.jsName}__camera-canvas`);
        this.cameraCaptureButton = this.querySelector(`.${this.jsName}__camera-capture-button`);
        this.cameraUseFileButton = this.querySelector(`.${this.jsName}__camera-use-file-button`);

        try {
            await this.startCamera();
        } catch (error: unknown) {
            this.showError([(error as Error).message]);
        }

        this.cameraButtonsAbortController?.abort();
        this.cameraButtonsAbortController = new AbortController();

        const { signal } = this.cameraButtonsAbortController;

        this.cameraCaptureButton?.addEventListener('click', () => this.onCameraCaptureButtonClick(), { signal });
        this.cameraUseFileButton?.addEventListener('click', () => this.onCameraUseFileButtonClick(), { signal });
    }

    protected onPopupClosed(): void {
        this.cameraButtonsAbortController?.abort();
        this.stopActiveMediaStream();
    }

    protected async startCamera(): Promise<void> {
        this.activeMediaStream = await navigator.mediaDevices.getUserMedia({
            video: { facingMode: 'environment' },
        });

        if (this.cameraStream) {
            this.cameraStream.srcObject = this.activeMediaStream;
        }
    }

    protected stopActiveMediaStream(): void {
        if (!this.activeMediaStream) {
            return;
        }

        this.activeMediaStream.getTracks().forEach((track) => track.stop());
        this.activeMediaStream = null;

        if (this.cameraStream) {
            this.cameraStream.srcObject = null;
        }
    }

    protected async onCameraCaptureButtonClick(): Promise<void> {
        const file = await this.capturePhotoFromCamera();

        if (!file) {
            return;
        }

        this.parent.dispatchEvent(new CustomEvent(EVENT_FILE_READY, { detail: { file } }));
    }

    protected async capturePhotoFromCamera(): Promise<File | null> {
        if (!this.cameraStream || !this.cameraCanvas) {
            return null;
        }

        const context = this.cameraCanvas.getContext('2d');

        if (!context) {
            return null;
        }

        const { videoWidth, videoHeight } = this.cameraStream;
        this.cameraCanvas.width = videoWidth;
        this.cameraCanvas.height = videoHeight;
        context.drawImage(this.cameraStream, 0, 0, videoWidth, videoHeight);

        return canvasToJpegFile(this.cameraCanvas);
    }

    protected onCameraUseFileButtonClick(): void {
        this.parent.dispatchEvent(new CustomEvent(EVENT_REQUEST_FILE_INPUT, { bubbles: true }));
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
