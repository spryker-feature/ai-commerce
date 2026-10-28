// Pushes the page aside while the panel is open; the state goes to `<html>` (`data-chat-dock*`, `--chat-dock-*`) for
// host CSS, and `target="none"` leaves the push to the host.

import { ChatDocking } from './docking';
import { defineElementOnce } from '@shared/ai-commerce/Chat/core/contracts/access';

defineElementOnce('chat-docking', ChatDocking);
