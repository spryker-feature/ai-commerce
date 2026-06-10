import { SmartCmsContentPanel } from './smart-cms-content/SmartCmsContentPanel';
import '../scss/smart-cms-content.scss';

document.addEventListener('DOMContentLoaded', () => {
    if (document.querySelector('.js-smart-cms-panel')) {
        new SmartCmsContentPanel();
    }
});
