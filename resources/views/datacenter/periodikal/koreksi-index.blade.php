@extends('layouts.app')
@section('title', 'Administrasi Periodikal — Koreksi Hasil Periodikal')
@section('breadcrumb', 'Data Center / Administrasi Periodikal / Koreksi Hasil Periodikal')

@section('content')
<x-page-header title="Administrasi Periodikal" subtitle="Koreksi Hasil Periodikal — riwayat proses kenaikan kelas/kelulusan"/>

<form method="GET" action="{{ route('periodikal.koreksi.index') }}" class="card card-pad mb-4 flex flex-wrap gap-2">
    <input name="q" value="{{ request('q') }}" class="input flex-1 min-w-[200px]" placeholder="Cari nama/NISN siswa...">
    <select name="ta_asal" class="select w-48">
        <option value="">Semua TA Asal</option>
        @foreach($tahunAjaran as $ta)
            <option value="{{ $ta->id }}" @selected(request('ta_asal')==$ta->id)>{{ $ta->nama_tahun_ajaran }} ({{ $ta->semester }})</option>
        @endforeach
    </select>
    <select name="ta_tujuan" class="select w-48">
        <option value="">Semua TA Tujuan</option>
        @foreach($tahunAjaran as $ta)
            <option value="{{ $ta->id }}" @selected(request('ta_tujuan')==$ta->id)>{{ $ta->nama_tahun_ajaran }} ({{ $ta->semester }})</option>
        @endforeach
    </select>
    <button class="btn-secondary"><x-icon name="search" class="w-4 h-4"/></button>
</form>

<div class="card">
    <table class="table-modern">
        <thead>
            <tr>
                <th>Siswa</th>
                <th>Kelas Lama</th>
                <th>Kelas Baru / Status</th>
                <th>Diproses</th>
                <th>Dikoreksi</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
        @forelse($items as $it)
            <tr>
                <td>
                    <div class="font-semibold text-ink-900">{{ optional($it->siswa)->nama_siswa ?? '—' }}</div>
                    <div class="text-xs text-ink-500">{{ optional($it->siswa)->nisn }}</div>
                </td>
                <td class="text-xs">
                    {{ optional($it->rombelAsal)->nama_rombel ?? '—' }}<br>
                    <span class="text-ink-500">{{ optional($it->tahunAjaranAsal)->nama_tahun_ajaran }}</span>
                </td>
                <td class="text-xs">
                    @if(in_array($it->status, ['Naik Kelas', 'Tinggal Kelas']))
                        {{ optional($it->rombelTujuan)->nama_rombel ?? '—' }}<br>
                        <span class="text-ink-500">{{ optional($it->tahunAjaranTujuan)->nama_tahun_ajaran }}</span><br>
                    @endif
                    @php
                        $badgeWarna = match($it->status) {
                            'Naik Kelas' => ['bg' => '#ecfdf5', 'text' => '#047857'],
                            'Tinggal Kelas' => ['bg' => '#fffbeb', 'text' => '#b45309'],
                            'Lulus' => ['bg' => '#eff6ff', 'text' => '#1d4ed8'],
                            'Keluar' => ['bg' => '#fff1f2', 'text' => '#be123c'],
                            default => ['bg' => '#f1f2f4', 'text' => '#8c8482'],
                        };
                    @endphp
                    <span style="display:inline-block;margin-top:4px;padding:2px 10px;border-radius:999px;font-size:11px;font-weight:600;background:{{ $badgeWarna['bg'] }};color:{{ $badgeWarna['text'] }};">
                        {{ $it->status === 'Keluar' ? 'Keluar / Pindah' : $it->status }}
                    </span>
                </td>
                <td class="text-xs">
                    {{ optional($it->diproses_pada)->format('d M Y H:i') ?? '—' }}<br>
                    <span class="text-ink-500">{{ optional($it->diprosesOleh)->name ?? '—' }}</span>
                </td>
                <td class="text-xs">
                    @if($it->dikoreksi_pada)
                        {{ $it->dikoreksi_pada->format('d M Y H:i') }}<br>
                        <span class="text-ink-500">{{ optional($it->dikoreksiOleh)->name ?? '—' }}</span>
                    @else
                        —
                    @endif
                </td>
                <td class="text-right whitespace-nowrap">
                    <a href="{{ route('periodikal.koreksi.edit', $it) }}" class="btn-ghost p-2"><x-icon name="edit"/></a>
                    <form method="POST" action="{{ route('periodikal.koreksi.undo', $it) }}" class="inline"
                          onsubmit="return confirm('Batalkan (undo) proses periodikal untuk siswa ini? Penempatan kelas baru akan dihapus dan status siswa dikembalikan ke Aktif.')">
                        @csrf @method('DELETE')
                        <button class="btn-ghost p-2 text-rose-600"><x-icon name="trash"/></button>
                    </form>
                </td>
            </tr>
        @empty
            <tr><td colspan="6" class="text-center py-8 text-ink-500">Belum ada riwayat periodikal.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $items->links() }}</div>
@endsection
