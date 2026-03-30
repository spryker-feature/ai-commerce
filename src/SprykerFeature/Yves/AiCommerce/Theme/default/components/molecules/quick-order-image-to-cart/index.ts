import register from 'ShopUi/app/registry';
export default register(
    'quick-order-image-to-cart',
    () => import(/* webpackMode: "eager" */ './quick-order-image-to-cart'),
);
