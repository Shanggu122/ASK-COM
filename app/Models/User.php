<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Models\Concerns\HasProfilePhoto;
use Illuminate\Support\Facades\Schema;

class User extends Authenticatable
{
    use Notifiable, HasProfilePhoto;

    protected $table = 't_student';
    protected $primaryKey = 'Stud_ID';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'Stud_ID', 'Name', 'Dept_ID', 'Email', 'Password' , 'profile_picture', 'is_active', 'remember_token'
    ];

    protected $hidden = [
        'Password',
    ];

    // Laravel expects 'password' field, so let's override
    public function getAuthPassword()
    {
        return $this->Password;
    }

    /**
     * Get the name of the "remember me" token column.
     */
    public function getRememberTokenName()
    {
        return 'remember_token';
    }

    public function getRememberToken()
    {
        $col = $this->getRememberTokenName();
        if (!Schema::hasTable($this->table) || !Schema::hasColumn($this->table, $col)) {
            return null; // column not present in legacy schema
        }
        return $this->{$col};
    }

    public function setRememberToken($value)
    {
        $col = $this->getRememberTokenName();
        // Only set if column exists to avoid SQL errors on save / remember
        if (Schema::hasTable($this->table) && Schema::hasColumn($this->table, $col)) {
            $this->{$col} = $value;
        }
    }

    // Switch to professor context

}
