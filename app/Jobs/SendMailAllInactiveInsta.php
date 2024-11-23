<?php

namespace App\Jobs;

use App\Http\Controllers\Controller;
use App\Models\LinkedSocialProfile;
use App\Models\PostLanguage;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendMailAllInactiveInsta implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    private $yellow_status_data;
    public function __construct($yellow_status_data)
    {
        $this->yellow_status_data = $yellow_status_data;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        foreach ($this->yellow_status_data as $data){
            $img_url = "";
            $subject = "";
            if ($data->language_id==PostLanguage::ENGLISH){
                $img_url = asset('img/eng_insta_disconnect.png');
                $subject = "[MeAround] Instagram sync is broken. please reconnect";
            }
            else if ($data->language_id==PostLanguage::KOREAN){
                $img_url = asset('img/Kor_insta_disconnect.png');
                $subject = "[MeAround] 인스타그램 동기화가 풀렸습니다. 다시 연결해 주세요";
            }
            else if ($data->language_id==PostLanguage::JAPANESE){
                $img_url = asset('img/jap_insta_disconnect.png');
                $subject = "[MeAround]インスタグラムの同期が解除されました。 再接続してください";
            }

            $mailData = (object)[
                'email' => $data->email,
                'social_name' => $data->social_name,
                'img_url' => $img_url,
                'deeplink' => "http://app.mearoundapp.com/me-talk/deeplink",
                'subject' => $subject
            ];
            Mail::to($data->email)->send(new \App\Mail\InstaStatusMail($mailData));

            $insta_profile = LinkedSocialProfile::where('id',$data->id)->first();
            $insta_profile->mail_count = $insta_profile->mail_count + 1;
            $insta_profile->last_send_mail_at = Carbon::now();
            $insta_profile->save();
        }
    }

}
