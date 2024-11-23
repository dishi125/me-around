<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class VerifyNumber extends Mailable
{
    use Queueable, SerializesModels;

    private $name;
    private $otp;
    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($data)
    {
        $this->name = $data->name;
        $this->otp = $data->otp;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('email.verifyNumber')->subject('Verify Number')->with([
            'name' => $this->name,
            'otp' => $this->otp,
        ]);
    }
}
