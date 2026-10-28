// Starter prompts in the welcome zone, sent on click; a prompt whose `{name}` placeholder has no value is dropped.

import { ChatSuggestedPrompts, SUGGESTED_PROMPTS_TAG_NAME } from './suggested-prompts';
import { defineElementOnce } from '@shared/ai-commerce/Chat/core/contracts/access';

defineElementOnce(SUGGESTED_PROMPTS_TAG_NAME, ChatSuggestedPrompts);
