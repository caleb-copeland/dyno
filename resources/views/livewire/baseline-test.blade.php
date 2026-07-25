<div class="dyno-wrap">
    @if ($finished)
        <div class="card" style="text-align:center;">
            <div class="metric" style="font-size:56px;">{{ rtrim(rtrim(number_format($value, 2), '0'), '.') }}<span class="muted" style="font-size:24px;"> {{ $def['unit'] }}</span></div>
            <div class="label" style="margin-top:8px;">recorded — {{ $def['label'] }}</div>
            <a href="{{ route('progress') }}" class="btn btn--primary btn--full" style="margin-top:20px;">Back to Progress</a>
        </div>
    @else
        <div style="display:flex;align-items:center;justify-content:space-between;margin:6px 0 14px;">
            <a href="{{ route('progress') }}" class="btn btn--ghost" style="min-height:44px;padding:10px 16px;">✕</a>
            <div class="label">Baseline test</div>
            <div style="width:44px;"></div>
        </div>

        <div class="card">
            <div style="font-weight:800;font-size:22px;">{{ $def['label'] }}</div>
            <div class="muted" style="font-size:14px;margin-top:8px;line-height:1.5;">{{ $def['description'] }}</div>
        </div>

        <div class="card">
            <div class="label" style="margin-bottom:10px;">Warm-up — complete every step</div>
            @foreach ($def['warmup'] as $i => $step)
                <div class="set-row" style="align-items:flex-start;">
                    <button type="button" class="set-check" aria-pressed="{{ ($warmupDone[$i] ?? false) ? 'true' : 'false' }}"
                            wire:click="$toggle('warmupDone.{{ $i }}')" style="margin-top:2px;">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" style="{{ ($warmupDone[$i] ?? false) ? '' : 'display:none' }}">
                            <path d="M5 13l4 4L19 7" stroke="#0A0A0B" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </button>
                    <span style="font-size:14px;line-height:1.5;">{{ $step }}</span>
                </div>
            @endforeach
        </div>

        <div class="card">
            <div class="label" style="margin-bottom:10px;">Result</div>
            <div style="display:flex;align-items:center;gap:10px;">
                <input type="number" step="0.5" wire:model="value" placeholder="0"
                       @disabled(! $this->warmupComplete)
                       style="flex:1;background:var(--bg);color:var(--text);border:1px solid var(--line);border-radius:12px;padding:14px;font-size:20px;font-weight:700;{{ $this->warmupComplete ? '' : 'opacity:.5;' }}">
                <span class="muted" style="font-weight:700;">{{ $def['unit'] }}</span>
            </div>
            @error('value') <div style="color:var(--danger-soft);font-size:13px;margin-top:8px;">{{ $message }}</div> @enderror

            @unless ($this->warmupComplete)
                <div class="muted" style="font-size:13px;margin-top:10px;">Finish the warm-up before recording — an unwarmed max test is dangerous and meaningless.</div>
            @endunless

            <button type="button" class="btn btn--primary btn--full" style="margin-top:14px;"
                    wire:click="record" @disabled(! $this->warmupComplete)
                    @style(['opacity:.5' => ! $this->warmupComplete])>Record result</button>
        </div>
    @endif
</div>
