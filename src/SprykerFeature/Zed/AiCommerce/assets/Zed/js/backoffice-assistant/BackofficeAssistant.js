import { SELECTORS } from './constants';
import { BackofficeAssistantState } from './BackofficeAssistantState';
import { BackofficeAssistantApi } from './BackofficeAssistantApi';
import { BackofficeAssistantStreamParser } from './BackofficeAssistantStreamParser';
import { BackofficeAssistantMessageRenderer } from './BackofficeAssistantMessageRenderer';
import { BackofficeAssistantAttachmentManager } from './BackofficeAssistantAttachmentManager';
import { BackofficeAssistantAgentBadge } from './BackofficeAssistantAgentBadge';
import { BackofficeAssistantHistories } from './BackofficeAssistantHistories';

export class BackofficeAssistant {
    #elements;
    #state;
    #api;
    #i18n;
    #renderer;
    #agentBadge;
    #attachments;
    #histories;

    constructor() {
        this.#elements = this.#resolveElements();

        if (!this.#elements) {
            return;
        }

        this.#state = new BackofficeAssistantState();
        this.#api = new BackofficeAssistantApi();
        this.#i18n = this.#resolveTranslations();
        this.#renderer = new BackofficeAssistantMessageRenderer(
            this.#elements.messages,
            this.#elements.panel,
            this.#i18n,
        );
        this.#agentBadge = new BackofficeAssistantAgentBadge(this.#elements.agentBadge, this.#elements.agentSelect);
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
        );

        this.#init();
    }

    #resolveElements() {
        const toggle = document.querySelector(SELECTORS.toggle);
        const panel = document.querySelector(SELECTORS.panel);

        if (!toggle || !panel) {
            return null;
        }

        return {
            toggle: toggle,
            panel: panel,
            close: panel.querySelector(SELECTORS.close),
            historyBtn: panel.querySelector(SELECTORS.historyBtn),
            newChat: panel.querySelector(SELECTORS.newChat),
            messages: panel.querySelector(SELECTORS.messages),
            histories: panel.querySelector(SELECTORS.histories),
            historiesList: panel.querySelector(SELECTORS.historiesList),
            historiesEmpty: panel.querySelector(SELECTORS.historiesEmpty),
            input: panel.querySelector(SELECTORS.input),
            send: panel.querySelector(SELECTORS.send),
            agentBadge: panel.querySelector(SELECTORS.agentBadge),
            agentSelect: panel.querySelector(SELECTORS.agentSelect),
            attach: panel.querySelector(SELECTORS.attach),
            fileInput: panel.querySelector(SELECTORS.fileInput),
            attachmentsPreview: panel.querySelector(SELECTORS.attachmentsPreview),
            footer: panel.querySelector(SELECTORS.footer),
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
        return this.#elements.panel.classList.contains('backoffice-assistant__panel--open');
    }

    #openPanel() {
        this.#elements.panel.classList.add('backoffice-assistant__panel--open');
        this.#elements.toggle.hidden = true;
        this.#loadAvailableAgents();
        this.#state.save(true);

        if (!this.#state.greetingShown && !this.#state.conversationReference) {
            this.#showGreeting();
        }
    }

    #closePanel() {
        this.#elements.panel.classList.remove('backoffice-assistant__panel--open');
        this.#elements.toggle.hidden = false;
        this.#state.save(false);
    }

    #showGreeting() {
        const userName = window.BackofficeAssistantConfig?.userName || 'there';
        this.#state.greetingShown = true;
        this.#renderer.addMessage('ai', this.#i18n.greeting.replace('__USERNAME__', userName));
    }

    #loadAvailableAgents() {
        this.#api
            .fetchHistories()
            .then((data) => {
                this.#agentBadge.populateSelector(data.available_agents || []);
            })
            .catch(() => {
                // Silently fail
            });
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
            this.#elements.send.classList.add('backoffice-assistant__send--stop');
            this.#elements.send.disabled = false;

            if (iconEl) {
                iconEl.classList.remove('fa-paper-plane');
                iconEl.classList.add('fa-stop');
            }
        } else {
            this.#elements.send.classList.remove('backoffice-assistant__send--stop');
            this.#elements.send.disabled = false;

            if (iconEl) {
                iconEl.classList.remove('fa-stop');
                iconEl.classList.add('fa-paper-plane');
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
        this.#resizeInput();
        this.#doSendMessage(prompt, attachmentsSnapshot);
    }

    #doSendMessage(prompt, messageAttachments) {
        const bubble = this.#renderer.addMessage('user', prompt);

        if (messageAttachments && messageAttachments.length > 0) {
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

        if (messageAttachments && messageAttachments.length > 0) {
            body.attachments = messageAttachments.map((a) => ({ content: a.content, mediaType: a.mediaType }));
        }

        this.#state.abortController = new AbortController();
        const signal = this.#state.abortController.signal;

        this.#api
            .sendPrompt(body, signal)
            .then((response) => {
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

                return this.#readStream(response, prompt, loadingEl, messageAttachments);
            })
            .catch((err) => {
                loadingEl.remove();

                if (err.name === 'AbortError') {
                    this.#renderer.addMessage('ai', this.#i18n.requestInterrupted);

                    return;
                }

                this.#renderer.addMessage('ai', this.#i18n.connectionError);
                this.#renderer.addRetryButton(() => {
                    this.#doSendMessage(prompt, messageAttachments);
                });
            })
            .finally(() => {
                this.#state.abortController = null;
                this.#setWaiting(false);
                this.#elements.input.focus();
            });
    }

    #readStream(response, prompt, loadingEl, messageAttachments) {
        const reader = response.body.getReader();
        const decoder = new TextDecoder();
        let receivedEvents = false;

        const parser = new BackofficeAssistantStreamParser((data) => {
            receivedEvents = true;
            this.#handleSseEvent(data, prompt, loadingEl, messageAttachments);
        });

        const readChunk = () => {
            return reader.read().then((result) => {
                if (result.done) {
                    loadingEl.remove();

                    if (!receivedEvents) {
                        this.#renderer.addMessage('ai', this.#i18n.noResponse);
                        this.#renderer.addRetryButton(() => {
                            this.#doSendMessage(prompt, messageAttachments);
                        });
                    }

                    return;
                }

                parser.feed(decoder.decode(result.value, { stream: true }));

                return readChunk();
            });
        };

        return readChunk();
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

    #loadConversationDetail(conversationReference) {
        this.#state.conversationReference = conversationReference;
        this.#state.save(true);
        this.#histories.hide();
        this.#renderer.clear();

        this.#api
            .fetchConversationDetail(conversationReference)
            .then((data) => {
                if (data.agent) {
                    this.#agentBadge.update(data.agent);
                }

                this.#agentBadge.setSelectedAgent(data.user_selected_agent);
                this.#renderConversationMessages(data.messages);
                this.#elements.input.focus();
            })
            .catch(() => {
                this.#renderer.addMessage('ai', this.#i18n.failedLoadHistory);
            });
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

    #resizeInput() {
        this.#elements.input.style.height = 'auto';
        this.#elements.input.style.height = Math.min(this.#elements.input.scrollHeight, 120) + 'px';
    }

    #restorePersistedState() {
        const savedState = this.#state.load();

        if (!savedState) {
            return;
        }

        if (savedState.conversationReference) {
            this.#state.conversationReference = savedState.conversationReference;
        }

        if (savedState.isOpen) {
            this.#elements.panel.classList.add('backoffice-assistant__panel--open');
            this.#elements.toggle.hidden = true;
            this.#state.greetingShown = true;

            if (this.#state.conversationReference) {
                this.#loadConversationDetail(this.#state.conversationReference);
            } else {
                this.#showGreeting();
            }
        }
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

        this.#elements.input.addEventListener('input', () => {
            this.#resizeInput();
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && this.#isPanelOpen()) {
                this.#closePanel();
            }
        });
    }
}
