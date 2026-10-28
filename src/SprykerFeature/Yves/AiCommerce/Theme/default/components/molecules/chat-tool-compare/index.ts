import register from 'ShopUi/app/registry';

export default register(
    'chat-tool-compare',
    () =>
        import(
            /* webpackMode: "lazy" */
            /* webpackChunkName: "storefront-assistant" */
            './chat-tool-compare'
        ),
);
