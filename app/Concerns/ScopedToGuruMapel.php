<?php

namespace App\Concerns;

use App\Models\GuruMapel;
use Illuminate\Database\Eloquent\Builder;

/**
 * Trait untuk membatasi data agar guru hanya melihat mata pelajaran
 * & rombel yang sudah ditugaskan (via tabel guru_mapel).
 * Admin bypass filter.
 */
trait ScopedToGuruMapel
{
    /** Apakah user sekarang harus di-scope? (guru). Admin → false (lihat semua). */
    protected function shouldScope($user): bool
    {
        return ($user?->user_type ?? 'admin') === 'guru';
    }

    /** Daftar mata_pelajaran_id yang ditugaskan ke guru saat ini. */
    protected function guruMapelIds($user): array
    {
        if (! $this->shouldScope($user)) return [];

        return GuruMapel::where('guru_id', $user->id)
            ->pluck('mata_pelajaran_id')->unique()->values()->toArray();
    }

    /** Daftar rombel_id yang ditugaskan ke guru saat ini. */
    protected function guruRombelIds($user): array
    {
        if (! $this->shouldScope($user)) return [];

        return GuruMapel::where('guru_id', $user->id)
            ->whereNotNull('rombongan_belajar_id')
            ->pluck('rombongan_belajar_id')->unique()->values()->toArray();
    }

    /** Daftar tingkat (kelas) yang diajar guru — dari rombel di penugasan guru_mapel. */
    protected function guruTingkatList($user): array
    {
        if (! $this->shouldScope($user)) return [];

        $rombelIds = $this->guruRombelIds($user);
        if (empty($rombelIds)) return [];

        return \App\Models\RombonganBelajar::whereIn('id', $rombelIds)
            ->pluck('tingkat')->filter()->map(fn ($t) => (int) $t)
            ->unique()->values()->toArray();
    }

    /**
     * Peta penugasan guru BERPASANGAN: [mata_pelajaran_id => [tingkat, ...]].
     *
     * Tingkat diambil per-baris guru_mapel (dari rombelnya masing-masing) —
     * BUKAN gabungan semua mapel × semua tingkat. Contoh: guru mengajar
     * Informatika di kelas 7 dan IPA di kelas 8–9 → dia TIDAK boleh melihat
     * soal IPA kelas 7 (dulu bocor karena mapel & tingkat difilter terpisah).
     *
     * Mapel yang penugasannya tanpa rombel → daftar tingkat kosong = mapel
     * itu tidak dibatasi tingkat (data penugasan tidak cukup untuk membatasi).
     */
    protected function guruMapelTingkatMap($user): array
    {
        if (! $this->shouldScope($user)) return [];

        $rows = GuruMapel::with('rombel:id,tingkat')
            ->where('guru_id', $user->id)->get();

        $map = [];
        foreach ($rows as $row) {
            if (! $row->mata_pelajaran_id) continue;
            $mapelId = (int) $row->mata_pelajaran_id;
            $map[$mapelId] ??= ['tingkat' => [], 'tanpa_batas' => false];

            $t = $row->rombel?->tingkat;
            if ($t) {
                $map[$mapelId]['tingkat'][] = (int) $t;
            } else {
                $map[$mapelId]['tanpa_batas'] = true;
            }
        }

        return array_map(
            fn ($v) => $v['tanpa_batas'] ? [] : array_values(array_unique($v['tingkat'])),
            $map
        );
    }

    /**
     * Apply scope ke query questions: guru hanya melihat soal pada pasangan
     * MAPEL+TINGKAT yang benar-benar diajarnya (per penugasan guru_mapel).
     *
     * Soal TANPA tingkat (null, banyak di data lama) tetap terlihat pada
     * mapel yang diajar, supaya soal lama tidak mendadak "hilang" dari guru.
     */
    protected function scopeBankSoalForUser(Builder $q, $user): Builder
    {
        if (! $this->shouldScope($user)) return $q;

        $map = $this->guruMapelTingkatMap($user);

        return $q->where(function ($outer) use ($map) {
            if (empty($map)) {
                $outer->whereRaw('1=0');
                return;
            }
            foreach ($map as $mapelId => $tingkatList) {
                $outer->orWhere(function ($x) use ($mapelId, $tingkatList) {
                    $x->where('mata_pelajaran_id', $mapelId);
                    if (! empty($tingkatList)) {
                        $x->where(fn ($y) => $y->whereNull('tingkat')->orWhereIn('tingkat', $tingkatList));
                    }
                });
            }
        });
    }

    /**
     * Dropdown tingkat (nomor => nama) sesuai hak user & mapel terpilih:
     *  - admin → semua tingkat;
     *  - guru + mapel dipilih → hanya tingkat di mana dia mengajar MAPEL ITU;
     *  - guru tanpa mapel → kosong (rule: filter kelas wajib pilih mapel dulu).
     */
    protected function tingkatDropdownFor($user, $mapelId = null): array
    {
        $all = \App\Models\TingkatKelas::dropdown();
        if (! $this->shouldScope($user)) return $all;

        $map = $this->guruMapelTingkatMap($user);
        $mapelId = (int) $mapelId;
        if (! $mapelId || ! array_key_exists($mapelId, $map)) return [];

        $allowed = $map[$mapelId];
        if (empty($allowed)) return $all; // penugasan mapel ini tanpa rombel → tak dibatasi

        return array_filter(
            $all,
            fn ($nama, $nomor) => in_array((int) $nomor, $allowed, true),
            ARRAY_FILTER_USE_BOTH
        );
    }

    /** Guard tunggal: bolehkah guru mengakses/mengubah 1 soal tertentu? */
    protected function assertBolehKelolaSoal($user, \App\Models\Question $soal): void
    {
        if (! $this->shouldScope($user)) return;

        $map = $this->guruMapelTingkatMap($user);
        if (! array_key_exists((int) $soal->mata_pelajaran_id, $map)) {
            abort(403, 'Anda tidak mengajar mapel ini.');
        }

        $allowed = $map[(int) $soal->mata_pelajaran_id];
        if ($soal->tingkat && ! empty($allowed) && ! in_array((int) $soal->tingkat, $allowed, true)) {
            abort(403, 'Anda tidak mengajar mapel ini di tingkat kelas '.$soal->tingkat.'.');
        }
    }

    /** Apply scope ke query quizzes: filter mapel + rombel guru */
    protected function scopeQuizForUser(Builder $q, $user): Builder
    {
        if (! $this->shouldScope($user)) return $q;

        $mapelIds = $this->guruMapelIds($user);
        $rombelIds = $this->guruRombelIds($user);

        return $q->whereIn('mata_pelajaran_id', $mapelIds ?: [0])
                 ->where(function ($x) use ($rombelIds) {
                     $x->whereNull('rombongan_belajar_id')
                       ->orWhereIn('rombongan_belajar_id', $rombelIds ?: [0]);
                 });
    }
}
