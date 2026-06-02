<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class BucketlistInvitation extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'bucketlist_invitations';

    protected $fillable = [
        'bucketlist_id', // Relasi ke bucket list
        'inviter_id',    // Yang mengundang
        'invitee_id',    // Yang diundang (target user)
        'status'         // 'pending', 'accepted', atau 'rejected'
    ];
}