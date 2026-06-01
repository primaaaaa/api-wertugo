<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class Report extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'reports';

    protected $fillable = [
        'reported_user_id',
        'reporter_id',
        'report_type', 
        'report_category', // TAMBAHAN: Untuk 'Penipuan', 'Ujaran Kebencian', dll
        'report_message',
        'report_status'
    ];

    // JEMBATAN 1: Untuk mengambil data siapa yang MELAPORKAN
    public function pelapor()
    {
        return $this->belongsTo(Account::class, 'reporter_id', '_id');
    }

    // JEMBATAN 2: Untuk mengambil data siapa/UMKM yang DILAPORKAN
    public function terlapor()
    {
        return $this->belongsTo(Account::class, 'reported_user_id', '_id');
    }
}