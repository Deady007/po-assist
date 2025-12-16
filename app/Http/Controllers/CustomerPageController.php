<?php

namespace App\Http\Controllers;

use App\Http\Requests\CustomerStoreRequest;
use App\Http\Requests\CustomerUpdateRequest;
use App\Models\Contact;
use App\Models\Customer;
use App\Services\CustomerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomerPageController extends Controller
{
    public function __construct(private CustomerService $customers) {}

    public function index(Request $request): View
    {
        $query = Customer::withCount('contacts');

        if ($search = $request->query('search')) {
            $query->where('name', 'like', "%{$search}%")
                ->orWhere('customer_code', 'like', "%{$search}%");
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $customers = $query->orderBy('name')->paginate(12)->withQueryString();

        return view('customers.index', [
            'customers' => $customers,
            'filters' => [
                'search' => $search,
                'is_active' => $request->query('is_active'),
            ],
        ]);
    }

    public function create(): View
    {
        return view('customers.create');
    }

    public function store(CustomerStoreRequest $request): RedirectResponse
    {
        $customer = $this->customers->create($request->validated());

        return redirect()->route('clients.customers.show', $customer->id)
            ->with('status', 'Customer created');
    }

    public function show(Customer $customer): View
    {
        $customer->load('primaryContact');
        $contacts = Contact::where('customer_id', $customer->id)
            ->orderByDesc('is_primary')
            ->orderBy('name')
            ->get();

        return view('customers.show', compact('customer', 'contacts'));
    }

    public function update(CustomerUpdateRequest $request, Customer $customer): RedirectResponse
    {
        $this->customers->update($customer, $request->validated());

        return redirect()->route('clients.customers.show', $customer->id)
            ->with('status', 'Customer updated');
    }

    public function activate(Customer $customer): RedirectResponse
    {
        $this->customers->toggle($customer);

        return redirect()->route('clients.customers.show', $customer->id)
            ->with('status', 'Customer status updated');
    }
}
