export const STORAGE_KEY = 'backoffice_assistant_state';
export const MAX_FILE_SIZE = 5 * 1024 * 1024;
export const SELECTORS = {
    toggle: '.js-backoffice-assistant__toggle',
    panel: '.js-backoffice-assistant__panel',
    close: '.js-backoffice-assistant__close',
    historyBtn: '.js-backoffice-assistant__history-btn',
    newChat: '.js-backoffice-assistant__new-chat',
    messages: '.js-backoffice-assistant__messages',
    histories: '.js-backoffice-assistant__histories',
    historiesList: '.js-backoffice-assistant__histories-list',
    historiesEmpty: '.js-backoffice-assistant__histories-empty',
    input: '.js-backoffice-assistant__input',
    send: '.js-backoffice-assistant__send',
    agentBadge: '.js-backoffice-assistant__agent-badge',
    agentSelect: '.js-backoffice-assistant__agent-select',
    attach: '.js-backoffice-assistant__attach',
    fileInput: '.js-backoffice-assistant__file-input',
    attachmentsPreview: '.js-backoffice-assistant__attachments-preview',
    footer: '.backoffice-assistant__footer',
};
export const ENDPOINTS = {
    prompt: '/ai-commerce/backoffice-assistant-prompt/index',
    histories: '/ai-commerce/backoffice-assistant-conversation/index',
    detail: '/ai-commerce/backoffice-assistant-conversation/detail',
    delete: '/ai-commerce/backoffice-assistant-conversation/delete',
};
