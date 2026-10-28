import register from 'ShopUi/app/registry';

export default register(
    'chat-display-setup',
    () =>
        import(
            /* webpackMode: "lazy" */
            /* webpackChunkName: "storefront-assistant" */
            './chat-display-setup'
        ),
);
