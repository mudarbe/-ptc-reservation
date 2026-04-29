<?php

namespace App\Mail;

use App\Models\Reservation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ReservationReminder extends Mailable
{
    use Queueable, SerializesModels;

    public $reservation;

    public function __construct(Reservation $reservation)
    {
        $this->reservation = $reservation;
    }

    public function build()
    {
        $res  = $this->reservation;
        $room = $res->room->name;
        $date = $res->reservation_date->format('F d, Y');
        $time = $res->time_slot;
        $act  = $res->activity_name;

        return $this->subject("📅 Reminder: Your reservation starts in 1 hour")
                    ->view('emails.reservation_reminder', compact('room', 'date', 'time', 'act'));
    }
}