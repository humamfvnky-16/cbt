@extends('layouts.app')
@section('title', 'Administrasi Periodikal — Proses Semua Siswa')
@section('breadcrumb', 'Data Center / Administrasi Periodikal / Proses Semua Siswa')

@section('content')
<x-page-header title="Administrasi Periodikal" subtitle="Proses Semua Siswa — kenaikan kelas/kelulusan massal per rombel"/>

@if($errors->any())
    <div class="card card-pad mb-4 text-sm text-rose-700" style="border:1px solid #fecdd3;background:#fff1f2;">
        @foreach($errors->all() as $pesan)
            <p>{{ $pesan }}</p>
        @endforeach
    </div>
@endif

<form method="GET" action="{{ route('periodikal.semua.form') }}" class="card card-pad mb-4 flex flex-wrap items-end gap-3">
    <div class="min-w-[220px]">
        <label class="label">Tahun Ajaran Asal</label>
        <select name="ta_asal" class="select">
            <option value="">— Pilih —</option>
            @foreach($tahunAjaran as $ta)
                <option value="{{ $ta->id }}" @selected($asalId == $ta->id)>{{ $ta->nama_tahun_ajaran }} ({{ $ta->semester }})</option>
            @endforeach
        </select>
    </div>
    <div class="min-w-[220px]">
        <label class="label">Tahun Ajaran Tujuan</label>
        <select name="ta_tujuan" class="select">
            <option value="">— Pilih —</option>
            @foreach($tahunAjaran as $ta)
                <option value="{{ $ta->id }}" @selected($tujuanId == $ta->id)>{{ $ta->nama_tahun_ajaran }} ({{ $ta->semester }})</option>
            @endforeach
        </select>
    </div>
    <button class="btn-secondary"><x-icon name="search" class="w-4 h-4"/> Tampilkan</button>
</form>

@if($asalId && $tujuanId)

    @if($tujuanBelumAdaRombel)
        <div class="card card-pad mb-4" style="border:1px solid #fde68a;background:#fffbeb;">
            <p class="text-sm text-ink-700 mb-3">
                Tahun ajaran tujuan belum memiliki struktur rombel. Anda bisa membuatnya otomatis
                (nama &amp; tingkat rombel diduplikasi dari tahun ajaran asal, tingkat dinaikkan +1),
                atau membuatnya manual lewat menu <b>Rombongan Belajar</b>.
            </p>
            <form method="POST" action="{{ route('periodikal.duplikasi-struktur') }}">
                @csrf
                <input type="hidden" name="ta_asal" value="{{ $asalId }}">
                <input type="hidden" name="ta_tujuan" value="{{ $tujuanId }}">
                <button class="btn-primary"><x-icon name="layers" class="w-4 h-4"/> Duplikasi Struktur Kelas</button>
            </form>
        </div>
    @endif

    @if($existing->isNotEmpty())
        @php $contoh = $existing->first(); @endphp
        <div class="card card-pad mb-4" style="border:1px solid #bfdbfe;background:#eff6ff;">
            <p class="text-sm text-ink-700">
                Kombinasi tahun ajaran ini <b>sudah pernah diproses sebelumnya</b>
                (terakhir: {{ optional($contoh->diproses_pada)->format('d M Y H:i') }}
                oleh {{ optional($contoh->diprosesOleh)->name ?? '—' }}).
                Mengirim ulang form di bawah akan <b>memperbarui</b> hasil sebelumnya, bukan menduplikasi data.
            </p>
        </div>
    @endif

    @if($rombelGroups && $rombelGroups->isNotEmpty())
        <form method="POST" action="{{ route('periodikal.semua.proses') }}" id="form-periodikal-semua">
            @csrf
            <input type="hidden" name="ta_asal" value="{{ $asalId }}">
            <input type="hidden" name="ta_tujuan" value="{{ $tujuanId }}">

            <div class="card">
                <table class="table-modern">
                    <thead>
                        <tr>
                            <th>Kelas Lama</th>
                            <th>Jumlah Siswa</th>
                            <th>Kelas Baru</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach($rombelGroups as $i => $rb)
                        @php
                            $riwayat = $existing->get($rb->id);
                            $tingkatTujuan = $rb->tingkat + 1;
                            $tebakan = $rombelTujuanOptions
                                ->where('tingkat', $tingkatTujuan)
                                ->first();
                            $defaultTujuan = $riwayat->rombel_tujuan_id ?? optional($tebakan)->id;
                            $defaultStatus = $riwayat->status ?? 'Naik Kelas';
                        @endphp
                        <tr>
                            <td class="font-semibold text-ink-900">
                                {{ $rb->nama_rombel }} <span class="text-xs text-ink-500">(Tingkat {{ $rb->tingkat }})</span>
                                <input type="hidden" name="rombel[{{ $i }}][rombel_asal_id]" value="{{ $rb->id }}">
                            </td>
                            <td>{{ $rb->siswa_count }} siswa</td>
                            <td>
                                <select name="rombel[{{ $i }}][rombel_tujuan_id]" class="select periodikal-rombel-tujuan">
                                    <option value="">— Lulus/Keluar —</option>
                                    @foreach($rombelTujuanOptions as $rt)
                                        <option value="{{ $rt->id }}" @selected($defaultTujuan == $rt->id)>{{ $rt->nama_rombel }} (Tingkat {{ $rt->tingkat }})</option>
                                    @endforeach
                                </select>
                            </td>
                            <td>
                                <select name="rombel[{{ $i }}][status]" class="select periodikal-status">
                                    <option value="Naik Kelas" @selected($defaultStatus === 'Naik Kelas')>Naik Kelas</option>
                                    <option value="Tinggal Kelas" @selected($defaultStatus === 'Tinggal Kelas')>Tinggal Kelas</option>
                                    <option value="Lulus" @selected($defaultStatus === 'Lulus')>Lulus</option>
                                    <option value="Keluar" @selected($defaultStatus === 'Keluar')>Keluar / Pindah</option>
                                </select>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>

            <div class="flex justify-end mt-4">
                <button class="btn-primary" onclick="return confirm('Proses periodikal untuk semua rombel di atas? Tindakan ini memindahkan/meluluskan seluruh siswa pada rombel yang ditampilkan.')">
                    <x-icon name="check" class="w-4 h-4"/> Proses Semua
                </button>
            </div>
        </form>
    @else
        <div class="card card-pad text-center py-8 text-ink-500">
            Tidak ada rombel dengan siswa aktif pada tahun ajaran asal ini.
        </div>
    @endif
@endif

@endsection

@push('scripts')
<script>
(function () {
    function terapkanToggle(row) {
        var statusSelect = row.querySelector('.periodikal-status');
        var tujuanSelect = row.querySelector('.periodikal-rombel-tujuan');
        if (!statusSelect || !tujuanSelect) return;
        var perluTujuan = ['Naik Kelas', 'Tinggal Kelas'].indexOf(statusSelect.value) !== -1;
        tujuanSelect.disabled = !perluTujuan;
        if (!perluTujuan) tujuanSelect.value = '';
    }

    document.querySelectorAll('#form-periodikal-semua tbody tr').forEach(function (row) {
        terapkanToggle(row);
        row.addEventListener('change', function (e) {
            if (e.target.classList.contains('periodikal-status')) terapkanToggle(row);
        });
    });
})();
</script>
@endpush
