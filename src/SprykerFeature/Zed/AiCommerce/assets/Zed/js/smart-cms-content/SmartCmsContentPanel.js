/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

/**
 * Wires the Smart CMS Content Assistant panel on the CMS Page and CMS Block glossary editors.
 *
 * On "Ask AI" it collects the current content of every placeholder across every locale from the
 * Summernote editors plus read-only entity context, posts them to the Zed endpoint, and replaces
 * the matching editors with the generated content. The glossary form is never auto-saved.
 */
import { SmartCmsContentPanelState } from './SmartCmsContentPanelState';

export class SmartCmsContentPanel {
    static #selectors = {
        panel: '.js-smart-cms-panel',
        toggle: '.js-smart-cms-panel__toggle',
        input: '.js-smart-cms-panel__input',
        ask: '.js-smart-cms-panel__ask',
        askLabel: '.js-smart-cms-panel__ask-label',
        message: '.js-smart-cms-panel__message',
        attach: '.js-smart-cms-panel__attach',
        fileInput: '.js-smart-cms-panel__file-input',
        attachments: '.js-smart-cms-panel__attachments',
        attachmentTemplate: '.js-smart-cms-attachment-template',
        attachmentName: '.js-smart-cms-panel__attachment-name',
        attachmentRemove: '.js-smart-cms-panel__attachment-remove',
        editor: 'textarea.html-editor',
        widgetInfoBlock: '.ibox',
        widgetInfoTitle: '.ibox-title',
        widgetInfoContent: '.ibox-content',
    };

    static #base64Pattern = /^data:[^;]+;base64,/;

    static #widgetInfoTitlePattern = /content widgets usage information/i;

    static #nodeName = {
        unorderedList: 'UL',
        strong: 'STRONG',
    };

    static #panePrefix = 'tab-content-';

    static #messageType = {
        success: 'success',
        error: 'error',
    };

    #config;
    #elements;
    #busyClass;
    #collapsedClass;
    #messageClasses;
    #messages;
    #idleLabel;
    #attachmentConfig;
    #attachments = [];
    #state = new SmartCmsContentPanelState();

    constructor() {
        this.#config = window.SmartCmsContentConfig ?? {};
        this.#attachmentConfig = this.#config.attachments ?? {};
        this.#elements = this.#resolveElements();
        this.#busyClass = this.#elements.panel.dataset.busyClass;
        this.#collapsedClass = this.#elements.panel.dataset.collapsedClass;
        this.#messageClasses = {
            visible: this.#elements.panel.dataset.messageVisibleClass,
            success: this.#elements.panel.dataset.messageSuccessClass,
            error: this.#elements.panel.dataset.messageErrorClass,
        };
        this.#messages = {
            done: this.#elements.panel.dataset.i18nDone,
            generating: this.#elements.panel.dataset.i18nGenerating,
            connectionError: this.#elements.panel.dataset.i18nConnectionError,
            attachmentTooLarge: this.#elements.panel.dataset.i18nAttachmentTooLarge,
            attachmentUnsupported: this.#elements.panel.dataset.i18nAttachmentUnsupported,
            attachmentCountExceeded: this.#elements.panel.dataset.i18nAttachmentCountExceeded,
            attachmentTotalSizeExceeded: this.#elements.panel.dataset.i18nAttachmentTotalSizeExceeded,
            attachmentReadError: this.#elements.panel.dataset.i18nAttachmentReadError,
        };
        this.#idleLabel = this.#elements.ask.dataset.labelIdle;
        this.#init();
    }

    #init() {
        this.#restoreCollapsedState();
        this.#elements.toggle.addEventListener('click', () => this.#onToggle());
        this.#elements.ask.addEventListener('click', () => this.#onAsk());
        this.#elements.attach.addEventListener('click', () => this.#elements.fileInput.click());
        this.#elements.fileInput.addEventListener('change', () => this.#onFilesSelected());
    }

    #resolveElements() {
        const panel = document.querySelector(SmartCmsContentPanel.#selectors.panel);

        if (!panel) {
            throw new Error('SmartCmsContentPanel: required element .js-smart-cms-panel not found');
        }

        const toggle = panel.querySelector(SmartCmsContentPanel.#selectors.toggle);
        const input = panel.querySelector(SmartCmsContentPanel.#selectors.input);
        const ask = panel.querySelector(SmartCmsContentPanel.#selectors.ask);
        const askLabel = panel.querySelector(SmartCmsContentPanel.#selectors.askLabel);
        const message = panel.querySelector(SmartCmsContentPanel.#selectors.message);
        const attach = panel.querySelector(SmartCmsContentPanel.#selectors.attach);
        const fileInput = panel.querySelector(SmartCmsContentPanel.#selectors.fileInput);
        const attachments = panel.querySelector(SmartCmsContentPanel.#selectors.attachments);
        const attachmentTemplate = panel.querySelector(SmartCmsContentPanel.#selectors.attachmentTemplate);

        if (
            !toggle ||
            !input ||
            !ask ||
            !askLabel ||
            !message ||
            !attach ||
            !fileInput ||
            !attachments ||
            !attachmentTemplate
        ) {
            throw new Error('SmartCmsContentPanel: a required panel element was not found');
        }

        return { panel, toggle, input, ask, askLabel, message, attach, fileInput, attachments, attachmentTemplate };
    }

    #restoreCollapsedState() {
        // Default to collapsed on first visit; honor the editor's saved choice afterwards.
        const savedState = this.#state.load();
        const isCollapsed = savedState?.collapsed ?? true;

        this.#applyCollapsedState(isCollapsed);
    }

    #onToggle() {
        const isCollapsed = !this.#elements.panel.classList.contains(this.#collapsedClass);

        this.#applyCollapsedState(isCollapsed);
        this.#state.save(isCollapsed);
    }

    #applyCollapsedState(isCollapsed) {
        this.#elements.panel.classList.toggle(this.#collapsedClass, isCollapsed);
        this.#elements.toggle.setAttribute('aria-expanded', String(!isCollapsed));
    }

    async #onAsk() {
        const userPrompt = (this.#elements.input?.value ?? '').trim();

        if (!userPrompt || this.#elements.ask.disabled) {
            return;
        }

        this.#setBusy(true);
        this.#hideMessage();

        try {
            const response = await this.#requestGeneration(userPrompt);
            const data = await response.json();

            if (!response.ok) {
                this.#showMessage(this.#extractError(data), SmartCmsContentPanel.#messageType.error);

                return;
            }

            this.#applyPlaceholders(data.placeholders ?? []);
            this.#showMessage(data.explanation || this.#messages.done, SmartCmsContentPanel.#messageType.success);
        } catch (error) {
            this.#showMessage(this.#messages.connectionError, SmartCmsContentPanel.#messageType.error);
        } finally {
            this.#setBusy(false);
        }
    }

    #requestGeneration(userPrompt) {
        const payload = {
            _token: this.#config.csrfToken ?? '',
            userPrompt: userPrompt,
            entityType: this.#config.entityType ?? '',
            idEntity: this.#config.idEntity ?? 0,
            entityContext: this.#config.entityContext ?? {},
            placeholders: this.#collectPlaceholders(),
            availableContentWidgets: this.#collectContentWidgets(),
            attachments: this.#attachments.map((attachment) => ({
                mediaType: attachment.mediaType,
                content: attachment.content,
            })),
        };

        return fetch(this.#config.endpoint, {
            method: 'POST',
            redirect: 'manual',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify(payload),
        });
    }

    /**
     * Collects the current content of every placeholder across every locale from the Summernote editors.
     */
    #collectPlaceholders() {
        const placeholders = {};

        this.#eachEditor((editor, placeholderName, localeName) => {
            if (!placeholders[placeholderName]) {
                placeholders[placeholderName] = { placeholder: placeholderName, translations: [] };
            }

            placeholders[placeholderName].translations.push({
                localeName: localeName,
                content: this.#getEditorContent(editor),
            });
        });

        return Object.values(placeholders);
    }

    /**
     * Collects the CMS content widgets available on the page from the editor's
     * "content widgets usage information" block, so the AI can reuse only widgets that exist.
     *
     * The block is rendered by the core /cms-content-widget/usage-information/index controller as a
     * sequence of: <strong>functionName</strong> ... usage line ... <ul><li><strong>tpl</strong> (path)</li></ul>.
     */
    #collectContentWidgets() {
        const content = this.#findWidgetInfoContent();

        if (!content) {
            return [];
        }

        const widgets = [];

        for (const functionNameElement of content.querySelectorAll('strong')) {
            const functionName = (functionNameElement.textContent ?? '').trim();

            if (!functionName || functionNameElement.closest('li')) {
                continue;
            }

            widgets.push({
                functionName: functionName,
                usageInformation: this.#extractWidgetUsage(functionNameElement),
                templates: this.#extractWidgetTemplates(functionNameElement),
            });
        }

        return widgets;
    }

    #findWidgetInfoContent() {
        for (const block of document.querySelectorAll(SmartCmsContentPanel.#selectors.widgetInfoBlock)) {
            const title = block.querySelector(SmartCmsContentPanel.#selectors.widgetInfoTitle);

            if (title && SmartCmsContentPanel.#widgetInfoTitlePattern.test(title.textContent ?? '')) {
                return block.querySelector(SmartCmsContentPanel.#selectors.widgetInfoContent) ?? block;
            }
        }

        return null;
    }

    /**
     * The usage syntax is the text between the widget's function-name <strong> and the next <br>.
     */
    #extractWidgetUsage(functionNameElement) {
        let node = functionNameElement.nextSibling;
        let usage = '';

        while (
            node &&
            node.nodeName !== SmartCmsContentPanel.#nodeName.unorderedList &&
            node.nodeName !== SmartCmsContentPanel.#nodeName.strong
        ) {
            usage += node.textContent ?? '';
            node = node.nextSibling;
        }

        return usage
            .replace(/usage information:/i, '')
            .replace(/available templates:/i, '')
            .trim();
    }

    /**
     * Template identifiers are the bold labels inside the widget's following <ul> list,
     * e.g. "default (path)" yields "default".
     */
    #extractWidgetTemplates(functionNameElement) {
        const templates = [];
        let node = functionNameElement.nextSibling;

        while (node && node.nodeName !== SmartCmsContentPanel.#nodeName.strong) {
            if (node.nodeName === SmartCmsContentPanel.#nodeName.unorderedList) {
                for (const item of node.querySelectorAll('li')) {
                    const label = item.querySelector('strong');
                    const name = (label?.textContent ?? '').trim();

                    if (name) {
                        templates.push(name);
                    }
                }

                break;
            }

            node = node.nextSibling;
        }

        return templates;
    }

    /**
     * Reads the chosen files as base64, validates them client-side against the configured limits,
     * keeps the accepted ones for the next request, and reports rejected ones in the message area.
     */
    async #onFilesSelected() {
        const files = [...(this.#elements.fileInput.files ?? [])];
        this.#elements.fileInput.value = '';

        if (files.length === 0) {
            return;
        }

        if (this.#attachments.length + files.length > (this.#attachmentConfig.maxCount ?? files.length)) {
            this.#showMessage(this.#messages.attachmentCountExceeded, SmartCmsContentPanel.#messageType.error);

            return;
        }

        for (const file of files) {
            await this.#addAttachment(file);
        }

        this.#renderAttachments();
    }

    async #addAttachment(file) {
        const allowedMediaTypes = this.#attachmentConfig.allowedMediaTypes ?? [];

        if (allowedMediaTypes.length && !allowedMediaTypes.includes(file.type)) {
            this.#showMessage(this.#messages.attachmentUnsupported, SmartCmsContentPanel.#messageType.error);

            return;
        }

        if (file.size > (this.#attachmentConfig.maxFileSizeBytes ?? file.size)) {
            this.#showMessage(this.#messages.attachmentTooLarge, SmartCmsContentPanel.#messageType.error);

            return;
        }

        const content = await this.#readFileAsBase64(file);

        if (content === null) {
            this.#showMessage(this.#messages.attachmentReadError, SmartCmsContentPanel.#messageType.error);

            return;
        }

        const totalSize = this.#attachments.reduce((sum, attachment) => sum + attachment.size, 0) + file.size;

        if (totalSize > (this.#attachmentConfig.maxTotalSizeBytes ?? totalSize)) {
            this.#showMessage(this.#messages.attachmentTotalSizeExceeded, SmartCmsContentPanel.#messageType.error);

            return;
        }

        this.#attachments.push({ name: file.name, mediaType: file.type, size: file.size, content: content });
    }

    #readFileAsBase64(file) {
        return new Promise((resolve) => {
            const reader = new FileReader();

            reader.addEventListener('load', () => {
                const result = String(reader.result ?? '');
                resolve(result.replace(SmartCmsContentPanel.#base64Pattern, ''));
            });
            reader.addEventListener('error', () => resolve(null));
            reader.readAsDataURL(file);
        });
    }

    #renderAttachments() {
        this.#elements.attachments.replaceChildren();

        for (const [index, attachment] of this.#attachments.entries()) {
            this.#elements.attachments.appendChild(this.#buildAttachmentItem(attachment, index));
        }
    }

    #buildAttachmentItem(attachment, index) {
        const fragment = this.#elements.attachmentTemplate.content.cloneNode(true);

        const name = fragment.querySelector(SmartCmsContentPanel.#selectors.attachmentName);

        if (name) {
            name.textContent = attachment.name ?? '';
        }

        const remove = fragment.querySelector(SmartCmsContentPanel.#selectors.attachmentRemove);
        remove?.addEventListener('click', () => this.#removeAttachment(index));

        return fragment;
    }

    #removeAttachment(index) {
        this.#attachments.splice(index, 1);
        this.#renderAttachments();
    }

    /**
     * Replaces the matching Summernote editor content per placeholder and locale.
     */
    #applyPlaceholders(placeholders) {
        const index = {};

        for (const placeholder of placeholders) {
            for (const translation of placeholder.translations ?? []) {
                index[`${placeholder.placeholder}::${translation.localeName}`] = translation.content;
            }
        }

        this.#eachEditor((editor, placeholderName, localeName) => {
            const content = index[`${placeholderName}::${localeName}`];

            if (content !== undefined) {
                this.#setEditorContent(editor, content);
            }
        });
    }

    /**
     * Iterates every glossary editor, resolving its placeholder (from the tab pane id) and locale.
     */
    #eachEditor(callback) {
        for (const editor of document.querySelectorAll(SmartCmsContentPanel.#selectors.editor)) {
            const placeholderName = this.#resolvePlaceholderName(editor);
            const localeName = this.#resolveLocaleName(editor);

            if (placeholderName && localeName) {
                callback(editor, placeholderName, localeName);
            }
        }
    }

    #resolvePlaceholderName(editor) {
        const pane = editor.closest('.tab-pane');

        if (!pane || pane.id.indexOf(SmartCmsContentPanel.#panePrefix) !== 0) {
            return null;
        }

        return pane.id.substring(SmartCmsContentPanel.#panePrefix.length);
    }

    #resolveLocaleName(editor) {
        const name = editor.getAttribute('name') ?? '';
        const localeFieldName = name.replace(/\[translation\]$/, '[localeName]');
        const localeField = document.querySelector(`[name="${localeFieldName}"]`);

        return localeField?.value ?? null;
    }

    #getEditorContent(editor) {
        const $editor = window.jQuery(editor);

        if ($editor.next('.note-editor').length) {
            return $editor.summernote('code');
        }

        return editor.value;
    }

    #setEditorContent(editor, content) {
        const $editor = window.jQuery(editor);

        if ($editor.next('.note-editor').length) {
            $editor.summernote('code', content);

            return;
        }

        editor.value = content;
    }

    #setBusy(isBusy) {
        this.#elements.ask.disabled = isBusy;
        this.#elements.input.disabled = isBusy;
        this.#elements.attach.disabled = isBusy;
        this.#elements.ask.classList.toggle(this.#busyClass, isBusy);
        this.#elements.askLabel.textContent = isBusy ? this.#messages.generating : this.#idleLabel;
    }

    #showMessage(message, type) {
        this.#elements.message.textContent = message;
        this.#elements.message.classList.remove(this.#messageClasses.success, this.#messageClasses.error);
        this.#elements.message.classList.add(this.#messageClasses[type], this.#messageClasses.visible);
    }

    #hideMessage() {
        this.#elements.message.textContent = '';
        this.#elements.message.classList.remove(
            this.#messageClasses.visible,
            this.#messageClasses.success,
            this.#messageClasses.error,
        );
    }

    #extractError(data) {
        if (!data?.error) {
            return this.#messages.connectionError;
        }

        if (Array.isArray(data.error)) {
            return data.error.map((item) => item.message ?? '').join(' ') || this.#messages.connectionError;
        }

        return data.error;
    }
}
