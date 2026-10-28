// Lists the customer's stored conversations to open, start anew or delete; deleting the open one starts a new one.

import { ChatHistories } from './histories';
import { defineElementOnce } from '@shared/ai-commerce/Chat/core/contracts/access';

defineElementOnce('chat-histories', ChatHistories);
