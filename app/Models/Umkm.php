<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class Umkm extends Model
{
    // Menggunakan koneksi MongoDB
    protected $connection = 'mongodb';
    protected $collection = 'umkms';

    // Kolom yang diizinkan untuk diisi secara massal
    protected $fillable = [
        'user_id',            
        'nama_usaha',         
        'deskripsi',          
        'lokasi',             
        'media_sosial',       
        'is_open',            
        'jadwal_operasional', 
        'katalog_galeri'      
    ];

    // Relasi balik ke model User
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}