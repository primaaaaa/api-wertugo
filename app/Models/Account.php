<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class Account extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'users';

    protected $primaryKey = '_id';
    public $incrementing = false;
    
    protected $fillable = [
        'email',
        'username',
        'password',
        'country',
        'role'
    ];
}