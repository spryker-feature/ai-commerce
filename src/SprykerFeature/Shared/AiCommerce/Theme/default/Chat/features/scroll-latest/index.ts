// A "jump to latest" button that appears once the reader scrolls away from the end of the transcript.

import { ChatScrollLatest } from './scroll-latest';
import { defineElementOnce } from '@shared/ai-commerce/Chat/core/contracts/access';

defineElementOnce('chat-scroll-latest', ChatScrollLatest);
