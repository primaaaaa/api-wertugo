<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class BucketlistPlace extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'bucketlist_places';

    protected $fillable = [
        'bucketlist_id', // Relasi ke bucket list
        'place_id',      // Relasi ke tempat wisata/kuliner
        'nama_tempat', 'lokasi', 'deskripsi', 'kategori', 'gambar',
        'personal_rating', 'comment'// (Opsional) Penilaian personal (1-5)
    ];

    protected $attribute = [
        'gambar',
    ];
}