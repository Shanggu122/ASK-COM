<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Models\Concerns\HasProfilePhoto;
use Illuminate\Support\Facades\Schema;

class Professor extends Authenticatable
{
    use Notifiable, HasProfilePhoto;

    protected $table = 'professors'; // Updated table name
    protected $primaryKey = 'Prof_ID';
    public $incrementing = false;
    protected $keyType = 'int';
    public $timestamps = false;

    protected $fillable = [
        'Prof_ID', 'Password', 'Name', 'Email', 'Dept_ID', 'Schedule', 'profile_picture', 'remember_token', 'is_active'
    ];

    protected $hidden = [
        'Password',
    ];

    // Ensure accessor appears when model serialized to arrays/JSON
    protected $appends = [
        'profile_photo_url'
    ];

    // If your password column is named 'Password', override getAuthPassword
    public function getAuthPassword()
    {
        return $this->Password;
    }


    //  public function setPasswordAttribute($value)
    // {
    //     $this->attributes['Password'] = bcrypt($value);
    // }

    public function subjects()
    {
        return $this->belongsToMany(Subject::class, 'professor_subject', 'Prof_ID', 'Subject_ID');
    }

    public function getRememberTokenName()
    {
        return 'remember_token';
    }

    public function getRememberToken()
    {
        $col = $this->getRememberTokenName();
        if (!Schema::hasTable($this->table) || !Schema::hasColumn($this->table, $col)) {
            return null;
        }
        return $this->{$col};
    }

    public function setRememberToken($value)
    {
        $col = $this->getRememberTokenName();
        if (Schema::hasTable($this->table) && Schema::hasColumn($this->table, $col)) {
            $this->{$col} = $value;
        }
    }
}