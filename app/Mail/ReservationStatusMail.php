<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ReservationStatusMail extends Mailable
{
    use Queueable, SerializesModels;

    public $data;   // professor_name, institutional_email, room, date, time_slot, activity, pax, status

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function build()
    {
        $subject = $this->data['status'] === 'approved'
            ? 'PTC Reservation – Booking Approved'
            : 'PTC Reservation – Booking Update';

        return $this->subject($subject)
                    ->view('emails.reservation_status');
    }
}