import { marked } from 'marked';
import DOMPurify from 'dompurify';

export class BackofficeAssistantMarkdownParser {
    constructor() {
        marked.setOptions({ breaks: true, gfm: true });
    }

    parse(text) {
        const rawHtml = marked.parse(text);

        return DOMPurify.sanitize(rawHtml, { ADD_ATTR: ['target'] });
    }
}
