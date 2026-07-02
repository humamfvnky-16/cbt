<?php

namespace App\Http\Controllers\Datacenter;

use App\Http\Controllers\Controller;
use App\Models\RiwayatPeriodikal;
use App\Models\RombonganBelajar;
use App\Models\Siswa;
use App\Models\SiswaRombel;
use App\Models\TahunAjaran;
use App\Models\TingkatKelas;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PeriodikalController extends Controller
{
    /* =======================================================================
     * PROSES SEMUA SISWA (bulk, per kelompok rombel)
     * ===================================================================== */

    public function semuaForm(Request $r)
    {
        $tahunAjaran = TahunAjaran::orderByDesc('id')->get();

        $asalId = $r->input('ta_asal') ?: optional(TahunAjaran::aktif())->id;
        $tujuanId = $r->input('ta_tujuan');

        $rombelGroups = null;
        $rombelTujuanOptions = null;
        $existing = collect();
        $tujuanBelumAdaRombel = false;

        if ($asalId && $tujuanId) {
            // satu baris per rombel asal yang benar-benar punya siswa
            $rombelGroups = RombonganBelajar::where('tahun_ajaran_id', $asalId)
                ->withCount('siswa')
                ->orderBy('tingkat')->orderBy('nama_rombel')
                ->get()
                ->filter(fn ($rb) => $rb->siswa_count > 0)
                ->values();

            $rombelTujuanOptions = RombonganBelajar::where('tahun_ajaran_id', $tujuanId)
                ->orderBy('tingkat')->orderBy('nama_rombel')->get();

            $tujuanBelumAdaRombel = $rombelTujuanOptions->isEmpty();

            // prefill dari riwayat proses sebelumnya (kalau pasangan TA ini pernah diproses)
            $existing = RiwayatPeriodikal::untukTransisi($asalId, $tujuanId)
                ->get()
                ->keyBy('rombel_asal_id');
        }

        return view('datacenter.periodikal.proses-semua', compact(
            'tahunAjaran', 'asalId', 'tujuanId', 'rombelGroups', 'rombelTujuanOptions',
            'existing', 'tujuanBelumAdaRombel'
        ));
    }

    /** Duplikasi struktur rombel dari TA asal ke TA tujuan (tingkat+1). POST biasa + redirect back. */
    public function duplikasiStruktur(Request $r)
    {
        $data = $r->validate([
            'ta_asal' => 'required|exists:tahun_ajaran,id',
            'ta_tujuan' => 'required|exists:tahun_ajaran,id|different:ta_asal',
        ]);

        $dibuat = 0;
        $dilewati = 0;
        $maxTingkat = TingkatKelas::max('nomor') ?? 12;

        DB::transaction(function () use ($data, &$dibuat, &$dilewati, $maxTingkat) {
            $sumberRombel = RombonganBelajar::where('tahun_ajaran_id', $data['ta_asal'])->get();

            foreach ($sumberRombel as $rb) {
                $tingkatBaru = $rb->tingkat + 1;
                if ($tingkatBaru > $maxTingkat) {
                    // tingkat tertinggi (lulus) -- tidak perlu rombel baru
                    continue;
                }

                $namaBaru = $this->hitungNamaRombelBaru($rb->nama_rombel, $rb->tingkat, $tingkatBaru);

                $sudahAda = RombonganBelajar::where('tahun_ajaran_id', $data['ta_tujuan'])
                    ->where('nama_rombel', $namaBaru)->exists();

                if ($sudahAda) {
                    $dilewati++;
                    continue;
                }

                RombonganBelajar::create([
                    'nama_rombel' => $namaBaru,
                    'tingkat' => $tingkatBaru,
                    'jurusan_id' => $rb->jurusan_id,
                    'tahun_ajaran_id' => $data['ta_tujuan'],
                    'wali_kelas_id' => null,
                    'kapasitas' => $rb->kapasitas,
                ]);
                $dibuat++;
            }
        });

        return back()->with('success', "Duplikasi struktur kelas selesai: {$dibuat} rombel dibuat, {$dilewati} dilewati (nama sudah ada).");
    }

    public function semuaProses(Request $r)
    {
        $data = $r->validate([
            'ta_asal' => 'required|exists:tahun_ajaran,id|different:ta_tujuan',
            'ta_tujuan' => 'required|exists:tahun_ajaran,id',
            'rombel' => 'required|array|min:1',
            'rombel.*.rombel_asal_id' => 'required|exists:rombongan_belajar,id',
            'rombel.*.status' => 'required|in:Naik Kelas,Tinggal Kelas,Lulus,Keluar',
            'rombel.*.rombel_tujuan_id' => 'nullable|exists:rombongan_belajar,id',
        ]);

        foreach ($data['rombel'] as $row) {
            if (in_array($row['status'], ['Naik Kelas', 'Tinggal Kelas'], true) && empty($row['rombel_tujuan_id'])) {
                return back()->withErrors('Kelas Baru wajib diisi untuk status Naik Kelas / Tinggal Kelas.')->withInput();
            }
        }

        $diproses = 0;

        DB::transaction(function () use ($data, &$diproses) {
            foreach ($data['rombel'] as $row) {
                $rombelAsal = RombonganBelajar::findOrFail($row['rombel_asal_id']);

                $siswaIds = SiswaRombel::where('rombongan_belajar_id', $rombelAsal->id)
                    ->where('tahun_ajaran_id', $data['ta_asal'])
                    ->pluck('siswa_id');

                foreach ($siswaIds as $siswaId) {
                    $this->prosesSatuSiswa(
                        siswaId: (int) $siswaId,
                        tahunAjaranAsalId: (int) $data['ta_asal'],
                        tahunAjaranTujuanId: (int) $data['ta_tujuan'],
                        rombelAsalId: $rombelAsal->id,
                        rombelTujuanId: $row['rombel_tujuan_id'] ?? null,
                        status: $row['status'],
                        metode: 'massal',
                    );
                    $diproses++;
                }
            }
        });

        return redirect()->route('periodikal.semua.form', [
            'ta_asal' => $data['ta_asal'], 'ta_tujuan' => $data['ta_tujuan'],
        ])->with('success', "Periodikal massal selesai: {$diproses} siswa diproses.");
    }

    /* =======================================================================
     * PROSES SISWA PER ROMBEL (per-siswa, untuk pengecualian)
     * ===================================================================== */

    public function perRombelForm(Request $r)
    {
        $rombelList = RombonganBelajar::with('tahunAjaran')
            ->orderByDesc('tahun_ajaran_id')->orderBy('tingkat')->orderBy('nama_rombel')
            ->get();
        $tahunAjaran = TahunAjaran::orderByDesc('id')->get();

        $rombelAsal = null;
        $siswaRows = null;
        $rombelTujuanOptions = null;
        $tujuanId = $r->input('ta_tujuan');

        if ($r->filled('rombel_asal_id')) {
            $rombelAsal = RombonganBelajar::with('tahunAjaran')->findOrFail($r->rombel_asal_id);

            if ($tujuanId) {
                $rombelTujuanOptions = RombonganBelajar::where('tahun_ajaran_id', $tujuanId)
                    ->orderBy('tingkat')->orderBy('nama_rombel')->get();

                $existingByStudent = RiwayatPeriodikal::where('rombel_asal_id', $rombelAsal->id)
                    ->where('tahun_ajaran_tujuan_id', $tujuanId)
                    ->get()->keyBy('siswa_id');

                $siswaRows = Siswa::whereHas('siswaRombel', fn ($q) => $q
                        ->where('rombongan_belajar_id', $rombelAsal->id)
                        ->where('tahun_ajaran_id', $rombelAsal->tahun_ajaran_id))
                    ->orderBy('nama_siswa')->get()
                    ->map(fn ($s) => [
                        'siswa' => $s,
                        'existing' => $existingByStudent->get($s->id),
                    ]);
            }
        }

        return view('datacenter.periodikal.proses-per-rombel', compact(
            'rombelList', 'tahunAjaran', 'rombelAsal', 'siswaRows', 'rombelTujuanOptions', 'tujuanId'
        ));
    }

    public function perRombelProses(Request $r)
    {
        $data = $r->validate([
            'rombel_asal_id' => 'required|exists:rombongan_belajar,id',
            'ta_tujuan' => 'required|exists:tahun_ajaran,id',
            'siswa' => 'required|array|min:1',
            'siswa.*.siswa_id' => 'required|exists:siswa,id',
            'siswa.*.status' => 'required|in:Naik Kelas,Tinggal Kelas,Lulus,Keluar',
            'siswa.*.rombel_tujuan_id' => 'nullable|exists:rombongan_belajar,id',
        ]);

        foreach ($data['siswa'] as $row) {
            if (in_array($row['status'], ['Naik Kelas', 'Tinggal Kelas'], true) && empty($row['rombel_tujuan_id'])) {
                return back()->withErrors('Kelas Baru wajib diisi untuk status Naik Kelas / Tinggal Kelas.')->withInput();
            }
        }

        $rombelAsal = RombonganBelajar::findOrFail($data['rombel_asal_id']);

        DB::transaction(function () use ($data, $rombelAsal) {
            foreach ($data['siswa'] as $row) {
                $this->prosesSatuSiswa(
                    siswaId: (int) $row['siswa_id'],
                    tahunAjaranAsalId: (int) $rombelAsal->tahun_ajaran_id,
                    tahunAjaranTujuanId: (int) $data['ta_tujuan'],
                    rombelAsalId: $rombelAsal->id,
                    rombelTujuanId: $row['rombel_tujuan_id'] ?? null,
                    status: $row['status'],
                    metode: 'per_rombel',
                );
            }
        });

        return redirect()->route('periodikal.per-rombel.form', [
            'rombel_asal_id' => $rombelAsal->id, 'ta_tujuan' => $data['ta_tujuan'],
        ])->with('success', 'Proses per rombel selesai.');
    }

    /* =======================================================================
     * KOREKSI HASIL PERIODIKAL
     * ===================================================================== */

    public function koreksiIndex(Request $r)
    {
        $items = RiwayatPeriodikal::with([
                'siswa', 'tahunAjaranAsal', 'tahunAjaranTujuan',
                'rombelAsal', 'rombelTujuan', 'diprosesOleh', 'dikoreksiOleh',
            ])
            ->when($r->ta_asal, fn ($q) => $q->where('tahun_ajaran_asal_id', $r->ta_asal))
            ->when($r->ta_tujuan, fn ($q) => $q->where('tahun_ajaran_tujuan_id', $r->ta_tujuan))
            ->when($r->q, fn ($q) => $q->whereHas('siswa', fn ($s) => $s
                ->where('nama_siswa', 'like', "%{$r->q}%")->orWhere('nisn', 'like', "%{$r->q}%")))
            ->orderByDesc('diproses_pada')
            ->paginate(25)->withQueryString();

        return view('datacenter.periodikal.koreksi-index', [
            'items' => $items,
            'tahunAjaran' => TahunAjaran::orderByDesc('id')->get(),
        ]);
    }

    public function koreksiEdit(RiwayatPeriodikal $riwayatPeriodikal)
    {
        $riwayatPeriodikal->load('siswa', 'tahunAjaranAsal', 'tahunAjaranTujuan', 'rombelAsal');

        $rombelTujuanOptions = RombonganBelajar::where('tahun_ajaran_id', $riwayatPeriodikal->tahun_ajaran_tujuan_id)
            ->orderBy('tingkat')->orderBy('nama_rombel')->get();

        return view('datacenter.periodikal.koreksi-edit', compact('riwayatPeriodikal', 'rombelTujuanOptions'));
    }

    public function koreksiUpdate(Request $r, RiwayatPeriodikal $riwayatPeriodikal)
    {
        $data = $r->validate([
            'status' => 'required|in:Naik Kelas,Tinggal Kelas,Lulus,Keluar',
            'rombel_tujuan_id' => 'nullable|exists:rombongan_belajar,id',
            'keterangan' => 'nullable|string',
        ]);

        if (in_array($data['status'], ['Naik Kelas', 'Tinggal Kelas'], true) && empty($data['rombel_tujuan_id'])) {
            return back()->withErrors('Kelas Baru wajib diisi untuk status Naik Kelas / Tinggal Kelas.')->withInput();
        }

        DB::transaction(function () use ($data, $riwayatPeriodikal) {
            // 1) batalkan efek lama, 2) terapkan efek baru
            $this->revertEfekPeriodikal($riwayatPeriodikal);

            $this->terapkanEfekPeriodikal(
                siswaId: $riwayatPeriodikal->siswa_id,
                tahunAjaranTujuanId: $riwayatPeriodikal->tahun_ajaran_tujuan_id,
                rombelTujuanId: $data['rombel_tujuan_id'] ?? null,
                status: $data['status'],
            );

            $riwayatPeriodikal->update([
                'status' => $data['status'],
                'rombel_tujuan_id' => in_array($data['status'], ['Naik Kelas', 'Tinggal Kelas'], true)
                    ? $data['rombel_tujuan_id'] : null,
                'keterangan' => $data['keterangan'] ?? $riwayatPeriodikal->keterangan,
                'metode' => 'koreksi',
                'dikoreksi_oleh_id' => auth('admin')->id(),
                'dikoreksi_pada' => now(),
            ]);
        });

        return redirect()->route('periodikal.koreksi.index')->with('success', 'Hasil periodikal dikoreksi.');
    }

    public function koreksiUndo(RiwayatPeriodikal $riwayatPeriodikal)
    {
        DB::transaction(function () use ($riwayatPeriodikal) {
            $this->revertEfekPeriodikal($riwayatPeriodikal);
            $riwayatPeriodikal->delete();
        });

        return back()->with('success', 'Riwayat periodikal dibatalkan (undo).');
    }

    /* =======================================================================
     * LOGIKA INTI (dipakai bersama oleh semua halaman di atas)
     * ===================================================================== */

    /**
     * Proses 1 siswa untuk 1 pasangan TA asal->tujuan. Idempotent: kalau sudah
     * pernah diproses untuk pasangan TA yang sama, efek lama dibatalkan dulu
     * sebelum efek baru diterapkan, lalu baris riwayat yang SAMA di-update
     * (bukan insert baris baru) -- lihat unique constraint di migration.
     */
    protected function prosesSatuSiswa(
        int $siswaId,
        int $tahunAjaranAsalId,
        int $tahunAjaranTujuanId,
        int $rombelAsalId,
        ?int $rombelTujuanId,
        string $status,
        string $metode
    ): void {
        $existing = RiwayatPeriodikal::where('siswa_id', $siswaId)
            ->where('tahun_ajaran_asal_id', $tahunAjaranAsalId)
            ->where('tahun_ajaran_tujuan_id', $tahunAjaranTujuanId)
            ->first();

        if ($existing) {
            $this->revertEfekPeriodikal($existing);
        }

        $this->terapkanEfekPeriodikal($siswaId, $tahunAjaranTujuanId, $rombelTujuanId, $status);

        RiwayatPeriodikal::updateOrCreate(
            [
                'siswa_id' => $siswaId,
                'tahun_ajaran_asal_id' => $tahunAjaranAsalId,
                'tahun_ajaran_tujuan_id' => $tahunAjaranTujuanId,
            ],
            [
                'rombel_asal_id' => $rombelAsalId,
                'rombel_tujuan_id' => in_array($status, ['Naik Kelas', 'Tinggal Kelas'], true) ? $rombelTujuanId : null,
                'status' => $status,
                'metode' => $metode,
                'diproses_oleh_id' => auth('admin')->id(),
                'diproses_pada' => now(),
            ]
        );
    }

    /** Terapkan efek nyata: baris siswa_rombel di TA tujuan + status siswa. */
    protected function terapkanEfekPeriodikal(int $siswaId, int $tahunAjaranTujuanId, ?int $rombelTujuanId, string $status): void
    {
        $siswa = Siswa::findOrFail($siswaId);

        if (in_array($status, ['Naik Kelas', 'Tinggal Kelas'], true)) {
            SiswaRombel::updateOrCreate(
                ['siswa_id' => $siswaId, 'tahun_ajaran_id' => $tahunAjaranTujuanId],
                ['rombongan_belajar_id' => $rombelTujuanId]
            );
            $siswa->update(['status_siswa' => 'Aktif', 'is_aktif' => true, 'tanggal_status' => null]);
        } elseif ($status === 'Lulus') {
            $siswa->update(['status_siswa' => 'Lulus', 'is_aktif' => false, 'tanggal_status' => now()->toDateString()]);
        } else { // Keluar
            $siswa->update(['status_siswa' => 'Keluar', 'is_aktif' => false, 'tanggal_status' => now()->toDateString()]);
        }
    }

    /**
     * Batalkan efek sebuah baris riwayat: hapus siswa_rombel yang dibuat proses
     * itu di TA tujuan (no-op kalau sudah tidak ada), kembalikan siswa ke Aktif.
     */
    protected function revertEfekPeriodikal(RiwayatPeriodikal $riwayat): void
    {
        SiswaRombel::where('siswa_id', $riwayat->siswa_id)
            ->where('tahun_ajaran_id', $riwayat->tahun_ajaran_tujuan_id)
            ->delete();

        Siswa::where('id', $riwayat->siswa_id)->update([
            'status_siswa' => 'Aktif', 'is_aktif' => true, 'tanggal_status' => null,
        ]);
    }

    /** Ganti prefix angka tingkat lama di nama rombel dengan tingkat baru, mis. "8-1" -> "9-1". */
    protected function hitungNamaRombelBaru(string $namaLama, int $tingkatLama, int $tingkatBaru): string
    {
        if (preg_match('/^('.$tingkatLama.')(\D.*)$/', $namaLama, $m)) {
            return $tingkatBaru.$m[2];
        }

        $hasil = preg_replace('/^\d+/', (string) $tingkatBaru, $namaLama);

        return $hasil !== null && $hasil !== $namaLama ? $hasil : ($tingkatBaru.'-'.$namaLama);
    }
}
