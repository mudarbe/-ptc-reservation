<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Reservation extends Model
{
    protected $fillable = [
        'user_id', 'room_id', 'reservation_date', 'time_slot',
        'activity_name', 'pax', 'status', 'hold_expires_at', 'admin_remarks'
    ];

    protected $casts = [
        'reservation_date' => 'date',
        'hold_expires_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(SystemUser::class, 'user_id');
    }

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    // Check if time slot is available (no conflicting approved or pending within hold)
    public static function isSlotAvailable($roomId, $date, $timeSlot)
{
    // Check blocked slots first
    if (\App\Models\BlockedSlot::isBlocked($roomId, $date, $timeSlot)) {
        return false;
    }

    $conflicting = self::where('room_id', $roomId)
        ->where('reservation_date', $date)
        ->where('time_slot', $timeSlot)
        ->where(function ($query) {
            $query->whereIn('status', ['approved', 'ongoing', 'done'])
                  ->orWhere(function ($q) {
                      $q->where('status', 'pending')
                        ->where('hold_expires_at', '>', now()->setTimezone('Asia/Manila'));
                  });
        })
        ->exists();

    return !$conflicting;
}
}