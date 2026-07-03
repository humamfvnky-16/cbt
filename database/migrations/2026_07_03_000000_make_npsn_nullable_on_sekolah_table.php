<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Kolom `sekolah.npsn` dibuat NOT NULL sejak awal (lihat
     * 2024_01_01_100000_create_datacenter_tables.php), tapi form Pengaturan
     * Aplikasi (resources/views/setting/index.blade.php) TIDAK mewajibkan
     * field NPSN diisi, dan validasinya di SettingController::update() juga
     * "nullable" -- jadi kalau admin menyimpan pengaturan tanpa mengisi NPSN,
     * update() langsung gagal dengan SQLSTATE[23000]: Column 'npsn' cannot
     * be null. Migrasi ini menyamakan skema database dengan maksud validasi
     * yang sudah ada: NPSN memang boleh kosong.
     *
     * Pakai DB::statement (raw SQL) alih-alih Blueprint::change() supaya
     * tidak butuh dependency doctrine/dbal yang belum tentu terpasang.
     */
    public function up(): void
    {
        DB::statement('ALTER TABLE sekolah MODIFY npsn VARCHAR(20) NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE sekolah MODIFY npsn VARCHAR(20) NOT NULL');
    }
};
