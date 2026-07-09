@props(['src' => null, 'name' => '', 'size' => 'w-8 h-8'])

@php
    $initials = collect(preg_split('/\s+/', trim($name)))
        ->filter()
        ->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))
        ->take(2)
        ->implode('');
@endphp

@if($src)
    <img src="{{ $src }}" alt="" {{ $attributes->merge(['class' => "$size rounded-full object-cover"]) }}>
@else
    <div {{ $attributes->merge(['class' => "$size rounded-full bg-brand-600 text-white flex items-center justify-center text-xs font-bold shrink-0"]) }}>
        {{ $initials }}
    </div>
@endif
