import { BackofficeAssistantState } from './BackofficeAssistantState';
import { BackofficeAssistantApi } from './BackofficeAssistantApi';
import { BackofficeAssistantStreamParser } from './BackofficeAssistantStreamParser';
import { BackofficeAssistantMessageRenderer } from './BackofficeAssistantMessageRenderer';
import { BackofficeAssistantAttachmentManager } from './BackofficeAssistantAttachmentManager';
import { BackofficeAssistantAgentBadge } from './BackofficeAssistantAgentBadge';
import { BackofficeAssistantHistories } from './BackofficeAssistantHistories';

export class BackofficeAssistant {
    static #selectors = {
        toggle: '.js-backoffice-assistant__toggle',
        panel: '.js-backoffice-assistant__panel',
        close: '.js-backoffice-assistant__close',
        historyBtn: '.js-backoffice-assistant__history-btn',
        newChat: '.js-backoffice-assistant__new-chat',
        messages: '.js-backoffice-assistant__messages',
        histories: '.js-backoffice-assistant__histories',
        historiesList: '.js-backoffice-assistant__histories-list',
        historiesEmpty: '.js-backoffice-assistant__histories-empty',
        input: '.js-backoffice-assistant__input',
        send: '.js-backoffice-assistant__send',
        agentBadge: '.js-backoffice-assistant__agent-badge',
        agentSelect: '.js-backoffice-assistant__agent-select',
        attach: '.js-backoffice-assistant__attach',
        fileInput: '.js-backoffice-assistant__file-input',
        attachmentsPreview: '.js-backoffice-assistant__attachments-preview',
        footer: '.js-backoffice-assistant__footer',
    };

    #elements;
    #state;
    #api;
    #i18n;
    #renderer;
    #agentBadge;
    #attachments;
    #histories;
    #cssClasses;
    #iconClasses;

    constructor() {
        this.#elements = this.#resolveElements();

        if (!this.#elements) {
            throw new Error('BackofficeAssistant: required root elements not found');
        }

        this.#cssClasses = {
            panelOpen: this.#elements.panel.dataset.classPanelOpen,
            sendStop: this.#elements.panel.dataset.classSendStop,
            badgeAnimate: this.#elements.panel.dataset.classBadgeAnimate,
            messagesHidden: this.#elements.panel.dataset.classMessagesHidden,
            itemDeleting: this.#elements.panel.dataset.classItemDeleting,
            itemDeleted: this.#elements.panel.dataset.classItemDeleted,
        };
        this.#iconClasses = {
            send: this.#elements.send.dataset.iconSend,
            stop: this.#elements.send.dataset.iconStop,
        };

        this.#state = new BackofficeAssistantState();
        this.#api = new BackofficeAssistantApi();
        this.#i18n = this.#resolveTranslations();
        this.#renderer = new BackofficeAssistantMessageRenderer(
            this.#elements.messages,
            this.#elements.panel,
            this.#i18n,
        );
        this.#agentBadge = new BackofficeAssistantAgentBadge(
            this.#elements.agentBadge,
            this.#elements.agentSelect,
            this.#cssClasses,
        );
        this.#attachments = new BackofficeAssistantAttachmentManager(
            this.#elements.attachmentsPreview,
            this.#elements.fileInput,
            this.#state,
            this.#renderer,
            this.#elements.panel,
            this.#i18n,
        );
        this.#histories = new BackofficeAssistantHistories(
            this.#elements,
            this.#api,
            this.#loadConversationDetail.bind(this),
            this.#handleConversationDeleted.bind(this),
            this.#elements.panel,
            this.#i18n,
            this.#cssClasses,
        );

        this.#init();
    }

    #resolveElements() {
        const toggle = document.querySelector(BackofficeAssistant.#selectors.toggle);
        const panel = document.querySelector(BackofficeAssistant.#selectors.panel);

        if (!toggle || !panel) {
            return null;
        }

        return {
            toggle: toggle,
            panel: panel,
            close: panel.querySelector(BackofficeAssistant.#selectors.close),
            historyBtn: panel.querySelector(BackofficeAssistant.#selectors.historyBtn),
            newChat: panel.querySelector(BackofficeAssistant.#selectors.newChat),
            messages: panel.querySelector(BackofficeAssistant.#selectors.messages),
            histories: panel.querySelector(BackofficeAssistant.#selectors.histories),
            historiesList: panel.querySelector(BackofficeAssistant.#selectors.historiesList),
            historiesEmpty: panel.querySelector(BackofficeAssistant.#selectors.historiesEmpty),
            input: panel.querySelector(BackofficeAssistant.#selectors.input),
            send: panel.querySelector(BackofficeAssistant.#selectors.send),
            agentBadge: panel.querySelector(BackofficeAssistant.#selectors.agentBadge),
            agentSelect: panel.querySelector(BackofficeAssistant.#selectors.agentSelect),
            attach: panel.querySelector(BackofficeAssistant.#selectors.attach),
            fileInput: panel.querySelector(BackofficeAssistant.#selectors.fileInput),
            attachmentsPreview: panel.querySelector(BackofficeAssistant.#selectors.attachmentsPreview),
            footer: panel.querySelector(BackofficeAssistant.#selectors.footer),
        };
    }

    #resolveTranslations() {
        const dataset = this.#elements.panel.dataset;

        return {
            retry: dataset.i18nRetry || 'Retry',
            arguments: dataset.i18nArguments || 'Arguments',
            showResult: dataset.i18nShowResult || 'Show result',
            hideResult: dataset.i18nHideResult || 'Hide result',
            unsupportedFileType: dataset.i18nUnsupportedFileType || 'Unsupported file type: ',
            fileTooLarge: dataset.i18nFileTooLarge || 'File too large (max 5 MB): ',
            failedLoadConversations: dataset.i18nFailedLoadConversations || 'Failed to load conversations.',
            deleteConversation: dataset.i18nDeleteConversation || 'Delete conversation',
            greeting: dataset.i18nGreeting || 'Hello, __USERNAME__! How can I help you today?',
            requestFailed: dataset.i18nRequestFailed || 'Request failed with status ',
            requestInterrupted: dataset.i18nRequestInterrupted || 'Request interrupted.',
            connectionError: dataset.i18nConnectionError || 'Connection error. Please try again.',
            noResponse: dataset.i18nNoResponse || 'No response received.',
            errorPrefix: dataset.i18nErrorPrefix || 'Error: ',
            failedLoadHistory: dataset.i18nFailedLoadHistory || 'Failed to load conversation history.',
            toolResult: dataset.i18nToolResult || 'Tool Result',
        };
    }

    #init() {
        this.#loadAvailableAgents();
        this.#restorePersistedState();
        this.#bindEvents();
    }

    #isPanelOpen() {
        return this.#elements.panel.classList.contains(this.#cssClasses.panelOpen);
    }

    #openPanel() {
        this.#elements.panel.classList.add(this.#cssClasses.panelOpen);
        this.#elements.toggle.hidden = true;
        this.#loadAvailableAgents();
        this.#state.save(true);

        if (!this.#state.greetingShown && !this.#state.conversationReference) {
            this.#showGreeting();
        }
    }

    #closePanel() {
        this.#elements.panel.classList.remove(this.#cssClasses.panelOpen);
        this.#elements.toggle.hidden = false;
        this.#state.save(false);
    }

    #showGreeting() {
        const userName = window.BackofficeAssistantConfig?.userName || 'there';
        this.#state.greetingShown = true;
        this.#renderer.addMessage('ai', this.#i18n.greeting.replace('__USERNAME__', userName));
    }

    async #loadAvailableAgents() {
        try {
            const data = await this.#api.fetchHistories();
            this.#agentBadge.populateSelector(data.available_agents || []);
        } catch {
            // Silently ignore
        }
    }

    #startNewConversation() {
        this.#state.reset();
        this.#agentBadge.reset();
        this.#renderer.clear();
        this.#histories.hide();
        this.#elements.input.value = '';
        this.#elements.input.focus();
        this.#showGreeting();
        this.#state.save(true);
    }

    #setWaiting(waiting) {
        this.#state.isWaiting = waiting;
        this.#elements.input.disabled = waiting;
        this.#elements.attach.disabled = waiting;

        const iconEl = this.#elements.send.querySelector('.fa');

        if (waiting) {
            this.#elements.send.classList.add(this.#cssClasses.sendStop);
            this.#elements.send.disabled = false;

            if (iconEl) {
                iconEl.classList.remove(this.#iconClasses.send);
                iconEl.classList.add(this.#iconClasses.stop);
            }
        } else {
            this.#elements.send.classList.remove(this.#cssClasses.sendStop);
            this.#elements.send.disabled = false;

            if (iconEl) {
                iconEl.classList.remove(this.#iconClasses.stop);
                iconEl.classList.add(this.#iconClasses.send);
            }
        }
    }

    #getBreadcrumb() {
        const breadcrumbEl = document.querySelector('.breadcrumb');

        if (breadcrumbEl) {
            return breadcrumbEl.textContent.trim().replace(/\s+/g, ' ');
        }

        return window.location.pathname;
    }

    #abortCurrentRequest() {
        if (this.#state.abortController) {
            this.#state.abortController.abort();
            this.#state.abortController = null;
        }
    }

    #sendMessage() {
        if (this.#state.isWaiting) {
            this.#abortCurrentRequest();

            return;
        }

        const prompt = this.#elements.input.value.trim();

        if (!prompt) {
            return;
        }

        const attachmentsSnapshot = this.#attachments.takeSnapshot();
        this.#elements.input.value = '';
        this.#doSendMessage(prompt, attachmentsSnapshot);
    }

    async #doSendMessage(prompt, messageAttachments) {
        const bubble = this.#renderer.addMessage('user', prompt);

        if (messageAttachments?.length > 0) {
            this.#renderer.addAttachmentPills(bubble, messageAttachments);
        }

        this.#setWaiting(true);

        const loadingEl = this.#renderer.addLoadingIndicator();
        const body = {
            prompt: prompt,
            context: { current_page: this.#getBreadcrumb() },
            selected_agent: this.#agentBadge.getSelectedAgent(),
        };

        if (this.#state.conversationReference) {
            body.conversation_reference = this.#state.conversationReference;
        }

        if (messageAttachments?.length > 0) {
            body.attachments = messageAttachments.map((a) => ({ content: a.content, mediaType: a.mediaType }));
        }

        this.#state.abortController = new AbortController();
        const signal = this.#state.abortController.signal;

        try {
            const response = await this.#api.sendPrompt(body, signal);

            if (!response.ok) {
                loadingEl.remove();
                this.#renderer.addMessage('ai', this.#i18n.requestFailed + response.status);
                this.#renderer.addRetryButton(() => {
                    this.#doSendMessage(prompt, messageAttachments);
                });
                this.#setWaiting(false);
                this.#elements.input.focus();

                return;
            }

            await this.#readStream(response, prompt, loadingEl, messageAttachments);
        } catch (err) {
            loadingEl.remove();

            if (err.name === 'AbortError') {
                this.#renderer.addMessage('ai', this.#i18n.requestInterrupted);
            } else {
                this.#renderer.addMessage('ai', this.#i18n.connectionError);
                this.#renderer.addRetryButton(() => {
                    this.#doSendMessage(prompt, messageAttachments);
                });
            }
        } finally {
            this.#state.abortController = null;
            this.#setWaiting(false);
            this.#elements.input.focus();
        }
    }

    async #readStream(response, prompt, loadingEl, messageAttachments) {
        const reader = response.body.getReader();
        const decoder = new TextDecoder();
        let receivedEvents = false;

        const parser = new BackofficeAssistantStreamParser((data) => {
            receivedEvents = true;
            this.#handleSseEvent(data, prompt, loadingEl, messageAttachments);
        });

        while (true) {
            const { done, value } = await reader.read();

            if (done) {
                break;
            }

            parser.feed(decoder.decode(value, { stream: true }));
        }

        loadingEl.remove();

        if (!receivedEvents) {
            this.#renderer.addMessage('ai', this.#i18n.noResponse);
            this.#renderer.addRetryButton(() => this.#doSendMessage(prompt, messageAttachments));
        }
    }

    #handleSseEvent(data, prompt, loadingEl, messageAttachments) {
        switch (data.type) {
            case 'agent_selected':
                this.#agentBadge.update(data.agent);

                if (data.conversation_reference) {
                    this.#state.conversationReference = data.conversation_reference;
                    this.#state.save(true);
                }

                break;
            case 'reasoning':
                this.#renderer.addReasoningMessage(data.message);

                break;
            case 'tool_call':
                this.#renderer.addToolCallMessage(data.name, data.arguments, null);

                break;
            case 'tool_call_result':
                this.#renderer.addToolCallMessage(data.name, null, data.result);

                break;
            case 'ai_response':
                loadingEl.remove();
                this.#renderer.addMessage('ai', data.message || '');

                if (data.conversation_reference) {
                    this.#state.conversationReference = data.conversation_reference;
                    this.#state.save(true);
                }

                break;
            case 'error':
                loadingEl.remove();
                this.#renderer.addMessage('ai', this.#i18n.errorPrefix + data.message);
                this.#renderer.addRetryButton(() => {
                    this.#doSendMessage(prompt, messageAttachments);
                });

                break;
            default:
                this.#handleLegacySseEvent(data, prompt, loadingEl, messageAttachments);
        }
    }

    #handleLegacySseEvent(data, prompt, loadingEl, messageAttachments) {
        if (data.error) {
            loadingEl.remove();
            this.#renderer.addMessage('ai', this.#i18n.errorPrefix + data.error);
            this.#renderer.addRetryButton(() => {
                this.#doSendMessage(prompt, messageAttachments);
            });

            return;
        }

        if (data.ai_response) {
            loadingEl.remove();
            this.#state.conversationReference = data.conversation_reference;
            this.#state.save(true);
            this.#renderer.addMessage('ai', data.ai_response);
        }
    }

    async #loadConversationDetail(conversationReference) {
        this.#state.conversationReference = conversationReference;
        this.#state.save(true);
        this.#histories.hide();
        this.#renderer.clear();

        try {
            const data = await this.#api.fetchConversationDetail(conversationReference);

            if (data.agent) {
                this.#agentBadge.update(data.agent);
            }

            this.#agentBadge.setSelectedAgent(data.user_selected_agent);
            this.#renderConversationMessages(data.messages);
            this.#elements.input.focus();
        } catch {
            this.#renderer.addMessage('ai', this.#i18n.failedLoadHistory);
        }
    }

    #renderConversationMessages(messages) {
        const list = Array.isArray(messages) ? messages : [];

        for (let i = 0; i < list.length; i++) {
            const msg = list[i];

            switch (msg.type) {
                case 'user':
                    this.#renderer.addMessage('user', msg.content || '');

                    break;
                case 'tool_call':
                    if (msg.tool_invocations && msg.tool_invocations.length > 0) {
                        for (let j = 0; j < msg.tool_invocations.length; j++) {
                            const inv = msg.tool_invocations[j];
                            this.#renderer.addToolCallMessage(inv.name || 'tool', inv.arguments || null, null);
                        }
                    } else {
                        this.#renderer.addToolCallMessage(msg.content || 'tool', null, null);
                    }

                    break;
                case 'tool_result':
                    if (msg.tool_invocations && msg.tool_invocations.length > 0) {
                        for (let k = 0; k < msg.tool_invocations.length; k++) {
                            const invResult = msg.tool_invocations[k];
                            this.#renderer.addToolCallMessage(
                                invResult.name || this.#i18n.toolResult,
                                invResult.arguments || null,
                                invResult.result || null,
                            );
                        }
                    } else {
                        this.#renderer.addToolCallMessage(this.#i18n.toolResult, null, msg.content || '');
                    }

                    break;
                default:
                    this.#renderer.addMessage('ai', msg.content || '');
            }
        }
    }

    #handleConversationDeleted(conversationReference) {
        if (this.#state.conversationReference === conversationReference) {
            this.#startNewConversation();
            this.#histories.show();
        }
    }

    #restorePersistedState() {
        const savedState = this.#state.load();

        if (!savedState) {
            return;
        }

        if (savedState.conversationReference) {
            this.#state.conversationReference = savedState.conversationReference;
        }

        if (!savedState.isOpen) {
            return;
        }

        this.#elements.panel.classList.add(this.#cssClasses.panelOpen);
        this.#elements.toggle.hidden = true;
        this.#state.greetingShown = true;

        if (this.#state.conversationReference) {
            this.#loadConversationDetail(this.#state.conversationReference);

            return;
        }

        this.#showGreeting();
    }

    #bindEvents() {
        this.#elements.toggle.addEventListener('click', () => {
            if (this.#isPanelOpen()) {
                this.#closePanel();
            } else {
                this.#openPanel();
            }
        });

        this.#elements.close.addEventListener('click', () => {
            this.#closePanel();
        });

        this.#elements.historyBtn.addEventListener('click', () => {
            this.#histories.show();
        });

        this.#elements.newChat.addEventListener('click', () => {
            this.#startNewConversation();
        });

        this.#elements.send.addEventListener('click', () => {
            this.#sendMessage();
        });

        if (this.#elements.agentSelect) {
            this.#elements.agentSelect.addEventListener('change', () => {
                if (this.#elements.agentSelect.value) {
                    this.#agentBadge.update(this.#elements.agentSelect.value);
                }
            });
        }

        this.#elements.attach.addEventListener('click', () => {
            this.#elements.fileInput.click();
        });

        this.#elements.fileInput.addEventListener('change', (event) => {
            this.#attachments.handleFileSelect(event.target.files);
        });

        this.#elements.input.addEventListener('keydown', (event) => {
            if (event.key === 'Enter' && !event.shiftKey) {
                event.preventDefault();
                this.#sendMessage();
            }
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && this.#isPanelOpen()) {
                this.#closePanel();
            }
        });
    }
}
