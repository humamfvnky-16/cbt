<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RiwayatPeriodikal extends Model
{
    protected $table = 'riwayat_periodikal';
    protected $guarded = ['id'];

    protected $casts = [
        'diproses_pada' => 'datetime',
        'dikoreksi_pada' => 'datetime',
    ];

    public function siswa()
    {
        return $this->belongsTo(Siswa::class);
    }

    public function tahunAjaranAsal()
    {
        return $this->belongsTo(TahunAjaran::class, 'tahun_ajaran_asal_id');
    }

    public function tahunAjaranTujuan()
    {
        return $this->belongsTo(TahunAjaran::class, 'tahun_ajaran_tujuan_id');
    }

    public function rombelAsal()
    {
        return $this->belongsTo(RombonganBelajar::class, 'rombel_asal_id');
    }

    public function rombelTujuan()
    {
        return $this->belongsTo(RombonganBelajar::class, 'rombel_tujuan_id');
    }

    public function diprosesOleh()
    {
        return $this->belongsTo(User::class, 'diproses_oleh_id');
    }

    public function dikoreksiOleh()
    {
        return $this->belongsTo(User::class, 'dikoreksi_oleh_id');
    }

    public function scopeUntukTransisi($query, int $asalId, int $tujuanId)
    {
        return $query->where('tahun_ajaran_asal_id', $asalId)
            ->where('tahun_ajaran_tujuan_id', $tujuanId);
    }
}
