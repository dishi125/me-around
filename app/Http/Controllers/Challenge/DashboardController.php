<?php

namespace App\Http\Controllers\Challenge;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $title = "Overview";

        return view('challenge.dashboard.index', compact('title','user'));
    }
}
