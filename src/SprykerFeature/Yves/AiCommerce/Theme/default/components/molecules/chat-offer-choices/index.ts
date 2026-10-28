import register from 'ShopUi/app/registry';

export default register(
    'chat-offer-choices',
    () =>
        import(
            /* webpackMode: "lazy" */
            /* webpackChunkName: "storefront-assistant" */
            './chat-offer-choices'
        ),
);
