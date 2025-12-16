@extends('layout')

@section('content')
  <div class="grid two">
    <div class="card">
      <h2 style="margin:0 0 6px 0;">Project Statuses</h2>
      <p class="muted">Order by <code>order_no</code>; only one default is allowed.</p>
      <div class="stacked">
        @foreach($statuses as $status)
          <div style="border:1px solid var(--border); border-radius:10px; padding:10px;">
            <form method="POST" action="{{ route('admin.config.statuses.update', $status->id) }}">
              @csrf
              @method('PUT')
              <div class="grid two" style="gap:10px;">
                <div>
                  <label>Name</label>
                  <input name="name" value="{{ $status->name }}" required>
                </div>
                <div>
                  <label>Order</label>
                  <input name="order_no" type="number" min="1" value="{{ $status->order_no }}" required>
                </div>
              </div>
              <div class="grid two" style="gap:10px; margin-top:6px;">
                <div>
                  <label>Default?</label>
                  <select name="is_default">
                    <option value="0" @selected(!$status->is_default)>No</option>
                    <option value="1" @selected($status->is_default)>Yes</option>
                  </select>
                </div>
                <div>
                  <label>Active?</label>
                  <select name="is_active">
                    <option value="1" @selected($status->is_active)>Active</option>
                    <option value="0" @selected(!$status->is_active)>Inactive</option>
                  </select>
                </div>
              </div>
              <div style="margin-top:8px; display:flex; gap:8px; align-items:center;">
                <button class="btn secondary" type="submit">Update</button>
                <a class="btn ghost" href="#" onclick="event.preventDefault(); document.getElementById('delete-status-{{ $status->id }}').submit();">Delete</a>
              </div>
            </form>
            <form id="delete-status-{{ $status->id }}" method="POST" action="{{ route('admin.config.statuses.destroy', $status->id) }}" onsubmit="return confirm('Delete status?');">
              @csrf
              @method('DELETE')
            </form>
          </div>
        @endforeach
      </div>
    </div>
    <div class="card">
      <h2 style="margin:0 0 6px 0;">Add Status</h2>
      <p class="muted">Defaults deselect other statuses automatically.</p>
      <form method="POST" action="{{ route('admin.config.statuses.store') }}">
        @csrf
        <div class="stacked">
          <div>
            <label>Name</label>
            <input name="name" required>
          </div>
          <div>
            <label>Order</label>
            <input name="order_no" type="number" min="1" value="1" required>
          </div>
          <div>
            <label>Default?</label>
            <select name="is_default">
              <option value="0">No</option>
              <option value="1">Yes</option>
            </select>
          </div>
          <div>
            <label>Active?</label>
            <select name="is_active">
              <option value="1">Active</option>
              <option value="0">Inactive</option>
            </select>
          </div>
        </div>
        <div style="margin-top:10px;">
          <button class="btn" type="submit">Create</button>
        </div>
      </form>
    </div>
  </div>
@endsection
