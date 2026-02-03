@extends('lti.layout')

@section('title', $chapter->title . ' - ' . $topic->title)

@section('content')
<div class="min-h-screen flex flex-col lg:flex-row">
    {{-- Sidebar Navigation --}}
    <aside class="w-full lg:w-72 border-b lg:border-b-0 lg:border-r border-zinc-100 bg-white p-4 lg:p-6 lg:sticky lg:top-0 lg:h-screen lg:overflow-y-auto">
        <div class="mb-6 pb-4 border-b border-zinc-100">
            <p class="text-xs text-zinc-400 mb-1">{{ $topic->title }}</p>
            <h1 class="text-lg font-semibold text-zinc-900">{{ $chapter->title }}</h1>
        </div>

        <nav class="space-y-1">
            @foreach($chapter->sections as $section)
                @php
                    $isActive = $activeSection && $section->id === $activeSection->id;
                    $url = route('lti.embed.chapter', [
                        'topic' => $topic->slug,
                        'chapter' => $chapter->slug,
                        'lti_session' => $session->session_token
                    ]) . '#section-' . $section->slug;
                @endphp
                <a
                    href="{{ $url }}"
                    data-section="{{ $section->slug }}"
                    class="section-link flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium transition-colors {{ $isActive ? 'bg-zinc-900 text-white' : 'text-zinc-500 hover:bg-zinc-50 hover:text-zinc-900' }}"
                >
                    <span class="flex items-center justify-center w-5 h-5 rounded-full text-[10px] font-bold {{ $isActive ? 'bg-white/20 text-white' : 'bg-zinc-100 text-zinc-500' }}">
                        {{ $loop->iteration }}
                    </span>
                    <span class="truncate">{{ $section->title }}</span>
                </a>
            @endforeach
        </nav>
    </aside>

    {{-- Main Content - All sections of this chapter --}}
    <main class="flex-1 p-6 lg:p-10 lg:max-h-screen lg:overflow-y-auto">
        @foreach($chapter->sections as $section)
            <section id="section-{{ $section->slug }}" class="mb-16 pb-16 border-b border-zinc-100 last:border-b-0 last:mb-0 last:pb-0">
                <header class="mb-8">
                    <h2 class="text-3xl lg:text-4xl font-bold tracking-tight text-zinc-900">
                        {{ $section->title }}
                    </h2>
                </header>

                <article class="prose prose-zinc max-w-none">
                    @include('lti.partials.content-blocks', ['blocks' => $section->content['blocks'] ?? []])
                </article>
            </section>
        @endforeach
    </main>
</div>

@push('scripts')
<script>
    // Highlight active section in nav when scrolling
    const sections = document.querySelectorAll('section[id^="section-"]');
    const navLinks = document.querySelectorAll('.section-link');

    function updateActiveSection() {
        let current = '';
        sections.forEach(section => {
            const rect = section.getBoundingClientRect();
            if (rect.top <= 100) {
                current = section.getAttribute('id').replace('section-', '');
            }
        });

        navLinks.forEach(link => {
            const slug = link.getAttribute('data-section');
            if (slug === current) {
                link.classList.add('bg-zinc-900', 'text-white');
                link.classList.remove('text-zinc-500', 'hover:bg-zinc-50', 'hover:text-zinc-900');
                link.querySelector('span:first-child').classList.add('bg-white/20', 'text-white');
                link.querySelector('span:first-child').classList.remove('bg-zinc-100', 'text-zinc-500');
            } else {
                link.classList.remove('bg-zinc-900', 'text-white');
                link.classList.add('text-zinc-500', 'hover:bg-zinc-50', 'hover:text-zinc-900');
                link.querySelector('span:first-child').classList.remove('bg-white/20', 'text-white');
                link.querySelector('span:first-child').classList.add('bg-zinc-100', 'text-zinc-500');
            }
        });
    }

    document.querySelector('main').addEventListener('scroll', updateActiveSection);
    window.addEventListener('scroll', updateActiveSection);
</script>
@endpush
@endsection
