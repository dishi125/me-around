<?php

namespace App\Http\Controllers\Challenge;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DeleteAccountController extends Controller
{
    public function index()
    {
        $title = "Delete account";

        return view('challenge.delete-account.index', compact('title'));
    }

}
