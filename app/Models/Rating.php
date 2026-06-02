<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class Rating extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'ratings';

    protected $fillable = [
        'user_id',   // Orang yang memberi rating
        'place_id',  // ID tempat wisata/kuliner yang dinilai
        'score'      // Angka rating 1 sampai 5
    ];
}