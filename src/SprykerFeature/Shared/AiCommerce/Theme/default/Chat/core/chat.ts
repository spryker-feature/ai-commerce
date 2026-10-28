import { CHAT_TAG_NAME } from './contracts/access';
import { defineElementOnce } from './runtime/define-element';
import { ChatElement } from './runtime/element';

defineElementOnce(CHAT_TAG_NAME, ChatElement);

export default ChatElement;
