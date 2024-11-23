<?php

namespace App\Console\Commands;

use App\Mail\RePaymentMail;
use App\Models\PaypalBillPaymentUser;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class RepaymentsMailCron extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'repaymentsmail:cron';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $today = Carbon::today();
        $repayments_data = PaypalBillPaymentUser::where('payment_method','01')
            ->where('status','success')
            ->where('next_payment_date',$today)
            ->get();

        if (count($repayments_data) > 0) {
            $formattedDate = $today->format('jS M');
            $mailData = (object)[
                'repayments_data' => $repayments_data,
                'subject' => "Today $formattedDate repayment list",
            ];
//            Mail::to("dishu1205099@gmail.com")->send(new RePaymentMail($mailData));
            Mail::to("gwb9160@nate.com")->send(new RePaymentMail($mailData));
        }

        $this->info('repaymentsmail:cron Command Run successfully!');
    }
}
