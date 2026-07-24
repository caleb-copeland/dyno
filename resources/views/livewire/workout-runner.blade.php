<div x-data="runner()" x-init="acquireWake()" x-on:session-finished.window="releaseWake()" class="dyno-wrap">

    @php
        $total = $this->totalSets;
        $doneCount = $this->completedSets;
        $pct = $total > 0 ? $doneCount / $total : 0;
        $circ = 2 * M_PI * 52;
    @endphp

    @if ($finished)
        {{-- ---------- Completed summary ---------- --}}
        <div class="card" style="text-align:center;">
            <div class="label" style="margin-bottom:12px;">Session complete</div>
            <div class="metric" style="font-size:64px;">{{ $doneCount }}<span class="muted" style="font-size:28px;">/{{ $total }}</span></div>
            <div class="label" style="margin-top:8px;">sets logged</div>
            <div style="font-weight:700;font-size:20px;margin-top:18px;">{{ $workout->name }}</div>
            <a href="{{ route('history') }}" class="btn btn--primary btn--full" style="margin-top:22px;">Done</a>
            <a href="{{ route('dashboard') }}" class="btn btn--ghost btn--full" style="margin-top:10px;">Home</a>
        </div>
    @else
        {{-- ---------- Header ---------- --}}
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px;">
            <a href="{{ route('dashboard') }}" class="btn btn--ghost" style="min-height:44px;padding:10px 16px;" x-on:click="cancel()">✕</a>
            <div class="label">Session</div>
            <div style="width:44px;"></div>
        </div>

        <div class="card" style="display:flex;align-items:center;gap:20px;">
            <svg width="120" height="120" viewBox="0 0 120 120" class="arc" aria-hidden="true">
                <circle class="arc__track" cx="60" cy="60" r="52" fill="none" stroke-width="10"/>
                <circle class="arc__fill" cx="60" cy="60" r="52" fill="none" stroke-width="10"
                        stroke="{{ $workout->focus_area->accentHex() }}"
                        stroke-dasharray="{{ $circ }}"
                        stroke-dashoffset="{{ $circ * (1 - $pct) }}"/>
            </svg>
            <div>
                <div style="font-weight:800;font-size:22px;line-height:1.1;">{{ $workout->name }}</div>
                <div class="label" style="margin-top:8px;display:flex;align-items:center;gap:8px;">
                    <span class="accent-dot" style="background:{{ $workout->focus_area->accentHex() }};"></span>
                    {{ $workout->focus_area->label() }}
                </div>
                <div class="muted" style="margin-top:6px;font-weight:600;">{{ $doneCount }} / {{ $total }} sets</div>
            </div>
        </div>

        {{-- ---------- Exercises ---------- --}}
        @foreach ($items as $item)
            <div class="card">
                <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:12px;">
                    <div>
                        <div style="display:flex;align-items:center;gap:8px;">
                            <span class="accent-dot" style="background:{{ $item['accent'] }};"></span>
                            <span style="font-weight:700;font-size:18px;">{{ $item['name'] }}</span>
                        </div>
                        <div class="muted" style="font-size:13px;margin-top:6px;">{{ $item['summary'] }}</div>
                    </div>
                    @if ($item['finger'])
                        <span class="pill" style="background:rgba(239,68,68,.15);color:#FCA5A5;white-space:nowrap;">⚠ fingers</span>
                    @endif
                </div>

                @if ($item['instructions'])
                    <div class="muted" style="font-size:13px;margin-top:10px;line-height:1.5;">{{ $item['instructions'] }}</div>
                @endif

                @if ($item['type'] === 'interval' && $item['interval_work_s'])
                    <button type="button" class="btn btn--ghost btn--full" style="margin-top:14px;"
                            x-on:click="startInterval({{ $item['interval_work_s'] }}, {{ $item['interval_rest_s'] ?? 0 }}, {{ $item['interval_reps'] ?? 1 }}, @js($item['name']))">
                        ▶ Start protocol · {{ $item['interval_work_s'] }}s on / {{ $item['interval_rest_s'] ?? 0 }}s off × {{ $item['interval_reps'] ?? 1 }}
                    </button>
                @endif

                <div style="margin-top:14px;">
                    @for ($s = 1; $s <= $item['sets']; $s++)
                        @php $key = $item['we_id'].':'.$s; $isDone = $done[$key] ?? false; @endphp
                        <div class="set-row {{ $isDone ? 'done' : '' }}">
                            <button type="button" class="set-check" aria-pressed="{{ $isDone ? 'true' : 'false' }}"
                                    aria-label="Set {{ $s }} {{ $isDone ? 'done' : 'not done' }}"
                                    wire:click="toggleSet({{ $item['we_id'] }}, {{ $item['exercise_id'] ?? 'null' }}, {{ $s }})"
                                    x-on:click="{{ (! $isDone && $item['rest_s']) ? 'startRest('.$item['rest_s'].')' : '' }}">
                                <svg x-cloak width="18" height="18" viewBox="0 0 24 24" fill="none" style="{{ $isDone ? '' : 'display:none' }}">
                                    <path d="M5 13l4 4L19 7" stroke="#0A0A0B" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </button>
                            <span class="set-label" style="font-weight:600;">Set {{ $s }}</span>
                            @if ($item['rest_s'])
                                <button type="button" class="btn btn--ghost" style="margin-left:auto;min-height:40px;padding:8px 14px;font-size:14px;"
                                        x-on:click="startRest({{ $item['rest_s'] }})">Rest</button>
                            @endif
                        </div>
                    @endfor
                </div>
            </div>
        @endforeach

        {{-- ---------- Finish ---------- --}}
        <div class="card card--raised" x-data="{ open: false }">
            <button type="button" class="btn btn--primary btn--full" x-show="!open" x-on:click="open = true">Finish session</button>
            <div x-show="open" x-cloak>
                <div class="label" style="margin-bottom:10px;">Perceived effort (RPE)</div>
                <div style="display:flex;flex-wrap:wrap;gap:8px;">
                    @for ($r = 1; $r <= 10; $r++)
                        <button type="button" class="btn btn--ghost" style="min-width:44px;min-height:44px;padding:8px;{{ $perceivedEffort === $r ? 'background:var(--core);color:#fff' : '' }}"
                                wire:click="$set('perceivedEffort', {{ $r }})">{{ $r }}</button>
                    @endfor
                </div>
                <textarea wire:model="sessionNotes" rows="3" placeholder="Session notes (optional)"
                          style="width:100%;margin-top:14px;background:var(--bg);color:var(--text);border:1px solid var(--line);border-radius:12px;padding:12px;font-size:15px;"></textarea>
                <button type="button" class="btn btn--primary btn--full" style="margin-top:12px;" wire:click="completeSession">Complete session</button>
                <button type="button" class="btn btn--ghost btn--full" style="margin-top:8px;" x-on:click="open = false">Cancel</button>
            </div>
        </div>

        {{-- ---------- Timer overlay ---------- --}}
        <div x-show="mode !== 'idle'" x-cloak
             style="position:fixed;inset:0;background:rgba(10,10,11,.97);display:flex;flex-direction:column;align-items:center;justify-content:center;z-index:50;padding:24px;text-align:center;">
            <div class="label" x-text="label"></div>
            <div class="timer-big" x-text="remaining" style="margin:14px 0;"
                 :style="phase === 'work' ? 'color:var(--grip)' : ''"></div>
            <div class="muted" x-show="mode === 'interval'" x-text="'Rep ' + rep + ' / ' + totalReps"></div>
            <div class="muted" x-show="mode === 'interval'" x-text="exName" style="margin-top:4px;"></div>
            <button type="button" class="btn btn--ghost" style="margin-top:32px;" x-on:click="cancel()">Stop</button>
        </div>
    @endif
</div>
