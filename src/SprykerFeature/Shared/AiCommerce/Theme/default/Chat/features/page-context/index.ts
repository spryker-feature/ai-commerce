// Sends the customer's current page as `pageContext`; hosts add page-context.twig to `hostFeatures` after the defaults.

import { ChatPageContext, PAGE_CONTEXT_TAG_NAME } from './page-context';
import { defineElementOnce } from '@shared/ai-commerce/Chat/core/contracts/access';

defineElementOnce(PAGE_CONTEXT_TAG_NAME, ChatPageContext);
