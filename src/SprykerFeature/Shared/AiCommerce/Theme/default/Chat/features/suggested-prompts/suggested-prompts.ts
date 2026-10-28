import type { ChatFeature, ChatFeatureContext } from '@shared/ai-commerce/Chat/core/contracts/context';
import type { ChatBlockTemplate } from '@shared/ai-commerce/Chat/core/contracts/blocks';
import { announceFeature } from '@shared/ai-commerce/Chat/core/contracts/access';
import { CORE_ZONE } from '@shared/ai-commerce/Chat/core/contracts/blocks';
import { REQUEST_LOCK } from '@shared/ai-commerce/Chat/core/contracts/tokens';

export const SUGGESTED_PROMPTS_TAG_NAME = 'chat-suggested-prompts';

const SOURCE_FILE = 'Shared/AiCommerce/Theme/default/Chat/features/suggested-prompts/suggested-prompts.ts';
const PROMPTS_ATTRIBUTE = 'prompts';
const SECTIONS_ATTRIBUTE = 'sections';
const GROUP_ATTRIBUTE = 'group';
const DEFAULT_GROUP = 'default';
const PLACEHOLDER_PATTERN = /\{(\w+)\}/g;

const RAIL_BLOCK = { name: 'suggestedPrompts', references: [] } as const;
const SECTION_BLOCK = { name: 'suggestedPromptSection', references: ['title', 'list'] } as const;
const PROMPT_BLOCK = { name: 'suggestedPrompt', references: ['text'] } as const;

export type ChatSuggestedPromptTable = Readonly<Record<string, readonly string[]>>;
export type ChatPromptValues = Readonly<Record<string, string>>;

export interface ChatSuggestedPromptSection {
    readonly title: string;
    readonly prompts: readonly string[];
}

export type ChatSuggestedPromptSectionTable = Readonly<Record<string, readonly ChatSuggestedPromptSection[]>>;

interface JsonAttributeContract {
    readonly attribute: string;
    readonly feature: string;
    readonly shape: string;
}

const PROMPTS_CONTRACT: JsonAttributeContract = {
    attribute: PROMPTS_ATTRIBUTE,
    feature: 'features.suggestedPrompts.prompts',
    shape: "a map of group => prompts such as { default: ['…'] }",
};

const SECTIONS_CONTRACT: JsonAttributeContract = {
    attribute: SECTIONS_ATTRIBUTE,
    feature: 'features.suggestedPrompts.sections',
    shape: "a map of group => sections such as { default: [{ title: '…', prompts: ['…'] }] }",
};

const isRecord = (value: unknown): value is Record<string, unknown> =>
    typeof value === 'object' && value !== null && !Array.isArray(value);

const readStrings = (value: unknown): string[] =>
    Array.isArray(value) ? value.filter((item): item is string => typeof item === 'string') : [];

const fillPlaceholders = (prompt: string, values: ChatPromptValues): string | null => {
    let isComplete = true;
    const filled = prompt.replace(PLACEHOLDER_PATTERN, (_placeholder: string, name: string) => {
        const value = (values[name] ?? '').trim();

        isComplete = isComplete && value !== '';

        return value;
    });

    return isComplete && filled.trim() !== '' ? filled : null;
};

const resolvePrompts = (prompts: readonly string[], values: ChatPromptValues): string[] => {
    const resolved: string[] = [];

    for (const prompt of prompts) {
        const filled = fillPlaceholders(prompt, values);

        if (filled !== null && !resolved.includes(filled)) {
            resolved.push(filled);
        }
    }

    return resolved;
};

const reportContractError = (contract: JsonAttributeContract, reason: string, cause?: unknown): void => {
    reportError(
        new Error(
            `<${SUGGESTED_PROMPTS_TAG_NAME}> has ${reason} "${contract.attribute}" attribute (reported by ` +
                `${SOURCE_FILE}); its prompts are not offered. Pass ${contract.feature} as ${contract.shape} (the ` +
                `feature twig encodes it), or leave the attribute out.`,
            cause === undefined ? undefined : { cause: cause },
        ),
    );
};

export class ChatSuggestedPrompts extends HTMLElement implements ChatFeature {
    private context: ChatFeatureContext | null = null;
    private rail: HTMLElement | null = null;
    private sectionTemplate: ChatBlockTemplate<'title' | 'list'> | null = null;
    private promptTemplate: ChatBlockTemplate<'text'> | null = null;
    private table: ChatSuggestedPromptTable = {};
    private sectionTable: ChatSuggestedPromptSectionTable = {};
    private buttons: HTMLElement[] = [];
    private isBusy = false;

    connectedCallback(): void {
        announceFeature(this);
    }

    initialize(context: ChatFeatureContext): void {
        this.context = context;
        this.table = this.readTable();
        this.sectionTable = this.readSectionTable();
        this.sectionTemplate = context.blocks.require(SECTION_BLOCK);
        this.promptTemplate = context.blocks.require(PROMPT_BLOCK);
        this.rail = context.blocks.require<never>(RAIL_BLOCK).create().root;
        context.zones.insert(CORE_ZONE.welcome, this.rail);
        this.isBusy = context.services.get(REQUEST_LOCK).isBusy();
        context.events.on('busy', (change) => this.setBusy(change.isBusy));
        this.showGroup(this.getAttribute(GROUP_ATTRIBUTE) || DEFAULT_GROUP, {});
    }

    dispose(): void {
        this.rail?.replaceChildren();
        this.rail = null;
        this.sectionTemplate = null;
        this.promptTemplate = null;
        this.context = null;
        this.table = {};
        this.sectionTable = {};
        this.buttons = [];
        this.isBusy = false;
    }

    promptsFor(group: string, values: ChatPromptValues): string[] {
        return resolvePrompts(this.table[group] ?? this.table[DEFAULT_GROUP] ?? [], values);
    }

    sectionsFor(group: string, values: ChatPromptValues): ChatSuggestedPromptSection[] {
        const sections = this.sectionTable[group] ?? this.sectionTable[DEFAULT_GROUP];

        if (sections === undefined) {
            const prompts = this.promptsFor(group, values);

            return prompts.length === 0 ? [] : [{ title: '', prompts: prompts }];
        }

        return sections
            .map((section) => ({ title: section.title, prompts: resolvePrompts(section.prompts, values) }))
            .filter((section) => section.prompts.length > 0);
    }

    showGroup(group: string, values: ChatPromptValues): void {
        const rail = this.rail;
        const sectionTemplate = this.sectionTemplate;
        const promptTemplate = this.promptTemplate;

        if (rail === null || sectionTemplate === null || promptTemplate === null) {
            return;
        }

        this.buttons = [];

        const sections = this.sectionsFor(group, values).map((section) => {
            const block = sectionTemplate.create();

            block.references.title.textContent = section.title;
            block.references.title.hidden = section.title === '';
            block.references.list.replaceChildren(
                ...section.prompts.map((prompt) => this.createButton(promptTemplate, prompt)),
            );

            return block.root;
        });

        rail.replaceChildren(...sections);
        rail.hidden = sections.length === 0;
    }

    private createButton(template: ChatBlockTemplate<'text'>, prompt: string): HTMLElement {
        const block = template.create();

        block.references.text.textContent = prompt;
        block.root.toggleAttribute('disabled', this.isBusy);
        block.root.addEventListener('click', () => {
            this.send(prompt).catch((error: unknown) => reportError(error));
        });
        this.buttons.push(block.root);

        return block.root;
    }

    private setBusy(isBusy: boolean): void {
        this.isBusy = isBusy;

        for (const button of this.buttons) {
            button.toggleAttribute('disabled', isBusy);
        }
    }

    private async send(prompt: string): Promise<void> {
        const context = this.context;

        if (context === null || this.isBusy) {
            return;
        }

        if (await context.conversation.send(prompt)) {
            return;
        }

        reportError(
            new Error(
                `<${SUGGESTED_PROMPTS_TAG_NAME}> could not send the prompt "${prompt}" (reported by ${SOURCE_FILE}): ` +
                    `the chat refused it because another request holds the request lock. Choose the prompt again ` +
                    `once the running request has finished.`,
            ),
        );
    }

    private readTable(): ChatSuggestedPromptTable {
        const parsed = this.readJsonAttribute(PROMPTS_CONTRACT);
        const table: Record<string, string[]> = {};

        for (const [group, prompts] of Object.entries(parsed ?? {})) {
            if (Array.isArray(prompts)) {
                table[group] = readStrings(prompts);
            }
        }

        return table;
    }

    private readSectionTable(): ChatSuggestedPromptSectionTable {
        const parsed = this.readJsonAttribute(SECTIONS_CONTRACT);
        const table: Record<string, ChatSuggestedPromptSection[]> = {};

        for (const [group, sections] of Object.entries(parsed ?? {})) {
            if (!Array.isArray(sections) || !sections.every(isRecord)) {
                reportContractError(SECTIONS_CONTRACT, `a group "${group}" that is not a list of sections in the`);

                continue;
            }

            table[group] = sections.map((section) => ({
                title: typeof section.title === 'string' ? section.title : '',
                prompts: readStrings(section.prompts),
            }));
        }

        return table;
    }

    private readJsonAttribute(contract: JsonAttributeContract): Record<string, unknown> | null {
        const value = this.getAttribute(contract.attribute);
        let parsed: unknown;

        if (value === null) {
            return null;
        }

        if (value.trim() === '') {
            reportContractError(contract, 'an empty');

            return null;
        }

        try {
            parsed = JSON.parse(value);
        } catch (error) {
            reportContractError(contract, 'a non-JSON', error);

            return null;
        }

        // json_encode turns an empty table into `[]`.
        if (Array.isArray(parsed) && parsed.length === 0) {
            return {};
        }

        if (!isRecord(parsed)) {
            reportContractError(contract, 'a JSON list instead of a map in the');

            return null;
        }

        return parsed;
    }
}
