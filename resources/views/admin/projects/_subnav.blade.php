@php
  $role = $role ?? (auth()->user()?->role?->name ?? '');
  $items = [
    ['label' => 'Overview', 'route' => 'admin.projects.show'],
    ['label' => 'Developer Assign', 'route' => 'admin.projects.developer_assign'],
    ['label' => 'Requirements', 'route' => 'admin.projects.requirements_management'],
    ['label' => 'Kick-off Call', 'route' => 'admin.projects.kickoff_call'],
    ['label' => 'Data Management', 'route' => 'admin.projects.data_management'],
    ['label' => 'Testing Assign', 'route' => 'admin.projects.testing_assign'],
    ['label' => 'Review', 'route' => 'admin.projects.review'],
    ['label' => 'Tasks', 'route' => 'admin.projects.tasks'],
    ['label' => 'Emails', 'route' => 'admin.projects.emails'],
  ];
@endphp

<div class="subnav">
  <a class="pill ghost sm" href="{{ route('admin.projects.index') }}">All projects</a>

  @foreach($items as $item)
    @php $active = request()->routeIs($item['route']); @endphp
    <a class="pill {{ $active ? 'active' : 'ghost' }} sm"
       href="{{ route($item['route'], $project->id) }}"
       @if($active) aria-current="page" @endif>
      {{ $item['label'] }}
    </a>
  @endforeach

  @if(in_array($role, ['Admin','PM'], true))
    <a class="pill ghost sm" href="{{ route('admin.projects.edit', $project->id) }}">Edit</a>
  @endif
</div>

