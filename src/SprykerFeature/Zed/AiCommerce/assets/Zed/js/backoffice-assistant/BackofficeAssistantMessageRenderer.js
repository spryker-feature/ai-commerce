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
            reasoning: panelEl.querySelector('[data-id="backoffice-assistant-reasoning"]'),
            attachmentsContainer: panelEl.querySelector('[data-id="backoffice-assistant-attachments-container"]'),
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

    keepLoadingIndicatorAtBottom(loadingEl) {
        if (loadingEl && loadingEl.isConnected && this.#messagesEl.lastElementChild !== loadingEl) {
            this.#messagesEl.appendChild(loadingEl);
        }
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
        const fragment = this.#templates.reasoning.content.cloneNode(true);
        const bubble = fragment.firstElementChild;

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
        const fragment = this.#templates.toolCall.content.cloneNode(true);
        const section = fragment.querySelector('[data-section="args"]');

        section.querySelector('.backoffice-assistant__tool-call-section-label').textContent = this.#i18n.arguments;
        section.querySelector('.backoffice-assistant__tool-call-code').textContent = JSON.stringify(args, null, 2);

        return section;
    }

    #createToolCallResult(result) {
        const fragment = this.#templates.toolCall.content.cloneNode(true);
        const section = fragment.querySelector('[data-section="result"]');
        const toggleBtn = section.querySelector('.backoffice-assistant__tool-call-toggle');
        const code = section.querySelector('.backoffice-assistant__tool-call-code');

        toggleBtn.textContent = this.#i18n.showResult;

        try {
            code.textContent = JSON.stringify(JSON.parse(result), null, 2);
        } catch {
            code.textContent = result;
        }

        toggleBtn.addEventListener('click', () => {
            const isCollapsed = code.classList.toggle('backoffice-assistant__tool-call-code--collapsed');
            toggleBtn.textContent = isCollapsed ? this.#i18n.showResult : this.#i18n.hideResult;
        });

        return section;
    }

    addAttachmentPills(bubble, attachments) {
        const fragment = this.#templates.attachmentsContainer.content.cloneNode(true);
        const container = fragment.firstElementChild;

        for (const attachment of attachments) {
            const pillFragment = this.#templates.attachmentPill.content.cloneNode(true);
            const pill = pillFragment.firstElementChild;

            pill.querySelector('.backoffice-assistant__message-attachment-pill-name').textContent = attachment.name;
            container.appendChild(pill);
        }

        bubble.appendChild(container);
    }

    clear() {
        this.#messagesEl.innerHTML = '';
    }
}
