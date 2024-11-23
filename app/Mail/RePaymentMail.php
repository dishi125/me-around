<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class RePaymentMail extends Mailable
{
    use Queueable, SerializesModels;
    private $repayments_data;
    private $subjectTitle;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($mailContent)
    {
        $this->subjectTitle = $mailContent->subject;
        $this->repayments_data = $mailContent->repayments_data;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('email.repayment-mail', ['subjectTitle' => $this->subjectTitle, 'repayments_data' => $this->repayments_data])->subject($this->subjectTitle);
    }
}
