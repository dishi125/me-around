<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use App\Mail\RegisterPaypalCardMail as registerMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Config;
use Illuminate\Support\Facades\Mail;

class RegisterPaypalCardMail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    private $mailData;
    public function __construct($mailData)
    {
        $this->mailData = $mailData;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        Mail::to("gwb9160@nate.com")->send(new registerMail($this->mailData));
    }
}
