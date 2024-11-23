<?php

namespace App\Http\Controllers\Challenge;

use App\Http\Controllers\Controller;
use App\Models\ChallengeAppInvited;
use App\Models\ChallengeInvitedUser;
use Illuminate\Http\Request;

class InvitationManagementController extends Controller
{
    public function index(Request $request)
    {
        $title = 'Invitation Management';
        $followerInvitationCount = ChallengeInvitedUser::where('is_admin_read',0)->count();
        $appInvitationCount = ChallengeAppInvited::where('is_admin_read',0)->count();

        return view('challenge.invitation-management.index', compact('title','followerInvitationCount','appInvitationCount'));
    }

    public function getJsonAllData(Request $request)
    {
        $columns = array(
            0 => 'users_detail.name',
        );

        $limit = $request->input('length');
        $start = $request->input('start');
        $search = $request->input('search.value');
        $draw = $request->input('draw');
        $order = $columns[$request->input('order.0.column')];
        $dir = $request->input('order.0.dir');

        $adminTimezone = $this->getAdminUserTimezone();
        try {
            $data = [];

            $userQuery = ChallengeInvitedUser::leftjoin('users_detail', 'users_detail.user_id', 'challenge_invited_users.invite_by')
                ->select('challenge_invited_users.id', 'users_detail.name', 'challenge_invited_users.invite_by')
                ->groupBy('challenge_invited_users.invite_by');

            if (!empty($search)) {
                $userQuery = $userQuery->where(function ($q) use ($search) {
                    $q->where('users_detail.name', 'LIKE', "%{$search}%");
                });
            }

            $totalData = count($userQuery->get());
            $totalFiltered = $totalData;

            $allData = $userQuery->offset($start)
                ->limit($limit)
                ->orderBy($order, $dir)
                ->get();

            $count = 0;
            foreach ($allData as $invited) {
                $data[$count]['user_name'] = $invited->name;
                $data[$count]['action'] = '<a href="javascript:void(0)" role="button" onclick="showInvitedUserList('.$invited->invite_by.')" class="btn btn-primary btn-sm mx-1" data-toggle="tooltip" data-original-title="">See More</a>';

                $count++;
            }

            $jsonData = array(
                "draw" => intval($draw),
                "recordsTotal" => intval($totalData),
                "recordsFiltered" => intval($totalFiltered),
                "data" => $data
            );
            return response()->json($jsonData);
        } catch (Exception $ex) {
            $jsonData = array(
                "draw" => intval($draw),
                "recordsTotal" => 0,
                "recordsFiltered" => 0,
                "data" => []
            );
            return response()->json($jsonData);
        }
    }

    public function invitedFollowerList($id){
        $adminTimezone = $this->getAdminUserTimezone();
        $users = ChallengeInvitedUser::leftjoin('challenges', function ($join) {
                $join->on('challenge_invited_users.challenge_id', '=', 'challenges.id');
            })
            ->leftjoin('users_detail', function ($join) {
                $join->on('challenge_invited_users.user_id', '=', 'users_detail.user_id');
            })
            ->where('challenge_invited_users.invite_by',$id)
            ->select('users_detail.name','challenges.title')
            ->get();

        return view('challenge.invitation-management.show-users-popup', compact('users','id'));
    }

    public function appInvitationIndex(Request $request)
    {
        $title = 'Invitation Management';
        ChallengeInvitedUser::where('is_admin_read',0)->update([
            'is_admin_read' => 1
        ]);
        ChallengeAppInvited::where('is_admin_read',0)->update([
            'is_admin_read' => 1
        ]);

        return view('challenge.invitation-management.app-invitation-index', compact('title'));
    }

    public function appInvitationJsonAllData(Request $request)
    {
        $columns = array(
            0 => 'users_detail.name',
        );

        $limit = $request->input('length');
        $start = $request->input('start');
        $search = $request->input('search.value');
        $draw = $request->input('draw');
        $order = $columns[$request->input('order.0.column')];
        $dir = $request->input('order.0.dir');

        $adminTimezone = $this->getAdminUserTimezone();
        try {
            $data = [];

            $userQuery = ChallengeAppInvited::leftjoin('users_detail', 'users_detail.user_id', 'challenge_app_invited.invite_by')
                ->select('challenge_app_invited.id', 'users_detail.name', 'challenge_app_invited.invite_by','challenge_app_invited.created_at')
                ->groupBy('challenge_app_invited.invite_by');

            if (!empty($search)) {
                $userQuery = $userQuery->where(function ($q) use ($search) {
                    $q->where('users_detail.name', 'LIKE', "%{$search}%");
                });
            }

            $totalData = count($userQuery->get());
            $totalFiltered = $totalData;

            $allData = $userQuery->offset($start)
                ->limit($limit)
                ->orderBy($order, $dir)
                ->get();

            $count = 0;
            foreach ($allData as $invited) {
                $data[$count]['sender'] = $invited->name;
                $data[$count]['time'] = $this->formatDateTimeCountryWise($invited->created_at,$adminTimezone);

                $count++;
            }

            $jsonData = array(
                "draw" => intval($draw),
                "recordsTotal" => intval($totalData),
                "recordsFiltered" => intval($totalFiltered),
                "data" => $data
            );
            return response()->json($jsonData);
        } catch (Exception $ex) {
            $jsonData = array(
                "draw" => intval($draw),
                "recordsTotal" => 0,
                "recordsFiltered" => 0,
                "data" => []
            );
            return response()->json($jsonData);
        }
    }

}
