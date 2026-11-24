import register from 'ShopUi/app/registry';

export default register(
    'chat-knowledge-sources',
    () =>
        import(
            /* webpackMode: "lazy" */
            /* webpackChunkName: "storefront-assistant" */
            './chat-knowledge-sources'
        ),
);
