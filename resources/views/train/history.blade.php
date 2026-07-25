@extends('layouts.dyno')
@section('title', 'History · Dyno')

@section('content')
    <h1 style="font-size:30px;font-weight:800;letter-spacing:-.02em;margin:6px 0 18px;">History</h1>

    @forelse ($logs as $log)
        <div class="card" style="display:flex;align-items:center;justify-content:space-between;gap:12px;">
            <div>
                <div style="font-weight:700;font-size:17px;">{{ $log->workout?->name ?? 'Workout' }}</div>
                <div class="muted" style="font-size:13px;margin-top:5px;">
                    {{ $log->started_at->format('D, M j · g:ia') }}
                    · {{ $log->completed_sets_count }} sets
                    @if ($log->perceived_effort) · RPE {{ $log->perceived_effort }} @endif
                </div>
                @if ($log->notes)
                    <div class="muted" style="font-size:13px;margin-top:6px;font-style:italic;">“{{ $log->notes }}”</div>
                @endif
            </div>
            @if ($log->isComplete())
                <span class="pill" style="background:rgba(34,197,94,.15);color:var(--success-soft);">Done</span>
            @elseif ($log->workout_id)
                <a href="{{ route('run', $log->workout_id) }}" class="pill" style="background:rgba(245,165,36,.15);color:var(--warn-soft);text-decoration:none;">Resume</a>
            @else
                <span class="pill" style="background:rgba(138,138,144,.15);color:var(--text-muted);">Ended</span>
            @endif
        </div>
    @empty
        <div class="card muted" style="text-align:center;">No sessions logged yet.</div>
    @endforelse

    @if ($logs->hasPages())
        <div style="display:flex;gap:10px;margin-top:16px;">
            @if ($logs->previousPageUrl())
                <a href="{{ $logs->previousPageUrl() }}" class="btn btn--ghost btn--full">← Newer</a>
            @endif
            @if ($logs->nextPageUrl())
                <a href="{{ $logs->nextPageUrl() }}" class="btn btn--ghost btn--full">Older →</a>
            @endif
        </div>
    @endif
@endsection
