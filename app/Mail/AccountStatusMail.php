<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AccountStatusMail extends Mailable
{
    use Queueable, SerializesModels;

    public $data;   // full_name, personal_email, institutional_email, status

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function build()
    {
        $subject = $this->data['status'] === 'approved'
            ? 'PTC Reservation – Account Approved'
            : 'PTC Reservation – Account Request Update';

        return $this->subject($subject)
                    ->view('emails.account_status');
    }
}