import { BackofficeAssistantMarkdownParser } from './BackofficeAssistantMarkdownParser';

export class BackofficeAssistantMessageRenderer {
    #messagesEl;
    #markdownParser;
    #i18n;
    #templates;

    constructor(messagesEl, panelEl, i18n) {
        this.#messagesEl = messagesEl;
        this.#markdownParser = new BackofficeAssistantMarkdownParser();
        this.#i18n = i18n;
        this.#templates = {
            message: panelEl.querySelector('[data-id="backoffice-assistant-message"]'),
            loading: panelEl.querySelector('[data-id="backoffice-assistant-loading"]'),
            retry: panelEl.querySelector('[data-id="backoffice-assistant-retry"]'),
            toolCall: panelEl.querySelector('[data-id="backoffice-assistant-tool-call"]'),
            attachmentPill: panelEl.querySelector('[data-id="backoffice-assistant-attachment-pill"]'),
        };
    }

    #scrollToBottom() {
        this.#messagesEl.scrollTop = this.#messagesEl.scrollHeight;
    }

    addMessage(role, text) {
        const fragment = this.#templates.message.content.cloneNode(true);
        const bubble = fragment.firstElementChild;

        bubble.classList.add('backoffice-assistant__message--' + role);

        if (role === 'ai') {
            bubble.innerHTML = this.#markdownParser.parse(text);
        } else {
            bubble.textContent = text;
        }

        this.#messagesEl.appendChild(bubble);
        this.#scrollToBottom();

        return bubble;
    }

    addLoadingIndicator() {
        const fragment = this.#templates.loading.content.cloneNode(true);
        const el = fragment.firstElementChild;

        this.#messagesEl.appendChild(el);
        this.#scrollToBottom();

        return el;
    }

    addRetryButton(onRetry) {
        const fragment = this.#templates.retry.content.cloneNode(true);
        const btn = fragment.firstElementChild;

        btn.textContent = this.#i18n.retry;
        btn.addEventListener('click', () => {
            btn.remove();
            onRetry();
        });

        this.#messagesEl.appendChild(btn);
        this.#scrollToBottom();
    }

    addReasoningMessage(text) {
        const bubble = document.createElement('div');
        bubble.classList.add('backoffice-assistant__message', 'backoffice-assistant__message--reasoning');
        bubble.textContent = text;

        this.#messagesEl.appendChild(bubble);
        this.#scrollToBottom();

        return bubble;
    }

    addToolCallMessage(name, args, result) {
        const bubble = document.createElement('div');
        bubble.classList.add('backoffice-assistant__message', 'backoffice-assistant__message--tool-call');

        bubble.appendChild(this.#createToolCallLabel(name));

        if (args && Object.keys(args).length > 0) {
            bubble.appendChild(this.#createToolCallArgs(args));
        }

        if (result) {
            bubble.appendChild(this.#createToolCallResult(result));
        }

        this.#messagesEl.appendChild(bubble);
        this.#scrollToBottom();

        return bubble;
    }

    #createToolCallLabel(name) {
        const fragment = this.#templates.toolCall.content.cloneNode(true);
        const label = fragment.querySelector('.backoffice-assistant__tool-call-label');

        label.querySelector('.backoffice-assistant__tool-call-name').textContent = name;

        return label;
    }

    #createToolCallArgs(args) {
        const section = document.createElement('div');
        section.classList.add('backoffice-assistant__tool-call-section');

        const sectionLabel = document.createElement('span');
        sectionLabel.classList.add('backoffice-assistant__tool-call-section-label');
        sectionLabel.textContent = this.#i18n.arguments;
        section.appendChild(sectionLabel);

        const code = document.createElement('pre');
        code.classList.add('backoffice-assistant__tool-call-code');
        code.textContent = JSON.stringify(args, null, 2);
        section.appendChild(code);

        return section;
    }

    #createToolCallResult(result) {
        const section = document.createElement('div');
        section.classList.add('backoffice-assistant__tool-call-section');

        const toggleBtn = document.createElement('button');
        toggleBtn.type = 'button';
        toggleBtn.classList.add('backoffice-assistant__tool-call-toggle');
        toggleBtn.textContent = this.#i18n.showResult;
        section.appendChild(toggleBtn);

        const code = document.createElement('pre');
        code.classList.add('backoffice-assistant__tool-call-code', 'backoffice-assistant__tool-call-code--collapsed');

        try {
            code.textContent = JSON.stringify(JSON.parse(result), null, 2);
        } catch {
            code.textContent = result;
        }

        section.appendChild(code);

        const i18n = this.#i18n;

        toggleBtn.addEventListener('click', () => {
            const isCollapsed = code.classList.toggle('backoffice-assistant__tool-call-code--collapsed');
            toggleBtn.textContent = isCollapsed ? i18n.showResult : i18n.hideResult;
        });

        return section;
    }

    addAttachmentPills(bubble, attachments) {
        const container = document.createElement('div');
        container.classList.add('backoffice-assistant__message-attachments');

        for (let i = 0; i < attachments.length; i++) {
            const fragment = this.#templates.attachmentPill.content.cloneNode(true);
            const pill = fragment.firstElementChild;

            pill.querySelector('.backoffice-assistant__message-attachment-pill-name').textContent = attachments[i].name;
            container.appendChild(pill);
        }

        bubble.appendChild(container);
    }

    clear() {
        this.#messagesEl.innerHTML = '';
    }
}
