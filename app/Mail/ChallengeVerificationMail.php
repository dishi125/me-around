<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ChallengeVerificationMail extends Mailable
{
    use Queueable, SerializesModels;
    public $mailData;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($mailData)
    {
        $this->mailData = $mailData;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $data = $this->mailData;
        $mail = $this->view('email.challenge-verification-mail', ['mailData' => $this->mailData])->subject($data['subject']);
        foreach ($data['images'] as $image) {
            $mail->attach($image['image_url']); // Adjust the method to get the path of the image dynamically
        }

        return $mail;
    }
}
