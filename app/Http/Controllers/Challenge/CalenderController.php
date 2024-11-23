<?php

namespace App\Http\Controllers\Challenge;

use App\Http\Controllers\Controller;
use App\Models\Challenge;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CalenderController extends Controller
{
    public function periodChallengeIndex()
    {
        $user = Auth::user();
        $title = "Calender";
        $day_wise_challenges = Challenge::with('challengedays')
                            ->whereNotNull('start_date')
                            ->whereNotNull('end_date')
                            ->get()
                            ->map(function ($item) {
                                $item['days'] = $item->challengedays()->pluck('day')->toArray();

                                $timeArr = explode(":",$item['verify_time']);
                                $time = isset($timeArr[2]) ? $timeArr[0].":".$timeArr[1] : $item['verify_time']; //remove seconds
                                $dbTime = \Carbon\Carbon::createFromFormat('H:i', $time, "UTC");
                                $userTime = $dbTime->setTimezone("Asia/Seoul");
                                $item['verify_time'] = $userTime->format('g:i A');

                                return $item;
                            });

        $today_date = Carbon::now()->format('Y-m-d');
        $dayName = Carbon::now()->format('D');
        $dayName = strtolower($dayName);
        $today_day = substr($dayName, 0, 2);

        $count_today_period_challenges = Challenge::leftjoin('challenge_days', function ($join) {
                $join->on('challenges.id', '=', 'challenge_days.challenge_id');
            })
            ->whereDate('challenges.start_date', '<=', $today_date)
            ->whereDate('challenges.end_date', '>=', $today_date)
            ->where('challenge_days.day',$today_day)
            ->where('challenges.is_period_challenge',1)
            ->count();

        $count_today_challenges = Challenge::whereDate('date',$today_date)
            ->where('is_period_challenge',0)
            ->count();

        return view('challenge.calender.period-challenge-index', compact('title','user','day_wise_challenges','count_today_period_challenges','count_today_challenges'));
    }

    public function challengeIndex()
    {
        $user = Auth::user();
        $title = "Calender";
        $challenges = Challenge::whereNotNull('date')
            ->get()
            ->map(function ($item) {
                $timeArr = explode(":",$item['verify_time']);
                $time = isset($timeArr[2]) ? $timeArr[0].":".$timeArr[1] : $item['verify_time']; //remove seconds
                $dbTime = \Carbon\Carbon::createFromFormat('H:i', $time, "UTC");
                $userTime = $dbTime->setTimezone("Asia/Seoul");
                $item['verify_time'] = $userTime->format('g:i A');

                return $item;
            });

        $today_date = Carbon::now()->format('Y-m-d');
        $dayName = Carbon::now()->format('D');
        $dayName = strtolower($dayName);
        $today_day = substr($dayName, 0, 2);

        $count_today_period_challenges = Challenge::leftjoin('challenge_days', function ($join) {
                $join->on('challenges.id', '=', 'challenge_days.challenge_id');
            })
            ->whereDate('challenges.start_date', '<=', $today_date)
            ->whereDate('challenges.end_date', '>=', $today_date)
            ->where('challenge_days.day',$today_day)
            ->where('challenges.is_period_challenge',1)
            ->count();

        $count_today_challenges = Challenge::whereDate('date',$today_date)
            ->where('is_period_challenge',0)
            ->count();

        return view('challenge.calender.challenge-index', compact('title','user','challenges','count_today_period_challenges','count_today_challenges'));
    }

}
