import './style.scss';
import register from 'ShopUi/app/registry';

// The chat defines its own tag when its module loads, so the registry only has to load it.
export default register(
    'spryker-chat',
    () =>
        import(
            /* webpackMode: "lazy" */
            /* webpackChunkName: "storefront-assistant" */
            '@shared/ai-commerce/Chat'
        ),
);
