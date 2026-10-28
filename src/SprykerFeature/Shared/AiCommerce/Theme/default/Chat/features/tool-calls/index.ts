// Renders each tool result through the renderer registered for its tool name, else as the generic `toolCall` block.

import { ChatToolCalls, TOOL_CALLS_TAG_NAME } from './tool-calls';
import { defineElementOnce } from '@shared/ai-commerce/Chat/core/contracts/access';

defineElementOnce(TOOL_CALLS_TAG_NAME, ChatToolCalls);
