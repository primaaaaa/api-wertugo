<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Place extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'category', 'description', 'location', 
        'latitude', 'longitude', 'contact', 'social_media', 'image'
    ];

    // Scope untuk filter kategori
    public function scopeCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    // Scope untuk search
    public function scopeSearch($query, $keyword)
    {
        return $query->where('name', 'like', "%{$keyword}%")
                     ->orWhere('location', 'like', "%{$keyword}%");
    }
}