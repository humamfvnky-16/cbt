<?php

namespace Database\Seeders;

use Database\Seeders\Concerns\SchoolSeederBase;

/**
 * Seeder data dummy untuk SMK (Sekolah Menengah Kejuruan) — Kurikulum Merdeka.
 * Tingkat 10, 11, 12.
 *
 * Struktur mengikuti Spektrum Keahlian SMK terbaru: nomenklatur Program Keahlian
 * lama (RPL/TKJ/MM/OTKP/BDP) diperbarui menjadi PPLG/TJKT/DKV/MPLB/BD. Mapel
 * kejuruan kelas X kini digabung menjadi satu mapel "Dasar-Dasar Program Keahlian"
 * per jurusan, sedangkan kelas XI-XII berisi mapel "Konsentrasi Keahlian". Semua
 * jurusan juga mendapat Projek Kreatif & Kewirausahaan (PKK) dan Praktik Kerja
 * Lapangan (PKL) sebagai mapel lintas program keahlian.
 *
 * Jalankan: php artisan db:seed --class=SmkSeeder
 */
class SmkSeeder extends SchoolSeederBase
{
    protected function namaAplikasi(): string
    {
        return 'CBT SMK Negeri 1 Modern';
    }

    protected function sekolahProfile(): array
    {
        return [
            'npsn' => '20300001',
            'nama_sekolah' => 'SMK Negeri 1 Modern',
            'jenjang' => 'SMK',
            'alamat' => 'Jl. Industri Raya No. 88',
            'kelurahan' => 'Pasir Putih', 'kecamatan' => 'Sawangan',
            'kabupaten' => 'Depok', 'provinsi' => 'Jawa Barat',
            'telepon' => '021-7777888', 'email' => 'info@smkn1modern.sch.id',
            'website' => 'https://smkn1modern.sch.id',
            'kepala_sekolah' => 'Drs. H. Imam Suryadi, M.M.',
            'nip_kepala_sekolah' => '196812121990031004',
        ];
    }

    protected function tingkatRange(): array { return [10, 11, 12]; }

    protected function jurusanList(): array
    {
        return [
            // Program Keahlian sesuai Spektrum Keahlian SMK Kurikulum Merdeka
            // (nomenklatur baru menggantikan RPL/TKJ/MM/OTKP/BDP)
            // [kode, nama, singkatan]
            ['PPLG', 'Pengembangan Perangkat Lunak & Gim',       'PPLG'],
            ['TJKT', 'Teknik Jaringan Komputer & Telekomunikasi','TJKT'],
            ['DKV',  'Desain Komunikasi Visual',                 'DKV'],
            ['AKL',  'Akuntansi & Keuangan Lembaga',              'AKL'],
            ['MPLB', 'Manajemen Perkantoran & Layanan Bisnis',   'MPLB'],
            ['BD',   'Bisnis Digital & Pemasaran',                'BD'],
        ];
    }

    protected function mapelList(): array
    {
        return [
            // [kode, nama, kelompok, kode_jurusan, tingkat]

            // === Mapel Umum (semua program keahlian)
            ['AGM',   'Pendidikan Agama Islam & Budi Pekerti', 'Umum', null, 10],
            ['PPK',   'Pendidikan Pancasila',                  'Umum', null, 10],
            ['BIN',   'Bahasa Indonesia',                      'Umum', null, 10],
            ['MTK',   'Matematika',                            'Umum', null, 10],
            ['BING',  'Bahasa Inggris',                        'Umum', null, 10],
            ['IPAS',  'IPA & IPS (IPAS)',                      'Umum', null, 10],
            ['SEJ',   'Sejarah',                               'Umum', null, 10],
            ['SBD',   'Seni Budaya',                           'Umum', null, 10],
            ['PJK',   'PJOK',                                  'Umum', null, 10],
            ['INF',   'Informatika',                           'Umum', null, 10],
            ['MULOK', 'Muatan Lokal',                          'Umum', null, 10],

            // === Dasar-Dasar Program Keahlian (Kelas X)
            ['DPPLG', 'Dasar-Dasar PPLG',               'Dasar Program Keahlian', 'PPLG', 10],
            ['DTJKT', 'Dasar-Dasar TJKT',               'Dasar Program Keahlian', 'TJKT', 10],
            ['DDKV',  'Dasar-Dasar DKV',                'Dasar Program Keahlian', 'DKV',  10],
            ['DAKL',  'Dasar-Dasar AKL',                'Dasar Program Keahlian', 'AKL',  10],
            ['DMPLB', 'Dasar-Dasar MPLB',               'Dasar Program Keahlian', 'MPLB', 10],
            ['DBD',   'Dasar-Dasar Bisnis & Pemasaran', 'Dasar Program Keahlian', 'BD',   10],

            // === Konsentrasi Keahlian PPLG (Kelas XI-XII)
            ['PBO',  'Pemrograman Berorientasi Objek',       'Konsentrasi Keahlian', 'PPLG', 11],
            ['PWEB', 'Pemrograman Web & Perangkat Bergerak', 'Konsentrasi Keahlian', 'PPLG', 11],
            ['BSD',  'Basis Data',                           'Konsentrasi Keahlian', 'PPLG', 11],
            ['PGIM', 'Pengembangan Gim',                      'Konsentrasi Keahlian', 'PPLG', 12],

            // === Konsentrasi Keahlian TJKT
            ['ASJ', 'Administrasi Sistem Jaringan',       'Konsentrasi Keahlian', 'TJKT', 11],
            ['TLJ', 'Teknologi Layanan Jaringan',         'Konsentrasi Keahlian', 'TJKT', 11],
            ['AIJ', 'Administrasi Infrastruktur Jaringan','Konsentrasi Keahlian', 'TJKT', 12],

            // === Konsentrasi Keahlian DKV
            ['ANI',  'Animasi 2D & 3D',         'Konsentrasi Keahlian', 'DKV', 11],
            ['FOTO', 'Fotografi Digital',       'Konsentrasi Keahlian', 'DKV', 11],
            ['PVE',  'Produksi Video & Editing','Konsentrasi Keahlian', 'DKV', 12],

            // === Konsentrasi Keahlian AKL
            ['AKJ',  'Akuntansi Keuangan',                 'Konsentrasi Keahlian', 'AKL', 11],
            ['AKPD', 'Akuntansi Perusahaan Jasa & Dagang', 'Konsentrasi Keahlian', 'AKL', 11],
            ['KOM',  'Komputer Akuntansi',                 'Konsentrasi Keahlian', 'AKL', 12],

            // === Konsentrasi Keahlian MPLB
            ['KORS',  'Korespondensi',          'Konsentrasi Keahlian', 'MPLB', 11],
            ['KEARS', 'Kearsipan',              'Konsentrasi Keahlian', 'MPLB', 11],
            ['HUMAS', 'Layanan Bisnis & Humas', 'Konsentrasi Keahlian', 'MPLB', 12],

            // === Konsentrasi Keahlian BD
            ['MKT',   'Marketing Digital',        'Konsentrasi Keahlian', 'BD', 11],
            ['ECOM',  'Pengelolaan Bisnis Online','Konsentrasi Keahlian', 'BD', 11],
            ['ADMTR', 'Administrasi Transaksi',   'Konsentrasi Keahlian', 'BD', 12],

            // === Lintas Program Keahlian
            ['PKK', 'Projek Kreatif & Kewirausahaan', 'Kewirausahaan', null, 11],
            ['PKL', 'Praktik Kerja Lapangan',         'PKL',           null, 12],
        ];
    }

    protected function guruList(): array
    {
        return [
            ['197005111995031001', 'Drs. Hartono Wijaya, M.T.',     'L', 'Wakasek Hubin',           'PNS'],
            ['197808152002032002', 'Ir. Indah Permatasari, M.Pd.',  'P', 'Kaprog PPLG',             'PNS'],
            ['198106202005011003', 'Joko Suprapto, S.Kom., M.Kom.', 'L', 'Guru PPLG',               'PNS'],
            ['198409102008012004', 'Kartika Sari, S.T.',            'P', 'Guru TJKT',               'PNS'],
            ['198712252010031005', 'Lukman Hakim, S.Kom.',          'L', 'Kaprog TJKT',             'PPPK'],
            ['198908152013022006', 'Maya Anggraeni, S.Ds.',         'P', 'Guru DKV',                'PPPK'],
            ['199102052015031007', 'Nurdin Saputra, S.E., M.Ak.',   'L', 'Kaprog AKL',              'PPPK'],
            ['199305182017042008', 'Olivia Damayanti, S.Pd.',       'P', 'Guru Bahasa Indonesia',   'GTT'],
            ['199506112019081009', 'Prabowo Kusuma, S.Pd.',         'L', 'Guru PJOK',               'GTT'],
            ['199708202020012010', 'Qurratul Aini, S.Pd.',          'P', 'Guru Matematika',         'GTT'],
            ['199810032021091011', 'Rizal Mahendra, S.E.',          'L', 'Guru Bisnis Digital',     'GTT'],
            ['199912152022032012', 'Sari Wahyuni, S.Pd.',           'P', 'Guru MPLB',               'GTT'],
            ['200002102023011013', 'Taufik Ramadhan, S.Kom.',       'L', 'Guru Informatika',        'GTT'],
        ];
    }

    protected function rombelList(): array
    {
        // X PPLG 1-2, X TJKT 1-2, X DKV 1, X AKL 1-2, X MPLB 1, X BD 1, dst untuk XI & XII
        $list = [];
        $tingkatLabel = [10 => 'X', 11 => 'XI', 12 => 'XII'];
        $jurusanRombel = [
            'PPLG' => 2,
            'TJKT' => 2,
            'DKV'  => 1,
            'AKL'  => 2,
            'MPLB' => 1,
            'BD'   => 1,
        ];
        foreach ([10, 11, 12] as $t) {
            $label = $tingkatLabel[$t];
            foreach ($jurusanRombel as $kode => $jumlah) {
                for ($i = 1; $i <= $jumlah; $i++) {
                    $list[] = ["$label $kode $i", $t, $kode];
                }
            }
        }
        return $list;
    }

    protected function siswaList(): array
    {
        $nama = [
            ['Ahmad Rifai',         'L'], ['Bintang Aulia',       'P'],
            ['Candra Pratama',      'L'], ['Diana Citra',         'P'],
            ['Endra Saputra',       'L'], ['Farah Nabila',        'P'],
            ['Galang Maulidan',     'L'], ['Hesti Pertiwi',       'P'],
            ['Iqbal Maulana',       'L'], ['Jelita Rahmania',     'P'],
            ['Kresna Bayu',         'L'], ['Lily Andriani',       'P'],
            ['Mahesa Putra',        'L'], ['Nadya Alifia',        'P'],
            ['Oki Pranata',         'L'], ['Putri Sahara',        'P'],
            ['Qisya Marlina',       'P'], ['Reza Pahlevi',        'L'],
            ['Sinta Bella',         'P'], ['Teguh Pratama',       'L'],
            ['Untung Saputra',      'L'], ['Verani Astuti',       'P'],
            ['Wira Sentosa',        'L'], ['Yulia Anggraeni',     'P'],
            ['Zulfikar Ali',        'L'], ['Adelia Marsha',       'P'],
            ['Bilal Hidayat',       'L'], ['Clara Annisa',        'P'],
            ['Daffa Wiratama',      'L'], ['Erlina Saputri',      'P'],
            ['Fadhil Akbar',        'L'], ['Geyssa Putri',        'P'],
            ['Hafiz Maulana',       'L'], ['Inara Salsabila',     'P'],
            ['Jefri Setiawan',      'L'], ['Khalisa Putri',       'P'],
        ];

        $rombels = [
            'X PPLG 1','X PPLG 2','X TJKT 1','X TJKT 2','X DKV 1','X AKL 1','X AKL 2','X MPLB 1','X BD 1',
            'XI PPLG 1','XI TJKT 1','XI DKV 1','XII PPLG 1','XII TJKT 1',
        ];
        $list = [];
        foreach ($nama as $i => [$n, $jk]) {
            $list[] = [
                '030' . str_pad((string) ($i + 1), 9, '0', STR_PAD_LEFT),
                'SMK' . str_pad((string) ($i + 1), 4, '0', STR_PAD_LEFT),
                $n, $jk,
                $rombels[$i % count($rombels)],
            ];
        }
        return $list;
    }
}
