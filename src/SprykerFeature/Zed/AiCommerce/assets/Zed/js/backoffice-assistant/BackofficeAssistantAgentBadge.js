export class BackofficeAssistantAgentBadge {
    #badgeEl;
    #selectEl;
    #pendingSelectedAgent = null;

    constructor(badgeEl, selectEl) {
        this.#badgeEl = badgeEl;
        this.#selectEl = selectEl;
    }

    update(agentName) {
        const previousName = this.#badgeEl.textContent;
        this.#badgeEl.textContent = agentName;
        this.#badgeEl.classList.remove('backoffice-assistant__agent-badge--animate');

        if (agentName !== previousName) {
            void this.#badgeEl.offsetWidth;
            this.#badgeEl.classList.add('backoffice-assistant__agent-badge--animate');
        }
    }

    reset() {
        this.#badgeEl.textContent = '';
        this.#badgeEl.classList.remove('backoffice-assistant__agent-badge--animate');
        this.#pendingSelectedAgent = null;

        if (this.#selectEl) {
            this.#selectEl.value = '';
        }
    }

    populateSelector(agentNames) {
        if (!this.#selectEl) {
            return;
        }

        while (this.#selectEl.options.length > 1) {
            this.#selectEl.remove(1);
        }

        agentNames.forEach((name) => {
            const opt = document.createElement('option');
            opt.value = name;
            opt.textContent = name;
            this.#selectEl.appendChild(opt);
        });

        if (this.#pendingSelectedAgent) {
            this.#selectEl.value = this.#pendingSelectedAgent;
            this.#pendingSelectedAgent = null;
        }
    }

    getSelectedAgent() {
        return this.#selectEl ? this.#selectEl.value : '';
    }

    setSelectedAgent(value) {
        if (!this.#selectEl) {
            return;
        }

        if (this.#selectEl.options.length > 1) {
            this.#selectEl.value = value || '';
        } else {
            // Options not yet populated — defer until populateSelector runs
            this.#pendingSelectedAgent = value || null;
        }
    }
}
