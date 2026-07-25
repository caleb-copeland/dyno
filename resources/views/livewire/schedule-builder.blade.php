<div class="dyno-wrap">
    <h1 style="font-size:30px;font-weight:800;letter-spacing:-.02em;margin:6px 0 16px;">Schedule</h1>

    {{-- ---------- Inputs ---------- --}}
    <div class="card" x-data="{ open: {{ $generated ? 'false' : 'true' }} }">
        <button type="button" class="btn btn--ghost btn--full" x-on:click="open = !open">
            <span x-text="open ? 'Hide setup' : 'Edit setup'"></span>
        </button>

        <div x-show="open" x-cloak style="margin-top:14px;">
            <div class="label" style="margin-bottom:8px;">Sessions per week</div>
            @foreach ($focusOptions as $value => $label)
                <div style="display:flex;align-items:center;justify-content:space-between;padding:6px 0;">
                    <span style="font-weight:600;">{{ $label }}</span>
                    <div style="display:flex;align-items:center;gap:10px;">
                        <button type="button" class="btn btn--ghost" style="min-width:40px;min-height:40px;padding:6px;" wire:click="$set('frequencies.{{ $value }}', {{ max(0, (int) ($frequencies[$value] ?? 0) - 1) }})">−</button>
                        <span style="min-width:20px;text-align:center;font-weight:700;">{{ $frequencies[$value] ?? 0 }}</span>
                        <button type="button" class="btn btn--ghost" style="min-width:40px;min-height:40px;padding:6px;" wire:click="$set('frequencies.{{ $value }}', {{ (int) ($frequencies[$value] ?? 0) + 1 }})">+</button>
                    </div>
                </div>
            @endforeach

            <div class="label" style="margin:16px 0 8px;">Training days</div>
            <div style="display:flex;gap:6px;flex-wrap:wrap;">
                @foreach ($dayLabels as $day => $short)
                    <button type="button" wire:click="toggleTrainingDay({{ $day }})"
                            class="btn btn--ghost" style="min-width:46px;min-height:44px;padding:8px;{{ in_array($day, $trainingDays, true) ? 'background:var(--accent);color:var(--bg)' : '' }}">{{ $short }}</button>
                @endforeach
            </div>

            <div class="label" style="margin:16px 0 8px;">Climbing days <span class="muted" style="text-transform:none;letter-spacing:0;">(maximal finger load — the scheduler works around these)</span></div>
            <div style="display:flex;gap:6px;flex-wrap:wrap;">
                @foreach ($dayLabels as $day => $short)
                    <button type="button" wire:click="toggleClimbingDay({{ $day }})"
                            class="btn btn--ghost" style="min-width:46px;min-height:44px;padding:8px;{{ in_array($day, $climbingDays, true) ? 'background:var(--push);color:#fff' : '' }}">{{ $short }}</button>
                @endforeach
            </div>

            <button type="button" class="btn btn--primary btn--full" style="margin-top:18px;" wire:click="generate" wire:loading.attr="disabled">
                {{ $generated ? 'Regenerate week' : 'Generate week' }}
            </button>
        </div>
    </div>

    @if ($generated)
        {{-- ---------- Warnings ---------- --}}
        @foreach ($issues['hard'] as $msg)
            <div class="card" style="background:rgba(239,68,68,.12);border:1px solid rgba(239,68,68,.4);color:var(--danger-soft);font-size:14px;">⛔ {{ $msg }}</div>
        @endforeach
        @foreach ($issues['soft'] as $msg)
            <div class="card" style="background:rgba(245,165,36,.10);color:var(--warn-soft);font-size:14px;padding:12px 16px;">⚠ {{ $msg }}</div>
        @endforeach

        @if (empty($current))
            <div class="card muted" style="text-align:center;">No valid arrangement for these inputs — the finger-load rules can't be satisfied. Try fewer grip sessions or fewer climbing days.</div>
        @endif

        {{-- ---------- Week grid ---------- --}}
        <div style="margin-top:6px;">
            @foreach ($week as $dayNum => $day)
                <div class="card" style="padding:12px 14px;margin-bottom:8px;{{ $day['isClimbing'] ? 'border:1px solid rgba(239,68,68,.35);' : '' }}">
                    <div style="display:flex;align-items:center;justify-content:space-between;">
                        <span class="label">{{ $day['short'] }}</span>
                        @if ($day['isClimbing'])
                            <span class="pill" style="background:rgba(239,68,68,.15);color:var(--danger-soft);">🧗 climbing</span>
                        @endif
                    </div>

                    @foreach ($day['sessions'] as $s)
                        <div class="set-row" style="margin-top:8px;">
                            <span class="accent-dot" style="background:{{ $s['accent'] }};"></span>
                            <span style="font-weight:600;">{{ $s['label'] }}</span>
                            <div style="margin-left:auto;display:flex;gap:6px;">
                                <button type="button" class="btn btn--ghost" style="min-width:38px;min-height:38px;padding:6px;" wire:click="moveSession({{ $s['index'] }}, -1)" aria-label="Move earlier">◀</button>
                                <button type="button" class="btn btn--ghost" style="min-width:38px;min-height:38px;padding:6px;" wire:click="moveSession({{ $s['index'] }}, 1)" aria-label="Move later">▶</button>
                                <button type="button" class="btn btn--ghost" style="min-width:38px;min-height:38px;padding:6px;" wire:click="removeSession({{ $s['index'] }})" aria-label="Remove">✕</button>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endforeach
        </div>

        {{-- ---------- Actions ---------- --}}
        <div style="display:flex;gap:10px;margin-top:6px;">
            <button type="button" class="btn btn--ghost btn--full" wire:click="shuffle">⇄ Shuffle</button>
            <button type="button" class="btn btn--primary btn--full" wire:click="save" @if(!empty($issues['hard']) || empty($current)) disabled style="opacity:.5;" @endif>Save schedule</button>
        </div>

        @if ($saved)
            <div class="card" style="background:rgba(34,197,94,.12);color:var(--success-soft);text-align:center;margin-top:10px;">✓ Schedule saved — see it on <a href="{{ route('dashboard') }}" style="color:var(--success-soft);text-decoration:underline;">Today</a>.</div>
        @endif
    @endif
</div>
