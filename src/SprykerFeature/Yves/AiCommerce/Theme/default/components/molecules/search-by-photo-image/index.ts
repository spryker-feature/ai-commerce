import './style.scss';
import register from 'ShopUi/app/registry';

export default register(
    'search-by-photo-image',
    () =>
        import(
            /* webpackMode: "lazy" */
            /* webpackChunkname: "search-by-photo-image" */
            './search-by-photo-image'
        ),
);
