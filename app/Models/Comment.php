<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model; 

class Comment extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'comments';

    protected $fillable = [
        'user_id',   // ID user pembeli/pengunjung yang berkomentar
        'umkm_id',   // ID UMKM yang dikomentari
        'content',   // Isi komentarnya
        'rating',
        'status'     // 'active' atau 'hidden' (dipakai saat kena report)
    ];
    
    protected $casts = [
        'comments' => 'array', 
    ];

    // Relasi ke tabel Account (Siapa yang nulis komentar)
    public function user()
    {
        return $this->belongsTo(Account::class, 'user_id', '_id');
    }

    // Relasi ke tabel Umkm (DI PERBAIKI: Mengarah ke Umkm::class)
    public function umkm()
    {
        return $this->belongsTo(Umkm::class, 'umkm_id', '_id');
    }
}