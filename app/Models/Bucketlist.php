<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class Bucketlist extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'bucketlists';

    protected $fillable = [
        'user_id',     // Pemilik utama (Traveller)
        'title',       // Judul bucket list
        'description'  // Deskripsi list
    ];
}