<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PasswordOtp extends Model
{
    use HasFactory;

    protected $fillable = ["email", "user_type", "otp", "attempt_count", "expires_at", "used_at"];

    // Use casts (preferred in recent Laravel versions)
    protected $casts = [
        "expires_at" => "datetime",
        "used_at" => "datetime",
        "created_at" => "datetime",
        "updated_at" => "datetime",
    ];
}
