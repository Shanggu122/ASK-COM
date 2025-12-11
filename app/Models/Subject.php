<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subject extends Model
{
    protected $table = 't_subject';
    protected $primaryKey = 'Subject_ID';
    public $timestamps = false;

    public function professors()
    {
        return $this->belongsToMany(\App\Models\Professor::class, 'professor_subject', 'Subject_ID', 'Prof_ID');
    }
}
