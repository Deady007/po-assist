@extends('layout')

@section('content')
  <div class="card">
    <h2 style="margin-bottom:6px;">Edit Project</h2>
    <p class="muted">Update overview fields; manage team/modules inside the project detail page.</p>
    <form method="POST" action="{{ route('admin.projects.update', $project->id) }}">
      @csrf
      @method('PUT')
      <div class="grid two">
        <div class="stacked">
          <div>
            <label>Project Name</label>
            <input name="name" value="{{ old('name', $project->name) }}" required>
          </div>
          <div>
            <label>Project Code</label>
            <input name="project_code" value="{{ old('project_code', $project->project_code) }}" placeholder="Auto if blank">
          </div>
          <div>
            <label>Customer</label>
            <select name="client_id" required>
              <option value="">Select customer</option>
              @foreach($customers as $customer)
                <option value="{{ $customer['client_id'] }}" @selected(old('client_id', $project->client_id) == $customer['client_id'])>
                  {{ $customer['name'] }} ({{ $customer['code'] }})
                </option>
              @endforeach
            </select>
          </div>
          <div>
            <label>Description</label>
            <textarea name="description">{{ old('description', $project->description) }}</textarea>
          </div>
        </div>
        <div class="stacked">
          <div class="grid two">
            <div>
              <label>Status</label>
              <select name="status_id">
                <option value="">Default</option>
                @foreach($statuses as $status)
                  <option value="{{ $status->id }}" @selected(old('status_id', $project->status_id) == $status->id)>{{ $status->name }}</option>
                @endforeach
              </select>
            </div>
            <div>
              <label>Priority</label>
              <select name="priority">
                @foreach(['low','medium','high','critical'] as $priority)
                  <option value="{{ $priority }}" @selected(old('priority', $project->priority) === $priority)>{{ ucfirst($priority) }}</option>
                @endforeach
              </select>
            </div>
          </div>
          <div class="grid two">
            <div>
              <label>Start Date</label>
              <input type="date" name="start_date" value="{{ old('start_date', optional($project->start_date)->toDateString()) }}">
            </div>
            <div>
              <label>Due Date</label>
              <input type="date" name="due_date" value="{{ old('due_date', optional($project->due_date)->toDateString()) }}">
            </div>
          </div>
          <div>
            <label>Owner</label>
            <select name="owner_user_id">
              <option value="">Unassigned</option>
              @foreach($users as $user)
                <option value="{{ $user->id }}" @selected(old('owner_user_id', $project->owner_user_id) == $user->id)>{{ $user->name }}</option>
              @endforeach
            </select>
          </div>
          <div>
            <label>Status</label>
            <select name="is_active">
              <option value="1" @selected(old('is_active', $project->is_active))>Active</option>
              <option value="0" @selected(!old('is_active', $project->is_active))>Inactive</option>
            </select>
          </div>
        </div>
      </div>
      <div style="margin-top:14px; display:flex; gap:8px;">
        <button class="btn" type="submit">Save</button>
        <a class="btn secondary" href="{{ route('admin.projects.show', $project->id) }}">Back to detail</a>
      </div>
    </form>
  </div>
@endsection
