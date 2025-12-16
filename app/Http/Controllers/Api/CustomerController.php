<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\ApiController;
use App\Http\Requests\CustomerStoreRequest;
use App\Http\Requests\CustomerUpdateRequest;
use App\Models\Customer;
use App\Services\CustomerService;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;

class CustomerController extends ApiController
{
    public function __construct(private CustomerService $customers) {}

    public function index(Request $request)
    {
        $query = Customer::with('primaryContact');

        if ($search = $request->query('search')) {
            $query->where('name', 'like', "%{$search}%")
                ->orWhere('customer_code', 'like', "%{$search}%");
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', (bool) $request->boolean('is_active'));
        }

        $perPage = min(50, max(1, (int) $request->query('per_page', 10)));
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

    public function store(CustomerStoreRequest $request)
    {
        try {
            $customer = $this->customers->create($request->validated());
        } catch (QueryException $e) {
            return $this->failure([['code' => 'DUPLICATE_CODE', 'message' => 'Customer code already exists']], 409);
        }

        return $this->success(['customer' => $customer], status: 201);
    }

    public function show(int $customer)
    {
        $model = Customer::with('contacts')->findOrFail($customer);
        return $this->success(['customer' => $model]);
    }

    public function update(CustomerUpdateRequest $request, int $customer)
    {
        $model = Customer::findOrFail($customer);

        try {
            $updated = $this->customers->update($model, $request->validated());
        } catch (QueryException $e) {
            return $this->failure([['code' => 'DUPLICATE_CODE', 'message' => 'Customer code already exists']], 409);
        }

        return $this->success(['customer' => $updated->fresh('primaryContact')]);
    }

    public function activate(int $customer)
    {
        $model = Customer::findOrFail($customer);
        $toggled = $this->customers->toggle($model);

        return $this->success(['customer' => $toggled]);
    }
}
