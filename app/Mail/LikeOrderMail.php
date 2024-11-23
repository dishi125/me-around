<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class LikeOrderMail extends Mailable
{
    use Queueable, SerializesModels;
    private $shopPost;
    private $shopData;
    private $user;
    private $subjectTitle;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($shopPost,$shopData,$user)
    {
        $this->subjectTitle = '[MeAround] '.$shopData->main_name.' / '.$shopData->shop_name.' new posts sync from instagram for '.$shopData->shop_name.' shop';
        $this->shopPost = $shopPost;
        $this->shopData = $shopData;
        $this->user = $user;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->view('email.likeorder-mail', ['subjectTitle' => $this->subjectTitle, 'shopPost' => $this->shopPost, 'shopData' => $this->shopData, 'user' => $this->user])->subject($this->subjectTitle);
    }
}
