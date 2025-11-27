import type {
    API,
    BlockTool,
    BlockToolConstructorOptions,
    BlockToolData,
} from '@editorjs/editorjs';

interface YouTubeBlockData extends BlockToolData {
    url?: string;
    videoId?: string;
    caption?: string;
}

interface YouTubeBlockConfig {
    placeholder?: string;
}

export default class YouTubeBlock implements BlockTool {
    private api: API;
    private data: YouTubeBlockData;
    private readOnly: boolean;
    private config: YouTubeBlockConfig;
    private wrapper: HTMLDivElement | null = null;

    constructor({
        api,
        data,
        readOnly,
        config = {},
    }: BlockToolConstructorOptions<YouTubeBlockData, YouTubeBlockConfig>) {
        this.api = api;
        this.readOnly = Boolean(readOnly);
        this.config = config;

        this.data = {
            url: data?.url ?? '',
            videoId: data?.videoId ?? '',
            caption: data?.caption ?? '',
        };
    }

    static get isReadOnlySupported() {
        return true;
    }

    static get toolbox() {
        return {
            title: 'YouTube',
            icon: '<svg viewBox="0 0 24 24" width="18" height="18" fill="none"><path d="M22.54 6.42a2.78 2.78 0 0 0-1.94-2C18.88 4 12 4 12 4s-6.88 0-8.6.46a2.78 2.78 0 0 0-1.94 2A29 29 0 0 0 1 11.75a29 29 0 0 0 .46 5.33A2.78 2.78 0 0 0 3.4 19c1.72.46 8.6.46 8.6.46s6.88 0 8.6-.46a2.78 2.78 0 0 0 1.94-2 29 29 0 0 0 .46-5.25 29 29 0 0 0-.46-5.33z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/><path d="m9.75 15.02 5.75-3.27-5.75-3.27v6.54z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>',
        };
    }

    static get pasteConfig() {
        return {
            patterns: {
                youtube: /(?:https?:\/\/)?(?:www\.)?(?:youtube\.com\/(?:watch\?v=|embed\/)|youtu\.be\/)([a-zA-Z0-9_-]{11})/,
            },
        };
    }

    private extractVideoId(url: string): string | null {
        const patterns = [
            /(?:youtube\.com\/watch\?v=)([a-zA-Z0-9_-]{11})/,
            /(?:youtube\.com\/embed\/)([a-zA-Z0-9_-]{11})/,
            /(?:youtu\.be\/)([a-zA-Z0-9_-]{11})/,
            /(?:youtube\.com\/v\/)([a-zA-Z0-9_-]{11})/,
            /(?:youtube\.com\/shorts\/)([a-zA-Z0-9_-]{11})/,
        ];

        for (const pattern of patterns) {
            const match = url.match(pattern);
            if (match) {
                return match[1];
            }
        }

        return null;
    }

    render() {
        this.wrapper = document.createElement('div');
        this.wrapper.className = 'youtube-block';

        if (this.data.videoId) {
            this.renderPreview();
        } else {
            this.renderInput();
        }

        return this.wrapper;
    }

    private renderInput() {
        if (!this.wrapper) return;
        this.wrapper.innerHTML = '';

        const inputWrapper = document.createElement('div');
        inputWrapper.className = 'youtube-block__input-wrapper';

        const icon = document.createElement('div');
        icon.className = 'youtube-block__icon';
        icon.innerHTML = `
            <svg viewBox="0 0 24 24" width="32" height="32" fill="none">
                <path d="M22.54 6.42a2.78 2.78 0 0 0-1.94-2C18.88 4 12 4 12 4s-6.88 0-8.6.46a2.78 2.78 0 0 0-1.94 2A29 29 0 0 0 1 11.75a29 29 0 0 0 .46 5.33A2.78 2.78 0 0 0 3.4 19c1.72.46 8.6.46 8.6.46s6.88 0 8.6-.46a2.78 2.78 0 0 0 1.94-2 29 29 0 0 0 .46-5.25 29 29 0 0 0-.46-5.33z" stroke="currentColor" stroke-width="1.5"/>
                <path d="m9.75 15.02 5.75-3.27-5.75-3.27v6.54z" stroke="currentColor" stroke-width="1.5"/>
            </svg>
        `;
        inputWrapper.appendChild(icon);

        const input = document.createElement('input');
        input.type = 'text';
        input.className = 'youtube-block__input';
        input.placeholder = this.config.placeholder ?? 'YouTube-URL einfügen…';
        input.value = this.data.url ?? '';
        input.disabled = this.readOnly;

        input.addEventListener('paste', (e) => {
            setTimeout(() => {
                this.handleUrlChange(input.value);
            }, 0);
        });

        input.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                this.handleUrlChange(input.value);
            }
        });

        input.addEventListener('blur', () => {
            if (input.value) {
                this.handleUrlChange(input.value);
            }
        });

        inputWrapper.appendChild(input);

        const hint = document.createElement('p');
        hint.className = 'youtube-block__hint';
        hint.textContent = 'Füge eine YouTube-URL ein und drücke Enter';
        inputWrapper.appendChild(hint);

        this.wrapper.appendChild(inputWrapper);
    }

    private handleUrlChange(url: string) {
        const videoId = this.extractVideoId(url);

        if (videoId) {
            this.data.url = url;
            this.data.videoId = videoId;
            this.renderPreview();
            this.api.blocks.getBlockByIndex(this.api.blocks.getCurrentBlockIndex())?.save();
        }
    }

    private renderPreview() {
        if (!this.wrapper || !this.data.videoId) return;
        this.wrapper.innerHTML = '';

        const previewWrapper = document.createElement('div');
        previewWrapper.className = 'youtube-block__preview';

        // Video container with aspect ratio
        const videoContainer = document.createElement('div');
        videoContainer.className = 'youtube-block__video-container';

        const iframe = document.createElement('iframe');
        iframe.src = `https://www.youtube-nocookie.com/embed/${this.data.videoId}`;
        iframe.className = 'youtube-block__iframe';
        iframe.setAttribute('frameborder', '0');
        iframe.setAttribute('allowfullscreen', 'true');
        iframe.setAttribute('allow', 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture');
        iframe.setAttribute('loading', 'lazy');

        videoContainer.appendChild(iframe);
        previewWrapper.appendChild(videoContainer);

        // Caption input
        if (!this.readOnly) {
            const captionInput = document.createElement('input');
            captionInput.type = 'text';
            captionInput.className = 'youtube-block__caption';
            captionInput.placeholder = 'Beschreibung hinzufügen (optional)…';
            captionInput.value = this.data.caption ?? '';
            
            const saveCaption = () => {
                this.data.caption = captionInput.value;
                this.api.blocks.getBlockByIndex(this.api.blocks.getCurrentBlockIndex())?.save();
            };
            
            captionInput.addEventListener('input', (e) => {
                this.data.caption = (e.target as HTMLInputElement).value;
            });
            captionInput.addEventListener('blur', saveCaption);
            captionInput.addEventListener('change', saveCaption);
            captionInput.addEventListener('keydown', (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    captionInput.blur();
                }
            });
            previewWrapper.appendChild(captionInput);

            // Change URL button
            const changeBtn = document.createElement('button');
            changeBtn.type = 'button';
            changeBtn.className = 'youtube-block__change-btn';
            changeBtn.textContent = 'URL ändern';
            changeBtn.addEventListener('click', () => {
                this.data.videoId = '';
                this.data.url = '';
                this.renderInput();
            });
            previewWrapper.appendChild(changeBtn);
        } else if (this.data.caption) {
            const captionText = document.createElement('p');
            captionText.className = 'youtube-block__caption-text';
            captionText.textContent = this.data.caption;
            previewWrapper.appendChild(captionText);
        }

        this.wrapper.appendChild(previewWrapper);
    }

    async save(): Promise<YouTubeBlockData> {
        return {
            url: this.data.url,
            videoId: this.data.videoId,
            caption: this.data.caption,
        };
    }

    validate(data: YouTubeBlockData): boolean {
        return Boolean(data.videoId);
    }

    onPaste(event: { detail: { data: string } }) {
        const url = event.detail.data;
        this.handleUrlChange(url);
    }
}

