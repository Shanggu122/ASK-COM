<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class AcademicYear extends Model
{
    use HasFactory;

    protected $fillable = [
        "label",
        "start_at",
        "end_at",
        "status",
        "activated_at",
        "closed_at",
        "activated_by",
        "closed_by",
    ];

    protected $casts = [
        "start_at" => "date",
        "end_at" => "date",
        "activated_at" => "datetime",
        "closed_at" => "datetime",
    ];

    public function terms(): HasMany
    {
        return $this->hasMany(Term::class);
    }

    public function activeTerm(): HasOne
    {
        return $this->hasOne(Term::class)->where("status", "active")->orderBy("sequence");
    }

    public function scopeActive($query)
    {
        return $query->where("status", "active");
    }

    public function scopeDraft($query)
    {
        return $query->where("status", "draft");
    }

    public function scopeClosed($query)
    {
        return $query->where("status", "closed");
    }
}
