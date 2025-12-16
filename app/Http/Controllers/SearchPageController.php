<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SearchPageController extends Controller
{
    public function __invoke(Request $request): View
    {
        $q = $request->query('q', '');
        $projects = collect();
        $customers = collect();

        if ($q) {
            $projects = Project::where('project_code', 'like', "%{$q}%")
                ->orWhere('name', 'like', "%{$q}%")
                ->orderBy('name')
                ->limit(10)
                ->get();

            $customers = Client::where('name', 'like', "%{$q}%")
                ->orderBy('name')
                ->limit(10)
                ->get();
        }

        return view('search.index', compact('q', 'projects', 'customers'));
    }
}
