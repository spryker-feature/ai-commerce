/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

/**
 * Persists the collapsed/expanded state of the Smart CMS Content Assistant panel in localStorage,
 * so the editor's choice survives page reloads. localStorage may be unavailable, so reads and
 * writes fail silently.
 */
export class SmartCmsContentPanelState {
    static #STORAGE_KEY = 'smart_cms_panel_state';

    save(isCollapsed) {
        try {
            localStorage.setItem(SmartCmsContentPanelState.#STORAGE_KEY, JSON.stringify({ collapsed: isCollapsed }));
        } catch {
            // localStorage may be unavailable
        }
    }

    load() {
        try {
            const raw = localStorage.getItem(SmartCmsContentPanelState.#STORAGE_KEY);

            return raw ? JSON.parse(raw) : null;
        } catch {
            return null;
        }
    }
}
