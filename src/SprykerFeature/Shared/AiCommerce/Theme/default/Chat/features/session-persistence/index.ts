// Restores the conversation and the panel's open and expanded state on the next page, from localStorage.

import { ChatSessionPersistence } from './session-persistence';
import { defineElementOnce } from '@shared/ai-commerce/Chat/core/contracts/access';

defineElementOnce('chat-session-persistence', ChatSessionPersistence);
