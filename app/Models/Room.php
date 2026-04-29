<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    protected $fillable = ['name', 'type', 'capacity', 'is_active'];

    public function reservations()
    {
        return $this->hasMany(Reservation::class);
    }

    // Get time slots based on room type
    public function getTimeSlotsAttribute()
    {
        if ($this->type === 'laboratory') {
            return ['7-12 pm', '12-3 pm', '4-9 pm'];
        } else {
            return ['7-10 am', '10-1 pm', '1-4 pm', '4-7 pm'];
        }
    }
}