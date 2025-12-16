@extends('layout')

@section('content')
  <div class="page-head">
    <div>
      <h1>Edit contact</h1>
      <p class="muted">{{ $contact->name }} · {{ $contact->customer?->name }}</p>
    </div>
    <a class="btn secondary" href="{{ route('clients.contacts.index', ['customer_id' => $contact->customer_id]) }}">Back</a>
  </div>

  <div class="card stacked">
    <form method="POST" action="{{ route('clients.contacts.update', $contact->id) }}" class="stacked">
      @csrf
      @method('PATCH')
      <input type="hidden" name="redirect_to" value="{{ route('clients.contacts.index', ['customer_id' => $contact->customer_id]) }}">
      <div>
        <label>Customer</label>
        <select name="customer_id" disabled>
          @foreach($customers as $customer)
            <option value="{{ $customer->id }}" @selected($customer->id === $contact->customer_id)>{{ $customer->name }}</option>
          @endforeach
        </select>
      </div>
      <div class="grid two">
        <div>
          <label>Name</label>
          <input name="name" value="{{ old('name', $contact->name) }}" required>
        </div>
        <div>
          <label>Email</label>
          <input name="email" type="email" value="{{ old('email', $contact->email) }}">
        </div>
      </div>
      <div class="grid two">
        <div>
          <label>Phone</label>
          <input name="phone" value="{{ old('phone', $contact->phone) }}">
        </div>
        <div>
          <label>Designation</label>
          <input name="designation" value="{{ old('designation', $contact->designation) }}">
        </div>
      </div>
      <div>
        <label>Tags (comma separated)</label>
        <input name="tags" value="{{ old('tags', implode(', ', $contact->tags ?? [])) }}">
      </div>
      <div class="row" style="gap:10px; align-items:center;">
        <label style="margin:0;">Primary?</label>
        <select name="is_primary" style="max-width:140px;">
          <option value="0" @selected(!$contact->is_primary)>No</option>
          <option value="1" @selected($contact->is_primary)>Yes</option>
        </select>
      </div>
      <div>
        <button class="btn" type="submit">Save contact</button>
      </div>
    </form>
  </div>
@endsection
