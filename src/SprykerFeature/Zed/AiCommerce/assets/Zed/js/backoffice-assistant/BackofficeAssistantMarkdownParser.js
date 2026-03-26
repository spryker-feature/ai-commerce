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

    highlightCode(code, language) {
        const hljs = window.hljs;

        if (!hljs) {
            return code;
        }

        try {
            if (language && hljs.getLanguage(language)) {
                return hljs.highlight(code, { language }).value;
            }

            return hljs.highlightAuto(code).value;
        } catch {
            return code;
        }
    }
}
