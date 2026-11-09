{{-- Beitrag in der Schüleransicht: ohne Autor-Kontaktdaten, Lesebestätigung, Rückmeldung, Reaktionen, Kommentare --}}
@php
    $header = $post->getMedia('header')->first();
    $files = $post->getMedia('files');
    $images = $post->getMedia('images');
@endphp
<article class="student-card overflow-hidden rounded-xl shadow-sm" id="post-{{ $post->id }}">
    @if($header && str_starts_with((string) $header->mime_type, 'image/'))
        <img src="{{ route('student.media', [$post->id, $header->id]) }}" alt="" class="max-h-56 w-full object-cover" loading="lazy">
    @endif
    <div class="p-4 sm:p-5">
        <div class="mb-1 flex flex-wrap items-center gap-2 text-xs student-muted">
            <span><i class="far fa-clock mr-1"></i>{{ $post->updated_at?->format('d.m.Y') }}</span>
            @if($post->sticky)<span class="rounded-full bg-amber-100 px-2 py-0.5 text-amber-800"><i class="fas fa-thumbtack mr-1"></i>Angeheftet</span>@endif
        </div>
        <h2 class="mb-2 break-words text-lg font-semibold">
            @if($full ?? false)
                {{ $post->header }}
            @else
                <a href="{{ route('student.posts.show', $post->id) }}" class="hover:underline">{{ $post->header }}</a>
            @endif
        </h2>
        <div class="student-prose break-words text-sm leading-relaxed {{ ($full ?? false) ? '' : 'line-clamp-4' }}">
            @safeHtml($post->news)
        </div>

        @if(($full ?? false) && $images->isNotEmpty())
            <div class="mt-4 grid grid-cols-2 gap-2 sm:grid-cols-3">
                @foreach($images as $image)
                    <a href="{{ route('student.media', [$post->id, $image->id]) }}" target="_blank" rel="noopener">
                        <img src="{{ route('student.media', [$post->id, $image->id]) }}?conversion=thumb" alt="{{ $image->name }}" class="aspect-square w-full rounded-lg object-cover" loading="lazy">
                    </a>
                @endforeach
            </div>
        @endif

        @if($files->isNotEmpty())
            <ul class="mt-4 space-y-1 text-sm">
                @foreach($files as $file)
                    <li>
                        <a href="{{ route('student.media', [$post->id, $file->id]) }}" class="inline-flex max-w-full items-center gap-2 hover:underline" style="color: var(--color-primary);">
                            <i class="fas fa-paperclip"></i><span class="truncate">{{ $file->name }}</span>
                        </a>
                    </li>
                @endforeach
            </ul>
        @endif

        @unless($full ?? false)
            <a href="{{ route('student.posts.show', $post->id) }}" class="mt-3 inline-block text-sm font-medium" style="color: var(--color-primary);">Weiterlesen <i class="fas fa-arrow-right ml-1"></i></a>
        @endunless
    </div>
</article>
