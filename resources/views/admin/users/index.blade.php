@extends('layout')

@section('content')
  <div class="row" style="align-items:flex-start; gap:18px;">
    <div class="col" style="min-width:320px; max-width:420px;">
      <div class="card">
        <h2 style="margin-bottom:6px;">{{ isset($editUser) ? 'Edit User' : 'Create User' }}</h2>
        <p class="muted">Admins can create or deactivate accounts and assign roles.</p>
        <form method="POST" action="{{ isset($editUser) ? route('admin.users.update', $editUser->id) : route('admin.users.store') }}">
          @csrf
          @if(isset($editUser))
            @method('PUT')
          @endif
          <div class="stacked">
            <div>
              <label>Name</label>
              <input name="name" value="{{ old('name', $editUser->name ?? '') }}" required>
            </div>
            <div>
              <label>Email</label>
              <input name="email" type="email" value="{{ old('email', $editUser->email ?? '') }}" required>
            </div>
            <div>
              <label>Phone</label>
              <input name="phone" value="{{ old('phone', $editUser->phone ?? '') }}">
            </div>
            <div>
              <label>Role</label>
              <select name="role_id" required>
                <option value="">Select role</option>
                @foreach($roles as $role)
                  <option value="{{ $role->id }}" @selected(old('role_id', $editUser->role_id ?? '') == $role->id)>{{ $role->name }}</option>
                @endforeach
              </select>
            </div>
            <div>
              <label>Password @if(isset($editUser))<span class="muted">(leave blank to keep)</span>@endif</label>
              <input name="password" type="password" {{ isset($editUser) ? '' : 'required' }} placeholder="Min 8 chars, mixed case + number">
            </div>
            <div>
              <label>Status</label>
              <select name="is_active">
                <option value="1" @selected(old('is_active', $editUser->is_active ?? true))>Active</option>
                <option value="0" @selected(!old('is_active', $editUser->is_active ?? true))>Inactive</option>
              </select>
            </div>
          </div>
          <div style="margin-top:14px; display:flex; gap:8px;">
            <button class="btn" type="submit">{{ isset($editUser) ? 'Update User' : 'Create User' }}</button>
            @if(isset($editUser))
              <a class="btn secondary" href="{{ route('admin.users.index') }}">Cancel</a>
            @endif
          </div>
        </form>
      </div>
    </div>
    <div class="col">
      <div class="card">
        <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:8px;">
          <div>
            <h2 style="margin:0;">Users</h2>
            <p class="muted">Admin-only create/delete. Use edit to deactivate.</p>
          </div>
          <span class="pill-tag">{{ $users->count() }} total</span>
        </div>
        <div class="stacked">
          @foreach($users as $user)
            <div style="display:flex; align-items:center; justify-content:space-between; padding:10px 0; border-bottom:1px solid var(--border);">
              <div>
                <div style="font-weight:700;">{{ $user->name }}</div>
                <div class="muted">{{ $user->email }} @if($user->phone) · {{ $user->phone }} @endif</div>
                <div class="muted">Role: {{ $user->role?->name ?? 'Unassigned' }}</div>
              </div>
              <div style="display:flex; align-items:center; gap:10px;">
                <span class="badge {{ $user->is_active ? 'green' : 'rose' }}">{{ $user->is_active ? 'Active' : 'Inactive' }}</span>
                <a class="btn secondary" href="{{ route('admin.users.edit', $user->id) }}">Edit</a>
                <form method="POST" action="{{ route('admin.users.destroy', $user->id) }}" onsubmit="return confirm('Delete user?')">
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
