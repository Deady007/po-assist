<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\ApiController;
use App\Models\Client;
use App\Models\Project;
use Illuminate\Http\Request;

class SearchController extends ApiController
{
    public function __invoke(Request $request)
    {
        $q = $request->query('q', '');
        $projects = Project::select('id', 'project_code', 'name', 'client_name', 'due_date')
            ->when($q, fn ($query) => $query->where(function ($sub) use ($q) {
                $sub->where('project_code', 'like', "%{$q}%")
                    ->orWhere('name', 'like', "%{$q}%");
            }))
            ->orderBy('name')
            ->limit(10)
            ->get();

        $customers = Client::select('id', 'client_code', 'name')
            ->when($q, fn ($query) => $query->where('name', 'like', "%{$q}%"))
            ->orderBy('name')
            ->limit(10)
            ->get();

        return $this->success([
            'projects' => $projects,
            'customers' => $customers,
        ]);
    }
}
