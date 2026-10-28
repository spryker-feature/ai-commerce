// Images must be same-origin paths or https URLs from `imageOrigins`; while that list is empty any https host passes.

import register from 'ShopUi/app/registry';

export default register(
    'chat-tool-products',
    () =>
        import(
            /* webpackMode: "lazy" */
            /* webpackChunkName: "storefront-assistant" */
            './chat-tool-products'
        ),
);
