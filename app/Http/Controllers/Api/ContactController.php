<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\ApiController;
use App\Http\Requests\ContactStoreRequest;
use App\Http\Requests\ContactUpdateRequest;
use App\Models\Contact;
use App\Services\ContactService;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;

class ContactController extends ApiController
{
    public function __construct(private ContactService $contacts) {}

    public function index(Request $request)
    {
        $query = Contact::with('customer');

        if ($customerId = $request->query('customer_id')) {
            $query->where('customer_id', $customerId);
        }

        if ($request->filled('is_primary')) {
            $query->where('is_primary', (bool) $request->boolean('is_primary'));
        }

        $perPage = min(100, max(1, (int) $request->query('per_page', 10)));
        $paginator = $query->orderBy('name')->paginate($perPage);

        return $this->success([
            'items' => $paginator->items(),
            'pagination' => [
                'total' => $paginator->total(),
                'per_page' => $paginator->perPage(),
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }

    public function store(ContactStoreRequest $request)
    {
        try {
            $contact = $this->contacts->create($request->validated());
        } catch (QueryException $e) {
            if (str_contains($e->getMessage(), 'contacts_primary_unique')) {
                return $this->failure([['code' => 'PRIMARY_EXISTS', 'message' => 'Primary contact already set']], 409);
            }
            throw $e;
        }

        return $this->success(['contact' => $contact], status: 201);
    }

    public function show(int $contact)
    {
        $model = Contact::with('customer')->findOrFail($contact);
        return $this->success(['contact' => $model]);
    }

    public function update(ContactUpdateRequest $request, int $contact)
    {
        $model = Contact::findOrFail($contact);

        try {
            $updated = $this->contacts->update($model, $request->validated());
        } catch (QueryException $e) {
            if (str_contains($e->getMessage(), 'contacts_primary_unique')) {
                return $this->failure([['code' => 'PRIMARY_EXISTS', 'message' => 'Primary contact already set']], 409);
            }
            throw $e;
        }

        return $this->success(['contact' => $updated]);
    }

    public function destroy(int $contact)
    {
        $model = Contact::findOrFail($contact);
        $this->contacts->delete($model);

        return $this->success(['message' => 'Contact removed']);
    }
}
