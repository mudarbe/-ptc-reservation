<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccountRequest extends Model
{
    protected $fillable = [
        'full_name',
        'personal_email',
        'institutional_email',
        'role',
        'status',
    ];
}