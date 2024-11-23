<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ResearchFormController extends Controller
{
    public function index(Request $request)
    {
        $title = 'Research form';

        return view('admin.research-form.index', compact('title'));
    }
}
