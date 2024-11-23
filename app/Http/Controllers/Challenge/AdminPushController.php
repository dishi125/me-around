<?php

namespace App\Http\Controllers\Challenge;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AdminPushController extends Controller
{
    public function index()
    {
        $title = "Admin push management";

        return view('challenge.admin-push.index', compact('title'));
    }
}
