{{-- WHOOP-style dark design tokens (§9). Inlined so the runner layout is
     self-contained and doesn't depend on Breeze's Alpine-bearing app.js. --}}
<style>
    :root {
        --bg: #0A0A0B;
        --surface: #141416;
        --surface-raised: #1E1E21;
        --text: #F2F2F3;
        --text-muted: #8A8A90;
        --line: #26262A;
        /* focus-area accents — must match App\Enums\FocusArea::accentHex() */
        --grip: #F5A524;
        --core: #3B82F6;
        --back: #22C55E;
        --arms: #A855F7;
        --legs: #14B8A6;
        --push: #EF4444;
    }

    * { box-sizing: border-box; }

    html, body {
        margin: 0;
        background: var(--bg);
        color: var(--text);
        font-family: ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
        -webkit-font-smoothing: antialiased;
    }

    .dyno-wrap { max-width: 640px; margin: 0 auto; padding: 20px 16px 96px; }

    .card {
        background: var(--surface);
        border-radius: 20px;
        padding: 20px;
        margin-bottom: 14px;
    }
    .card--raised { background: var(--surface-raised); }

    .label {
        text-transform: uppercase;
        letter-spacing: 0.12em;
        font-size: 11px;
        font-weight: 600;
        color: var(--text-muted);
    }
    .metric { font-size: 44px; font-weight: 800; letter-spacing: -0.03em; line-height: 1; }
    .muted { color: var(--text-muted); }

    .accent-dot { width: 10px; height: 10px; border-radius: 50%; display: inline-block; }

    .btn {
        appearance: none; border: 0; cursor: pointer;
        background: var(--surface-raised); color: var(--text);
        border-radius: 14px; padding: 16px 20px; font-size: 16px; font-weight: 600;
        min-height: 56px; /* large touch target — chalky hands, mid-session */
        display: inline-flex; align-items: center; justify-content: center; gap: 10px;
        transition: transform .06s ease, background .15s ease;
    }
    .btn:active { transform: scale(.98); }
    .btn:focus-visible { outline: 3px solid var(--core); outline-offset: 2px; }
    .btn--full { width: 100%; }
    .btn--primary { background: var(--text); color: #0A0A0B; }
    .btn--ghost { background: transparent; box-shadow: inset 0 0 0 1px var(--line); }

    .set-row {
        display: flex; align-items: center; gap: 14px;
        padding: 14px 16px; border-radius: 14px; background: var(--surface-raised);
        margin-bottom: 8px; min-height: 60px;
    }
    .set-check {
        width: 34px; height: 34px; border-radius: 10px; flex: 0 0 auto;
        border: 2px solid var(--line); background: transparent; cursor: pointer;
        display: grid; place-items: center;
    }
    .set-check[aria-pressed="true"] { background: var(--back); border-color: var(--back); }
    .set-row.done .set-label { color: var(--text-muted); text-decoration: line-through; }

    /* circular progress arc */
    .arc { transform: rotate(-90deg); }
    .arc__track { stroke: var(--line); }
    .arc__fill { stroke-linecap: round; transition: stroke-dashoffset .3s ease; }

    .timer-big { font-variant-numeric: tabular-nums; font-size: 72px; font-weight: 800; letter-spacing: -0.04em; }

    .pill { font-size: 12px; font-weight: 700; padding: 4px 10px; border-radius: 999px; }

    @media (prefers-reduced-motion: reduce) {
        .btn, .arc__fill { transition: none; }
    }
</style>
