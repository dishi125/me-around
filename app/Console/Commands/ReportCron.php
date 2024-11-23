<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Status;
use App\Models\ReportClient;
use App\Models\ReportTypes;
use App\Models\Config;
use App\Models\ReviewComments;
use App\Models\ReviewCommentReply;
use App\Models\CommunityComments;
use App\Models\CommunityCommentReply;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ReportCron extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'report:cron';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete Commment if reported more times';

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
        Log::info('Cron is running');    
        $config = Config::where('key',Config::REVIEW_POST_REPORTED_DELETE)->first();
        $delete_comment_total = $config ?  (int) filter_var($config->value, FILTER_SANITIZE_NUMBER_INT) : 0;
        if($delete_comment_total) {
            $commentsReported = ReportClient::where('report_type_id',ReportTypes::REVIEWS_COMMENT)->groupBy('entity_id')->get(['entity_id' , DB::raw('count(*) as total_reported')]);
            $commentsReplyReported = ReportClient::where('report_type_id',ReportTypes::REVIEWS_COMMENT_REPLY)->groupBy('entity_id')->get(['entity_id' , DB::raw('count(*) as total_reported')]);

            $communityCommentsReported = ReportClient::where('report_type_id',ReportTypes::COMMUNITY_COMMENT)->groupBy('entity_id')->get(['entity_id' , DB::raw('count(*) as total_reported')]);
            $communityCommentsReplyReported = ReportClient::where('report_type_id',ReportTypes::COMMUNITY_COMMENT_REPLY)->groupBy('entity_id')->get(['entity_id' , DB::raw('count(*) as total_reported')]);
    
            foreach($commentsReported as $report){            
                if($report && $report->total_reported >= $delete_comment_total) {
                    ReviewComments::where('id',$report->entity_id)->delete();
                }
            }
            foreach($commentsReplyReported as $report){            
                if($report && $report->total_reported >= $delete_comment_total) {
                    ReviewCommentReply::where('id',$report->entity_id)->delete();
                }
            }
            foreach($communityCommentsReported as $communityReport){            
                if($communityReport && $communityReport->total_reported >= $delete_comment_total) {
                    CommunityComments::where('id',$communityReport->entity_id)->delete();
                }
            }
            foreach($communityCommentsReplyReported as $communityReport){            
                if($communityReport && $communityReport->total_reported >= $delete_comment_total) {
                    CommunityCommentReply::where('id',$communityReport->entity_id)->delete();
                }
            }
        }
       
        $this->info('Report:Cron Cummand Run successfully!');
    }
}
