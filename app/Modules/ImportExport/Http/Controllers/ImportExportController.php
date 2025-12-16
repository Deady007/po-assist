<?php

namespace App\Modules\ImportExport\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ImportJob;
use App\Services\ImportExportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Response;
use Illuminate\View\View;

class ImportExportController extends Controller
{
    public function __construct(private ImportExportService $service)
    {
    }

    public function index(): View
    {
        $jobs = ImportJob::orderByDesc('created_at')->limit(20)->get();
        $supportedModels = $this->supportedModels();

        return view('admin.import_export.index', compact('jobs', 'supportedModels'));
    }

    public function export(): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $data = request()->validate([
            'model' => 'required|in:users,clients,projects,project_statuses',
            'format' => 'required|in:csv,xlsx',
        ]);

        $path = $this->service->export($data['model'], $data['format']);
        $name = basename($path);

        return Response::download($path, $name);
    }

    public function import(): RedirectResponse
    {
        $data = request()->validate([
            'model' => 'required|in:users,clients,projects,project_statuses',
            'file' => 'required|file|mimes:csv,txt,xlsx',
        ]);

        $job = $this->service->import($data['model'], $data['file'], auth()->id());

        $message = $job->status === 'completed_with_errors'
            ? 'Import finished with some row errors.'
            : 'Import completed successfully.';

        return redirect()->route('admin.import-export.index')->with('status', $message);
    }

    private function supportedModels(): array
    {
        return [
            'users' => 'Users',
            'clients' => 'Clients',
            'projects' => 'Projects',
            'project_statuses' => 'Project Statuses',
        ];
    }
}
