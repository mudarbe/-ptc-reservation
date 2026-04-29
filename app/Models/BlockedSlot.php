<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BlockedSlot extends Model
{
    protected $fillable = [
        'room_id', 'date', 'time_slot', 'type', 'created_by', 'notes'
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    public function creator()
    {
        return $this->belongsTo(SystemUser::class, 'created_by');
    }

    // Check if a slot is blocked
    public static function isBlocked($roomId, $date, $timeSlot)
    {
        return self::where('room_id', $roomId)
            ->where('date', $date)
            ->where('time_slot', $timeSlot)
            ->exists();
    }
}