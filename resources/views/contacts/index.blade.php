@extends('layout')

@section('content')
  <div class="page-head">
    <div>
      <h1>Contacts</h1>
      <p class="muted">Filter by customer and manage primary contacts.</p>
    </div>
  </div>

  <div class="card stacked">
    <form method="GET" class="row" style="gap:12px; align-items:flex-end;">
      <div class="col">
        <label>Customer</label>
        <select name="customer_id">
          <option value="">All</option>
          @foreach($customers as $customer)
            <option value="{{ $customer->id }}" @selected(request('customer_id') == $customer->id)>{{ $customer->name }}</option>
          @endforeach
        </select>
      </div>
      <div class="col" style="max-width:140px;">
        <button class="btn secondary" type="submit">Apply</button>
      </div>
    </form>
  </div>

  @if(auth()->user()?->role?->name === 'Admin' || auth()->user()?->role?->name === 'PM')
    <div class="card stacked" style="margin-top:14px;">
      <h3 style="margin:0;">Quick add</h3>
      <form method="POST" action="{{ route('clients.contacts.store') }}" class="grid two">
        @csrf
        <div>
          <label>Customer</label>
          <select name="customer_id" required>
            <option value="">Select</option>
            @foreach($customers as $customer)
              <option value="{{ $customer->id }}" @selected(request('customer_id') == $customer->id)>{{ $customer->name }}</option>
            @endforeach
          </select>
        </div>
        <div>
          <label>Name</label>
          <input name="name" required>
        </div>
        <div>
          <label>Email</label>
          <input name="email" type="email">
        </div>
        <div>
          <label>Phone</label>
          <input name="phone">
        </div>
        <div>
          <label>Designation</label>
          <input name="designation">
        </div>
        <div>
          <label>Tags (comma separated)</label>
          <input name="tags">
        </div>
        <div>
          <label>Primary?</label>
          <select name="is_primary">
            <option value="0">No</option>
            <option value="1">Yes</option>
          </select>
        </div>
        <div style="align-self:flex-end;">
          <input type="hidden" name="redirect_to" value="{{ url()->current() }}?customer_id={{ request('customer_id') }}">
          <button class="btn" type="submit">Add contact</button>
        </div>
      </form>
    </div>
  @endif

  <div class="stacked" style="margin-top:16px;">
    @foreach($contacts as $contact)
      <div class="card row" style="align-items:center; justify-content:space-between; gap:12px;">
        <div class="col">
          <div style="display:flex; gap:8px; align-items:center;">
            <strong>{{ $contact->name }}</strong>
            @if($contact->is_primary)
              <span class="badge amber">Primary</span>
            @endif
          </div>
          <div class="muted">{{ $contact->email ?? 'No email' }} @if($contact->phone) · {{ $contact->phone }} @endif</div>
          <div class="muted">Customer: {{ $contact->customer?->name }}</div>
        </div>
        <div class="row" style="gap:8px;">
          @if(auth()->user()?->role?->name === 'Admin' || auth()->user()?->role?->name === 'PM')
            @if(!$contact->is_primary)
              <form method="POST" action="{{ route('clients.contacts.update', $contact->id) }}">
                @csrf
                @method('PATCH')
                <input type="hidden" name="is_primary" value="1">
                <input type="hidden" name="redirect_to" value="{{ url()->current() }}?customer_id={{ request('customer_id') }}">
                <button class="btn secondary" type="submit">Make primary</button>
              </form>
            @endif
            <a class="btn secondary" href="{{ route('clients.contacts.edit', $contact->id) }}">Edit</a>
            <form method="POST" action="{{ route('clients.contacts.destroy', $contact->id) }}" onsubmit="return confirm('Delete contact?');">
              @csrf
              @method('DELETE')
              <button class="btn secondary" type="submit">Delete</button>
            </form>
          @endif
        </div>
      </div>
    @endforeach
  </div>

  <div style="margin-top:12px;">
    {{ $contacts->links() }}
  </div>
@endsection
