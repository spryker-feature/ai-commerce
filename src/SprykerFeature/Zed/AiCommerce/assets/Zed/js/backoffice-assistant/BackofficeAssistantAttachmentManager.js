import { MAX_FILE_SIZE } from './constants';

export class BackofficeAssistantAttachmentManager {
    constructor(previewEl, fileInputEl, state, renderer, panelEl, i18n) {
        this.previewEl = previewEl;
        this.fileInputEl = fileInputEl;
        this.state = state;
        this.renderer = renderer;
        this.i18n = i18n;
        this.chipTemplate = panelEl.querySelector('[data-id="backoffice-assistant-attachment-chip"]');
        this.allowedTypes = fileInputEl
            .getAttribute('accept')
            .split(',')
            .map((t) => t.trim());
    }

    handleFileSelect(files) {
        for (let i = 0; i < files.length; i++) {
            this.processFile(files[i]);
        }

        this.fileInputEl.value = '';
    }

    processFile(file) {
        if (this.allowedTypes.indexOf(file.type) === -1) {
            this.renderer.addMessage('ai', this.i18n.unsupportedFileType + file.name);

            return;
        }

        if (file.size > MAX_FILE_SIZE) {
            this.renderer.addMessage('ai', this.i18n.fileTooLarge + file.name);

            return;
        }

        this.readAsBase64(file);
    }

    readAsBase64(file) {
        const reader = new FileReader();

        reader.onload = (event) => {
            const base64 = event.target.result.split(',')[1];

            this.state.pendingAttachments.push({
                name: file.name,
                content: base64,
                mediaType: file.type,
            });

            this.renderChip(file.name, this.state.pendingAttachments.length - 1);
        };

        reader.readAsDataURL(file);
    }

    renderChip(fileName, index) {
        const fragment = this.chipTemplate.content.cloneNode(true);
        const chip = fragment.firstElementChild;

        chip.dataset.index = String(index);

        const nameSpan = chip.querySelector('.backoffice-assistant__attachment-chip-name');
        nameSpan.textContent = fileName.length > 20 ? fileName.substring(0, 17) + '...' : fileName;
        nameSpan.title = fileName;

        chip.querySelector('.backoffice-assistant__attachment-chip-remove').addEventListener('click', () => {
            const idx = parseInt(chip.dataset.index, 10);
            this.state.pendingAttachments.splice(idx, 1);
            chip.remove();
            this.reindexChips();
        });

        this.previewEl.appendChild(chip);
    }

    reindexChips() {
        const chips = this.previewEl.querySelectorAll('.backoffice-assistant__attachment-chip');

        for (let i = 0; i < chips.length; i++) {
            chips[i].dataset.index = String(i);
        }
    }

    takeSnapshot() {
        const snapshot = this.state.pendingAttachments.slice();
        this.clear();

        return snapshot;
    }

    clear() {
        this.state.pendingAttachments = [];
        this.previewEl.innerHTML = '';
    }
}
