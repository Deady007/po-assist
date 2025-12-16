@extends('layout')

@section('content')
  <div class="page-head">
    <div>
      <h1>{{ $customer->name }}</h1>
      <p class="muted">Code {{ $customer->customer_code }} · {{ $customer->industry ?? 'Industry N/A' }}</p>
    </div>
    <div class="row" style="gap:10px; flex-wrap:wrap; justify-content:flex-end;">
      <form method="POST" action="{{ route('clients.customers.activate', $customer->id) }}">
        @csrf
        @method('PATCH')
        <button class="btn secondary" type="submit">{{ $customer->is_active ? 'Deactivate' : 'Activate' }}</button>
      </form>
      <a class="btn secondary" href="{{ route('clients.customers.index') }}">Back</a>
    </div>
  </div>

  @if(session('status'))
    <div class="card" style="border-color:#bbf7d0;">{{ session('status') }}</div>
  @endif

  <div class="grid two" style="align-items:start;">
    <div class="card stacked">
      <div style="display:flex; align-items:center; gap:10px;">
        <span class="badge {{ $customer->is_active ? 'green' : 'rose' }}">{{ $customer->is_active ? 'Active' : 'Inactive' }}</span>
        @if($customer->primaryContact)
          <span class="badge blue">Primary: {{ $customer->primaryContact->name }}</span>
        @endif
      </div>
      <div class="stacked">
        <div><strong>Website:</strong> {{ $customer->website ?: 'Not set' }}</div>
        <div><strong>Billing address:</strong> {{ $customer->billing_address ?: 'Not set' }}</div>
      </div>
      @if(auth()->user()?->role?->name === 'Admin' || auth()->user()?->role?->name === 'PM')
        <hr>
        <form method="POST" action="{{ route('clients.customers.update', $customer->id) }}" class="stacked">
          @csrf
          @method('PATCH')
          <div class="grid two">
            <div>
              <label>Name</label>
              <input name="name" value="{{ old('name', $customer->name) }}" required>
            </div>
            <div>
              <label>Customer code</label>
              <input name="customer_code" value="{{ old('customer_code', $customer->customer_code) }}">
            </div>
          </div>
          <div class="grid two">
            <div>
              <label>Industry</label>
              <input name="industry" value="{{ old('industry', $customer->industry) }}">
            </div>
            <div>
              <label>Website</label>
              <input name="website" value="{{ old('website', $customer->website) }}">
            </div>
          </div>
          <div>
            <label>Billing address</label>
            <textarea name="billing_address">{{ old('billing_address', $customer->billing_address) }}</textarea>
          </div>
          <div class="row" style="gap:10px; align-items:center;">
            <label style="margin:0;">Active?</label>
            <select name="is_active" style="max-width:140px;">
              <option value="1" @selected(old('is_active', $customer->is_active ? '1' : '0') === '1')>Yes</option>
              <option value="0" @selected(old('is_active', $customer->is_active ? '1' : '0') === '0')>No</option>
            </select>
          </div>
          <div>
            <button class="btn" type="submit">Save changes</button>
          </div>
        </form>
      @endif
    </div>

    <div class="card stacked">
      <div style="display:flex; justify-content:space-between; align-items:center;">
        <div>
          <h3 style="margin:0;">Add contact</h3>
          <p class="muted">Mark a contact as primary to surface across the workspace.</p>
        </div>
      </div>
      <form method="POST" action="{{ route('clients.contacts.store') }}" class="stacked">
        @csrf
        <input type="hidden" name="customer_id" value="{{ $customer->id }}">
        <input type="hidden" name="redirect_to" value="{{ url()->current() }}">
        <div class="grid two">
          <div>
            <label>Name</label>
            <input name="name" required>
          </div>
          <div>
            <label>Email</label>
            <input name="email" type="email">
          </div>
        </div>
        <div class="grid two">
          <div>
            <label>Phone</label>
            <input name="phone">
          </div>
          <div>
            <label>Designation</label>
            <input name="designation">
          </div>
        </div>
        <div>
          <label>Tags (comma separated)</label>
          <input name="tags" placeholder="billing, decision-maker">
        </div>
        <div class="row" style="gap:10px; align-items:center;">
          <label style="margin:0;">Primary?</label>
          <select name="is_primary" style="max-width:140px;">
            <option value="0">No</option>
            <option value="1">Yes</option>
          </select>
        </div>
        <div>
          <button class="btn secondary" type="submit">Save contact</button>
        </div>
      </form>
    </div>
  </div>

  <div class="card" style="margin-top:16px;">
    <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:10px;">
      <h3 style="margin:0;">Contacts</h3>
      <a class="btn secondary" href="{{ route('clients.contacts.index', ['customer_id' => $customer->id]) }}">Open contacts view</a>
    </div>
    <div class="stacked">
      @forelse($contacts as $contact)
        <div style="padding:12px 0; border-bottom:1px solid var(--border); display:flex; align-items:center; justify-content:space-between; gap:14px;">
          <div>
            <div style="display:flex; gap:8px; align-items:center;">
              <strong>{{ $contact->name }}</strong>
              @if($contact->is_primary)
                <span class="badge amber">Primary</span>
              @endif
            </div>
            <div class="muted">{{ $contact->email ?: 'No email' }} @if($contact->phone) · {{ $contact->phone }} @endif</div>
            <div class="muted">{{ $contact->designation ?: 'No designation' }}</div>
          </div>
          <div class="row" style="gap:8px;">
            @if(auth()->user()?->role?->name === 'Admin' || auth()->user()?->role?->name === 'PM')
              @if(!$contact->is_primary)
                <form method="POST" action="{{ route('clients.contacts.update', $contact->id) }}">
                  @csrf
                  @method('PATCH')
                  <input type="hidden" name="is_primary" value="1">
                  <input type="hidden" name="redirect_to" value="{{ url()->current() }}">
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
      @empty
        <div class="muted">No contacts yet. Add one above.</div>
      @endforelse
    </div>
  </div>
@endsection
