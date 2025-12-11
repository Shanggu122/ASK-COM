<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Term extends Model
{
    use HasFactory;

    protected $fillable = [
        "academic_year_id",
        "sequence",
        "name",
        "start_at",
        "end_at",
        "enrollment_deadline",
        "grade_submission_deadline",
        "status",
        "activated_at",
        "closed_at",
    ];

    protected $casts = [
        "start_at" => "date",
        "end_at" => "date",
        "enrollment_deadline" => "date",
        "grade_submission_deadline" => "date",
        "activated_at" => "datetime",
        "closed_at" => "datetime",
    ];

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy("start_at");
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where("status", "active");
    }

    public function scopeDraft(Builder $query): Builder
    {
        return $query->where("status", "draft");
    }

    public function scopeClosed(Builder $query): Builder
    {
        return $query->where("status", "closed");
    }
}
