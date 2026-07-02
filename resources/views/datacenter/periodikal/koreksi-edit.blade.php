@extends('layouts.app')
@section('title', 'Koreksi Hasil Periodikal')
@section('breadcrumb', 'Data Center / Administrasi Periodikal / Koreksi Hasil Periodikal')

@section('content')
<x-page-header title="Koreksi Hasil Periodikal"/>

<div class="card card-pad mb-4 max-w-2xl">
    <div class="grid md:grid-cols-2 gap-3 text-sm">
        <div>
            <div class="text-ink-500 text-xs">Siswa</div>
            <div class="font-semibold text-ink-900">{{ optional($riwayatPeriodikal->siswa)->nama_siswa }} ({{ optional($riwayatPeriodikal->siswa)->nisn }})</div>
        </div>
        <div>
            <div class="text-ink-500 text-xs">Kelas Asal</div>
            <div class="font-semibold text-ink-900">{{ optional($riwayatPeriodikal->rombelAsal)->nama_rombel ?? '—' }}</div>
        </div>
        <div>
            <div class="text-ink-500 text-xs">Tahun Ajaran Asal</div>
            <div class="font-semibold text-ink-900">{{ optional($riwayatPeriodikal->tahunAjaranAsal)->nama_tahun_ajaran }}</div>
        </div>
        <div>
            <div class="text-ink-500 text-xs">Tahun Ajaran Tujuan</div>
            <div class="font-semibold text-ink-900">{{ optional($riwayatPeriodikal->tahunAjaranTujuan)->nama_tahun_ajaran }}</div>
        </div>
    </div>
</div>

<form method="POST" action="{{ route('periodikal.koreksi.update', $riwayatPeriodikal) }}" class="card card-pad space-y-4 max-w-2xl" id="form-koreksi-edit">
    @csrf
    @method('PUT')

    <div class="grid md:grid-cols-2 gap-4">
        <div>
            <label class="label">Status</label>
            <select name="status" id="koreksi-status" class="select">
                <option value="Naik Kelas" @selected($riwayatPeriodikal->status === 'Naik Kelas')>Naik Kelas</option>
                <option value="Tinggal Kelas" @selected($riwayatPeriodikal->status === 'Tinggal Kelas')>Tinggal Kelas</option>
                <option value="Lulus" @selected($riwayatPeriodikal->status === 'Lulus')>Lulus</option>
                <option value="Keluar" @selected($riwayatPeriodikal->status === 'Keluar')>Keluar / Pindah</option>
            </select>
        </div>
        <div>
            <label class="label">Kelas Baru</label>
            <select name="rombel_tujuan_id" id="koreksi-rombel-tujuan" class="select">
                <option value="">— Lulus/Keluar —</option>
                @foreach($rombelTujuanOptions as $rt)
                    <option value="{{ $rt->id }}" @selected($riwayatPeriodikal->rombel_tujuan_id == $rt->id)>{{ $rt->nama_rombel }} (Tingkat {{ $rt->tingkat }})</option>
                @endforeach
            </select>
        </div>
    </div>

    <x-field type="textarea" name="keterangan" label="Catatan Koreksi (opsional)" :value="$riwayatPeriodikal->keterangan" placeholder="Alasan koreksi, mis. salah pilih kelas tujuan"/>

    @if($errors->any())
        <div class="text-sm text-rose-700">
            @foreach($errors->all() as $pesan)
                <p>{{ $pesan }}</p>
            @endforeach
        </div>
    @endif

    <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
        <a href="{{ route('periodikal.koreksi.index') }}" class="btn-secondary">Batal</a>
        <button class="btn-primary" onclick="return confirm('Simpan koreksi untuk riwayat periodikal siswa ini?')">Simpan Koreksi</button>
    </div>
</form>
@endsection

@push('scripts')
<script>
(function () {
    var statusSelect = document.getElementById('koreksi-status');
    var tujuanSelect = document.getElementById('koreksi-rombel-tujuan');
    if (!statusSelect || !tujuanSelect) return;

    function terapkanToggle() {
        var perluTujuan = ['Naik Kelas', 'Tinggal Kelas'].indexOf(statusSelect.value) !== -1;
        tujuanSelect.disabled = !perluTujuan;
        if (!perluTujuan) tujuanSelect.value = '';
    }

    terapkanToggle();
    statusSelect.addEventListener('change', terapkanToggle);
})();
</script>
@endpush
