import './chat-display-setup.scss';
import type { ChatFeature, ChatFeatureContext } from '@shared/ai-commerce/Chat/core/contracts/context';
import { CHAT_TURN_STATE } from '@shared/ai-commerce/Chat/core/contracts/context';
import type { ChatBlockTemplate } from '@shared/ai-commerce/Chat/core/contracts/blocks';
import { announceFeature } from '@shared/ai-commerce/Chat/core/contracts/access';
import type { ChatToolCall, ChatToolCalls } from '@shared/ai-commerce/Chat/features/tool-calls/tool-calls';
import type ChatToolProducts from '../chat-tool-products/chat-tool-products';

export const DISPLAY_SETUP_TAG_NAME = 'chat-display-setup';

const SOURCE_FILE = 'Yves/AiCommerce/Theme/default/components/molecules/chat-display-setup/chat-display-setup.ts';
const TOOL_CALLS_TAG_NAME = 'chat-tool-calls';
const TOOL_PRODUCTS_TAG_NAME = 'chat-tool-products';
// Must match DisplaySetupToolPlugin::TOOL_NAME.
const DISPLAY_SETUP_TOOL_NAME = 'display_setup';
const TEXT_ATTRIBUTE = 'data-text';
const TEXT_WITHOUT_BUDGET_ATTRIBUTE = 'data-text-without-budget';
const TOTAL_PLACEHOLDER = '%total%';
const BUDGET_PLACEHOLDER = '%budget%';

const TOTAL_BLOCK = { name: 'setupTotal', references: [] } as const;

interface SetupTotal {
    readonly totalFormatted: string;
    readonly budgetFormatted: string;
}

const isRecord = (value: unknown): value is Record<string, unknown> =>
    typeof value === 'object' && value !== null && !Array.isArray(value);

const isModelCorrection = (decoded: Record<string, unknown>): boolean =>
    (typeof decoded.error === 'string' || 'alreadyShown' in decoded) &&
    !('totalFormatted' in decoded) &&
    !('products' in decoded);

const parseSetupTotal = (result: string): SetupTotal | string | null => {
    let decoded: unknown;

    try {
        decoded = JSON.parse(result);
    } catch {
        return 'it is not JSON';
    }

    if (isRecord(decoded) && isModelCorrection(decoded)) {
        return null;
    }

    const totalFormatted = isRecord(decoded) ? decoded.totalFormatted : null;
    const budgetFormatted = isRecord(decoded) ? decoded.budgetFormatted : null;

    if (typeof totalFormatted !== 'string' || totalFormatted.trim() === '') {
        return 'it has no "totalFormatted" text';
    }

    return {
        totalFormatted: totalFormatted,
        budgetFormatted: typeof budgetFormatted === 'string' ? budgetFormatted.trim() : '',
    };
};

export default class ChatDisplaySetup extends HTMLElement implements ChatFeature {
    readonly requires = [TOOL_CALLS_TAG_NAME, TOOL_PRODUCTS_TAG_NAME];

    private toolProducts: ChatToolProducts | null = null;
    private totalTemplate: ChatBlockTemplate<never> | null = null;
    private unregisterRenderer: (() => void) | null = null;
    private readonly renderedResults = new Set<HTMLElement>();

    connectedCallback(): void {
        announceFeature(this);
    }

    initialize(context: ChatFeatureContext): void {
        const toolCalls = context.features.find(TOOL_CALLS_TAG_NAME) as ChatToolCalls | null;
        const toolProducts = context.features.find(TOOL_PRODUCTS_TAG_NAME) as ChatToolProducts | null;

        if (
            toolCalls === null ||
            typeof toolCalls.tools?.register !== 'function' ||
            toolProducts === null ||
            typeof toolProducts.products?.render !== 'function'
        ) {
            throw new Error(
                `<${DISPLAY_SETUP_TAG_NAME}> renders through <${TOOL_CALLS_TAG_NAME}> and ` +
                    `<${TOOL_PRODUCTS_TAG_NAME}>, ` +
                    `and one of them is not initialized (reported by ${SOURCE_FILE}). Render both in the same chat; ` +
                    `display-setup.twig is included after them in the host's hostFeatures block.`,
            );
        }

        this.toolProducts = toolProducts;
        this.totalTemplate = context.blocks.require<never>(TOTAL_BLOCK);
        this.unregisterRenderer = toolCalls.tools.register(DISPLAY_SETUP_TOOL_NAME, (call) => this.render(call));
        context.events.on('conversation:reset', () => this.forgetDetachedResults());
        context.events.on('conversation:loaded', () => this.forgetDetachedResults());
        context.events.on('turn:state', (change) => {
            if (change.state === CHAT_TURN_STATE.sending) {
                this.forgetDetachedResults();
            }
        });
    }

    dispose(): void {
        this.unregisterRenderer?.();
        this.unregisterRenderer = null;

        for (const result of this.renderedResults) {
            result.remove();
        }

        this.renderedResults.clear();
        this.toolProducts = null;
        this.totalTemplate = null;
    }

    private render(call: ChatToolCall): Element | null {
        const toolProducts = this.toolProducts;

        if (toolProducts === null || this.totalTemplate === null) {
            return null;
        }

        const setupTotal = parseSetupTotal(call.result);

        if (setupTotal === null) {
            return null;
        }

        const products = toolProducts.products.read(call.result);
        const problem =
            typeof setupTotal === 'string' ? setupTotal : products.length === 0 ? 'it has no product to show' : '';

        if (typeof setupTotal === 'string' || problem !== '') {
            reportError(
                new Error(
                    `The "${DISPLAY_SETUP_TOOL_NAME}" tool result could not be shown as a setup: ${problem} ` +
                        `(reported by ${SOURCE_FILE}). It falls back to the generic tool-call block; check what ` +
                        `DisplaySetupToolPlugin returned for this turn.`,
                ),
            );

            return null;
        }

        const total = this.totalTemplate.create().root;
        const text = total.getAttribute(
            setupTotal.budgetFormatted === '' ? TEXT_WITHOUT_BUDGET_ATTRIBUTE : TEXT_ATTRIBUTE,
        );

        total.textContent = (text ?? '')
            .replace(TOTAL_PLACEHOLDER, () => setupTotal.totalFormatted)
            .replace(BUDGET_PLACEHOLDER, () => setupTotal.budgetFormatted);

        const result = toolProducts.products.render(products, [total]);

        this.renderedResults.add(result);

        return result;
    }

    private forgetDetachedResults(): void {
        for (const result of [...this.renderedResults]) {
            if (!result.isConnected) {
                this.renderedResults.delete(result);
            }
        }
    }
}
