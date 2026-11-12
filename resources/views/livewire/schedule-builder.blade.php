<div>
    <div style="display:flex;align-items:baseline;justify-content:space-between;gap:12px;margin:6px 0 18px;">
        <h1 style="font-size:30px;font-weight:800;letter-spacing:-.02em;margin:0;">Schedule</h1>
        <div class="muted" style="font-size:13px;white-space:nowrap;">
            <span wire:loading style="color:var(--text-muted);">Saving…</span>
            <span wire:loading.remove style="color:{{ $saved ? 'var(--success-soft)' : 'var(--text-muted)' }};">{{ $saved ? '✓ Saved' : 'Auto-saves' }}</span>
        </div>
    </div>

    <p class="muted" style="font-size:14px;margin-bottom:14px;">Add workouts to any day — changes save automatically.</p>

    <div style="display:flex;flex-direction:column;gap:10px;">
        @foreach ($week as $dayNum => $day)
            <div class="card" style="padding:14px 16px;">
                <div style="display:flex;align-items:center;justify-content:space-between;">
                    <span style="font-weight:800;font-size:18px;">{{ $day['label'] }}</span>
                    <span class="muted" style="font-size:13px;">{{ count($day['items']) === 0 ? 'Rest' : count($day['items']).'×' }}</span>
                </div>

                @foreach ($day['items'] as $item)
                    <div style="display:flex;align-items:center;gap:10px;margin-top:10px;padding:10px 12px;border-radius:12px;background:var(--surface-raised);">
                        <span class="accent-dot" style="background:{{ $item['accent'] }};"></span>
                        <div style="flex:1;min-width:0;">
                            <div style="font-weight:600;">{{ $item['name'] }}</div>
                            <div class="muted" style="font-size:12px;">{{ $item['focus'] }}@if ($item['minutes']) · ~{{ $item['minutes'] }} min @endif</div>
                        </div>
                        <button type="button" class="btn btn--ghost" style="min-width:38px;min-height:38px;padding:6px;" wire:click="removeWorkout({{ $dayNum }}, {{ $item['index'] }})" aria-label="Remove {{ $item['name'] }}">✕</button>
                    </div>
                @endforeach

                <select aria-label="Add workout to {{ $day['label'] }}"
                        x-on:change="$wire.addWorkout({{ $dayNum }}, $event.target.value); $event.target.value = ''"
                        class="dyno-select" style="margin-top:12px;">
                    <option value="">+ Add workout…</option>
                    @foreach ($options as $focusLabel => $group)
                        <optgroup label="{{ $focusLabel }}">
                            @foreach ($group as $workout)
                                <option value="{{ $workout->id }}">{{ $workout->name }}</option>
                            @endforeach
                        </optgroup>
                    @endforeach
                </select>
            </div>
        @endforeach
    </div>
</div>
