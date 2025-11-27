import { Spinner } from '@/components/ui/spinner';
import { cn } from '@/lib/utils';
import type { OutputData, ToolConstructable } from '@editorjs/editorjs';
import { useEffect, useRef, useState } from 'react';

type EditorInstance = import('@editorjs/editorjs').default;

const defaultValue: OutputData = {
    time: Date.now(),
    blocks: [
        {
            type: 'paragraph',
            data: {
                text: '',
            },
        },
    ],
    version: '2.31.0',
};

interface RichTextEditorProps {
    initialValue?: OutputData;
    onChange?: (value: OutputData) => void;
    placeholder?: string;
    className?: string;
    readOnly?: boolean;
}

export function RichTextEditor({
    initialValue,
    onChange,
    placeholder = 'Starte mit deinem ersten Abschnitt…',
    className,
    readOnly = false,
}: RichTextEditorProps) {
    const holderRef = useRef<HTMLDivElement | null>(null);
    const editorRef = useRef<EditorInstance | null>(null);
    const initialValueRef = useRef<OutputData | undefined>(initialValue);
    const hasHydratedRef = useRef(false);
    const onChangeRef = useRef(onChange);
    const [isReady, setIsReady] = useState(false);

    useEffect(() => {
        onChangeRef.current = onChange;
    }, [onChange]);

    useEffect(() => {
        if (!hasHydratedRef.current) {
            initialValueRef.current = initialValue ?? defaultValue;
        }
    }, [initialValue]);

    useEffect(() => {
        let isActive = true;

        const initialize = async () => {
            if (typeof window === 'undefined' || !holderRef.current || editorRef.current) {
                return;
            }

            const [
                { default: EditorJS },
                { default: Header },
                { default: List },
                { default: Paragraph },
                { default: ImageTool },
                { default: CodeWithLanguage },
                { default: AlertBlock },
                { default: QuizBlock },
                { default: YouTubeBlock },
            ] =
                await Promise.all([
                    import('@editorjs/editorjs'),
                    import('@editorjs/header'),
                    import('@editorjs/list'),
                    import('@editorjs/paragraph'),
                    import('@editorjs/image'),
                    import('@/components/editor/tools/code-with-language'),
                    import('@/components/editor/tools/alert-block'),
                    import('@/components/editor/tools/quiz-block'),
                    import('@/components/editor/tools/youtube-block'),
                ]);

            if (!isActive || !holderRef.current) {
                return;
            }

            const editor = new EditorJS({
                holder: holderRef.current,
                readOnly,
                placeholder,
                data: initialValueRef.current ?? defaultValue,
                inlineToolbar: true,
                tools: {
                    header: {
                        class: Header as unknown as ToolConstructable,
                        config: {
                            levels: [2, 3, 4],
                            defaultLevel: 2,
                        },
                    },
                    list: {
                        class: List as unknown as ToolConstructable,
                        inlineToolbar: true,
                        config: {
                            defaultStyle: 'unordered',
                        },
                    },
                    paragraph: {
                        class: Paragraph as unknown as ToolConstructable,
                    },
                    image: {
                        class: ImageTool as unknown as ToolConstructable,
                        config: {
                            captionPlaceholder: 'Bildunterschrift eingeben…',
                            buttonContent: 'Bild auswählen',
                            uploader: {
                                async uploadByFile(file: File) {
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

                                    return response.json();
                                },
                                async uploadByUrl(url: string) {
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

                                    return response.json();
                                },
                            },
                        },
                    },
                    code: {
                        class: CodeWithLanguage as unknown as ToolConstructable,
                        config: {
                            placeholder: 'Füge hier deinen Code ein…',
                            languages: [
                                { label: 'JavaScript', value: 'javascript' },
                                { label: 'TypeScript', value: 'typescript' },
                                { label: 'PHP', value: 'php' },
                                { label: 'CSS', value: 'css' },
                                { label: 'HTML', value: 'markup' },
                                { label: 'Shell', value: 'bash' },
                            ],
                            defaultLanguage: 'javascript',
                        },
                    },
                    alert: {
                        class: AlertBlock as unknown as ToolConstructable,
                        config: {
                            defaultType: 'info',
                            placeholder: 'Beschreibe deinen Hinweis…',
                        },
                    },
                    quiz: {
                        class: QuizBlock as unknown as ToolConstructable,
                    },
                    youtube: {
                        class: YouTubeBlock as unknown as ToolConstructable,
                        config: {
                            placeholder: 'YouTube-URL einfügen…',
                        },
                    },
                },
                async onChange(api) {
                    const data = await api.saver.save();
                    onChangeRef.current?.(data);
                },
                onReady() {
                    hasHydratedRef.current = true;
                    setIsReady(true);
                },
            });

            editorRef.current = editor;
        };

        void initialize();

        return () => {
            isActive = false;
            editorRef.current?.destroy();
            editorRef.current = null;
        };
    }, [placeholder, readOnly]);

    const isGutenbergStyle = className?.includes('gutenberg-editor');

    return (
        <div
            className={cn(
                'relative min-h-[320px] transition',
                !isGutenbergStyle && 'rounded-2xl border border-zinc-200 bg-white shadow-sm focus-within:border-zinc-900 focus-within:shadow-md',
                className,
            )}
        >
            {!isReady && (
                <div className="pointer-events-none absolute inset-0 flex items-center justify-center">
                    <Spinner className="size-6 text-zinc-400" />
                </div>
            )}
            <div
                className={cn(
                    'editorjs h-full w-full',
                    isGutenbergStyle ? 'py-2' : 'px-4 py-4 sm:px-6 sm:py-6',
                )}
                ref={holderRef}
            />
        </div>
    );
}

