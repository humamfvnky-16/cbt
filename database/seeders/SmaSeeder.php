<?php

namespace Database\Seeders;

use Database\Seeders\Concerns\SchoolSeederBase;

/**
 * Seeder data dummy untuk SMA (Sekolah Menengah Atas) — Kurikulum Merdeka.
 * Tingkat 10, 11, 12.
 *
 * Kurikulum Merdeka menghapus jurusan/peminatan IPA-IPS-Bahasa sejak kelas X:
 * - Fase E (kelas X): seluruh siswa mengambil mapel umum yang sama, tanpa peminatan.
 * - Fase F (kelas XI-XII): siswa memilih Mata Pelajaran Pilihan lintas kelompok
 *   (MIPA / IPS / Bahasa & Budaya) — bukan lagi jurusan tetap seperti Kurikulum 2013.
 *   Kelompok pilihan di seeder ini dipakai untuk pengelompokan rombel saja.
 *
 * Jalankan: php artisan db:seed --class=SmaSeeder
 */
class SmaSeeder extends SchoolSeederBase
{
    protected function namaAplikasi(): string
    {
        return 'CBT SMA Negeri 1 Modern';
    }

    protected function sekolahProfile(): array
    {
        return [
            'npsn' => '20200001',
            'nama_sekolah' => 'SMA Negeri 1 Modern',
            'jenjang' => 'SMA',
            'alamat' => 'Jl. Diponegoro No. 24',
            'kelurahan' => 'Tegalega', 'kecamatan' => 'Kota Bogor',
            'kabupaten' => 'Kota Bogor', 'provinsi' => 'Jawa Barat',
            'telepon' => '0251-2345678', 'email' => 'info@sman1modern.sch.id',
            'website' => 'https://sman1modern.sch.id',
            'kepala_sekolah' => 'Dr. H. Bambang Sutrisno, M.Pd.',
            'nip_kepala_sekolah' => '197005101995031003',
        ];
    }

    protected function tingkatRange(): array { return [10, 11, 12]; }

    protected function jurusanList(): array
    {
        return [
            // Kelompok Mata Pelajaran Pilihan (Fase F / kelas XI-XII) —
            // bukan jurusan tetap seperti Kurikulum 2013, hanya dipakai
            // untuk pengelompokan rombel pada seeder ini.
            // [kode, nama, singkatan]
            ['IPA', 'Matematika & Ilmu Pengetahuan Alam', 'MIPA'],
            ['IPS', 'Ilmu Pengetahuan Sosial',            'IPS'],
            ['BHS', 'Bahasa & Budaya',                    'BB'],
        ];
    }

    protected function mapelList(): array
    {
        return [
            // [kode, nama, kelompok, kode_jurusan, tingkat]

            // === Mapel Umum Fase E (Kelas X) — wajib semua siswa, belum ada peminatan
            ['AGM',   'Pendidikan Agama Islam & Budi Pekerti', 'Umum', null, 10],
            ['PPK',   'Pendidikan Pancasila',                  'Umum', null, 10],
            ['BIN',   'Bahasa Indonesia',                      'Umum', null, 10],
            ['MTK',   'Matematika',                            'Umum', null, 10],
            ['BING',  'Bahasa Inggris',                        'Umum', null, 10],
            ['IPAF',  'Ilmu Pengetahuan Alam',                 'Umum', null, 10],
            ['IPSF',  'Ilmu Pengetahuan Sosial',               'Umum', null, 10],
            ['SEJ',   'Sejarah',                               'Umum', null, 10],
            ['SBD',   'Seni Budaya',                           'Umum', null, 10],
            ['PJK',   'PJOK',                                  'Umum', null, 10],
            ['INF',   'Informatika',                           'Umum', null, 10],
            ['MULOK', 'Muatan Lokal',                          'Umum', null, 10],

            // === Mapel Pilihan Fase F (Kelas XI-XII) — Kelompok MIPA
            ['FIS',  'Fisika',               'Pilihan', 'IPA', 11],
            ['KIM',  'Kimia',                'Pilihan', 'IPA', 11],
            ['BIO',  'Biologi',              'Pilihan', 'IPA', 11],
            ['INFL', 'Informatika Lanjutan', 'Pilihan', 'IPA', 11],

            // === Mapel Pilihan Fase F — Kelompok IPS
            ['EKO', 'Ekonomi',     'Pilihan', 'IPS', 11],
            ['GEO', 'Geografi',    'Pilihan', 'IPS', 11],
            ['SOS', 'Sosiologi',   'Pilihan', 'IPS', 11],
            ['ANT', 'Antropologi', 'Pilihan', 'IPS', 11],

            // === Mapel Pilihan Fase F — Kelompok Bahasa & Budaya
            ['BINGL', 'Bahasa Inggris Lanjutan',           'Pilihan', 'BHS', 11],
            ['BJPG',  'Bahasa Jepang',                     'Pilihan', 'BHS', 11],
            ['SASI',  'Bahasa Indonesia Lanjutan (Sastra)','Pilihan', 'BHS', 11],
            ['ANTB',  'Antropologi',                       'Pilihan', 'BHS', 11],
        ];
    }

    protected function guruList(): array
    {
        return [
            ['197505101998031001', 'Drs. Agus Salim, M.Pd.',         'L', 'Wakasek Kurikulum',       'PNS'],
            ['198008052001031002', 'Diana Pertiwi, S.Pd., M.Pd.',    'P', 'Guru Matematika',         'PNS'],
            ['198202152005012003', 'Eko Widodo, S.Si., M.Si.',       'L', 'Guru Fisika',             'PNS'],
            ['198512102008012004', 'Fitri Handayani, S.Pd.',         'P', 'Guru Bahasa Inggris',     'PNS'],
            ['198808202010031005', 'Gunawan Hidayat, M.Pd.',         'L', 'Guru Kimia',              'PPPK'],
            ['199103082013022006', 'Hesti Rahayu, S.Sos., M.Pd.',    'P', 'Guru Sosiologi',          'PPPK'],
            ['199205162015011007', 'Irfan Maulana, S.Pd.',           'L', 'Guru PJOK',               'PPPK'],
            ['199403182017042008', 'Jihan Kartika, S.Pd.',           'P', 'Guru Biologi',            'GTT'],
            ['199607252019081009', 'Kemal Pasha, S.Pd.',             'L', 'Guru Sejarah',            'GTT'],
            ['199809102021091010', 'Lestari Wulandari, S.Pd.',       'P', 'Guru Bahasa Indonesia',   'GTT'],
            ['199911052022011011', 'Muhammad Rizky, S.Kom.',         'L', 'Guru Informatika',        'GTT'],
        ];
    }

    protected function rombelList(): array
    {
        // Kelas X (Fase E): belum ada peminatan/jurusan.
        // Kelas XI & XII (Fase F): dikelompokkan per Kelompok Mapel Pilihan.
        $list = [];
        for ($i = 1; $i <= 6; $i++) {
            $list[] = ["X-$i", 10, null];
        }
        $tingkatLabel = [11 => 'XI', 12 => 'XII'];
        foreach ([11, 12] as $t) {
            $label = $tingkatLabel[$t];
            for ($i = 1; $i <= 3; $i++) $list[] = ["$label MIPA $i", $t, 'IPA'];
            for ($i = 1; $i <= 2; $i++) $list[] = ["$label IPS $i", $t, 'IPS'];
            $list[] = ["$label BB 1", $t, 'BHS'];
        }
        return $list;
    }

    protected function siswaList(): array
    {
        $nama = [
            ['Aditya Wirawan',      'L'], ['Bella Anggraeni',     'P'],
            ['Cahyo Nugroho',       'L'], ['Dewi Sartika',        'P'],
            ['Erlangga Putra',      'L'], ['Fitriana Devi',       'P'],
            ['Gilang Ramadhan',     'L'], ['Hanifah Zahra',       'P'],
            ['Ilham Akbar',         'L'], ['Jasmine Aurelia',     'P'],
            ['Kevin Sanjaya',       'L'], ['Lina Marpaung',       'P'],
            ['Maulana Yusuf',       'L'], ['Nayla Ramadhani',     'P'],
            ['Oktavian Hakim',      'L'], ['Putri Maharani',      'P'],
            ['Qori Saputra',        'L'], ['Rahma Aulia',         'P'],
            ['Surya Pratama',       'L'], ['Tiara Larasati',      'P'],
            ['Usman Ramadhan',      'L'], ['Vina Septiana',       'P'],
            ['Wahyu Nugraha',       'L'], ['Yasmin Khairunnisa',  'P'],
            ['Zaki Maulana',        'L'], ['Anggi Saputri',       'P'],
            ['Bayu Wicaksono',      'L'], ['Cintya Bella',        'P'],
            ['Doni Wibowo',         'L'], ['Erika Pranata',       'P'],
        ];

        $rombels = [
            'X-1','X-2','X-3','X-4','X-5','X-6',
            'XI MIPA 1','XI IPS 1','XII MIPA 1','XII IPS 1',
        ];
        $list = [];
        foreach ($nama as $i => [$n, $jk]) {
            $list[] = [
                '020' . str_pad((string) ($i + 1), 9, '0', STR_PAD_LEFT),
                'SMA' . str_pad((string) ($i + 1), 4, '0', STR_PAD_LEFT),
                $n, $jk,
                $rombels[$i % count($rombels)],
            ];
        }
        return $list;
    }
}
