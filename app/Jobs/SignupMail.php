<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use App\Mail\SignupMail as registerMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Config;
use Illuminate\Support\Facades\Mail;

class SignupMail implements ShouldQueue
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
        $data = $this->mailData;
        $email = $data->to_email;
        Mail::to($email)->send(new registerMail($this->mailData));
    }
}
