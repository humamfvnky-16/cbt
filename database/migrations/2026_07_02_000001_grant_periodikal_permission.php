<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Data migration:
 *  - Tambah permission baru: periodikal/*
 *  - Sync ke role 'admin' dan 'super-admin' yang sudah ada di DB (TIDAK ke
 *    'operator' -- periodikal adalah operasi bulk yang mengubah data
 *    kelas/status ratusan siswa sekaligus, sengaja dibatasi seperti 'guru/*'
 *    yang juga tidak diberikan ke operator).
 *
 * Tanpa migration ini, admin/super-admin yang akunnya terhubung ke role custom
 * (role_id terisi) akan mendapat 403 saat membuka menu Administrasi Periodikal,
 * karena HasRbac::getPermissions() memakai daftar permission eksplisit dari
 * role tersebut (bukan wildcard '*') begitu role_id ter-set -- lihat
 * app/Concerns/HasRbac.php. Fitur baru selalu perlu didaftarkan seperti ini,
 * mengikuti pola migration 2024_01_12_000000_grant_hasil_backup_permissions.php.
 *
 * Tidak merubah skema — hanya isi data tabel permissions & role_permissions.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! $this->tableExists('roles') || ! $this->tableExists('permissions') || ! $this->tableExists('role_permissions')) {
            return;
        }

        // 1) Pastikan permission baru ada
        $permission = DB::table('permissions')->where('permission', 'periodikal/*')->first();
        if (! $permission) {
            $permId = DB::table('permissions')->insertGetId([
                'permission' => 'periodikal/*',
                'label' => 'Administrasi Periodikal (Kenaikan Kelas & Kelulusan)',
                'group' => 'datacenter',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            $permId = $permission->id;
        }

        // 2) Grant ke role admin & super-admin
        $roleIds = DB::table('roles')
            ->whereIn('name', ['admin', 'super-admin'])
            ->pluck('id')->toArray();

        foreach ($roleIds as $roleId) {
            $already = DB::table('role_permissions')
                ->where('role_id', $roleId)
                ->where('permission_id', $permId)
                ->exists();
            if (! $already) {
                DB::table('role_permissions')->insert([
                    'role_id'       => $roleId,
                    'permission_id' => $permId,
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        if (! $this->tableExists('permissions')) return;

        $permId = DB::table('permissions')->where('permission', 'periodikal/*')->value('id');

        if ($permId) {
            DB::table('role_permissions')->where('permission_id', $permId)->delete();
            DB::table('permissions')->where('id', $permId)->delete();
        }
    }

    protected function tableExists(string $table): bool
    {
        try {
            return \Illuminate\Support\Facades\Schema::hasTable($table);
        } catch (\Throwable) {
            return false;
        }
    }
};
