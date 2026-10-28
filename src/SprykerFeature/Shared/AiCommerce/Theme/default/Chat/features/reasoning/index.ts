// Shows the model's reasoning as a collapsible block, folded once the answer starts; a loaded conversation has none.

import { ChatReasoning } from './reasoning';
import { defineElementOnce } from '@shared/ai-commerce/Chat/core/contracts/access';

defineElementOnce('chat-reasoning', ChatReasoning);
