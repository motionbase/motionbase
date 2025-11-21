import type {
    API,
    BlockTool,
    BlockToolConstructorOptions,
} from '@editorjs/editorjs';

type LanguageOption = {
    label: string;
    value: string;
};

interface CodeWithLanguageData {
    code?: string;
    language?: string;
}

interface CodeWithLanguageConfig {
    languages?: LanguageOption[];
    defaultLanguage?: string;
    placeholder?: string;
}

const DEFAULT_LANGUAGES: LanguageOption[] = [
    { label: 'JavaScript', value: 'javascript' },
    { label: 'TypeScript', value: 'typescript' },
    { label: 'PHP', value: 'php' },
    { label: 'CSS', value: 'css' },
    { label: 'HTML', value: 'markup' },
    { label: 'Shell', value: 'bash' },
];

export default class CodeWithLanguage implements BlockTool {
    private api: API;
    private readOnly: boolean;
    private config: CodeWithLanguageConfig;
    private data: Required<CodeWithLanguageData>;
    private wrapper: HTMLDivElement | null = null;
    private textarea: HTMLTextAreaElement | null = null;
    private select: HTMLSelectElement | null = null;

    constructor({
        api,
        config = {},
        data,
        readOnly,
    }: BlockToolConstructorOptions<CodeWithLanguageData, CodeWithLanguageConfig>) {
        this.api = api;
        this.config = config;
        this.readOnly = Boolean(readOnly);
        const defaultLanguage =
            data?.language ??
            config.defaultLanguage ??
            DEFAULT_LANGUAGES[0]?.value ??
            'javascript';

        this.data = {
            code: data?.code ?? '',
            language: defaultLanguage,
        };
    }

    static get isReadOnlySupported() {
        return true;
    }

    static get toolbox() {
        return {
            title: 'Code',
            icon: '<svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M8 17L3 12L8 7" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"></path><path d="M16 7L21 12L16 17" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"></path><path d="M14 5L10 19" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"></path></svg>',
        };
    }

    render() {
        this.wrapper = document.createElement('div');
        this.wrapper.className = 'code-tool space-y-3';

        const controlRow = document.createElement('div');
        controlRow.className = 'code-tool__controls';

        this.select = document.createElement('select');
        this.select.className = 'code-tool__select';
        this.select.disabled = this.readOnly;

        const languages = this.config.languages ?? DEFAULT_LANGUAGES;
        languages.forEach((language) => {
            const option = document.createElement('option');
            option.value = language.value;
            option.textContent = language.label;
            this.select?.appendChild(option);
        });
        this.select.value = this.data.language;
        this.select.addEventListener('change', (event) => {
            const target = event.target as HTMLSelectElement;
            this.data.language = target.value;
            this.api.blocks.save();
        });

        controlRow.appendChild(this.select);

        this.textarea = document.createElement('textarea');
        this.textarea.className = 'code-tool__textarea';
        this.textarea.placeholder =
            this.config.placeholder ?? 'Füge hier deinen Code ein…';
        this.textarea.value = this.data.code;
        this.textarea.disabled = this.readOnly;
        this.textarea.addEventListener('input', (event) => {
            const target = event.target as HTMLTextAreaElement;
            this.data.code = target.value;
        });

        this.wrapper.appendChild(controlRow);
        this.wrapper.appendChild(this.textarea);

        return this.wrapper;
    }

    save() {
        const code = this.textarea?.value ?? this.data.code;
        const language = this.select?.value ?? this.data.language;

        this.data = { code, language };

        return this.data;
    }
}

