import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { cn } from '@/lib/utils';
import { router } from '@inertiajs/react';
import { MessageCircle, Send, X, Loader2 } from 'lucide-react';
import { useState, useRef, useEffect } from 'react';
import ReactMarkdown from 'react-markdown';
import remarkGfm from 'remark-gfm';
import { Prism as SyntaxHighlighter } from 'react-syntax-highlighter';
import { oneDark } from 'react-syntax-highlighter/dist/esm/styles/prism';

interface Message {
    role: 'user' | 'assistant';
    content: string;
}

interface TopicChatProps {
    topicSlug: string;
}

export function TopicChat({ topicSlug }: TopicChatProps) {
    const [isExpanded, setIsExpanded] = useState(false);
    const [messages, setMessages] = useState<Message[]>([]);
    const [input, setInput] = useState('');
    const [isLoading, setIsLoading] = useState(false);
    const [error, setError] = useState<string | null>(null);
    const messagesEndRef = useRef<HTMLDivElement>(null);

    const scrollToBottom = () => {
        messagesEndRef.current?.scrollIntoView({ behavior: 'smooth' });
    };

    useEffect(() => {
        scrollToBottom();
    }, [messages]);

    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();

        if (!input.trim() || isLoading) {
            return;
        }

        const question = input.trim();
        setInput('');
        setError(null);

        // Add user message to chat
        const userMessage: Message = { role: 'user', content: question };
        setMessages((prev) => [...prev, userMessage]);
        setIsLoading(true);

        try {
            const response = await fetch(`/themen/${topicSlug}/chat`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '',
                },
                body: JSON.stringify({ question }),
            });

            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.error || 'Ein Fehler ist aufgetreten');
            }

            // Add assistant message to chat
            const assistantMessage: Message = { role: 'assistant', content: data.answer };
            setMessages((prev) => [...prev, assistantMessage]);
        } catch (err) {
            const errorMessage = err instanceof Error ? err.message : 'Ein Fehler ist aufgetreten';
            setError(errorMessage);
        } finally {
            setIsLoading(false);
        }
    };

    const handleClose = () => {
        setIsExpanded(false);
    };

    const handleOpen = () => {
        setIsExpanded(true);
        // Add welcome message if this is the first time
        if (messages.length === 0) {
            setMessages([
                {
                    role: 'assistant',
                    content: 'Hallo! Ich bin dein Lern-Assistent für dieses Thema. Stelle mir gerne Fragen zu den Kursinhalten!',
                },
            ]);
        }
    };

    return (
        <>
            {/* Chat Button */}
            {!isExpanded && (
                <button
                    onClick={handleOpen}
                    className="fixed bottom-6 right-6 flex h-14 w-14 items-center justify-center rounded-full bg-[#ff0055] text-white shadow-lg transition-all hover:scale-110 hover:shadow-xl focus:outline-none focus:ring-2 focus:ring-[#ff0055] focus:ring-offset-2 z-50"
                    aria-label="Chat öffnen"
                >
                    <MessageCircle className="h-6 w-6" />
                </button>
            )}

            {/* Chat Window */}
            {isExpanded && (
                <div className="fixed bottom-6 right-6 z-50 flex h-[600px] w-[400px] flex-col rounded-2xl border border-zinc-200 bg-white shadow-2xl">
                    {/* Header */}
                    <div className="flex items-center justify-between border-b border-zinc-100 bg-[#ff0055] px-4 py-3 rounded-t-2xl">
                        <div className="flex items-center gap-2 text-white">
                            <MessageCircle className="h-5 w-5" />
                            <span className="font-semibold">Lern-Assistent</span>
                        </div>
                        <button
                            onClick={handleClose}
                            className="rounded-lg p-1 text-white/80 transition-colors hover:bg-white/10 hover:text-white"
                            aria-label="Chat schließen"
                        >
                            <X className="h-5 w-5" />
                        </button>
                    </div>

                    {/* Messages */}
                    <div className="flex-1 overflow-y-auto p-4 space-y-4">
                        {messages.map((message, index) => (
                            <div
                                key={index}
                                className={cn(
                                    'flex',
                                    message.role === 'user' ? 'justify-end' : 'justify-start'
                                )}
                            >
                                <div
                                    className={cn(
                                        'max-w-[80%] rounded-2xl px-4 py-2.5 text-sm',
                                        message.role === 'user'
                                            ? 'bg-[#ff0055] text-white'
                                            : 'bg-zinc-100 text-zinc-900'
                                    )}
                                >
                                    {message.role === 'user' ? (
                                        <div className="whitespace-pre-wrap break-words">
                                            {message.content}
                                        </div>
                                    ) : (
                                        <div className="prose prose-sm max-w-none [&_p]:my-1 [&_h1]:my-2 [&_h2]:my-2 [&_ul]:my-1 [&_ul]:ml-4 [&_ul]:list-disc [&_ol]:my-1 [&_ol]:ml-4 [&_ol]:list-decimal [&_li]:my-0.5 [&_strong]:font-semibold [&_code]:text-xs [&_code]:bg-zinc-800 [&_code]:text-zinc-100 [&_code]:px-1.5 [&_code]:py-0.5 [&_code]:rounded [&_pre]:my-2 [&_pre]:p-0 [&_pre]:bg-transparent">
                                            <ReactMarkdown
                                                remarkPlugins={[remarkGfm]}
                                                components={{
                                                    code({ className, children, ...props }) {
                                                        const match = /language-(\w+)/.exec(className || '');
                                                        const language = match ? match[1] : '';
                                                        const isCodeBlock = className?.includes('language-');

                                                        return isCodeBlock ? (
                                                            <SyntaxHighlighter
                                                                style={oneDark as any}
                                                                language={language}
                                                                PreTag="div"
                                                                customStyle={{
                                                                    margin: 0,
                                                                    borderRadius: '0.375rem',
                                                                    fontSize: '0.75rem',
                                                                    lineHeight: '1.25rem',
                                                                }}
                                                            >
                                                                {String(children).replace(/\n$/, '')}
                                                            </SyntaxHighlighter>
                                                        ) : (
                                                            <code className={className} {...props}>
                                                                {children}
                                                            </code>
                                                        );
                                                    }
                                                }}
                                            >
                                                {message.content}
                                            </ReactMarkdown>
                                        </div>
                                    )}
                                </div>
                            </div>
                        ))}

                        {isLoading && (
                            <div className="flex justify-start">
                                <div className="max-w-[80%] rounded-2xl bg-zinc-100 px-4 py-2.5">
                                    <Loader2 className="h-5 w-5 animate-spin text-zinc-500" />
                                </div>
                            </div>
                        )}

                        {error && (
                            <div className="rounded-lg bg-rose-50 border border-rose-200 px-3 py-2 text-sm text-rose-600">
                                {error}
                            </div>
                        )}

                        <div ref={messagesEndRef} />
                    </div>

                    {/* Input */}
                    <form onSubmit={handleSubmit} className="border-t border-zinc-100 p-4">
                        <div className="flex gap-2">
                            <Input
                                value={input}
                                onChange={(e) => setInput(e.target.value)}
                                placeholder="Stelle eine Frage..."
                                className="flex-1"
                                disabled={isLoading}
                                maxLength={500}
                            />
                            <Button
                                type="submit"
                                disabled={isLoading || !input.trim()}
                                className="bg-[#ff0055] hover:bg-[#e6004d] text-white"
                                size="icon"
                            >
                                <Send className="h-4 w-4" />
                            </Button>
                        </div>
                    </form>
                </div>
            )}
        </>
    );
}
