@extends('layouts.dyno')
@section('title', 'Progress · Dyno')

@section('content')
    <h1 style="font-size:30px;font-weight:800;letter-spacing:-.02em;margin:6px 0 16px;">Progress</h1>

    {{-- ---------- Baseline tests ---------- --}}
    <div class="label" style="margin-bottom:8px;">Baseline tests</div>
    @foreach ($tests as $t)
        <div class="card">
            <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px;">
                <div>
                    <div style="font-weight:700;font-size:17px;">{{ $t['def']['label'] }}</div>
                    <div class="muted" style="font-size:13px;margin-top:5px;">
                        @if ($t['last'])
                            Last: {{ rtrim(rtrim(number_format($t['last']->value, 2), '0'), '.') }} {{ $t['last']->unit }}
                            · {{ $t['last']->tested_at->diffForHumans() }}
                        @else
                            Not tested yet
                        @endif
                    </div>
                </div>
                @if ($t['retest_due'])
                    <span class="pill" style="background:rgba(245,165,36,.15);color:#FBBF24;white-space:nowrap;">re-test due</span>
                @endif
            </div>

            @if ($t['unlocked'])
                <a href="{{ route('tests.run', $t['def']['key']) }}" class="btn btn--ghost btn--full" style="margin-top:12px;">
                    {{ $t['last'] ? 'Re-test' : 'Take test' }}
                </a>
            @else
                <div class="set-row" style="margin-top:12px;justify-content:center;">
                    🔒 Log {{ $t['sessions_needed'] }} more session{{ $t['sessions_needed'] === 1 ? '' : 's' }} to unlock
                </div>
            @endif
        </div>
    @endforeach

    {{-- ---------- Recent sessions ---------- --}}
    <div class="label" style="margin:22px 0 8px;">Recent sessions</div>
    @forelse ($recent as $log)
        <div class="card" style="display:flex;align-items:center;justify-content:space-between;gap:12px;padding:14px 16px;">
            <div>
                <div style="font-weight:600;">{{ $log->workout?->name ?? 'Workout' }}</div>
                <div class="muted" style="font-size:13px;margin-top:4px;">
                    {{ $log->completed_at->format('D, M j') }}
                    @if ($log->perceived_effort) · RPE {{ $log->perceived_effort }} @endif
                </div>
            </div>
            <span class="pill" style="background:rgba(34,197,94,.15);color:#86EFAC;">Done</span>
        </div>
    @empty
        <div class="card muted" style="text-align:center;">No completed sessions yet.</div>
    @endforelse

    <a href="{{ route('history') }}" class="btn btn--ghost btn--full" style="margin-top:8px;">Full history →</a>
@endsection
