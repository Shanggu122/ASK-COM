<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VideoCallMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'channel',
        'sender_role',
        'sender_uid',
        'sender_name',
        'message',
    ];
}
