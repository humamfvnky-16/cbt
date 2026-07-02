<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tambahan status semantik untuk siswa (Aktif/Lulus/Keluar), terpisah dari
        // is_aktif (yang tetap murni flag enrolled/bisa-login). Diisi/dijaga oleh
        // fitur Administrasi Periodikal.
        Schema::table('siswa', function (Blueprint $table) {
            $table->enum('status_siswa', ['Aktif', 'Lulus', 'Keluar'])->default('Aktif')->after('is_aktif');
            $table->date('tanggal_status')->nullable()->after('status_siswa');
        });

        // Riwayat/log setiap proses periodikal (kenaikan kelas / kelulusan / keluar)
        // per siswa per pasangan tahun ajaran. Dipakai untuk audit trail dan sebagai
        // sumber data halaman "Koreksi Hasil Periodikal".
        Schema::create('riwayat_periodikal', function (Blueprint $table) {
            $table->id();
            $table->foreignId('siswa_id')->constrained('siswa')->cascadeOnDelete();

            $table->foreignId('tahun_ajaran_asal_id')->constrained('tahun_ajaran')->cascadeOnDelete();
            $table->foreignId('tahun_ajaran_tujuan_id')->constrained('tahun_ajaran')->cascadeOnDelete();

            $table->foreignId('rombel_asal_id')->nullable()->constrained('rombongan_belajar')->nullOnDelete();
            $table->foreignId('rombel_tujuan_id')->nullable()->constrained('rombongan_belajar')->nullOnDelete();

            $table->enum('status', ['Naik Kelas', 'Tinggal Kelas', 'Lulus', 'Keluar'])->default('Naik Kelas');

            // 'massal' = dibuat dari Proses Semua Siswa, 'per_rombel' = Proses Siswa Per
            // Rombel, 'koreksi' = hasil edit manual lewat Koreksi Hasil Periodikal.
            $table->enum('metode', ['massal', 'per_rombel', 'koreksi'])->default('massal');

            $table->text('keterangan')->nullable();

            $table->foreignId('diproses_oleh_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('diproses_pada')->nullable();

            $table->foreignId('dikoreksi_oleh_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('dikoreksi_pada')->nullable();

            $table->timestamps();

            // Satu siswa hanya boleh punya 1 baris riwayat per pasangan TA asal->tujuan.
            // Re-run proses (massal/per-rombel) maupun koreksi selalu UPDATE baris yang
            // sama (lihat PeriodikalController::prosesSatuSiswa), tidak pernah insert
            // duplikat, sehingga constraint ini tidak pernah membentur race re-run.
            $table->unique(['siswa_id', 'tahun_ajaran_asal_id', 'tahun_ajaran_tujuan_id'], 'uniq_periodikal_siswa_transisi');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('riwayat_periodikal');

        Schema::table('siswa', function (Blueprint $table) {
            $table->dropColumn(['status_siswa', 'tanggal_status']);
        });
    }
};
