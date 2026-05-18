<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;
// use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'rooms';

    protected $fillable = [
        'room_id',
        'room_name',
        'room_member',
        'room_code',
        'room_creator'
    ];
}
