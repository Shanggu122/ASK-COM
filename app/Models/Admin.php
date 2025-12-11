<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Models\Concerns\HasProfilePhoto;

class Admin extends Authenticatable
{
    use Notifiable, HasProfilePhoto;

    protected $table = "admin";
    protected $primaryKey = "Admin_ID";
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = ["Admin_ID", "Name", "Email", "Password", "profile_picture"];

    protected $hidden = ["Password"];

    public function getAuthPassword()
    {
        return $this->Password;
    }
}
