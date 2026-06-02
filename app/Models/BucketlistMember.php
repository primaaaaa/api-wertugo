<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class BucketlistMember extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'bucketlist_members';

    protected $fillable = [
        'bucketlist_id', // Relasi ke bucket list
        'user_id',       // Teman yang diajak kolaborasi
        'role'           // Misalnya: 'contributor' atau 'viewer'
    ];
}