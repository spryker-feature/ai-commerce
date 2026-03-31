import './style.scss';
import register from 'ShopUi/app/registry';

export default register(
    'search-by-file-image',
    () =>
        import(
            /* webpackMode: "lazy" */
            /* webpackChunkname: "search-by-file-image" */
            './search-by-file-image'
        ),
);
