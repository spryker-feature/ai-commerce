export class BackofficeAssistantAgentBadge {
    #badgeEl;
    #selectEl;
    #cssClasses;
    #pendingSelectedAgent = null;
    #agentDescriptions = new Map();

    constructor(badgeEl, selectEl, cssClasses) {
        if (!badgeEl || !selectEl) {
            throw new Error('BackofficeAssistantAgentBadge: required elements not found');
        }

        this.#badgeEl = badgeEl;
        this.#selectEl = selectEl;
        this.#cssClasses = cssClasses;
    }

    update(agentName) {
        const previousName = this.#badgeEl.textContent;
        this.#badgeEl.textContent = agentName;
        this.#badgeEl.title = this.#agentDescriptions.get(agentName) ?? '';
        this.#badgeEl.classList.remove(this.#cssClasses.badgeAnimate);

        if (agentName !== previousName) {
            void this.#badgeEl.offsetWidth;
            this.#badgeEl.classList.add(this.#cssClasses.badgeAnimate);
        }
    }

    reset() {
        this.#badgeEl.textContent = '';
        this.#badgeEl.title = '';
        this.#badgeEl.classList.remove(this.#cssClasses.badgeAnimate);
        this.#pendingSelectedAgent = null;
        this.#selectEl.value = '';
    }

    populateSelector(agents) {
        while (this.#selectEl.options.length > 1) {
            this.#selectEl.remove(1);
        }

        this.#agentDescriptions.clear();

        for (const agent of agents) {
            const name = agent.name ?? agent;
            const description = agent.description ?? '';

            this.#agentDescriptions.set(name, description);

            const opt = document.createElement('option');
            opt.value = name;
            opt.textContent = name;
            opt.title = description;
            this.#selectEl.appendChild(opt);
        }

        if (this.#pendingSelectedAgent) {
            this.#selectEl.value = this.#pendingSelectedAgent;
            this.#pendingSelectedAgent = null;
        }
    }

    getSelectedAgent() {
        return this.#selectEl.value;
    }

    setSelectedAgent(value) {
        if (this.#selectEl.options.length > 1) {
            this.#selectEl.value = value || '';
        } else {
            // Options not yet populated — defer until populateSelector runs
            this.#pendingSelectedAgent = value || null;
        }
    }
}
