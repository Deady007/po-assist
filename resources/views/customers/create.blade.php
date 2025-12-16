@extends('layout')

@section('content')
  <div class="page-head">
    <div>
      <h1>Create customer</h1>
      <p class="muted">Customer codes generate from the sequence engine if left blank.</p>
    </div>
    <a class="btn secondary" href="{{ route('clients.customers.index') }}">Back to list</a>
  </div>

  <div class="card stacked">
    <form method="POST" action="{{ route('clients.customers.store') }}">
      @csrf
      <div class="grid two">
        <div>
          <label>Name</label>
          <input name="name" value="{{ old('name') }}" required>
        </div>
        <div>
          <label>Customer code</label>
          <input name="customer_code" value="{{ old('customer_code') }}" placeholder="Auto-generated if empty">
        </div>
      </div>
      <div class="grid two">
        <div>
          <label>Industry</label>
          <input name="industry" value="{{ old('industry') }}">
        </div>
        <div>
          <label>Website</label>
          <input name="website" value="{{ old('website') }}" placeholder="https://">
        </div>
      </div>
      <div>
        <label>Billing address</label>
        <textarea name="billing_address">{{ old('billing_address') }}</textarea>
      </div>
      <div class="row" style="gap:10px; align-items:center;">
        <label style="margin:0;">Active?</label>
        <select name="is_active" style="max-width:140px;">
          <option value="1" @selected(old('is_active', '1') === '1')>Yes</option>
          <option value="0" @selected(old('is_active') === '0')>No</option>
        </select>
      </div>
      <div style="margin-top:14px;">
        <button class="btn" type="submit">Create customer</button>
      </div>
    </form>
  </div>
@endsection
