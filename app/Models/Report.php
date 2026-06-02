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
        'comment_id',       // TAMBAHAN BARU: Untuk menyimpan ID komentar yang dilaporkan
        'report_type', 
        'report_category', 
        'report_message',
        'report_status',
        'internal_note'
    ];

    // JEMBATAN 1: Untuk mengambil data siapa yang MELAPORKAN
// Di dalam model Report.php (Backend)
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