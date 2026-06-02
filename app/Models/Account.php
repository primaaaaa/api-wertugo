<?php

namespace App\Models;

use Illuminate\Support\Facades\Storage;
use MongoDB\Laravel\Auth\User as Authenticatable; // Gunakan ini untuk autentikasi
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Account extends Authenticatable // extends Authenticatable, bukan Model
{
    use HasApiTokens, Notifiable;

    protected $connection = 'mongodb';
    protected $collection = 'accounts'; // sesuaikan dengan nama collection Anda

    protected $primaryKey = '_id';
    public $incrementing = false;

    protected $fillable = [
        'email',
        'username',
        'password',
        'country',
        'foto_profil',
        'role'
    ];

    protected $attributes = [
        'foto_profil' => 'default-profile.png'
    ];

    protected $hidden = [
        'password'
    ];

    public function getFotoProfilUrlAttribute()
    {
        if (!$this->foto_profil || $this->foto_profil === 'default-profile.png') {
            return asset('images/default-profile.png');
        }
        return Storage::url($this->foto_profil); // hasilnya: /storage/profile_photos/xxx.jpg
    }
}