<?php

namespace App\Models;
use MongoDB\Laravel\Eloquent\Model;
// use Illuminate\Database\Eloquent\Model;

class Verification extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'verification';

    protected $fillable = [
        'nib_dokumen',
        'npwp_dokumen', 
        'legalitas_bangunan_dokumen',
        'sertifikat_halal_dokumen',
        'sertifikat_usaha_pariwisata_dokumen',
        'verification_status',
        'id_umkm'
    ];

    // JEMBATAN RELASI: Menyambungkan id_umkm ke tabel Account
    public function umkm()
    {
        return $this->belongsTo(Umkm::class, 'id_umkm', '_id');
    }
}