import './style.scss';
import register from 'ShopUi/app/registry';

export default register(
    'search-by-image',
    () =>
        import(
            /* webpackMode: "lazy" */
            /* webpackChunkname: "search-by-image" */
            './search-by-image'
        ),
);
