import Component from 'ShopUi/models/component';

export default class QuickOrderImageToCart extends Component {
    protected inputFile: HTMLInputElement;
    protected fileUploadMessage: HTMLElement;
    protected removeIcon: HTMLElement;
    protected browseFileLabel: HTMLLabelElement;
    protected uploadMessage: string;
    protected readonly hiddenClass: string = 'is-hidden';
    protected readonly browseFileLabelToggleClass: string = 'label--browse-file-cursor-default';
    protected readonly fileSelectErrorClass: string = `${this.name}__file-select--error`;

    protected readyCallback(): void {}

    protected init(): void {
        this.inputFile = <HTMLInputElement>this.querySelector(`input[type="file"]`);
        this.fileUploadMessage = <HTMLElement>this.getElementsByClassName(`${this.jsName}__file-select`)[0];
        this.uploadMessage = <string>this.fileUploadMessage.innerText;
        this.removeIcon = <HTMLElement>this.getElementsByClassName(`${this.jsName}__remove-file`)[0];
        this.browseFileLabel = <HTMLLabelElement>this.getElementsByClassName(`${this.jsName}__browse-file`)[0];
        this.mapEvents();
    }

    protected mapEvents(): void {
        this.inputFile.addEventListener('change', this.inputFileHandler.bind(this, this.inputFile));
        this.removeIcon.addEventListener('click', this.cleanInputFile.bind(this));
    }

    protected inputFileHandler(inputFile: HTMLInputElement): void {
        if (!inputFile.files || inputFile.files.length === 0) {
            this.fileUploadMessage.innerText = this.uploadMessage;

            return;
        }

        const maxFileSizeInBytes = Number(this.getAttribute('max-file-size'));

        if (maxFileSizeInBytes > 0 && Array.from(inputFile.files).some((file) => file.size > maxFileSizeInBytes)) {
            this.fileUploadMessage.innerText = this.getAttribute('file-size-error-message') ?? '';
            this.fileUploadMessage.classList.add(this.fileSelectErrorClass);
            inputFile.value = '';

            return;
        }

        this.fileUploadMessage.classList.remove(this.fileSelectErrorClass);
        this.fileUploadMessage.innerText = Array.from(inputFile.files)
            .map((file) => file.name)
            .join(', ');
        this.removeIcon.classList.remove(this.hiddenClass);
        this.browseFileLabel.removeAttribute('for');
    }

    protected cleanInputFile(event: Event): void {
        event.preventDefault();
        this.inputFile.value = '';
        this.fileUploadMessage.innerText = this.uploadMessage;
        this.fileUploadMessage.classList.remove(this.fileSelectErrorClass);
        this.removeIcon.classList.add(this.hiddenClass);
        if (this.inputFileId) {
            this.browseFileLabel.setAttribute('for', this.inputFileId);
        }
    }

    protected get inputFileId(): string | null {
        return this.getAttribute('input-file-id');
    }
}
