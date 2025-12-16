<?php

namespace App\Http\Controllers;

use App\Http\Requests\ContactStoreRequest;
use App\Http\Requests\ContactUpdateRequest;
use App\Models\Contact;
use App\Models\Customer;
use App\Services\ContactService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ContactPageController extends Controller
{
    public function __construct(private ContactService $contacts) {}

    public function index(Request $request): View
    {
        $customers = Customer::orderBy('name')->get();
        $query = Contact::with('customer');

        if ($customerId = $request->query('customer_id')) {
            $query->where('customer_id', $customerId);
        }

        $contacts = $query->orderByDesc('is_primary')->orderBy('name')->paginate(15)->withQueryString();

        return view('contacts.index', compact('contacts', 'customers'));
    }

    public function store(ContactStoreRequest $request): RedirectResponse
    {
        $contact = $this->contacts->create($request->validated());
        $redirect = $request->input('redirect_to') ?: route('clients.contacts.index', ['customer_id' => $contact->customer_id]);

        return redirect($redirect)->with('status', 'Contact created');
    }

    public function edit(Contact $contact): View
    {
        $customers = Customer::orderBy('name')->get();
        return view('contacts.edit', compact('contact', 'customers'));
    }

    public function update(ContactUpdateRequest $request, Contact $contact): RedirectResponse
    {
        $updated = $this->contacts->update($contact, $request->validated());
        $redirect = $request->input('redirect_to') ?: route('clients.contacts.index', ['customer_id' => $updated->customer_id]);

        return redirect($redirect)->with('status', 'Contact updated');
    }

    public function destroy(Contact $contact): RedirectResponse
    {
        $customerId = $contact->customer_id;
        $this->contacts->delete($contact);

        return redirect()->route('clients.contacts.index', ['customer_id' => $customerId])->with('status', 'Contact removed');
    }
}
