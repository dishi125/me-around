<?php

namespace App\Jobs;

use App\Models\UserHiddenCategory;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class HiddenCategory implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    private $data;
    public function __construct($data)
    {
        $this->data = $data;
    }


    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $data = $this->data;

        $login_users = DB::table('users')->whereNull('deleted_at')->pluck('id')->toArray();
        $non_login_users = DB::table('non_login_user_details')->pluck('id')->toArray();

        if ($data['is_hidden'] == 1){
            foreach ($login_users as $user) {
                UserHiddenCategory::firstOrCreate([
                    'category_id' => $data['category_id'],
                    'user_id' => $user,
                    'user_type' => UserHiddenCategory::LOGIN
                ]);
            }

            foreach ($non_login_users as $user) {
                UserHiddenCategory::firstOrCreate([
                    'category_id' => $data['category_id'],
                    'user_id' => $user,
                    'user_type' => UserHiddenCategory::NONLOGIN
                ]);
            }
        }
        else {
            UserHiddenCategory::where('category_id',$data['category_id'])->where('hidden_by','admin')->forceDelete();
        }
    }

}
