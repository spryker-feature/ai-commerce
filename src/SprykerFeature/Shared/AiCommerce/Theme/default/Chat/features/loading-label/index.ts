// Names the running tool on the core loading indicator until its output or denial arrives.

import { ChatLoadingLabel, LOADING_LABEL_TAG_NAME } from './loading-label';
import { defineElementOnce } from '@shared/ai-commerce/Chat/core/contracts/access';

defineElementOnce(LOADING_LABEL_TAG_NAME, ChatLoadingLabel);
