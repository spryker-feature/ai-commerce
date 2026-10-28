# Chat

One chat for every host: the `<spryker-chat>` element (core) plus `<chat-*>` feature elements, all markup in twig,
all styling through `--chat-*` custom properties. Hosts: the storefront organism `storefront-assistant` (Yves) and the
Back Office chat widget (Zed).

```
Chat/
  index.ts, index.scss     one entry per host: the core + every default feature
  core/                    chat.twig (embed), chat.scss (tokens, base rules), contracts/, runtime/, services/
  core/<component>/        panel, menu, transcript, composer, markdown: <component>.ts, <component>.twig (blocks), _<component>.scss
  features/<name>/         <name>.ts (class), index.ts (registration), <name>.twig (blocks), _<name>.scss
```

Only what every host uses lives here. A feature one host needs lives with that host: the storefront ones as ShopUi
molecules named like their tag in `Yves/AiCommerce/Theme/default/components/molecules/` (`chat-tool-products`, …), the
Back Office ones in `Zed/AiCommerce/assets/Zed/chat/features/` (TS, SCSS) and
`Zed/AiCommerce/Presentation/Partials/Chat/` (twig). The settings of the shared page-context feature live per host too:
the molecule `chat-page-context` on the storefront, `Partials/Chat/page-context.twig` in the Back Office.

A storefront feature molecule is an ordinary ShopUi molecule: its twig extends the component model with the feature tag
and holds the feature blocks, its `index.ts` registers the tag with the ShopUi registry, which loads the class lazily
into the `storefront-assistant` chunk, and the class imports its stylesheet, so nothing of it lands in the page
bundles. The class is still a chat feature (it extends `HTMLElement`, not the ShopUi `Component`); the chat waits for
the tag like for any feature.

## Mount the chat in a host

Embed the core twig, import the entry, add the host features next to it. The entry loads every shared feature with
its styles (page-context included: only its twig and rules are per host) and default-exports the chat element class.

```twig
{% embed '@AiCommerce/Chat/core/chat.twig' with { chat: { promptUrl: '/prompt', csrfToken: token }, features: {} } only %}
    {% block hostFeatures %}
        {% include '@AiCommerce/Chat/features/page-context/page-context.twig' with { pageContext: { rules: [] } } only %}
    {% endblock %}
{% endembed %}
```

```ts
import '@shared/ai-commerce/Chat';
```

The storefront registers the entry as a lazy ShopUi component on the `spryker-chat` tag, so the chat lands in the
`storefront-assistant` chunk (`yves_default.storefront-assistant.js` / `.css`), not in the page bundles.

Consequence: a host feature rendered in twig but not imported stays an undefined tag; the core reports it by name after
its 5 s readiness wait and starts without it.

## Features

A feature is on when its element is inside `<spryker-chat>`. `chat.twig` renders every default feature with its
settings from `features.<name>`; the host includes its own in `hostFeatures`, each with its settings under the
feature's name (`{ pageContext: … }`). A host drops a default feature by overriding its `feature<Name>` block with an
empty one, and a host feature by not including it.

| Feature | Tag | Kind | Requires |
|---|---|---|---|
| agent-selector, histories, attachments, suggested-prompts, reasoning, tool-calls, message-actions, scroll-latest, session-persistence, trigger, docking | `chat-<name>` | default | — |
| page-context | `chat-page-context` | host, shared (storefront, Back Office) | — |
| loading-label | `chat-loading-label` | default | tool-calls |
| tool-products | `chat-tool-products` | storefront only | tool-calls |
| tool-compare | `chat-tool-compare` | storefront only | tool-calls, tool-products |
| offer-choices | `chat-offer-choices` | storefront only | tool-calls |
| display-setup | `chat-display-setup` | storefront only | tool-calls, tool-products |
| form-fill, form-context | `chat-form-fill`, `chat-form-context` | Back Office only | — |

DOM order is initialization order; `requires` moves a feature after the features it names.
Consequence: a missing or cyclic requirement is reported by tag at start-up and the feature stays inactive.

## Blocks

Every element the chat renders is cloned from a `<template data-block="…">` inside a twig `{% block %}` of the same
name. The `data-ref` children listed below are required; a missing block or reference fails at start-up naming the
owner, the block and the reference.

| Owner | Blocks (required `data-ref`) |
|---|---|
| core (`core/panel/panel.twig`, `core/composer/composer.twig`, `core/transcript/transcript.twig`) | `launcher`, `panel` (close, scroller, transcript, composer, newConversation; optional welcomeZone, moreToggle, moreMenu, expand), `composer` (input, send, stop; optional limit: the length notice, which takes the disclosure's line while shown; optional disclosure: the AI notice the input is described by), `messageUser` (text), `messageAssistant` (content), `messageError` (text), `loading` (optional label, status), `retry` |
| agent-selector | `agentSelectorPicker` (toggle, label, menu), `agentSelectorOption` (label, description) |
| attachments | `attachmentsControl` (button, input), `attachmentsChip` (name, remove), `attachmentsPills`, `attachmentsPill` (name), `attachmentsNotice` (unsupportedType, tooLarge, tooMany, readFailed, fileName) |
| histories | `historiesToggle`, `historiesView` (close, startNew, list, loading, empty, count, select, selectLabel, cancelLabel, footer, selectAll, deleteSelected, deleteError), `historiesItem` (checkbox, open, name, current, time, delete), `historiesError` |
| suggested-prompts | `suggestedPrompts`, `suggestedPromptSection` (title, list), `suggestedPrompt` (text) |
| reasoning | `reasoningBlock` (content), `reasoningPart` |
| tool-calls | `toolCall` (name, toggle, showLabel, hideLabel, result), `toolCallDenied` |
| message-actions | `messageActions` (copy, regenerate, time; optional copyStatus, copyBuffer) |
| scroll-latest | `scrollLatest` (button) |
| page-context | `pageContextChip` (label, remove; optional attach) |
| tool-products | `productResult`, `productCarousel` (track, previous, next), `productCard` (image, badge, name, sku, skuValue, price, priceCurrent, priceOriginal, priceOutlier, rating, ratingValue, reviews, reviewCount, reason, description, view; optional compare), `productPrompts`, `productPrompt` (text), `productSeeAll`, `productRefinements` (pills, chips), `productRefinementChip` (label, count), `productFilterPill` (label) |
| tool-compare | `compareTray` (count, clear, chips, submit), `compareChip` (image, name, remove), `comparisonCard` (count, products, open, link), `comparisonCardProduct` (image, name, price), `comparisonView` (back, count, differences, body), `comparisonTable` (head, body), `comparisonHeadCell` (image, name, sku, skuValue, price, link), `comparisonSection` (label), `comparisonRow` (label), `comparisonValue`, `comparisonEmptyValue` |
| offer-choices | `offerChoices` (question, choices), `offerChoice` (text) |
| display-setup | `setupTotal` |
| loading-label | none: it writes the running tool into the `label` and `status` references of the core `loading` block |
| form-context | `formContext` (attach, attachLabel, chip, chipLabel, remove) |
| trigger | none: it binds the host's buttons from `button.twig`, which are rendered outside the chat |
| docking | none: it marks `<html>` and pads the body (or leaves the push to the host) |
| form-fill | none: it fills page form fields from `data-form-fill` events |

Texts with placeholders sit in `data-*` attributes of a block root and use `%name%` (`trans({'%count%': '%count%'})`).
These are checked at start-up: tool-products `productSeeAll` (`data-text`) and `productRefinements` (`data-select-text`,
`data-remove-text`); tool-compare `compareTray` (`data-selected-one`, `data-selected-many`, `data-remove-label`,
`data-message`, `data-product-format`, `data-product-separator`), `comparisonCard` and `comparisonView`
(`data-count-text`), `comparisonTable` (all eight texts); form-context `formContext` (`data-label`, `data-attach-label`).
Consequence: an override must keep those attributes, or the feature fails at start-up or shows the raw template.

## Zones

Features insert their elements into named `data-zone` elements of the core blocks: `headerStart`, `headerActions`,
`views`, `welcome`, `composerStart`, `composerActions`, `footer`, `moreMenu` (`CORE_ZONE` in
`core/contracts/blocks.ts`). The `welcome` zone holds fresh-conversation content: the core hides it once the customer
has sent a message (or a conversation is loading), not for a notice alone, and shows it again when the transcript is cleared; the greeting above it stays for the whole conversation.
The `moreMenu` zone is the "More options" menu of the header and exists only with `chat.header.moreMenu` (the default;
a host that passes `false` keeps header buttons): a feature asks `zones.has(CORE_ZONE.moreMenu)` and inserts a
`role="menuitem"` button there, or a header button into `headerActions` otherwise, as `chat-histories` does. The core
closes the menu after any item is chosen. `chat.header.expand` (default `true`) renders the expand button: the panel
takes the full width under the `spryker-chat--expanded` class, which the host styles against next to `data-open`; `panel.setExpanded()` and
the `panel:expand` event carry the state, and `chat-session-persistence` restores it.

```ts
context.zones.insert(CORE_ZONE.composerStart, chip.root, 'start');
```

The core removes a feature's insertions when the feature is disposed. Consequence: a `panel` or `composer` override
that drops a `data-zone` breaks every feature inserting there.

## Events

DOM events (cross-bundle): `spryker-chat:feature-connected`, `:provide`, `:init` (provide services before start-up),
`:ready` (`detail.handle`, `detail.features`), `:request` (used by `requestChat()`), `:session-expired` (cancelable;
the default reloads the page).

Context events (`context.events.on()`, `handle.on()`): `stream` (every envelope), `turn:state`, `turn:end`,
`message:rendered`, `message:stored` (stored roles the core does not render, e.g. `tool_result`),
`conversation:reset`, `conversation:loaded`, `panel:toggle`, `busy`.

```ts
requestChat(element, (handle) => handle.on('panel:toggle', ({ isOpen }) => trigger.setAttribute('aria-pressed', String(isOpen))));
```

Stability promises: `turn:state` with `sending` is emitted before the turn's `message:rendered`; listeners run in
registration order, and a throwing listener is reported without stopping the others.

Consequence: code in another bundle never imports the core element; it reaches the chat only through these events and
the handle.

## Tokens

Tier 1 (`core/chat.scss`, the only place with literals): `--chat-color-*`, `--chat-space-*`, `--chat-radius*`,
`--chat-font*`, `--chat-size-*`, `--chat-duration-*`, `--chat-layer`, … Tier 2: component tokens such as
`--chat-bubble-user-background` or `--chat-product-card-width`, derived from tier 1 only; every feature declares its
own tier-2 tokens on `spryker-chat` in its `_<name>.scss`.

A host maps tier 1 to its theme with a more specific selector, on the chat and on the trigger rendered outside it; the
panel width it sets is also what the docking feature pushes the page by:

```scss
storefront-assistant spryker-chat,
.chat-trigger {
    --chat-color-accent: #{$setting-color-main};
    --chat-size-panel-width: clamp(400px, 28vw, 480px);
}
```

Consequence: a host never styles feature classes, and a new semantic value becomes a tier-1 token, never a literal in a
feature or in the core styles, which write out only structural values such as 0 and 100%.

## Services

Core services by token (`core/contracts/tokens.ts`): `TRANSPORT`, `STREAM_PARSER`, `SANITIZER`, `MARKDOWN`, `STATE`,
`REQUEST_LOCK`, `API`. A feature replaces one through `provides`; a host through a `spryker-chat:init` listener.

```ts
// A feature declares its replacement:
readonly provides = [{ token: MARKDOWN, class: PlainTextMarkdown }];
// A host provides before the chat resolves its services:
document.addEventListener('spryker-chat:init', (event) => provideChatService(event.target as Element, API, ProjectChatApi));
```

Services are resolved once at start-up. Consequence: a feature defined after start-up cannot replace a service; its
`provides` is reported, not applied.

## ChatApi

`ChatApi` (`core/contracts/api.ts`): `prompt(payload, signal)` → SSE body, `conversations()`, `messages(reference)`,
`delete(references)`. `HttpChatApi` (`core/services/api.ts`) is the one implementation; the storefront
(`StorefrontAssistantController`) and the Back Office (`BackofficeAssistantChatController`) answer the same contract:
POST JSON prompt (`message`, `conversationReference`, plus contributed fields such as `attachments`, `selectedAgent`,
`pageContext`, `formContext`), GET list (`conversations`, `availableAgents`, `userSelectedAgent`), GET messages by
`?conversationReference=` (`messages`), DELETE with `{ conversationReferences }` (`deletedCount`), `X-Requested-With`,
`csrf-token` → `X-CSRF-Token`. A 401 or a redirect (the Back Office firewall sends a request without a session to the
login page) raises `spryker-chat:session-expired`.

Consequence: a new endpoint is added to `ChatApi`, `HttpChatApi` and both controllers.

## Attribute contract

Hosts pass structured values in the `features` map; feature twigs encode them (`| json_encode` with
`JSON_THROW_ON_ERROR`, so a value that cannot be encoded fails the render) into the element attributes. Defaults of
default features live in `chat.twig`; host features default their own keys with `??`.

```twig
features: { suggestedPrompts: { prompts: { default: ['What is new?'] } }, toolProducts: { searchUrl: path('search') } }
```

Consequence: a feature twig reads only `features.<its name>` and `chat`, never another feature's keys; an absent
attribute means "not configured", an empty one is reported as unreadable.

## Tool renderer contract

A feature that renders a tool result declares `requires = ['chat-tool-calls']`, takes the element through
`context.features.find('chat-tool-calls')` (types by `import type` from `@shared/…/tool-calls/chat-tool-calls`) and
registers by tool name; it calls the returned function in `dispose()`.

```ts
this.unregister = toolCalls.tools.register('offer_choices', (call) => this.render(call.result));
```

Renderers run in registration order, the first non-null element wins, a throwing renderer is reported and skipped;
live and stored (`tool_result`) results go through the same renderers. `tools.labelFor(name)` gives the shop label.
A result that is a model-facing correction rather than a payload — `{ "error": "…" }`, `{ "alreadyShown": … }`, with
none of the payload keys — is not a defect: the storefront tools answer that way so the model retries. A renderer
returns a silent `null` for it (the generic block, or nothing with `hide-unclaimed-results`), and reports only shapes
that are genuinely malformed.
Consequence: an unclaimed result shows the generic block, or nothing with `hide-unclaimed-results` (storefront); a
renderer that cannot read its result reports why before returning `null`.

## Links and images from tool results

Tool results come from an AI turn: a link is used only when it is a same-origin path (`/…`, no `//`, no backslash, no
whitespace), an image when it is such a path or an https URL from `toolProducts.imageOrigins`.

```twig
toolProducts: { searchUrl: path('search'), imageOrigins: ['media.example.com'] }
```

Consequence: with `imageOrigins` empty any https image host is accepted (the former storefront behaviour); list the
media hosts to close that.

## Add a feature

One folder `features/<name>/`: `<name>.ts`, a class extending `HTMLElement` with an argument-free constructor,
`connectedCallback` → `announceFeature(this)`, `initialize(context)` and `dispose()`; `index.ts`, registering it with
`defineElementOnce`, which hosts import as the folder (`@shared/ai-commerce/Chat/features/<name>`); `<name>.twig`,
block-only plain twig (no markup outside blocks, no ShopUi or Gui helpers); `_<name>.scss`, a partial with tier-2 tokens.

```ts
export class ChatHello extends HTMLElement implements ChatFeature {
    connectedCallback(): void { announceFeature(this); }
    initialize(context: ChatFeatureContext): void { context.zones.insert(CORE_ZONE.welcome, context.blocks.require(HELLO).create().root); }
    dispose(): void {}
}
```

Core imports go through `@shared/ai-commerce/Chat/core/contracts/…`, other features only through `import type`
from `@shared/…`. Consequence: the core releases subscriptions, contributions and zone insertions itself;
`dispose()` undoes only what the feature did on its own.

The feature is described once, in the header comment of its `index.ts`: what it renders, its blocks, attributes and
events, its `requires` and its host contract. The class, twig and stylesheet have no header, only why-comments at the
code they explain. Core TypeScript (`core/**/*.ts`, `index.ts`) keeps a header per file; core twig and
SCSS have none.
Consequence: no other file in a feature folder is named `index`; webpack resolves the folder import with every script
and style extension and tries the project folder first, so a project `index.scss` without `index.ts` would capture it.

## Trigger button

The `trigger` feature binds static buttons that open and close the chat from anywhere in the page (a header, a top
bar). The host includes `features/trigger/button.twig` where it wants a button; the feature finds every
`[data-chat-trigger]` button when the chat starts. The button renders `hidden` and the feature reveals it, so a page
whose chat never starts shows no dead button. A click toggles the panel, `aria-pressed` follows the open state, closing
returns focus to the button in view, and while one is in view the chat gets `data-trigger-visible`, which hides the
floating `launcher`. Each host has a host file with its texts and placement:
the molecule `storefront-assistant-trigger` (storefront, a static button the project header includes) and
`Partials/Chat/trigger.twig` (Back Office, a fixed button in the corner that `chat-widget.twig` includes).

```twig
{% include '@AiCommerce/Chat/features/trigger/button.twig' with { trigger: { label: 'Assistant', class: 'my-header__button' } } only %}
```

`trigger` keys: `label` (visible text; the `aria-label` is the `ai_commerce.storefront_assistant.action.open` translation), `placement` (`static`, the default: a pill in the
flow of the page; `fixed`: the floating pill in the corner, the look of the core launcher, kept under the open panel),
`class` and `attributes` (extra ones), `chat` (the `id` of the chat it belongs to; empty binds to every chat in the
page). The button sits outside `<spryker-chat>`, so
`core/chat.scss` declares the tier-1 tokens on `.chat-trigger` too, and a host maps its tokens on both
selectors (see Tokens). A project changes the button everywhere by shadowing `button.twig`, or its `trigger` and
`triggerIcon` blocks in an extending host file; a host drops the feature with an empty `featureTrigger` block.

Consequence: only buttons already in the page when the chat starts are bound; one rendered later stays hidden.

## Docking

The `docking` feature pushes the page aside while the panel is open. On `<html>` it sets `data-chat-docked` (open and
narrower than the viewport), `data-chat-expanded` (open at full width), `data-chat-dock-target` and, once the first
state is applied, `data-chat-dock-animated`; and the properties `--chat-dock-width` (the panel width in pixels),
`--chat-dock-duration` and `--chat-dock-easing` (the panel transition), because the page cannot read the chat's
tokens. The width is measured, so each host's panel width and breakpoints decide when the page is pushed, with no
breakpoint in the feature. `features.docking.target`: `body` (default) pads the body; `none` leaves the push to the
host, which styles its layout against the same attributes, as the Back Office does with its content and top bar.
While expanded the body does not scroll, and the push stays, so collapsing does not slide the page.

```scss
:root[data-chat-docked] .content-page { margin-right: var(--chat-dock-width); }
```

Consequence: a host that wants the panel to cover the page drops the feature with an empty `featureDocking` block.

## Override a block

Override the block by name in the host embed; keep its `<template data-block>`, its required `data-ref`s and its
`data-*` texts.

```twig
{% block messageAssistant %}
    <template data-block="messageAssistant"><div class="my-answer"><div data-ref="content"></div></div></template>
{% endblock %}
```

Consequence: the override applies only to that host; to change it everywhere, shadow the twig file.

## Shadow or extend a feature

`@AiCommerce/Chat/…` (twig) and `@shared/ai-commerce/…` (TS/SCSS) resolve project directories first, so a project
file at the same path replaces the core one. `@SprykerFeature:AiCommerce/Chat/…` always addresses the core twig file,
so a template can extend what it replaces. TS has one alias: a project shadows the feature's `index.ts` and subclasses
the core class, which `@shared/…` finds in the core because the project ships no file at the class path.

```ts
// src/Pyz/Shared/AiCommerce/Theme/default/Chat/features/suggested-prompts/index.ts
import { ChatSuggestedPrompts } from '@shared/ai-commerce/Chat/features/suggested-prompts/suggested-prompts';
import { defineElementOnce } from '@shared/ai-commerce/Chat/core/contracts/access';
class ProjectSuggestedPrompts extends ChatSuggestedPrompts {}
defineElementOnce('chat-suggested-prompts', ProjectSuggestedPrompts);
```

Consequence: the first definition of a tag wins, so a shadowed `index.ts` is the only place a tag is defined.

## Change a feature for one host

| | Storefront | Back Office |
|---|---|---|
| twig | the molecule overridden in the project Yves theme, extending `molecule('<molecule>', '@SprykerFeature:AiCommerce')` | the host file in the project `Zed/AiCommerce/Presentation/Partials/Chat/`, extending `@SprykerFeature:AiCommerce/Partials/Chat/<name>.twig` |
| settings | the molecule `data` (`{% define data = { … } %}` in the override, or `data` at the include) | `{% set <name> = { … } %}` above the `extends`; the host file merges its values under it |
| TS | a project `index.ts` of the molecule whose lazy module subclasses the core class (default export) and exports the subclass as default; for the shared page-context, a project `Chat/features/page-context/index.ts` in Shared that registers the subclass (see `OVERRIDES.md`, section 3.5) | a project entry that registers the subclass, loaded by the project layout before the chat script |
| CSS | tokens under `storefront-assistant spryker-chat` | a stylesheet of that project entry |

Consequence: the merge is shallow, so a project `rules` list replaces the host's rules; a rule is added by listing the
host's rules again.

## Page context (host-neutral)

`pageContext.rules` describes the host's pages; the first rule whose `when` (selector present), `unless`
(selector absent), `pathPrefix` and `minimumPathSegments` match, and whose `required` fields have values, wins. Field
reads: `text`, `value`, `attribute` (+ `attribute`), `nameValue`, `queryParameter` (+ `parameter`), `location`,
`fixed` (+ `value`); `list: true` collects every match; `scope` reads the fields inside the first matching element
that has them all; `label` is the chip text with `%field%` placeholders.

```twig
{ pageType: 'product', label: '%productName%', when: '.product-detail', scope: '[itemtype="https://schema.org/Product"]',
  fields: { productName: { read: 'attribute', selector: 'meta[itemprop="name"]', attribute: 'content', required: true } } }
```

The prompt then carries `pageContext: { pageType, …fields }` while the chip is attached, and the suggested prompts
switch to the group named like the page type. `optIn` offers the context (the chip's `attach` button shows
while it is offered), `singleTurn` detaches it after one turn. A host may pass the rules as JSON text instead of a list. Consequence: the core never knows a host's pages; a
retemplated page only changes the rules, and a malformed rule fails start-up once, naming every rule, field and fix.
