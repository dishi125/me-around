<?php

namespace App\Jobs;

use App\Models\Config;
use Illuminate\Bus\Queueable;
use App\Mail\RegisterAccountMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class RegisterSendMailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    private $mailData;
    /**
     * Create a new job instance.
     *
     * @return void
     */
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
        /*$config = Config::where('key', Config::INSTAGRAM_REGISTER_PUSH_EMAIL)->first();
        Mail::to($config->value)->send(new RegisterAccountMail($this->mailData));*/

        $config = Config::where('key', Config::INSTAGRAM_REGISTER_PUSH_EMAIL)->first();
        if(!empty($config) && $config->value!="") {
            $emails = explode(",", $config->value);
            Mail::to($emails)->send(new RegisterAccountMail($this->mailData));
        }
    }
}
