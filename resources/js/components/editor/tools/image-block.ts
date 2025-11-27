import type { API, BlockTool, BlockToolData, ToolConfig } from '@editorjs/editorjs';

interface ImageBlockData extends BlockToolData {
    url?: string;
    caption?: string;
    withBorder?: boolean;
    withBackground?: boolean;
    stretched?: boolean;
}

interface ImageBlockConfig extends ToolConfig {
    captionPlaceholder?: string;
}

export default class ImageBlock implements BlockTool {
    private api: API;
    private config: ImageBlockConfig;
    private data: ImageBlockData;
    private wrapper: HTMLElement | null = null;
    private readOnly: boolean;

    static get toolbox() {
        return {
            title: 'Bild',
            icon: '<svg width="17" height="15" viewBox="0 0 336 276" xmlns="http://www.w3.org/2000/svg"><path d="M291 150.242V79c0-18.778-15.222-34-34-34H79c-18.778 0-34 15.222-34 34v42.264l67.179-44.192 80.398 71.614 56.686-29.14L291 150.242zm-.345 51.622l-42.3-30.246-56.3 29.884-80.773-66.925L45 174.187V197c0 18.778 15.222 34 34 34h178c17.126 0 31.295-12.663 33.655-29.136zM79 0h178c43.63 0 79 35.37 79 79v118c0 43.63-35.37 79-79 79H79c-43.63 0-79-35.37-79-79V79C0 35.37 35.37 0 79 0zm105 82a9 9 0 1 1 18 0 9 9 0 0 1-18 0zm-4-25c18.778 0 34 15.222 34 34s-15.222 34-34 34-34-15.222-34-34 15.222-34 34-34z"/></svg>',
        };
    }

    static get isReadOnlySupported() {
        return true;
    }

    constructor({
        data,
        api,
        config,
        readOnly,
    }: {
        data: ImageBlockData;
        api: API;
        config: ImageBlockConfig;
        readOnly: boolean;
    }) {
        this.api = api;
        this.config = config;
        this.readOnly = readOnly;
        this.data = {
            url: data?.url ?? '',
            caption: data?.caption ?? '',
            withBorder: data?.withBorder ?? false,
            withBackground: data?.withBackground ?? false,
            stretched: data?.stretched ?? false,
        };
    }

    render(): HTMLElement {
        this.wrapper = document.createElement('div');
        this.wrapper.className = 'image-block';

        if (this.data.url) {
            this.renderImage();
        } else {
            this.renderUploader();
        }

        return this.wrapper;
    }

    private renderUploader() {
        if (!this.wrapper) return;

        this.wrapper.innerHTML = '';
        this.wrapper.className = 'image-block image-block--empty';

        const uploaderWrapper = document.createElement('div');
        uploaderWrapper.className = 'image-block__uploader';

        // Icon
        const icon = document.createElement('div');
        icon.className = 'image-block__icon';
        icon.innerHTML = `
            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
                <circle cx="8.5" cy="8.5" r="1.5"/>
                <polyline points="21 15 16 10 5 21"/>
            </svg>
        `;
        uploaderWrapper.appendChild(icon);

        // Buttons container
        const buttonsContainer = document.createElement('div');
        buttonsContainer.className = 'image-block__buttons';

        // File upload button
        const uploadBtn = document.createElement('button');
        uploadBtn.type = 'button';
        uploadBtn.className = 'image-block__button image-block__button--primary';
        uploadBtn.innerHTML = `
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                <polyline points="17 8 12 3 7 8"/>
                <line x1="12" y1="3" x2="12" y2="15"/>
            </svg>
            Datei hochladen
        `;
        
        const fileInput = document.createElement('input');
        fileInput.type = 'file';
        fileInput.accept = 'image/*';
        fileInput.style.display = 'none';
        
        uploadBtn.addEventListener('click', () => fileInput.click());
        fileInput.addEventListener('change', (e) => {
            const file = (e.target as HTMLInputElement).files?.[0];
            if (file) {
                this.uploadFile(file);
            }
        });

        buttonsContainer.appendChild(uploadBtn);
        buttonsContainer.appendChild(fileInput);

        // Media library button
        const libraryBtn = document.createElement('button');
        libraryBtn.type = 'button';
        libraryBtn.className = 'image-block__button image-block__button--secondary';
        libraryBtn.innerHTML = `
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="3" y="3" width="18" height="18" rx="2" ry="2"/>
                <line x1="3" y1="9" x2="21" y2="9"/>
                <line x1="9" y1="21" x2="9" y2="9"/>
            </svg>
            Mediathek
        `;
        libraryBtn.addEventListener('click', () => this.openMediaLibrary());
        buttonsContainer.appendChild(libraryBtn);

        uploaderWrapper.appendChild(buttonsContainer);

        // Divider
        const divider = document.createElement('div');
        divider.className = 'image-block__divider';
        divider.textContent = 'oder';
        uploaderWrapper.appendChild(divider);

        // URL input
        const urlWrapper = document.createElement('div');
        urlWrapper.className = 'image-block__url-wrapper';
        
        const urlInput = document.createElement('input');
        urlInput.type = 'url';
        urlInput.className = 'image-block__url-input';
        urlInput.placeholder = 'Bild-URL einfügen…';
        urlInput.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                const url = urlInput.value.trim();
                if (url) {
                    this.uploadByUrl(url);
                }
            }
        });
        urlWrapper.appendChild(urlInput);

        uploaderWrapper.appendChild(urlWrapper);
        this.wrapper.appendChild(uploaderWrapper);
    }

    private renderImage() {
        if (!this.wrapper) return;

        this.wrapper.innerHTML = '';
        this.wrapper.className = 'image-block image-block--filled';

        if (this.data.withBorder) {
            this.wrapper.classList.add('image-block--with-border');
        }
        if (this.data.withBackground) {
            this.wrapper.classList.add('image-block--with-background');
        }
        if (this.data.stretched) {
            this.wrapper.classList.add('image-block--stretched');
        }

        // Image container
        const imageContainer = document.createElement('div');
        imageContainer.className = 'image-block__image-container';

        const img = document.createElement('img');
        img.src = this.data.url ?? '';
        img.alt = this.data.caption ?? '';
        img.className = 'image-block__image';
        imageContainer.appendChild(img);

        this.wrapper.appendChild(imageContainer);

        // Caption
        if (!this.readOnly) {
            const caption = document.createElement('input');
            caption.type = 'text';
            caption.className = 'image-block__caption';
            caption.placeholder = this.config.captionPlaceholder ?? 'Bildunterschrift eingeben…';
            caption.value = this.data.caption ?? '';
            caption.addEventListener('input', (e) => {
                this.data.caption = (e.target as HTMLInputElement).value;
            });
            this.wrapper.appendChild(caption);

            // Change image button
            const changeBtn = document.createElement('button');
            changeBtn.type = 'button';
            changeBtn.className = 'image-block__change-btn';
            changeBtn.textContent = 'Bild ändern';
            changeBtn.addEventListener('click', () => {
                this.data.url = '';
                this.renderUploader();
            });
            this.wrapper.appendChild(changeBtn);
        } else if (this.data.caption) {
            const captionText = document.createElement('p');
            captionText.className = 'image-block__caption-text';
            captionText.textContent = this.data.caption;
            this.wrapper.appendChild(captionText);
        }
    }

    private async uploadFile(file: File) {
        if (!this.wrapper) return;

        // Show loading state
        this.wrapper.innerHTML = '';
        this.wrapper.className = 'image-block image-block--loading';
        
        const loadingDiv = document.createElement('div');
        loadingDiv.className = 'image-block__loading';
        loadingDiv.innerHTML = `
            <div class="image-block__spinner"></div>
            <p>Bild wird hochgeladen…</p>
        `;
        this.wrapper.appendChild(loadingDiv);

        try {
            const formData = new FormData();
            formData.append('image', file);

            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

            const response = await fetch('/upload/image', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken ?? '',
                    'Accept': 'application/json',
                },
                body: formData,
            });

            const result = await response.json();

            if (result.success === 1 && result.file?.url) {
                this.data.url = result.file.url;
                this.renderImage();
            } else {
                this.showError(result.message ?? 'Upload fehlgeschlagen');
            }
        } catch (error) {
            this.showError('Upload fehlgeschlagen');
        }
    }

    private async uploadByUrl(url: string) {
        if (!this.wrapper) return;

        // Show loading state
        this.wrapper.innerHTML = '';
        this.wrapper.className = 'image-block image-block--loading';
        
        const loadingDiv = document.createElement('div');
        loadingDiv.className = 'image-block__loading';
        loadingDiv.innerHTML = `
            <div class="image-block__spinner"></div>
            <p>Bild wird geladen…</p>
        `;
        this.wrapper.appendChild(loadingDiv);

        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

            const response = await fetch('/upload/image-by-url', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken ?? '',
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ url }),
            });

            const result = await response.json();

            if (result.success === 1 && result.file?.url) {
                this.data.url = result.file.url;
                this.renderImage();
            } else {
                this.showError(result.message ?? 'URL konnte nicht geladen werden');
            }
        } catch (error) {
            this.showError('URL konnte nicht geladen werden');
        }
    }

    private openMediaLibrary() {
        // Dispatch custom event to open media library
        const event = new CustomEvent('open-media-library', {
            detail: {
                type: 'image',
                onSelect: (media: { url: string; alt?: string }) => {
                    this.data.url = media.url;
                    if (media.alt) {
                        this.data.caption = media.alt;
                    }
                    this.renderImage();
                },
            },
        });
        window.dispatchEvent(event);
    }

    private showError(message: string) {
        if (!this.wrapper) return;

        this.wrapper.innerHTML = '';
        this.wrapper.className = 'image-block image-block--error';

        const errorDiv = document.createElement('div');
        errorDiv.className = 'image-block__error';
        errorDiv.innerHTML = `
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10"/>
                <line x1="12" y1="8" x2="12" y2="12"/>
                <line x1="12" y1="16" x2="12.01" y2="16"/>
            </svg>
            <p>${message}</p>
            <button type="button" class="image-block__retry-btn">Erneut versuchen</button>
        `;
        
        const retryBtn = errorDiv.querySelector('.image-block__retry-btn');
        retryBtn?.addEventListener('click', () => this.renderUploader());
        
        this.wrapper.appendChild(errorDiv);
    }

    renderSettings() {
        return [
            {
                icon: '<svg width="17" height="10" viewBox="0 0 17 10" xmlns="http://www.w3.org/2000/svg"><path d="M13.568 5.925H4.056l1.703 1.703a1.125 1.125 0 0 1-1.59 1.591L.962 6.014A1.069 1.069 0 0 1 .588 4.26L4.38.469a1.069 1.069 0 0 1 1.512 1.511L4.084 3.787h9.606l-1.85-1.85a1.069 1.069 0 1 1 1.512-1.51l3.792 3.791a1.069 1.069 0 0 1-.475 1.788L13.514 9.3a1.125 1.125 0 0 1-1.59-1.591l1.644-1.784z"/></svg>',
                label: 'Gestreckt',
                toggle: 'stretched',
                isActive: this.data.stretched,
                onActivate: () => {
                    this.data.stretched = !this.data.stretched;
                    this.wrapper?.classList.toggle('image-block--stretched', this.data.stretched);
                },
            },
            {
                icon: '<svg width="20" height="20" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path d="M15.8 10.592v2.043h2.35v2.138H15.8v2.232h-2.25v-2.232h-2.4v-2.138h2.4v-2.28h2.25v.237h.05zm-11.8.817h2.35v2.138H4v-2.138zm6-1.817h2.35v2.138H10v-2.138zM4 5.775h2.35v2.138H4V5.775zm6 0h2.35v2.138H10V5.775zm6 0h2.35v2.138H16V5.775zM4 .592h2.35V2.73H4V.592zm6 0h2.35V2.73H10V.592zm6 0h2.35V2.73H16V.592z"/></svg>',
                label: 'Mit Hintergrund',
                toggle: 'withBackground',
                isActive: this.data.withBackground,
                onActivate: () => {
                    this.data.withBackground = !this.data.withBackground;
                    this.wrapper?.classList.toggle('image-block--with-background', this.data.withBackground);
                },
            },
            {
                icon: '<svg width="20" height="20" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path d="M15.8 10.592v2.043h2.35v2.138H15.8v2.232h-2.25v-2.232h-2.4v-2.138h2.4v-2.28h2.25v.237h.05zm-11.8.817h2.35v2.138H4v-2.138zm6-1.817h2.35v2.138H10v-2.138zM4 5.775h2.35v2.138H4V5.775zm6 0h2.35v2.138H10V5.775zm6 0h2.35v2.138H16V5.775zM4 .592h2.35V2.73H4V.592zm6 0h2.35V2.73H10V.592zm6 0h2.35V2.73H16V.592z"/></svg>',
                label: 'Mit Rahmen',
                toggle: 'withBorder',
                isActive: this.data.withBorder,
                onActivate: () => {
                    this.data.withBorder = !this.data.withBorder;
                    this.wrapper?.classList.toggle('image-block--with-border', this.data.withBorder);
                },
            },
        ];
    }

    save(): ImageBlockData {
        return {
            url: this.data.url,
            caption: this.data.caption,
            withBorder: this.data.withBorder,
            withBackground: this.data.withBackground,
            stretched: this.data.stretched,
        };
    }

    validate(savedData: ImageBlockData): boolean {
        return !!savedData.url;
    }
}

