@extends('layout')

@section('content')
  <div class="grid two">
    <div class="card">
      <h2 style="margin:0 0 6px 0;">Sequence Engine</h2>
      <p class="muted">Reusable, transaction-safe generator. Supports prefixes, padding, and reset policies.</p>
      <div class="stacked">
        @foreach($sequences as $sequence)
          <form method="POST" action="{{ route('admin.config.sequences.update', $sequence->id) }}" style="border:1px solid var(--border); border-radius:10px; padding:10px;">
            @csrf
            @method('PUT')
            <div class="grid two" style="gap:10px;">
              <div>
                <label>Model</label>
                <input name="model_name" value="{{ $sequence->model_name }}" required>
              </div>
              <div>
                <label>Prefix</label>
                <input name="prefix" value="{{ $sequence->prefix }}">
              </div>
            </div>
            <div class="grid two" style="gap:10px; margin-top:6px;">
              <div>
                <label>Padding</label>
                <input type="number" name="padding" min="1" max="10" value="{{ $sequence->padding }}" required>
              </div>
              <div>
                <label>Start from</label>
                <input type="number" name="start_from" min="1" value="{{ $sequence->start_from }}" required>
              </div>
            </div>
            <div class="grid two" style="gap:10px; margin-top:6px;">
              <div>
                <label>Reset policy</label>
                <select name="reset_policy">
                  @foreach(['none','yearly','monthly'] as $policy)
                    <option value="{{ $policy }}" @selected($sequence->reset_policy === $policy)>{{ ucfirst($policy) }}</option>
                  @endforeach
                </select>
              </div>
              <div>
                <label>Format template</label>
                <input name="format_template" value="{{ $sequence->format_template }}">
                <span class="helper">Use {prefix}, {year}, {month}, {seq}</span>
              </div>
            </div>
            <div style="margin-top:8px;">
              <button class="btn secondary" type="submit">Update</button>
            </div>
          </form>
        @endforeach
      </div>
    </div>
    <div class="card">
      <h2 style="margin:0 0 6px 0;">Add Sequence</h2>
      <p class="muted">Example: <code>PRJ-{year}-{seq}</code> with padding 5.</p>
      <form method="POST" action="{{ route('admin.config.sequences.store') }}">
        @csrf
        <div class="stacked">
          <div>
            <label>Model</label>
            <input name="model_name" required placeholder="client, project, task, ...">
          </div>
          <div>
            <label>Prefix</label>
            <input name="prefix" placeholder="PRJ-">
          </div>
          <div class="grid two">
            <div>
              <label>Padding</label>
              <input type="number" name="padding" min="1" max="10" value="4" required>
            </div>
            <div>
              <label>Start from</label>
              <input type="number" name="start_from" min="1" value="1" required>
            </div>
          </div>
          <div>
            <label>Reset policy</label>
            <select name="reset_policy">
              <option value="none">None</option>
              <option value="yearly">Yearly</option>
              <option value="monthly">Monthly</option>
            </select>
          </div>
          <div>
            <label>Format template</label>
            <input name="format_template" placeholder="{prefix}{year}-{seq}">
            <span class="helper">Placeholders: {prefix} {year} {month} {seq}</span>
          </div>
        </div>
        <div style="margin-top:10px;">
          <button class="btn" type="submit">Create</button>
        </div>
      </form>
    </div>
  </div>
@endsection
