@props(['post', 'ratio' => '16/9', 'size' => '28'])
@php
    $coverUrl = null;
    if ($post->cover_image) {
        $coverUrl = Str::startsWith($post->cover_image, ['http://', 'https://'])
            ? $post->cover_image
            : Storage::url($post->cover_image);
    }
    $initial = mb_substr($post->category->name, 0, 1);
@endphp
@if($coverUrl)
    <img src="{{ $coverUrl }}" alt="{{ $post->title }}" loading="lazy"
         class="aspect-[{{ $ratio }}] w-full object-cover transition-transform duration-300 group-hover:scale-[1.02]">
@else
    <div class="flex aspect-[{{ $ratio }}] w-full items-center justify-center bg-canvas-subtle text-ink-muted text-2xl font-bold transition-transform duration-300 group-hover:scale-[1.02]">
        {{ $initial }}
    </div>
@endif
