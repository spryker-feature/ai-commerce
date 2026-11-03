# Storefront Assistant organism

The storefront host of the shared AI chat. The chat itself (core, features, blocks, tokens, events, extension points)
lives in `src/SprykerFeature/AiCommerce/src/SprykerFeature/Shared/AiCommerce/Theme/default/Chat/` and is documented in
its `README.md`.

This organism only:

- embeds `@AiCommerce/Chat/core/chat.twig` with the storefront URLs, limits and texts, and includes the storefront
  feature molecules (`chat-page-context`, `chat-tool-products`, `chat-tool-compare`,
  `chat-offer-choices`, `chat-display-setup`) in its `hostFeatures` block (`storefront-assistant.twig`);
- passes the widget's `chatOwnerToken` (a SHA-256 hash of the customer reference) as `chat.owner`, and the agent
  selector storage key as `features.sessionPersistence.clearStorageKeys`, so a session and an agent selection stored
  for another customer are removed and not restored;
- loads the chat lazily when `<spryker-chat>` is on the page (`index.ts`);
- maps the chat tokens to the ShopUi theme, for the chat and the header trigger, including the panel width the default
  docking feature pushes the page by (`style.scss`).
