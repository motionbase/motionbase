'use client';

import { useState, useCallback } from 'react';
import { cn } from '@/lib/utils';

interface CodeBlockProps {
    code: string;
    language?: string;
    filename?: string;
    showLineNumbers?: boolean;
    className?: string;
}

const LANGUAGE_LABELS: Record<string, string> = {
    javascript: 'JavaScript',
    typescript: 'TypeScript',
    jsx: 'JSX',
    tsx: 'TSX',
    php: 'PHP',
    css: 'CSS',
    html: 'HTML',
    markup: 'HTML',
    bash: 'Terminal',
    shell: 'Terminal',
    sh: 'Terminal',
    json: 'JSON',
    yaml: 'YAML',
    sql: 'SQL',
    python: 'Python',
    ruby: 'Ruby',
    go: 'Go',
    rust: 'Rust',
    java: 'Java',
    kotlin: 'Kotlin',
    swift: 'Swift',
    c: 'C',
    cpp: 'C++',
    csharp: 'C#',
    plaintext: 'Text',
};

export function CodeBlock({
    code,
    language = 'plaintext',
    filename,
    showLineNumbers = false,
    className,
}: CodeBlockProps) {
    const [copied, setCopied] = useState(false);

    const copyToClipboard = useCallback(async () => {
        try {
            await navigator.clipboard.writeText(code);
            setCopied(true);
            setTimeout(() => setCopied(false), 2000);
        } catch {
            // Fallback für ältere Browser
            const textArea = document.createElement('textarea');
            textArea.value = code;
            textArea.style.position = 'fixed';
            textArea.style.left = '-999999px';
            document.body.appendChild(textArea);
            textArea.select();
            document.execCommand('copy');
            document.body.removeChild(textArea);
            setCopied(true);
            setTimeout(() => setCopied(false), 2000);
        }
    }, [code]);

    const languageLabel = LANGUAGE_LABELS[language] || language;
    const languageClass = `language-${language}`;

    const lines = code.split('\n');

    return (
        <div
            className={cn(
                'group relative mb-6 overflow-hidden rounded-xl border border-zinc-200 bg-zinc-50',
                className,
            )}
        >
            {/* Header */}
            <div className="flex items-center justify-between border-b border-zinc-200 bg-zinc-100/80 px-4 py-2">
                {/* Filename or language */}
                <span className="text-xs font-medium text-zinc-600">
                    {filename || languageLabel}
                </span>

                {/* Copy Button */}
                <button
                    onClick={copyToClipboard}
                    className={cn(
                        'flex items-center gap-1.5 rounded-md px-2.5 py-1.5 text-xs font-medium transition-all',
                        copied
                            ? 'bg-emerald-500/10 text-emerald-600'
                            : 'text-zinc-500 hover:bg-zinc-200 hover:text-zinc-800',
                    )}
                    aria-label={copied ? 'Kopiert!' : 'Code kopieren'}
                >
                    {copied ? (
                        <>
                            <CheckIcon className="h-3.5 w-3.5" />
                            <span>Kopiert!</span>
                        </>
                    ) : (
                        <>
                            <CopyIcon className="h-3.5 w-3.5" />
                            <span className="hidden sm:inline">Kopieren</span>
                        </>
                    )}
                </button>
            </div>

            {/* Code Content */}
            <div className="relative overflow-x-auto bg-white">
                <pre
                    className={cn(
                        'p-4 text-sm leading-relaxed',
                        showLineNumbers && 'pl-0',
                        languageClass,
                    )}
                >
                    {showLineNumbers ? (
                        <code className={cn('grid', languageClass)}>
                            {lines.map((line, index) => (
                                <span key={index} className="table-row">
                                    <span className="table-cell select-none pr-4 text-right text-zinc-400">
                                        {index + 1}
                                    </span>
                                    <span className="table-cell pl-4">
                                        {line || ' '}
                                    </span>
                                </span>
                            ))}
                        </code>
                    ) : (
                        <code className={languageClass}>{code}</code>
                    )}
                </pre>
            </div>
        </div>
    );
}

function CopyIcon({ className }: { className?: string }) {
    return (
        <svg
            xmlns="http://www.w3.org/2000/svg"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            strokeWidth="2"
            strokeLinecap="round"
            strokeLinejoin="round"
            className={className}
        >
            <rect width="14" height="14" x="8" y="8" rx="2" ry="2" />
            <path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2" />
        </svg>
    );
}

function CheckIcon({ className }: { className?: string }) {
    return (
        <svg
            xmlns="http://www.w3.org/2000/svg"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            strokeWidth="2.5"
            strokeLinecap="round"
            strokeLinejoin="round"
            className={className}
        >
            <polyline points="20 6 9 17 4 12" />
        </svg>
    );
}

