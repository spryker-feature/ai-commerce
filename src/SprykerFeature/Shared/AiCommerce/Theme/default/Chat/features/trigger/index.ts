// Lets host buttons from button.twig toggle the panel; a button rendered after the chat started is not bound.

import { ChatTrigger } from './trigger';
import { defineElementOnce } from '@shared/ai-commerce/Chat/core/contracts/access';

defineElementOnce('chat-trigger', ChatTrigger);
