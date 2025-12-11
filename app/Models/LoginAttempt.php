<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LoginAttempt extends Model
{
    protected $fillable = [
        'stud_id','prof_id','admin_id','ip','user_agent','successful','reason'
    ];
}
