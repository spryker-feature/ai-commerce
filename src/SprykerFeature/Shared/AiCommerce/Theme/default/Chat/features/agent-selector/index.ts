// Pins the agent that answers, sent as `selectedAgent` ("Auto" sends null), and shows which agent answered.

import { ChatAgentSelector } from './agent-selector';
import { defineElementOnce } from '@shared/ai-commerce/Chat/core/contracts/access';

defineElementOnce('chat-agent-selector', ChatAgentSelector);
