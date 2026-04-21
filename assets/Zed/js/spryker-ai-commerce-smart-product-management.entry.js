import '../scss/main.scss';
import { AiCategorySuggestion } from './ai-product-management/ai-category-suggestion';
import { AiImageAltText, AiImageAltTextInjector } from './ai-product-management/ai-image-alt-text';
document.addEventListener('DOMContentLoaded', () => {
    new AiCategorySuggestion().init();
    new AiImageAltText().init();
    new AiImageAltTextInjector().init();

    document.querySelectorAll('[popover][data-ai-popover]').forEach((popover) => {
        popover.addEventListener('toggle', (event) => {
            const modal = popover.querySelector('.modal');
            if (!modal) {
                return;
            }

            event.newState === 'open' ? modal.classList.add('show') : modal.classList.remove('show');
        });
    });
});
