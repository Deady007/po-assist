@extends('layout')

@section('content')
  <h2>Product Update Email</h2>

  <form method="POST" action="{{ route('emails.product.generate') }}">
    @csrf

    <div class="row">
      <div class="col">
        <label>Project</label>
        <select name="project_id" required>
          <option value="">-- choose --</option>
          @foreach ($projects as $p)
            <option value="{{ $p->id }}" @if((string)$selectedProjectId === (string)$p->id) selected @endif>
              {{ $p->name }}
            </option>
          @endforeach
        </select>
      </div>

      <div class="col">
        <label>Tone</label>
        <select name="tone" required>
          <option value="formal" selected>Formal</option>
          <option value="executive">Executive</option>
          <option value="neutral">Neutral</option>
        </select>
      </div>
    </div>

    <label>Date</label>
    <input name="date" value="{{ date('Y-m-d') }}" required>

    <label>Completed (one per line)</label>
    <textarea name="completed" rows="5" required>- </textarea>

    <label>In Progress (one per line)</label>
    <textarea name="in_progress" rows="5" required>- </textarea>

    <label>Risks / Blockers</label>
    <textarea name="risks" rows="3" required>None</textarea>

    <label>Topics for Review Meeting</label>
    <textarea name="review_topics" rows="4" required>- </textarea>

    <button type="submit">Generate with Gemini</button>
  </form>
@endsection
