<?php

namespace App\Console;

use App\Models\Config;
use App\Models\LinkedSocialProfile;
use Illuminate\Console\Scheduling\Schedule;
use App\Http\Controllers\Admin\InstagramController;
use App\Http\Controllers\Api\NewHomepageController;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\File;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        Commands\CreditCheckCron::class,
        Commands\BookingCron::class,
        Commands\PostCron::class,
        Commands\ShopCron::class,
        Commands\ManagerCron::class,
        Commands\ReportCron::class,
        //Commands\ShopCreditDeduct::class,
        Commands\UserExpiry::class,
        Commands\LevelUpdate::class,
        Commands\OutsideBooking::class,
        Commands\FeedMissed::class,
        Commands\NonLoginFeedMissed::class,
        Commands\ChangeCardStatus::class,
        Commands\ChangeSadCardStatus::class,
        Commands\SyncInstagramPosts::class,
        Commands\RefreshInstagramToken::class,
        Commands\SyncInstagramPostPages::class,
        Commands\DecreaseShopDays::class,
        Commands\UpdateLoveCountCron::class,
        Commands\DeleteTextFileCron::class,
        Commands\RepaymentsMailCron::class,
        Commands\VerifyChallenge::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('credit-check:cron')->everyMinute();

        $schedule->command('booking:cron')->everyMinute();

        $schedule->command('report:cron')->everyMinute();

        $schedule->command('outsidebooking:cron')->everyMinute();

        // $schedule->command('shop:cron')->everyMinute();

        $schedule->command('post:cron')->daily();

        $schedule->command('updatelovecount:cron')->daily();

        // $schedule->command('manager:cron')->daily();

        //$schedule->command('shop-credit-deduct:cron')->daily();

        //$schedule->command('userexpiry:cron')->daily();
        $schedule->command('userexpiry:cron')->dailyAt('23:00');

        $schedule->command('levelupdate:cron')->dailyAt('23:00');

        $schedule->command('feedmissed:cron')->dailyAt('23:52');

        $schedule->command('nonloginfeedmissed:cron')->dailyAt('23:52');

        $schedule->command('changesadcardstatus:cron')->dailyAt('23:57');

        $schedule->command('changecardstatus:cron')->everyMinute();

        //$schedule->command('queue:work --daemon')->everyMinute()->withoutOverlapping();
//        $schedule->command('queue:work')->everyMinute()->withoutOverlapping();


        $schedule->command('refresh_instagram_token:cron')->dailyAt('23:30')->withoutOverlapping();

        $selected = Config::where('key', Config::INSTA_CRON_TIME)->first();
        $selectedVal = $selected->value ?? 'hourly';

        $availableTime = array_keys(LinkedSocialProfile::CRON_TIME);

        $selectedVal = (in_array($selectedVal, $availableTime)) ? $selectedVal : 'hourly';

        //$schedule->command('sync_instagram_posts:cron')->$selectedVal()->name('insert_posts')->withoutOverlapping();

        $schedule->command('sync_instagram_pages:cron')->$selectedVal();
        $schedule->command('sync_instagram_posts:cron')->$selectedVal();


       $schedule->call(function () {
            $conObj = new NewHomepageController();
            $conObj->removeDuplicatePostsData();
        })->everyFiveMinutes();

        $schedule->command('decreaseshopday:cron')->dailyAt('23:50')->withoutOverlapping();
        /*
        $schedule->call(function () {
            $conObj = new InstagramController();
            $conObj->duplicateIssue();
        })->dailyAt('06:39'); */

        $schedule->call(function () {
            $logsPath = storage_path('logs');
            $files = File::glob("$logsPath/*.log");
            $logFilesToDelete = array_slice($files, 2); // Get all files except the last two
            foreach ($logFilesToDelete as $file) {
                File::delete($file);
            }
        })->daily();

        $schedule->command('deletetextfile:cron')->everyFiveMinutes();

        $schedule->command('repaymentsmail:cron')->daily();

        $schedule->command('verifychallenge:cron')->dailyAt('14:00');
//        $schedule->command('verifychallenge:cron')->everyMinute();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
