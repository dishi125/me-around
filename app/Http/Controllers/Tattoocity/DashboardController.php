<?php

namespace App\Http\Controllers\Tattoocity;

use App\Http\Controllers\Controller;
use App\Models\Config;
use App\Models\EntityTypes;
use App\Models\Hospital;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $title = "Overview";

        return view('tattoocity.dashboard.index', compact('title','user'));
    }

}
