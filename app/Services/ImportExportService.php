<?php

namespace App\Services;

use App\Models\Client;
use App\Models\ImportJob;
use App\Models\ImportJobRowError;
use App\Models\Project;
use App\Models\ProjectStatus;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx as XlsxWriter;
use RuntimeException;

class ImportExportService
{
    public function __construct(private SequenceService $sequences)
    {
    }

    public function export(string $modelName, string $format = 'csv'): string
    {
        [$headers, $rows] = $this->rowsForExport($modelName);

        return $format === 'xlsx'
            ? $this->exportXlsx($headers, $rows, "{$modelName}_export.xlsx")
            : $this->exportCsv($headers, $rows, "{$modelName}_export.csv");
    }

    public function import(string $modelName, UploadedFile $file, ?int $userId = null): ImportJob
    {
        $job = ImportJob::create([
            'model_name' => $modelName,
            'file_name' => $file->getClientOriginalName(),
            'uploaded_by' => $userId,
            'status' => 'pending',
            'created_by' => $userId,
            'updated_by' => $userId,
        ]);

        $job->update(['status' => 'running']);

        $rows = $this->readRows($file->getRealPath());
        $job->total_rows = count($rows);
        $errorCount = 0;

        foreach ($rows as $index => $row) {
            $existing = $this->lookupExisting($modelName, $row);
            $rules = $this->rulesFor($modelName, $existing);
            $validator = Validator::make($row, $rules, [], $this->attributesFor($modelName));

            if ($validator->fails()) {
                $errors = $validator->errors()->toArray();
                foreach ($errors as $field => $messages) {
                    foreach ($messages as $message) {
                        ImportJobRowError::create([
                            'import_job_id' => $job->id,
                            'row_number' => $index + 2, // +1 for header, +1 for 1-indexing
                            'field_name' => $field,
                            'error_message' => $message,
                        ]);
                        $errorCount++;
                    }
                }
                continue;
            }

            $this->persistRow($modelName, $validator->validated(), $existing, $userId);
        }

        $job->error_count = $errorCount;
        $job->status = $errorCount > 0 ? 'completed_with_errors' : 'completed';
        $job->save();

        return $job;
    }

    private function readRows(string $path): array
    {
        $spreadsheet = IOFactory::load($path);
        $sheet = $spreadsheet->getActiveSheet()->toArray(null, true, true, true);
        $headers = [];
        $rows = [];

        foreach ($sheet as $rowIndex => $row) {
            $values = array_values($row);
            if (!$headers) {
                $headers = array_map(fn ($h) => $this->normalizeHeader($h), $values);
                continue;
            }

            // skip empty rows
            if (!array_filter($values, fn ($v) => $v !== null && $v !== '')) {
                continue;
            }

            $normalized = [];
            foreach ($headers as $i => $key) {
                $normalized[$key] = isset($values[$i]) && is_string($values[$i])
                    ? trim($values[$i])
                    : ($values[$i] ?? null);
            }

            $rows[] = $normalized;
        }

        return $rows;
    }

    private function normalizeHeader(?string $header): string
    {
        $header = trim((string) $header);
        $header = strtolower(preg_replace('/[^a-z0-9]+/i', '_', $header));
        return trim($header, '_');
    }

    private function exportCsv(array $headers, array $rows, string $filename): string
    {
        $path = storage_path("app/tmp/{$filename}");
        if (!is_dir(dirname($path))) {
            mkdir(dirname($path), 0777, true);
        }

        $fh = fopen($path, 'w');
        fputcsv($fh, $headers);
        foreach ($rows as $row) {
            $line = [];
            foreach ($headers as $key) {
                $line[] = $row[$key] ?? '';
            }
            fputcsv($fh, $line);
        }
        fclose($fh);

        return $path;
    }

    private function exportXlsx(array $headers, array $rows, string $filename): string
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray($headers, null, 'A1');
        $rowIndex = 2;
        foreach ($rows as $row) {
            $line = [];
            foreach ($headers as $key) {
                $line[] = $row[$key] ?? '';
            }
            $sheet->fromArray($line, null, "A{$rowIndex}");
            $rowIndex++;
        }

        $path = storage_path("app/tmp/{$filename}");
        if (!is_dir(dirname($path))) {
            mkdir(dirname($path), 0777, true);
        }

        $writer = new XlsxWriter($spreadsheet);
        $writer->save($path);

        return $path;
    }

    private function rowsForExport(string $modelName): array
    {
        return match ($modelName) {
            'users' => [
                ['name', 'email', 'phone', 'role', 'is_active'],
                User::with('role')->get()->map(function (User $user) {
                    return [
                        'name' => $user->name,
                        'email' => $user->email,
                        'phone' => $user->phone,
                        'role' => $user->role?->name,
                        'is_active' => $user->is_active ? '1' : '0',
                    ];
                })->all(),
            ],
            'clients' => [
                ['client_code', 'name', 'industry', 'website', 'contact_person_name', 'contact_email', 'contact_phone', 'billing_address', 'is_active'],
                Client::all()->map(function (Client $client) {
                    return [
                        'client_code' => $client->client_code,
                        'name' => $client->name,
                        'industry' => $client->industry,
                        'website' => $client->website,
                        'contact_person_name' => $client->contact_person_name,
                        'contact_email' => $client->contact_email,
                        'contact_phone' => $client->contact_phone,
                        'billing_address' => $client->billing_address,
                        'is_active' => $client->is_active ? '1' : '0',
                    ];
                })->all(),
            ],
            'projects' => [
                ['project_code', 'name', 'client_code', 'status', 'priority', 'start_date', 'due_date', 'owner_email', 'is_active', 'description'],
                Project::with(['client', 'status', 'owner'])->get()->map(function (Project $project) {
                    return [
                        'project_code' => $project->project_code,
                        'name' => $project->name,
                        'client_code' => $project->client?->client_code,
                        'status' => $project->status?->name,
                        'priority' => $project->priority,
                        'start_date' => optional($project->start_date)->toDateString(),
                        'due_date' => optional($project->due_date)->toDateString(),
                        'owner_email' => $project->owner?->email,
                        'is_active' => $project->is_active ? '1' : '0',
                        'description' => $project->description,
                    ];
                })->all(),
            ],
            'project_statuses' => [
                ['name', 'order_no', 'is_default', 'is_active'],
                ProjectStatus::orderBy('order_no')->get()->map(function (ProjectStatus $status) {
                    return [
                        'name' => $status->name,
                        'order_no' => $status->order_no,
                        'is_default' => $status->is_default ? '1' : '0',
                        'is_active' => $status->is_active ? '1' : '0',
                    ];
                })->all(),
            ],
            default => throw new RuntimeException("Unsupported export model: {$modelName}"),
        };
    }

    private function rulesFor(string $modelName, $existing = null): array
    {
        return match ($modelName) {
            'users' => [
                'name' => 'required|string|max:255',
                'email' => 'required|email|max:255|unique:users,email' . ($existing ? ',' . $existing->id : ''),
                'phone' => 'nullable|string|max:30',
                'role' => 'required|string|exists:roles,name',
                'password' => $existing ? 'nullable|string|min:8|regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\\d).+$/' : 'required|string|min:8|regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\\d).+$/',
                'is_active' => 'nullable|in:0,1,true,false',
            ],
            'clients' => [
                'client_code' => [
                    'nullable',
                    'string',
                    'max:50',
                    $existing ? Rule::unique('clients', 'client_code')->ignore($existing->id) : Rule::unique('clients', 'client_code'),
                ],
                'name' => 'required|string|max:255',
                'industry' => 'nullable|string|max:255',
                'website' => 'nullable|string|max:255',
                'contact_person_name' => 'nullable|string|max:255',
                'contact_email' => 'nullable|email',
                'contact_phone' => 'nullable|string|max:30',
                'billing_address' => 'nullable|string',
                'is_active' => 'nullable|in:0,1,true,false',
            ],
            'projects' => [
                'project_code' => [
                    'nullable',
                    'string',
                    'max:50',
                    $existing ? Rule::unique('projects', 'project_code')->ignore($existing->id) : Rule::unique('projects', 'project_code'),
                ],
                'name' => 'required|string|max:255',
                'client_code' => 'required|string|exists:clients,client_code',
                'status' => 'nullable|string|exists:project_statuses,name',
                'priority' => 'nullable|in:low,medium,high,critical',
                'start_date' => 'nullable|date',
                'due_date' => 'nullable|date',
                'owner_email' => 'nullable|email',
                'description' => 'nullable|string',
                'is_active' => 'nullable|in:0,1,true,false',
            ],
            'project_statuses' => [
                'name' => 'required|string|max:255',
                'order_no' => 'required|integer|min:1',
                'is_default' => 'nullable|in:0,1,true,false',
                'is_active' => 'nullable|in:0,1,true,false',
            ],
            default => [],
        };
    }

    private function attributesFor(string $modelName): array
    {
        return match ($modelName) {
            'users' => [
                'role' => 'role',
            ],
            default => [],
        };
    }

    private function lookupExisting(string $modelName, array $row)
    {
        return match ($modelName) {
            'users' => isset($row['email']) ? User::where('email', $row['email'])->first() : null,
            'clients' => isset($row['client_code']) ? Client::where('client_code', $row['client_code'])->first() : null,
            'projects' => isset($row['project_code']) ? Project::where('project_code', $row['project_code'])->first() : null,
            'project_statuses' => isset($row['name']) ? ProjectStatus::where('name', $row['name'])->first() : null,
            default => null,
        };
    }

    private function persistRow(string $modelName, array $row, $existing = null, ?int $userId = null): void
    {
        switch ($modelName) {
            case 'users':
                $role = Role::where('name', $row['role'] ?? null)->first();
                if (!$role) {
                    throw new RuntimeException('Role not found for user import');
                }

                /** @var User $user */
                $user = $existing ?: new User();
                $user->name = $row['name'];
                $user->email = $row['email'];
                $user->phone = $row['phone'] ?? null;
                $user->role_id = $role->id;
                $user->is_active = filter_var($row['is_active'] ?? true, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? true;
                if (!empty($row['password'])) {
                    $user->password = $row['password'];
                }
                $user->created_by = $user->exists ? $user->created_by : $userId;
                $user->updated_by = $userId;
                $user->save();
                break;

            case 'clients':
                $client = $existing ?: new Client();
                $client->client_code = $row['client_code'] ?: $this->sequences->next('client');
                $client->name = $row['name'];
                $client->industry = $row['industry'] ?? null;
                $client->website = $row['website'] ?? null;
                $client->contact_person_name = $row['contact_person_name'] ?? null;
                $client->contact_email = $row['contact_email'] ?? null;
                $client->contact_phone = $row['contact_phone'] ?? null;
                $client->billing_address = $row['billing_address'] ?? null;
                $client->is_active = filter_var($row['is_active'] ?? true, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? true;
                $client->created_by = $client->exists ? $client->created_by : $userId;
                $client->updated_by = $userId;
                $client->save();
                break;

            case 'projects':
                $client = Client::where('client_code', $row['client_code'] ?? '')->first();
                $status = isset($row['status']) ? ProjectStatus::where('name', $row['status'])->first() : null;
                $owner = isset($row['owner_email']) ? User::where('email', $row['owner_email'])->first() : null;

                /** @var Project $project */
                $project = $existing ?: new Project();
                $project->project_code = $row['project_code'] ?: $this->sequences->next('project');
                $project->name = $row['name'];
                $project->client_id = $client?->id;
                $project->client_name = $client?->name;
                $project->status_id = $status?->id;
                $project->priority = $row['priority'] ?? 'medium';
                $project->start_date = $row['start_date'] ?? null;
                $project->due_date = $row['due_date'] ?? null;
                $project->owner_user_id = $owner?->id;
                $project->description = $row['description'] ?? null;
                $project->is_active = filter_var($row['is_active'] ?? true, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? true;
                $project->created_by = $project->exists ? $project->created_by : $userId;
                $project->updated_by = $userId;
                $project->save();
                break;

            case 'project_statuses':
                $status = $existing ?: new ProjectStatus();
                $status->name = $row['name'];
                $status->order_no = (int) $row['order_no'];
                $status->is_default = filter_var($row['is_default'] ?? false, FILTER_VALIDATE_BOOLEAN);
                $status->is_active = filter_var($row['is_active'] ?? true, FILTER_VALIDATE_BOOLEAN);
                $status->created_by = $status->exists ? $status->created_by : $userId;
                $status->updated_by = $userId;
                $status->save();

                if ($status->is_default) {
                    ProjectStatus::where('id', '!=', $status->id)->update(['is_default' => false]);
                }
                break;
        }
    }
}
