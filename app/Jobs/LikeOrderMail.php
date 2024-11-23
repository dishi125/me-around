<?php

namespace App\Jobs;

use App\Models\Config;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class LikeOrderMail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */

    private $shopPost, $shopData, $user;
    public function __construct($shopPost, $shopData, $user)
    {
        $this->shopPost = $shopPost;
        $this->shopData = $shopData;
        $this->user = $user;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $config = Config::where('key', Config::LIKE_ORDER)->first();
        if(!empty($config) && $config->value!="") {
            $emails = explode(",", $config->value);
            Mail::to($emails)->send(new \App\Mail\LikeOrderMail($this->shopPost,$this->shopData,$this->user));
        }
    }

}
