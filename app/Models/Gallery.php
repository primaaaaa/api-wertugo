<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class Gallery extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'galleries';

    protected $fillable = [
        'umkm_id',    // ID dari tempat usaha (relasi ke koleksi umkms)
        'image_path', // Path file foto/gambar
        'caption'     // (Opsional) jika pengusaha ingin memberi nama menu/produk
    ];

    public function umkm()
    {
        return $this->belongsTo(Umkm::class, 'umkm_id');
    }
}