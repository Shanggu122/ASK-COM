<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CalendarOverride extends Model
{
    protected $fillable = [
        "start_date",
        "end_date",
        "scope_type",
        "scope_id",
        "effect",
        "allowed_mode",
        "reason_key",
        "reason_text",
        "created_by",
    ];
}
