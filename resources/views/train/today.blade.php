@extends('layouts.dyno')
@section('title', 'Today · Dyno')

@section('content')
    <div style="display:flex;align-items:baseline;justify-content:space-between;margin:6px 0 18px;">
        <h1 style="font-size:30px;font-weight:800;letter-spacing:-.02em;margin:0;">Train</h1>
        <span class="muted" style="font-weight:600;">{{ now()->format('D, M j') }}</span>
    </div>

    @if ($recent->isNotEmpty())
        <div class="label" style="margin-bottom:8px;">Recent</div>
        <div class="card" style="padding:8px 8px;">
            @foreach ($recent as $log)
                <div style="display:flex;align-items:center;justify-content:space-between;padding:10px 12px;">
                    <div style="font-weight:600;">{{ $log->workout?->name ?? 'Workout' }}</div>
                    <div class="muted" style="font-size:13px;">{{ $log->completed_at?->diffForHumans() }}</div>
                </div>
            @endforeach
        </div>
    @endif

    @forelse ($workouts as $focusLabel => $group)
        <div class="label" style="margin:20px 0 8px;">{{ $focusLabel }}</div>
        @foreach ($group as $workout)
            <a href="{{ route('run', $workout) }}" class="card" style="display:flex;align-items:center;justify-content:space-between;text-decoration:none;color:var(--text);gap:12px;">
                <div>
                    <div style="font-weight:700;font-size:18px;display:flex;align-items:center;gap:8px;">
                        <span class="accent-dot" style="background:{{ $workout->focus_area->accentHex() }};"></span>
                        {{ $workout->name }}
                    </div>
                    <div class="muted" style="font-size:13px;margin-top:6px;">
                        {{ $workout->workout_exercises_count }} exercises
                        @if ($workout->estimated_minutes) · ~{{ $workout->estimated_minutes }} min @endif
                        @if ($workout->level) · {{ $workout->level->label() }} @endif
                    </div>
                </div>
                <span class="btn btn--primary" style="min-height:44px;padding:10px 18px;">Start</span>
            </a>
        @endforeach
    @empty
        <div class="card muted" style="text-align:center;">No workouts published yet.</div>
    @endforelse
@endsection
