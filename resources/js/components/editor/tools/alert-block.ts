import EditorJS, {
    type API,
    type BlockTool,
    type BlockToolConstructorOptions,
    type BlockToolData,
    type OutputData,
    type ToolConstructable,
} from '@editorjs/editorjs';
import Header from '@editorjs/header';
import List from '@editorjs/list';
import Paragraph from '@editorjs/paragraph';
import CodeWithLanguage from './code-with-language';

type AlertType = 'warning' | 'danger' | 'info' | 'neutral';

interface AlertBlockData extends BlockToolData {
    content?: string;
    type?: AlertType;
    contentBlocks?: OutputData;
}

interface AlertBlockConfig {
    placeholder?: string;
    defaultType?: AlertType;
}

const ALERT_OPTIONS: Array<{
    label: string;
    value: AlertType;
}> = [
    { label: 'Information', value: 'info' },
    { label: 'Warnung', value: 'warning' },
    { label: 'Gefahr', value: 'danger' },
    { label: 'Neutral', value: 'neutral' },
];

export default class AlertBlock implements BlockTool {
    private api: API;
    private data: Required<AlertBlockData>;
    private readOnly: boolean;
    private config: AlertBlockConfig;
    private select: HTMLSelectElement | null = null;
    private editorHolder: HTMLDivElement | null = null;
    private innerEditor: EditorJS | null = null;

    constructor({
        api,
        data,
        readOnly,
        config = {},
    }: BlockToolConstructorOptions<AlertBlockData, AlertBlockConfig>) {
        this.api = api;
        this.readOnly = Boolean(readOnly);
        this.config = config;

        this.data = {
            content: data?.content ?? '',
            type: data?.type ?? config.defaultType ?? 'info',
            contentBlocks: data?.contentBlocks ?? createDefaultAlertBlocks(),
        };
    }

    static get isReadOnlySupported() {
        return true;
    }

    static get toolbox() {
        return {
            title: 'Alert',
            icon: '<svg viewBox="0 0 24 24" width="18" height="18" fill="none"><path d="M12 9v4" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/><circle cx="12" cy="16" r="0.5" fill="currentColor"/><path d="M10.29 3.86c.75-1.3 2.67-1.3 3.42 0l7.21 12.53c.75 1.3-.19 2.92-1.71 2.92H4.79c-1.52 0-2.46-1.62-1.71-2.92L10.29 3.86Z" stroke="currentColor" stroke-width="1.6"/></svg>',
        };
    }

    render() {
        const wrapper = document.createElement('div');
        wrapper.className = 'alert-tool space-y-3';

        const controls = document.createElement('div');
        controls.className = 'alert-tool__controls';

        this.select = document.createElement('select');
        this.select.className = 'alert-tool__select';
        this.select.disabled = this.readOnly;

        ALERT_OPTIONS.forEach((option) => {
            const opt = document.createElement('option');
            opt.value = option.value;
            opt.textContent = option.label;
            this.select?.appendChild(opt);
        });
        this.select.value = this.data.type;
        this.select.addEventListener('change', (event) => {
            const target = event.target as HTMLSelectElement;
            this.data.type = target.value as AlertType;
        });

        controls.appendChild(this.select);

        wrapper.appendChild(controls);

        this.editorHolder = document.createElement('div');
        this.editorHolder.className = 'alert-tool__editor';
        wrapper.appendChild(this.editorHolder);

        if (!this.readOnly) {
            void this.initializeInnerEditor();
        } else {
            this.editorHolder.innerHTML = this.api.sanitizer.clean(
                this.data.contentBlocks?.blocks
                    ?.map((block) => ('text' in block.data ? String(block.data.text) : ''))
                    .join('<br />') ?? '',
            );
        }

        return wrapper;
    }

    async save() {
        const type = this.select?.value ?? this.data.type;
        const contentBlocks = this.innerEditor ? await this.innerEditor.save() : this.data.contentBlocks;

        this.data = {
            content: '',
            type: type as AlertType,
            contentBlocks,
        };

        return this.data;
    }

    destroy() {
        this.innerEditor?.destroy();
        this.innerEditor = null;
    }

    validate(data: AlertBlockData) {
        return Boolean(data.contentBlocks?.blocks?.length);
    }

    private async initializeInnerEditor() {
        if (!this.editorHolder) return;

        this.innerEditor = new EditorJS({
            holder: this.editorHolder,
            data: this.data.contentBlocks ?? createDefaultAlertBlocks(),
            minHeight: 0,
            inlineToolbar: true,
            defaultBlock: 'paragraph',
            tools: {
                paragraph: {
                    class: Paragraph as ToolConstructable,
                    inlineToolbar: true,
                },
                header: {
                    class: Header as ToolConstructable,
                    config: {
                        levels: [3, 4],
                        defaultLevel: 3,
                    },
                },
                list: {
                    class: List as ToolConstructable,
                    inlineToolbar: true,
                    config: {
                        defaultStyle: 'unordered',
                    },
                },
                code: {
                    class: CodeWithLanguage as unknown as ToolConstructable,
                    config: {
                        languages: [
                            { label: 'JavaScript', value: 'javascript' },
                            { label: 'TypeScript', value: 'typescript' },
                            { label: 'PHP', value: 'php' },
                            { label: 'CSS', value: 'css' },
                            { label: 'HTML', value: 'markup' },
                        ],
                        defaultLanguage: 'javascript',
                        placeholder: 'Code innerhalb des Alerts…',
                    },
                },
            },
        });
    }
}

function createDefaultAlertBlocks(): OutputData {
    const id =
        typeof crypto !== 'undefined' && typeof crypto.randomUUID === 'function'
            ? crypto.randomUUID()
            : `alert-${Date.now()}`;
    return {
        time: Date.now(),
        version: '2.31.0',
        blocks: [
            {
                id,
                type: 'paragraph',
                data: {
                    text: 'Dein Hinweistext…',
                },
            },
        ],
    };
}

