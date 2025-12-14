<?php

namespace App\Http\Controllers;

use App\Models\Project;

class DashboardController extends Controller
{
    public function index()
    {
        $projects = Project::orderBy('name')->get();
        return view('dashboard', compact('projects'));
    }
}
