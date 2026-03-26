export class BackofficeAssistantHistories {
    #historiesEl;
    #historiesList;
    #historiesEmpty;
    #messagesEl;
    #footerEl;
    #inputEl;
    #sendBtn;
    #api;
    #onSelect;
    #onDeleteCurrent;
    #i18n;
    #historyItemTemplate;

    constructor(elements, api, onSelect, onDeleteCurrent, panelEl, i18n) {
        this.#historiesEl = elements.histories;
        this.#historiesList = elements.historiesList;
        this.#historiesEmpty = elements.historiesEmpty;
        this.#messagesEl = elements.messages;
        this.#footerEl = elements.footer;
        this.#inputEl = elements.input;
        this.#sendBtn = elements.send;
        this.#api = api;
        this.#onSelect = onSelect;
        this.#onDeleteCurrent = onDeleteCurrent;
        this.#i18n = i18n;
        this.#historyItemTemplate = panelEl.querySelector('[data-id="backoffice-assistant-history-item"]');
    }

    show() {
        this.#historiesEl.hidden = false;
        this.#messagesEl.classList.add('backoffice-assistant__messages--hidden');
        this.#footerEl.hidden = true;
        this.#inputEl.disabled = true;
        this.#sendBtn.disabled = true;
        this.#load();
    }

    hide() {
        this.#historiesEl.hidden = true;
        this.#messagesEl.classList.remove('backoffice-assistant__messages--hidden');
        this.#footerEl.hidden = false;
        this.#inputEl.disabled = false;
        this.#sendBtn.disabled = false;
    }

    #load() {
        this.#api
            .fetchHistories()
            .then((data) => {
                const histories = data.histories || [];

                this.#historiesList.innerHTML = '';

                if (histories.length === 0) {
                    this.#historiesEmpty.hidden = false;

                    return;
                }

                this.#historiesEmpty.hidden = true;
                histories.forEach((entry) => {
                    this.#historiesList.appendChild(this.#createHistoryItem(entry));
                });
            })
            .catch(() => {
                this.#historiesEmpty.hidden = false;
                this.#historiesEmpty.textContent = this.#i18n.failedLoadConversations;
            });
    }

    #createHistoryItem(entry) {
        const fragment = this.#historyItemTemplate.content.cloneNode(true);
        const li = fragment.firstElementChild;

        const nameSpan = li.querySelector('.backoffice-assistant__histories-item-name');
        nameSpan.textContent = entry.name || entry.conversation_reference;

        const agentSpan = li.querySelector('.backoffice-assistant__histories-item-agent');

        if (entry.agent) {
            agentSpan.textContent = entry.agent;
        } else {
            agentSpan.remove();
        }

        const deleteBtn = li.querySelector('.backoffice-assistant__histories-item-delete');
        deleteBtn.title = this.#i18n.deleteConversation;
        deleteBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            this.#deleteItem(entry.conversation_reference, li);
        });

        li.addEventListener('click', () => {
            this.#onSelect(entry.conversation_reference);
        });

        return li;
    }

    #deleteItem(conversationReference, listItem) {
        listItem.style.pointerEvents = 'none';
        listItem.classList.add('backoffice-assistant__histories-item--deleting');

        this.#api
            .deleteConversation(conversationReference)
            .then(() => {
                listItem.addEventListener(
                    'transitionend',
                    () => {
                        listItem.remove();

                        if (this.#historiesList.children.length === 0) {
                            this.#historiesEmpty.hidden = false;
                        }

                        this.#onDeleteCurrent(conversationReference);
                    },
                    { once: true },
                );

                listItem.classList.add('backoffice-assistant__histories-item--deleted');
            })
            .catch(() => {
                listItem.style.pointerEvents = '';
                listItem.classList.remove('backoffice-assistant__histories-item--deleting');
            });
    }
}
