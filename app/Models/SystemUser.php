<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SystemUser extends Authenticatable
{
    use HasFactory;

    protected $fillable = [
        'full_name',
        'personal_email',
        'institutional_email',
        'password',
        'role',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function isMis()
    {
        return $this->role === 'mis';
    }

    public function isProfessor()
    {
        return $this->role === 'professor';
    }

    public function reservations()
    {
        return $this->hasMany(\App\Models\Reservation::class, 'user_id');
    }
}