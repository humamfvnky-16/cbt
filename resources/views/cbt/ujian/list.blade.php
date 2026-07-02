@extends('layouts.app')
@section('title', 'Daftar Ujian')

@section('content')
<x-page-header title="Daftar Ujian Tersedia" subtitle="Pilih ujian untuk dikerjakan"/>

<div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
    @forelse($quizzes as $q)
        <div class="card card-pad flex flex-col">
            <div class="flex items-center gap-2 mb-2 flex-wrap">
                <span class="badge-info">{{ optional($q->mapel)->nama_mapel ?? 'Umum' }}</span>
                <span class="{{ $q->status_badge }}">{{ ucfirst($q->status) }}</span>
            </div>
            <div class="text-lg font-bold text-ink-900">{{ $q->name }}</div>
            <div class="text-sm text-ink-500 mt-1 flex-1">{{ Str::limit($q->description, 100) }}</div>
            <div class="mt-3 flex items-center justify-between text-xs text-ink-500 border-t border-slate-100 pt-3">
                <span><x-icon name="clock" class="inline w-3.5 h-3.5"/> {{ $q->duration }} menit</span>
                <span>{{ $q->questions_count }} soal</span>
            </div>

            @php
                $info = $statusUjian[$q->id] ?? null;
                $sudahMaxAttempt = $info && $q->max_attempts && $info['jumlah_selesai'] >= $q->max_attempts;
            @endphp

            @if($info && $info['attempt_blokir'])
                <div class="mt-4">
                    <button type="button" disabled class="btn-secondary w-full opacity-60 cursor-not-allowed">
                        <x-icon name="key" class="w-4 h-4"/> Ujian Diblokir
                    </button>
                    <a href="{{ route('siswa.ujian.blocked', [$q, $info['attempt_blokir']]) }}" class="block text-center text-xs text-ink-500 mt-1 underline">Lihat detail</a>
                </div>
            @elseif($info && $info['attempt_sedang'])
                <a href="{{ route('siswa.ujian.show', [$q, $info['attempt_sedang']]) }}" class="btn-primary w-full mt-4 block text-center">Lanjutkan Ujian</a>
            @elseif($sudahMaxAttempt)
                <div class="mt-4">
                    <button type="button" disabled class="btn-secondary w-full cursor-not-allowed" style="opacity:.55;filter:grayscale(.4);">
                        <x-icon name="key" class="w-4 h-4"/> Ujian Terkunci
                    </button>
                    @if($info['attempt_terbaru_selesai'])
                        <a href="{{ route('siswa.ujian.result', [$q, $info['attempt_terbaru_selesai']]) }}" class="block text-center text-xs text-ink-500 mt-1 underline">Lihat Hasil</a>
                    @endif
                </div>
            @else
                <form method="POST" action="{{ route('siswa.ujian.start', $q) }}" class="mt-4">
                    @csrf
                    <button class="btn-primary w-full">{{ $info && $info['jumlah_selesai'] > 0 ? 'Ulangi Ujian' : 'Mulai Ujian' }} <x-icon name="arrow-right" class="w-4 h-4"/></button>
                </form>
            @endif
        </div>
    @empty
        <div class="col-span-full card card-pad text-center text-ink-500">Belum ada ujian yang dapat dikerjakan.</div>
    @endforelse
</div>
<div class="mt-4">{{ $quizzes->links() }}</div>
@endsection
