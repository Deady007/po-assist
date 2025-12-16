@extends('layout')

@section('content')
  <div class="page-head">
    <div>
      <h1>Customers</h1>
      <p class="muted">Track customer codes, industries, and contacts. Codes auto-generate via the sequence engine.</p>
    </div>
    @if(auth()->user()?->role?->name === 'Admin' || auth()->user()?->role?->name === 'PM')
      <a class="btn" href="{{ route('clients.customers.create') }}">New customer</a>
    @endif
  </div>

  <form method="GET" class="card row" style="align-items:flex-end; gap:12px; flex-wrap:wrap;">
    <div class="col">
      <label>Search</label>
      <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Name or code">
    </div>
    <div class="col" style="max-width:220px;">
      <label>Status</label>
      <select name="is_active">
        <option value="">All</option>
        <option value="1" @selected(($filters['is_active'] ?? '') === '1')>Active</option>
        <option value="0" @selected(($filters['is_active'] ?? '') === '0')>Inactive</option>
      </select>
    </div>
    <div class="col" style="max-width:120px;">
      <button class="btn secondary" type="submit">Filter</button>
    </div>
  </form>

  <div class="stacked">
    @foreach($customers as $customer)
      <div class="card row" style="align-items:center; justify-content:space-between; gap:14px;">
        <div class="col">
          <div style="display:flex; gap:8px; align-items:center;">
            <div class="badge blue">{{ $customer->customer_code }}</div>
            <strong>{{ $customer->name }}</strong>
          </div>
          <div class="muted">{{ $customer->industry ?? 'Industry N/A' }}</div>
          <div class="muted">Contacts: {{ $customer->contacts_count }}</div>
        </div>
        <div class="col" style="flex:0; display:flex; gap:8px; align-items:center;">
          <span class="badge {{ $customer->is_active ? 'green' : 'rose' }}">{{ $customer->is_active ? 'Active' : 'Inactive' }}</span>
          <a class="btn secondary" href="{{ route('clients.customers.show', $customer->id) }}">View</a>
        </div>
      </div>
    @endforeach
  </div>

  <div style="margin-top:12px;">
    {{ $customers->links() }}
  </div>
@endsection
