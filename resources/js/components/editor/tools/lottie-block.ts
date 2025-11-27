import type {
    API,
    BlockTool,
    BlockToolConstructorOptions,
    BlockToolData,
} from '@editorjs/editorjs';

interface LottieBlockData extends BlockToolData {
    url?: string;
    caption?: string;
    loop?: boolean;
    autoplay?: boolean;
}

interface LottieBlockConfig {
    placeholder?: string;
}

export default class LottieBlock implements BlockTool {
    private api: API;
    private data: LottieBlockData;
    private readOnly: boolean;
    private config: LottieBlockConfig;
    private wrapper: HTMLDivElement | null = null;

    constructor({
        api,
        data,
        readOnly,
        config = {},
    }: BlockToolConstructorOptions<LottieBlockData, LottieBlockConfig>) {
        this.api = api;
        this.readOnly = Boolean(readOnly);
        this.config = config;

        this.data = {
            url: data?.url ?? '',
            caption: data?.caption ?? '',
            loop: data?.loop ?? true,
            autoplay: data?.autoplay ?? true,
        };
    }

    static get isReadOnlySupported() {
        return true;
    }

    static get toolbox() {
        return {
            title: 'Lottie',
            icon: '<svg viewBox="0 0 24 24" width="18" height="18" fill="none"><path d="M12 22c5.523 0 10-4.477 10-10S17.523 2 12 2 2 6.477 2 12s4.477 10 10 10z" stroke="currentColor" stroke-width="1.5"/><path d="M8 12l3 3 5-6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>',
        };
    }

    render() {
        this.wrapper = document.createElement('div');
        this.wrapper.className = 'lottie-block';

        if (this.data.url) {
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
        inputWrapper.className = 'lottie-block__input-wrapper';

        const icon = document.createElement('div');
        icon.className = 'lottie-block__icon';
        icon.innerHTML = `
            <svg viewBox="0 0 24 24" width="32" height="32" fill="none">
                <path d="M12 22c5.523 0 10-4.477 10-10S17.523 2 12 2 2 6.477 2 12s4.477 10 10 10z" stroke="currentColor" stroke-width="1.5"/>
                <path d="M9.5 8.5l6 3.5-6 3.5v-7z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        `;
        inputWrapper.appendChild(icon);

        // File upload button
        const uploadBtn = document.createElement('button');
        uploadBtn.type = 'button';
        uploadBtn.className = 'lottie-block__upload-btn';
        uploadBtn.innerHTML = `
            <svg viewBox="0 0 24 24" width="18" height="18" fill="none">
                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                <polyline points="17,8 12,3 7,8" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                <line x1="12" y1="3" x2="12" y2="15" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
            Lottie-Datei hochladen
        `;
        uploadBtn.disabled = this.readOnly;

        const fileInput = document.createElement('input');
        fileInput.type = 'file';
        fileInput.accept = '.json,.lottie';
        fileInput.style.display = 'none';
        fileInput.addEventListener('change', async (e) => {
            const target = e.target as HTMLInputElement;
            const file = target.files?.[0];
            if (file) {
                await this.uploadFile(file);
            }
        });

        uploadBtn.addEventListener('click', () => fileInput.click());
        inputWrapper.appendChild(uploadBtn);
        inputWrapper.appendChild(fileInput);

        // Divider
        const divider = document.createElement('div');
        divider.className = 'lottie-block__divider';
        divider.innerHTML = '<span>oder</span>';
        inputWrapper.appendChild(divider);

        // URL input
        const input = document.createElement('input');
        input.type = 'text';
        input.className = 'lottie-block__input';
        input.placeholder = this.config.placeholder ?? 'Lottie JSON-URL einfügen…';
        input.value = this.data.url ?? '';
        input.disabled = this.readOnly;

        input.addEventListener('paste', () => {
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
        hint.className = 'lottie-block__hint';
        hint.textContent = 'Unterstützt .json und .lottie Dateien';
        inputWrapper.appendChild(hint);

        this.wrapper.appendChild(inputWrapper);
    }

    private async uploadFile(file: File): Promise<void> {
        const formData = new FormData();
        formData.append('lottie', file);

        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';

        try {
            // Show loading state
            if (this.wrapper) {
                this.wrapper.innerHTML = `
                    <div class="lottie-block__loading">
                        <div class="lottie-block__spinner"></div>
                        <p>Wird hochgeladen…</p>
                    </div>
                `;
            }

            const response = await fetch('/upload/lottie', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
                body: formData,
            });

            if (!response.ok) {
                const errorText = await response.text();
                console.error('Lottie upload failed with status:', response.status, errorText);
                alert(`Upload fehlgeschlagen (${response.status}): ${errorText || 'Unbekannter Fehler'}`);
                this.renderInput();
                return;
            }

            const result = await response.json();

            if (result.success === 1) {
                this.data.url = result.file.url;
                this.renderPreview();
                this.api.blocks.getBlockByIndex(this.api.blocks.getCurrentBlockIndex())?.save();
            } else {
                console.error('Lottie upload failed:', result.message);
                alert('Datei konnte nicht hochgeladen werden: ' + (result.message || 'Unbekannter Fehler'));
                this.renderInput();
            }
        } catch (error) {
            console.error('Lottie upload error:', error);
            alert('Netzwerkfehler beim Hochladen der Datei. Bitte versuche es erneut.');
            this.renderInput();
        }
    }

    private handleUrlChange(url: string) {
        if (url && (url.endsWith('.json') || url.endsWith('.lottie') || url.includes('lottiefiles.com') || url.includes('assets'))) {
            this.data.url = url;
            this.renderPreview();
            this.api.blocks.getBlockByIndex(this.api.blocks.getCurrentBlockIndex())?.save();
        }
    }

    private renderPreview() {
        if (!this.wrapper || !this.data.url) return;
        this.wrapper.innerHTML = '';

        const previewWrapper = document.createElement('div');
        previewWrapper.className = 'lottie-block__preview';

        // Lottie container
        const lottieContainer = document.createElement('div');
        lottieContainer.className = 'lottie-block__animation';
        lottieContainer.id = `lottie-${Date.now()}`;

        // Load lottie-web dynamically
        this.loadLottiePlayer(lottieContainer);

        previewWrapper.appendChild(lottieContainer);

        // Controls (only in edit mode)
        if (!this.readOnly) {
            const controls = document.createElement('div');
            controls.className = 'lottie-block__controls';

            // Loop toggle
            const loopLabel = document.createElement('label');
            loopLabel.className = 'lottie-block__toggle';
            const loopCheckbox = document.createElement('input');
            loopCheckbox.type = 'checkbox';
            loopCheckbox.checked = this.data.loop ?? true;
            loopCheckbox.addEventListener('change', () => {
                this.data.loop = loopCheckbox.checked;
                this.api.blocks.getBlockByIndex(this.api.blocks.getCurrentBlockIndex())?.save();
            });
            loopLabel.appendChild(loopCheckbox);
            loopLabel.appendChild(document.createTextNode(' Loop'));
            controls.appendChild(loopLabel);

            // Autoplay toggle
            const autoplayLabel = document.createElement('label');
            autoplayLabel.className = 'lottie-block__toggle';
            const autoplayCheckbox = document.createElement('input');
            autoplayCheckbox.type = 'checkbox';
            autoplayCheckbox.checked = this.data.autoplay ?? true;
            autoplayCheckbox.addEventListener('change', () => {
                this.data.autoplay = autoplayCheckbox.checked;
                this.api.blocks.getBlockByIndex(this.api.blocks.getCurrentBlockIndex())?.save();
            });
            autoplayLabel.appendChild(autoplayCheckbox);
            autoplayLabel.appendChild(document.createTextNode(' Autoplay'));
            controls.appendChild(autoplayLabel);

            previewWrapper.appendChild(controls);

            // Caption input
            const captionInput = document.createElement('input');
            captionInput.type = 'text';
            captionInput.className = 'lottie-block__caption';
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
            previewWrapper.appendChild(captionInput);

            // Change button
            const changeBtn = document.createElement('button');
            changeBtn.type = 'button';
            changeBtn.className = 'lottie-block__change-btn';
            changeBtn.textContent = 'Animation ändern';
            changeBtn.addEventListener('click', () => {
                this.data.url = '';
                this.renderInput();
            });
            previewWrapper.appendChild(changeBtn);
        } else if (this.data.caption) {
            const captionText = document.createElement('p');
            captionText.className = 'lottie-block__caption-text';
            captionText.textContent = this.data.caption;
            previewWrapper.appendChild(captionText);
        }

        this.wrapper.appendChild(previewWrapper);
    }

    private async loadLottiePlayer(container: HTMLElement) {
        const url = this.data.url ?? '';
        const isDotLottie = url.endsWith('.lottie');

        try {
            if (isDotLottie) {
                // Use dotLottie-web for .lottie files
                const { DotLottie } = await import('@lottiefiles/dotlottie-web');

                // Create a canvas for dotLottie
                const canvas = document.createElement('canvas');
                canvas.style.width = '100%';
                canvas.style.height = '100%';
                container.appendChild(canvas);

                new DotLottie({
                    canvas,
                    src: url,
                    loop: this.data.loop ?? true,
                    autoplay: this.data.autoplay ?? true,
                });
            } else {
                // Use lottie-web for .json files
                // @ts-ignore - dynamic import
                const lottie = await import('lottie-web');

                lottie.default.loadAnimation({
                    container,
                    renderer: 'svg',
                    loop: this.data.loop ?? true,
                    autoplay: this.data.autoplay ?? true,
                    path: url,
                });
            }
        } catch (error) {
            console.error('Failed to load Lottie animation:', error);
            container.innerHTML = `
                <div class="lottie-block__error">
                    <p>Lottie-Animation konnte nicht geladen werden.</p>
                    <small>${url}</small>
                </div>
            `;
        }
    }

    async save(): Promise<LottieBlockData> {
        return {
            url: this.data.url,
            caption: this.data.caption,
            loop: this.data.loop,
            autoplay: this.data.autoplay,
        };
    }

    validate(data: LottieBlockData): boolean {
        return Boolean(data.url);
    }
}

