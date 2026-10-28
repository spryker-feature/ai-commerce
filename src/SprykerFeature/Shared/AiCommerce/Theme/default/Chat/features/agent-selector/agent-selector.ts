import type { ChatFeature, ChatFeatureContext } from '@shared/ai-commerce/Chat/core/contracts/context';
import type { ChatBlockTemplate } from '@shared/ai-commerce/Chat/core/contracts/blocks';
import type { ChatStreamEvent } from '@shared/ai-commerce/Chat/core/contracts/stream';
import { STREAM_DATA_EVENT_PREFIX, STREAM_EVENT_TYPE } from '@shared/ai-commerce/Chat/core/contracts/stream';
import { announceFeature } from '@shared/ai-commerce/Chat/core/contracts/access';
import { CORE_ZONE } from '@shared/ai-commerce/Chat/core/contracts/blocks';
import { ChatSessionExpiredError } from '@shared/ai-commerce/Chat/core/contracts/api';
import { API } from '@shared/ai-commerce/Chat/core/contracts/tokens';

const SOURCE_FILE = 'Shared/AiCommerce/Theme/default/Chat/features/agent-selector/agent-selector.ts';
const AGENTS_ATTRIBUTE = 'agents';
const STORAGE_KEY_ATTRIBUTE = 'storage-key';
const LOAD_AGENTS_ATTRIBUTE = 'load-agents';
const AUTO_LABEL_ATTRIBUTE = 'data-auto-label';
const AUTO_DESCRIPTION_ATTRIBUTE = 'data-auto-description';
const ACTIVE_ATTRIBUTE = 'data-active';
const CHANGED_ATTRIBUTE = 'data-changed';
const VALUE_ATTRIBUTE = 'data-value';
const AGENT_DATA_EVENT = `${STREAM_DATA_EVENT_PREFIX}agent` as const;
const AUTO_VALUE = '';
const KEY = {
    escape: 'Escape',
    tab: 'Tab',
    arrowDown: 'ArrowDown',
    arrowUp: 'ArrowUp',
    home: 'Home',
    end: 'End',
} as const;

const PICKER_BLOCK = { name: 'agentSelectorPicker', references: ['toggle', 'label', 'menu'] } as const;
const OPTION_BLOCK = { name: 'agentSelectorOption', references: ['label', 'description'] } as const;

type PickerReference = (typeof PICKER_BLOCK.references)[number];

interface ChatAgent {
    readonly label: string;
    readonly description: string;
}

interface StoredAgents {
    readonly conversationReference: string;
    readonly selectedAgent: string;
    readonly activeAgent: string;
}

let isStorageFailureReported = false;

const reportStorageFailure = (storageKey: string, operation: string, error: unknown): void => {
    if (isStorageFailureReported) {
        return;
    }

    isStorageFailureReported = true;
    reportError(
        new Error(
            `<chat-agent-selector> could not ${operation} localStorage under "${storageKey}" (reported by ` +
                `${SOURCE_FILE}); the picked agent is not kept for a reloaded conversation. Check whether the browser ` +
                `blocks or filled the site storage, or remove a broken value under that key; reported once per page.`,
            { cause: error },
        ),
    );
};

const isRecord = (value: unknown): value is Record<string, unknown> =>
    typeof value === 'object' && value !== null && !Array.isArray(value);

const readString = (record: Record<string, unknown>, field: string): string =>
    typeof record[field] === 'string' ? record[field] : '';

const humanizeAgentName = (name: string): string => {
    const words = name.replace(/[_-]+/g, ' ').trim();

    return words === '' ? name : words.replace(/\b\w/g, (character: string) => character.toUpperCase());
};

export class ChatAgentSelector extends HTMLElement implements ChatFeature {
    private readonly agents = new Map<string, ChatAgent>();
    private picker: HTMLElement | null = null;
    private references: Readonly<Record<PickerReference, HTMLElement>> | null = null;
    private optionTemplate: ChatBlockTemplate<'label' | 'description'> | null = null;
    private stored: StoredAgents | null = null;
    private selectedAgent = AUTO_VALUE;
    private activeAgent = '';
    private conversationReference = '';
    // Bumped by every agent load and by dispose(), so a load that finishes late is ignored.
    private agentLoad = 0;
    private isLoadingAgents = false;
    private isAgentLoadRequested = false;

    private readonly closeOnOutsideClick = (event: MouseEvent): void => {
        if (!(event.target instanceof Node) || this.picker === null || !this.picker.contains(event.target)) {
            this.closeMenu(false);
        }
    };

    connectedCallback(): void {
        announceFeature(this);
    }

    initialize(context: ChatFeatureContext): void {
        const pickerBlock = context.blocks.require(PICKER_BLOCK).create();

        this.optionTemplate = context.blocks.require(OPTION_BLOCK);
        this.picker = pickerBlock.root;
        this.references = pickerBlock.references;
        this.readAgents();
        this.stored = this.readStored();
        this.conversationReference = context.state.conversationReference;
        this.renderOptions();
        this.bindMenu(pickerBlock.references);
        this.render(false);

        context.conversation.contribute(() => ({
            selectedAgent: this.selectedAgent === AUTO_VALUE ? null : this.selectedAgent,
        }));
        context.events.on('stream', (event: ChatStreamEvent) => this.handleStreamEvent(event));
        context.events.on('turn:end', () => {
            this.conversationReference = context.state.conversationReference;
            this.store();
        });
        context.events.on('conversation:reset', (reset) => {
            this.conversationReference = reset.conversationReference;
            this.applySelection(AUTO_VALUE, '');
        });
        context.events.on('conversation:loaded', (loaded) => this.restoreFor(loaded.conversationReference));
        context.zones.insert(CORE_ZONE.headerStart, pickerBlock.root);

        if (this.hasAttribute(LOAD_AGENTS_ATTRIBUTE)) {
            this.isLoadingAgents = true;
            this.isAgentLoadRequested = false;
            context.events.on('panel:toggle', (toggle) => {
                if (toggle.isOpen && !this.isAgentLoadRequested) {
                    this.isAgentLoadRequested = true;
                    this.loadAgents(context).catch((error: unknown) => reportError(error));
                }
            });
        }
    }

    dispose(): void {
        this.closeMenu(false);
        this.agents.clear();
        this.picker = null;
        this.references = null;
        this.optionTemplate = null;
        this.stored = null;
        this.selectedAgent = AUTO_VALUE;
        this.activeAgent = '';
        this.agentLoad += 1;
        this.isLoadingAgents = false;
        this.isAgentLoadRequested = false;
    }

    private handleStreamEvent(event: ChatStreamEvent): void {
        if (event.type === STREAM_EVENT_TYPE.start) {
            const agent = event.messageMetadata?.agent;

            if (typeof agent === 'string' && agent !== '') {
                this.setActiveAgent(agent);
            }

            return;
        }

        if (event.type === AGENT_DATA_EVENT && 'data' in event && isRecord(event.data)) {
            this.setActiveAgent(readString(event.data, 'name'));
        }
    }

    private setActiveAgent(agent: string): void {
        const hasChanged = agent !== this.activeAgent;

        this.activeAgent = agent;
        this.render(hasChanged);
        this.store();
    }

    private restoreFor(conversationReference: string): void {
        const stored = this.stored;
        const isSameConversation = conversationReference === this.conversationReference;

        this.conversationReference = conversationReference;

        if (stored !== null && stored.conversationReference === conversationReference) {
            this.applySelection(stored.selectedAgent, stored.activeAgent);
        } else if (!isSameConversation) {
            this.applySelection(AUTO_VALUE, '');
        }
    }

    private applySelection(selectedAgent: string, activeAgent: string, shouldStore: boolean = true): void {
        this.selectedAgent = this.agents.has(selectedAgent) || this.isLoadingAgents ? selectedAgent : AUTO_VALUE;
        this.activeAgent = activeAgent;
        this.render(false);

        if (shouldStore) {
            this.store();
        }
    }

    private select(value: string): void {
        if (value !== this.selectedAgent) {
            this.applySelection(value, this.activeAgent);
        }
    }

    private labelFor(agent: string): string {
        return this.agents.get(agent)?.label ?? humanizeAgentName(agent);
    }

    private render(isChanged: boolean): void {
        const picker = this.picker;
        const references = this.references;

        if (picker === null || references === null) {
            return;
        }

        const shownAgent = this.selectedAgent !== AUTO_VALUE ? this.selectedAgent : this.activeAgent;
        const previousLabel = references.label.textContent;

        references.label.textContent =
            shownAgent === '' ? (picker.getAttribute(AUTO_LABEL_ATTRIBUTE) ?? '') : this.labelFor(shownAgent);
        references.toggle.title =
            this.agents.get(shownAgent)?.description || (picker.getAttribute(AUTO_DESCRIPTION_ATTRIBUTE) ?? '');
        references.toggle.toggleAttribute(ACTIVE_ATTRIBUTE, shownAgent !== '');
        picker.hidden = this.agents.size === 0 && shownAgent === '';

        for (const option of this.options()) {
            option.setAttribute('aria-checked', String(option.getAttribute(VALUE_ATTRIBUTE) === this.selectedAgent));
        }

        // Restarting the animation needs a style flush between removing and setting the attribute.
        references.label.removeAttribute(CHANGED_ATTRIBUTE);

        if (isChanged && references.label.textContent !== previousLabel) {
            references.label.getBoundingClientRect();
            references.label.setAttribute(CHANGED_ATTRIBUTE, '');
        }
    }

    private renderOptions(): void {
        const picker = this.picker;
        const references = this.references;
        const template = this.optionTemplate;

        if (picker === null || references === null || template === null) {
            return;
        }

        const createOption = (value: string, label: string, description: string): HTMLElement => {
            const option = template.create();

            option.root.setAttribute(VALUE_ATTRIBUTE, value);
            option.references.label.textContent = label;
            option.references.description.textContent = description;
            option.references.description.title = description;
            option.references.description.hidden = description === '';
            option.root.addEventListener('click', () => {
                this.select(value);
                this.closeMenu(true);
            });

            return option.root;
        };

        references.menu.replaceChildren(
            createOption(
                AUTO_VALUE,
                picker.getAttribute(AUTO_LABEL_ATTRIBUTE) ?? '',
                picker.getAttribute(AUTO_DESCRIPTION_ATTRIBUTE) ?? '',
            ),
            ...[...this.agents].map(([name, agent]) => createOption(name, agent.label, agent.description)),
        );
    }

    private bindMenu(references: Readonly<Record<PickerReference, HTMLElement>>): void {
        references.menu.hidden = true;
        references.toggle.setAttribute('aria-expanded', 'false');
        references.toggle.addEventListener('click', () =>
            references.menu.hidden ? this.openMenu(false) : this.closeMenu(false),
        );
        references.toggle.addEventListener('keydown', (event: KeyboardEvent) => {
            if (event.key === KEY.arrowDown) {
                event.preventDefault();
                this.openMenu(true);
            } else if (event.key === KEY.escape && !references.menu.hidden) {
                this.consumeEscape(event);
            }
        });
        references.menu.addEventListener('keydown', (event: KeyboardEvent) => this.handleMenuKey(event));
    }

    private handleMenuKey(event: KeyboardEvent): void {
        if (event.key === KEY.escape) {
            this.consumeEscape(event);

            return;
        }

        if (event.key === KEY.tab) {
            this.closeMenu(false);

            return;
        }

        const options = this.options();
        const current = options.indexOf(document.activeElement as HTMLElement);
        const nextIndex: Record<string, number> = {
            [KEY.arrowDown]: (current + 1) % options.length,
            [KEY.arrowUp]: (current - 1 + options.length) % options.length,
            [KEY.home]: 0,
            [KEY.end]: options.length - 1,
        };

        if (options.length > 0 && event.key in nextIndex) {
            event.preventDefault();
            options[nextIndex[event.key]]?.focus();
        }
    }

    private consumeEscape(event: KeyboardEvent): void {
        event.preventDefault();
        event.stopPropagation();
        this.closeMenu(true);
    }

    private openMenu(shouldFocusFirstOption: boolean): void {
        const references = this.references;

        if (references === null) {
            return;
        }

        references.menu.hidden = false;
        references.toggle.setAttribute('aria-expanded', 'true');
        document.addEventListener('click', this.closeOnOutsideClick, true);

        if (shouldFocusFirstOption) {
            this.options()[0]?.focus();
        }
    }

    private closeMenu(shouldRestoreFocus: boolean): void {
        document.removeEventListener('click', this.closeOnOutsideClick, true);

        const references = this.references;

        if (references === null || references.menu.hidden) {
            return;
        }

        references.menu.hidden = true;
        references.toggle.setAttribute('aria-expanded', 'false');

        if (shouldRestoreFocus) {
            references.toggle.focus();
        }
    }

    private options(): HTMLElement[] {
        return Array.from(this.references?.menu.querySelectorAll<HTMLElement>(`[${VALUE_ATTRIBUTE}]`) ?? []);
    }

    private readAgents(): void {
        const value = this.getAttribute(AGENTS_ATTRIBUTE);
        let parsed: unknown;

        this.agents.clear();

        if (value === null) {
            return;
        }

        if (value.trim() === '') {
            reportError(
                new Error(
                    `<chat-agent-selector> has an empty "${AGENTS_ATTRIBUTE}" attribute (reported by ${SOURCE_FILE}); ` +
                        `only "Auto" is offered. Pass features.agentSelector.agents as a list (the feature twig ` +
                        `encodes it), or leave the attribute out when the agents are loaded.`,
                ),
            );

            return;
        }

        try {
            parsed = JSON.parse(value);
        } catch (error) {
            reportError(
                new Error(
                    `<chat-agent-selector> has an "${AGENTS_ATTRIBUTE}" attribute that is not JSON (reported by ` +
                        `${SOURCE_FILE}); only "Auto" is offered. Pass features.agentSelector.agents as the JSON ` +
                        `list of { name, label, description } the configuration produces.`,
                    { cause: error },
                ),
            );

            return;
        }

        if (!Array.isArray(parsed)) {
            reportError(
                new Error(
                    `<chat-agent-selector> has an "${AGENTS_ATTRIBUTE}" attribute that is JSON but not a list (reported ` +
                        `by ${SOURCE_FILE}); only "Auto" is offered. Pass features.agentSelector.agents as the JSON ` +
                        `list of { name, label, description } the configuration produces, not an object.`,
                ),
            );

            return;
        }

        this.addAgents(parsed);
    }

    // The first entry of a name wins: configured agents keep their labels over loaded ones.
    private addAgents(agents: readonly unknown[]): void {
        for (const agent of agents) {
            const name = isRecord(agent) ? readString(agent, 'name') : '';

            if (isRecord(agent) && name !== '' && !this.agents.has(name)) {
                this.agents.set(name, {
                    label: readString(agent, 'label') || humanizeAgentName(name),
                    description: readString(agent, 'description'),
                });
            }
        }
    }

    private async loadAgents(context: ChatFeatureContext): Promise<void> {
        this.agentLoad += 1;

        const load = this.agentLoad;
        let availableAgents: unknown;

        try {
            availableAgents = (await context.services.get(API).conversations()).availableAgents;
        } catch (error) {
            if (load === this.agentLoad) {
                this.finishAgentLoad([]);
            }

            if (!(error instanceof ChatSessionExpiredError)) {
                reportError(
                    new Error(
                        `<chat-agent-selector> has "${LOAD_AGENTS_ATTRIBUTE}" but could not load the agents through ` +
                            `ChatApi.conversations() (reported by ${SOURCE_FILE}); only "Auto" and the configured ` +
                            `agents are offered. Check the conversations request in the network panel.`,
                        { cause: error },
                    ),
                );
            }

            return;
        }

        if (availableAgents !== undefined && !Array.isArray(availableAgents)) {
            reportError(
                new Error(
                    `<chat-agent-selector> has "${LOAD_AGENTS_ATTRIBUTE}", but ChatApi.conversations() returned ` +
                        `"availableAgents" that is not a list (reported by ${SOURCE_FILE}); only "Auto" and the ` +
                        `configured agents are offered. Check the conversations response in the network panel and ` +
                        `the ChatApi that maps it.`,
                ),
            );
        }

        if (load === this.agentLoad) {
            this.finishAgentLoad(Array.isArray(availableAgents) ? availableAgents : []);
        }
    }

    // Not stored here: this runs before conversation:loaded restores the stored selection and would overwrite it.
    private finishAgentLoad(agents: readonly unknown[]): void {
        this.isLoadingAgents = false;
        this.addAgents(agents);
        this.renderOptions();
        this.applySelection(this.selectedAgent, this.activeAgent, false);
    }

    private storageKey(): string {
        return this.getAttribute(STORAGE_KEY_ATTRIBUTE) ?? '';
    }

    private readStored(): StoredAgents | null {
        let stored: unknown;

        try {
            stored = this.storageKey() === '' ? null : JSON.parse(localStorage.getItem(this.storageKey()) ?? 'null');
        } catch (error) {
            reportStorageFailure(this.storageKey(), 'read', error);

            return null;
        }

        return isRecord(stored)
            ? {
                  conversationReference: readString(stored, 'conversationReference'),
                  selectedAgent: readString(stored, 'selectedAgent'),
                  activeAgent: readString(stored, 'activeAgent'),
              }
            : null;
    }

    private store(): void {
        if (this.storageKey() === '') {
            return;
        }

        this.stored = {
            conversationReference: this.conversationReference,
            selectedAgent: this.selectedAgent,
            activeAgent: this.activeAgent,
        };

        try {
            localStorage.setItem(this.storageKey(), JSON.stringify(this.stored));
        } catch (error) {
            reportStorageFailure(this.storageKey(), 'write', error);
        }
    }
}
