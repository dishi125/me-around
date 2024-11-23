<?php

namespace App\Http\Controllers\QrCode;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $title = "Overview";

        return view('qr-code.dashboard.index', compact('title','user'));
    }
}
