@extends('layouts.app')
@section('title', 'Administrasi Periodikal — Proses Siswa Per Rombel')
@section('breadcrumb', 'Data Center / Administrasi Periodikal / Proses Siswa Per Rombel')

@section('content')
<x-page-header title="Administrasi Periodikal" subtitle="Proses Siswa Per Rombel — koreksi/pengecualian individual"/>

@if($errors->any())
    <div class="card card-pad mb-4 text-sm text-rose-700" style="border:1px solid #fecdd3;background:#fff1f2;">
        @foreach($errors->all() as $pesan)
            <p>{{ $pesan }}</p>
        @endforeach
    </div>
@endif

<form method="GET" action="{{ route('periodikal.per-rombel.form') }}" class="card card-pad mb-4 flex flex-wrap items-end gap-3">
    <div class="min-w-[260px]">
        <label class="label">Rombel Asal</label>
        <select name="rombel_asal_id" class="select">
            <option value="">— Pilih —</option>
            @foreach($rombelList as $rb)
                <option value="{{ $rb->id }}" @selected(optional($rombelAsal)->id == $rb->id)>
                    {{ $rb->nama_rombel }} — {{ optional($rb->tahunAjaran)->nama_tahun_ajaran }}
                </option>
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

@if($rombelAsal && $tujuanId)
    @if($siswaRows && $siswaRows->isNotEmpty())
        <form method="POST" action="{{ route('periodikal.per-rombel.proses') }}" id="form-periodikal-per-rombel">
            @csrf
            <input type="hidden" name="rombel_asal_id" value="{{ $rombelAsal->id }}">
            <input type="hidden" name="ta_tujuan" value="{{ $tujuanId }}">

            <div class="card">
                <table class="table-modern">
                    <thead>
                        <tr>
                            <th>NISN</th>
                            <th>Nama Siswa</th>
                            <th>Kelas Baru</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach($siswaRows as $i => $row)
                        @php
                            $siswa = $row['siswa'];
                            $riwayat = $row['existing'];
                            $tingkatTujuan = $rombelAsal->tingkat + 1;
                            $tebakan = $rombelTujuanOptions->where('tingkat', $tingkatTujuan)->first();
                            $defaultTujuan = optional($riwayat)->rombel_tujuan_id ?? optional($tebakan)->id;
                            $defaultStatus = optional($riwayat)->status ?? 'Naik Kelas';
                        @endphp
                        <tr>
                            <td>{{ $siswa->nisn }}</td>
                            <td class="font-semibold text-ink-900">
                                {{ $siswa->nama_siswa }}
                                <input type="hidden" name="siswa[{{ $i }}][siswa_id]" value="{{ $siswa->id }}">
                            </td>
                            <td>
                                <select name="siswa[{{ $i }}][rombel_tujuan_id]" class="select periodikal-rombel-tujuan">
                                    <option value="">— Lulus/Keluar —</option>
                                    @foreach($rombelTujuanOptions as $rt)
                                        <option value="{{ $rt->id }}" @selected($defaultTujuan == $rt->id)>{{ $rt->nama_rombel }} (Tingkat {{ $rt->tingkat }})</option>
                                    @endforeach
                                </select>
                            </td>
                            <td>
                                <select name="siswa[{{ $i }}][status]" class="select periodikal-status">
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
                <button class="btn-primary" onclick="return confirm('Simpan perubahan periodikal untuk semua siswa di rombel ini?')">
                    <x-icon name="check" class="w-4 h-4"/> Simpan Perubahan
                </button>
            </div>
        </form>
    @else
        <div class="card card-pad text-center py-8 text-ink-500">
            Rombel ini tidak memiliki siswa.
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

    document.querySelectorAll('#form-periodikal-per-rombel tbody tr').forEach(function (row) {
        terapkanToggle(row);
        row.addEventListener('change', function (e) {
            if (e.target.classList.contains('periodikal-status')) terapkanToggle(row);
        });
    });
})();
</script>
@endpush
