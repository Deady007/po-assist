<?php

namespace App\Modules\ClientManagement\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Modules\ClientManagement\Http\Requests\ClientStoreRequest;
use App\Modules\ClientManagement\Http\Requests\ClientUpdateRequest;
use App\Services\SequenceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ClientController extends Controller
{
    public function __construct(private SequenceService $sequences)
    {
    }

    public function index(): View
    {
        $clients = Client::orderBy('name')->get();
        return view('admin.clients.index', compact('clients'));
    }

    public function store(ClientStoreRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $code = $data['client_code'] ?? null;
        if (!$code) {
            $code = $this->sequences->next('client');
        }

        Client::create([
            'client_code' => $code,
            'name' => $data['name'],
            'industry' => $data['industry'] ?? null,
            'website' => $data['website'] ?? null,
            'contact_person_name' => $data['contact_person_name'] ?? null,
            'contact_email' => $data['contact_email'] ?? null,
            'contact_phone' => $data['contact_phone'] ?? null,
            'billing_address' => $data['billing_address'] ?? null,
            'is_active' => $data['is_active'] ?? true,
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
        ]);

        return redirect()->route('admin.clients.index')->with('status', 'Client created');
    }

    public function edit(int $client): View
    {
        $model = Client::findOrFail($client);
        $clients = Client::orderBy('name')->get();

        return view('admin.clients.index', [
            'clients' => $clients,
            'editClient' => $model,
        ]);
    }

    public function update(ClientUpdateRequest $request, int $client): RedirectResponse
    {
        $model = Client::findOrFail($client);
        $data = $request->validated();

        $model->update([
            'client_code' => $data['client_code'] ?? $model->client_code ?? $this->sequences->next('client'),
            'name' => $data['name'],
            'industry' => $data['industry'] ?? null,
            'website' => $data['website'] ?? null,
            'contact_person_name' => $data['contact_person_name'] ?? null,
            'contact_email' => $data['contact_email'] ?? null,
            'contact_phone' => $data['contact_phone'] ?? null,
            'billing_address' => $data['billing_address'] ?? null,
            'is_active' => $data['is_active'] ?? $model->is_active,
            'updated_by' => auth()->id(),
        ]);

        return redirect()->route('admin.clients.index')->with('status', 'Client updated');
    }

    public function destroy(int $client): RedirectResponse
    {
        $model = Client::withCount('projects')->findOrFail($client);
        if ($model->projects_count > 0) {
            return redirect()->route('admin.clients.index')->withErrors(['Cannot delete client with projects.']);
        }

        $model->delete();

        return redirect()->route('admin.clients.index')->with('status', 'Client removed');
    }
}
