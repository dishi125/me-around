<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ReelsDownloaderLogController extends Controller
{
    public function index(Request $request)
    {
        $title = 'Reels downloader log';

        return view('admin.reels-downloader-log.index', compact('title'));
    }
}
