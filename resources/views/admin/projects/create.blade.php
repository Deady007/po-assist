@extends('layout')

@section('content')
  <div class="page-head">
    <div>
      <h1>Create project</h1>
      <p class="muted">Codes auto-generate; override if needed.</p>
    </div>
    <a class="btn secondary" href="{{ route('admin.projects.index') }}">Back</a>
  </div>

  <div class="card stacked">
    <form method="POST" action="{{ route('admin.projects.store') }}">
      @csrf
      <div class="grid two">
        <div>
          <label>Name</label>
          <input name="name" value="{{ old('name') }}" required>
        </div>
        <div>
          <label>Project code</label>
          <input name="project_code" value="{{ old('project_code') }}" placeholder="Auto if blank">
        </div>
      </div>
      <div>
        <label>Customer</label>
        <select name="client_id" required>
          <option value="">Select customer</option>
          @foreach($customers as $customer)
            <option value="{{ $customer['client_id'] }}" @selected(old('client_id') == $customer['client_id'])>
              {{ $customer['name'] }} ({{ $customer['code'] }})
            </option>
          @endforeach
        </select>
      </div>
      <div class="grid two">
        <div>
          <label>Owner</label>
          <select name="owner_user_id" required>
            <option value="">Select owner</option>
            @foreach($users as $user)
              <option value="{{ $user->id }}" @selected(old('owner_user_id') == $user->id)>{{ $user->name }}</option>
            @endforeach
          </select>
        </div>
        <div>
          <label>Status</label>
          <select name="status_id">
            <option value="">Default</option>
            @foreach($statuses as $status)
              <option value="{{ $status->id }}" @selected(old('status_id') == $status->id)>{{ $status->name }}</option>
            @endforeach
          </select>
        </div>
      </div>
      <div class="grid two">
        <div>
          <label>Start date</label>
          <input type="date" name="start_date" value="{{ old('start_date') }}">
        </div>
        <div>
          <label>Due date</label>
          <input type="date" name="due_date" value="{{ old('due_date') }}" required>
        </div>
      </div>
      <div class="grid two">
        <div>
          <label>Priority</label>
          <select name="priority">
            @foreach(['low','medium','high'] as $priority)
              <option value="{{ $priority }}" @selected(old('priority','medium') === $priority)>{{ ucfirst($priority) }}</option>
            @endforeach
          </select>
        </div>
        <div>
          <label>Active</label>
          <select name="is_active">
            <option value="1" @selected(old('is_active', '1') === '1')>Active</option>
            <option value="0" @selected(old('is_active') === '0')>Inactive</option>
          </select>
        </div>
      </div>
      <div>
        <label>Description</label>
        <textarea name="description">{{ old('description') }}</textarea>
      </div>
      <div style="margin-top:14px;">
        <button class="btn" type="submit">Create project</button>
      </div>
    </form>
  </div>
@endsection
