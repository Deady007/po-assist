@extends('layout')

@section('content')
  <div class="row" style="align-items:flex-start; gap:18px;">
    <div class="col" style="min-width:320px; max-width:420px;">
      <div class="card">
        <h2 style="margin-bottom:6px;">{{ isset($editClient) ? 'Edit Client' : 'Create Client' }}</h2>
        <p class="muted">Client codes auto-generate from the sequence engine.</p>
        <form method="POST" action="{{ isset($editClient) ? route('admin.clients.update', $editClient->id) : route('admin.clients.store') }}">
          @csrf
          @if(isset($editClient))
            @method('PUT')
          @endif
          <div class="stacked">
            <div>
              <label>Name</label>
              <input name="name" value="{{ old('name', $editClient->name ?? '') }}" required>
            </div>
            <div>
              <label>Client Code</label>
              <input name="client_code" value="{{ old('client_code', $editClient->client_code ?? '') }}" placeholder="Auto if blank">
            </div>
            <div>
              <label>Industry</label>
              <input name="industry" value="{{ old('industry', $editClient->industry ?? '') }}">
            </div>
            <div>
              <label>Website</label>
              <input name="website" value="{{ old('website', $editClient->website ?? '') }}">
            </div>
            <div>
              <label>Contact Person</label>
              <input name="contact_person_name" value="{{ old('contact_person_name', $editClient->contact_person_name ?? '') }}">
            </div>
            <div>
              <label>Contact Email</label>
              <input name="contact_email" type="email" value="{{ old('contact_email', $editClient->contact_email ?? '') }}">
            </div>
            <div>
              <label>Contact Phone</label>
              <input name="contact_phone" value="{{ old('contact_phone', $editClient->contact_phone ?? '') }}">
            </div>
            <div>
              <label>Billing Address</label>
              <textarea name="billing_address">{{ old('billing_address', $editClient->billing_address ?? '') }}</textarea>
            </div>
            <div>
              <label>Status</label>
              <select name="is_active">
                <option value="1" @selected(old('is_active', $editClient->is_active ?? true))>Active</option>
                <option value="0" @selected(!old('is_active', $editClient->is_active ?? true))>Inactive</option>
              </select>
            </div>
          </div>
          <div style="margin-top:14px; display:flex; gap:8px;">
            <button class="btn" type="submit">{{ isset($editClient) ? 'Update Client' : 'Create Client' }}</button>
            @if(isset($editClient))
              <a class="btn secondary" href="{{ route('admin.clients.index') }}">Cancel</a>
            @endif
          </div>
        </form>
      </div>
    </div>
    <div class="col">
      <div class="card">
        <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:8px;">
          <div>
            <h2 style="margin:0;">Clients</h2>
            <p class="muted">Search/filter ready via browser find; status + projects visible.</p>
          </div>
          <span class="pill-tag">{{ $clients->count() }} total</span>
        </div>
        <div class="stacked">
          @foreach($clients as $client)
            <div style="padding:12px 0; border-bottom:1px solid var(--border); display:flex; justify-content:space-between; gap:16px;">
              <div>
                <div style="font-weight:700;">{{ $client->name }} <span class="muted">({{ $client->client_code }})</span></div>
                <div class="muted">{{ $client->industry ?? 'Industry N/A' }} @if($client->website) · {{ $client->website }} @endif</div>
                @if($client->contact_person_name || $client->contact_email)
                  <div class="muted">Contact: {{ $client->contact_person_name }} {{ $client->contact_email ? '(' . $client->contact_email . ')' : '' }}</div>
                @endif
              </div>
              <div style="display:flex; align-items:center; gap:8px; flex-shrink:0;">
                <span class="badge {{ $client->is_active ? 'green' : 'rose' }}">{{ $client->is_active ? 'Active' : 'Inactive' }}</span>
                <a class="btn secondary" href="{{ route('admin.clients.edit', $client->id) }}">Edit</a>
                <form method="POST" action="{{ route('admin.clients.destroy', $client->id) }}" onsubmit="return confirm('Delete client? Projects must be removed first.');">
                  @csrf
                  @method('DELETE')
                  <button class="btn secondary" type="submit">Delete</button>
                </form>
              </div>
            </div>
          @endforeach
        </div>
      </div>
    </div>
  </div>
@endsection
