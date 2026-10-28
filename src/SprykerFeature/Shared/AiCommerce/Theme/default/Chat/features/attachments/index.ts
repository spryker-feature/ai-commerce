// Attaches files to the next message, sent as base64 `attachments`; a malformed limit keeps the feature inactive.

import { ChatAttachments } from './attachments';
import { defineElementOnce } from '@shared/ai-commerce/Chat/core/contracts/access';

defineElementOnce('chat-attachments', ChatAttachments);
