<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class VerifyEmail extends Mailable
{
    use Queueable, SerializesModels;
    public $user_detail;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($user_detail)
    {
        $this->user_detail = $user_detail;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('email.verifyEmail')->subject($this->user_detail->subject);
    }
}
