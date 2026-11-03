// Restores the conversation and the panel's open and expanded state on the next page, from localStorage.
// With `owner` (chat.owner, an opaque owner token) a session stored for another owner is dropped with the
// `clear-storage-keys` keys.

import { ChatSessionPersistence } from './session-persistence';
import { defineElementOnce } from '@shared/ai-commerce/Chat/core/contracts/access';

defineElementOnce('chat-session-persistence', ChatSessionPersistence);
