@extends('layouts.app')

@section('title', 'Beranda Siswa')
@section('breadcrumb', 'Beranda')

@section('content')
<div class="space-y-6">
    <div class="card overflow-hidden">
        <div class="p-6 bg-gradient-to-br from-brand-600 to-brand-800 text-white">
            <p class="text-brand-100 text-sm">Selamat datang,</p>
            <h2 class="text-2xl md:text-3xl font-bold mt-1">{{ $siswa->nama_siswa }}</h2>
            <p class="text-brand-100 text-sm mt-1">NISN {{ $siswa->nisn }}</p>
        </div>
    </div>

    <div>
        <h3 class="text-base font-semibold text-ink-900 mb-3">Ujian Tersedia</h3>
        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
            @forelse($ujianTersedia as $q)
                <div class="card card-pad flex flex-col">
                    <div class="flex items-center gap-2 mb-2">
                        <span class="badge-info">{{ optional($q->mapel)->nama_mapel ?? 'Umum' }}</span>
                        <span class="{{ $q->status_badge }}">{{ ucfirst($q->status) }}</span>
                    </div>
                    <div class="font-semibold text-ink-900">{{ $q->name }}</div>
                    <div class="text-xs text-ink-500 mt-1">{{ Str::limit($q->description, 80) }}</div>
                    <div class="mt-3 flex items-center justify-between text-xs text-ink-500">
                        <span><x-icon name="clock" class="inline w-3.5 h-3.5"/> {{ $q->duration }} menit</span>
                        <span>{{ $q->questions_count ?? $q->questions()->count() }} soal</span>
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
                            <button class="btn-primary w-full">{{ $info && $info['jumlah_selesai'] > 0 ? 'Ulangi Ujian' : 'Mulai Ujian' }}</button>
                        </form>
                    @endif
                </div>
            @empty
                <div class="col-span-full card card-pad text-center text-ink-500">Belum ada ujian yang tersedia untuk Anda.</div>
            @endforelse
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h3 class="text-base font-semibold text-ink-900">Riwayat Ujian Terakhir</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="table-modern">
                <thead><tr><th>Ujian</th><th>Mapel</th><th>Tanggal</th><th class="text-right">Nilai</th></tr></thead>
                <tbody>
                @forelse($riwayat as $r)
                    <tr>
                        <td class="font-semibold text-ink-900">{{ optional($r->quiz)->name }}</td>
                        <td>{{ optional($r->quiz->mapel ?? null)->nama_mapel ?? '-' }}</td>
                        <td>{{ $r->created_at->format('d M Y H:i') }}</td>
                        <td class="text-right font-bold text-brand-600">{{ $r->score !== null ? number_format($r->score, 1) : '-' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-center py-6 text-ink-500">Belum ada riwayat ujian.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
