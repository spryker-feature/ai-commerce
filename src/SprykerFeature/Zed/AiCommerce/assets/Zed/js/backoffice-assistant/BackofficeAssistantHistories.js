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
    #cssClasses;

    constructor(elements, api, onSelect, onDeleteCurrent, panelEl, i18n, cssClasses) {
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
        this.#cssClasses = cssClasses;
    }

    show() {
        this.#historiesEl.hidden = false;
        this.#messagesEl.classList.add(this.#cssClasses.messagesHidden);
        this.#footerEl.hidden = true;
        this.#inputEl.disabled = true;
        this.#sendBtn.disabled = true;
        this.#load();
    }

    hide() {
        this.#historiesEl.hidden = true;
        this.#messagesEl.classList.remove(this.#cssClasses.messagesHidden);
        this.#footerEl.hidden = false;
        this.#inputEl.disabled = false;
        this.#sendBtn.disabled = false;
    }

    async #load() {
        try {
            const data = await this.#api.fetchHistories();
            const histories = data.histories || [];

            this.#historiesList.innerHTML = '';

            if (histories.length === 0) {
                this.#historiesEmpty.hidden = false;

                return;
            }

            this.#historiesEmpty.hidden = true;

            for (const entry of histories) {
                this.#historiesList.appendChild(this.#createHistoryItem(entry));
            }
        } catch {
            this.#historiesEmpty.hidden = false;
            this.#historiesEmpty.textContent = this.#i18n.failedLoadConversations;
        }
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

    async #deleteItem(conversationReference, listItem) {
        listItem.classList.add(this.#cssClasses.itemDeleting);

        try {
            await this.#api.deleteConversation(conversationReference);

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

            listItem.classList.add(this.#cssClasses.itemDeleted);
        } catch {
            listItem.classList.remove(this.#cssClasses.itemDeleting);
        }
    }
}
