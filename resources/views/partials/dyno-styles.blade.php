{{-- WHOOP-style dark design tokens (§9). Inlined so the runner layout is
     self-contained and doesn't depend on Breeze's Alpine-bearing app.js. --}}
<style>
    :root {
        --bg: #0A0A0B;
        /* WHOOP-style ambient gradient — kept dark so glass cards retain contrast */
        --bg-grad: linear-gradient(180deg, #14171F 0%, #0C0D11 52%, #0A0A0B 100%);
        --surface: #141416;
        --surface-raised: #1E1E21;
        /* glass fill + hairline for translucent cards over the gradient */
        --glass: rgba(28, 28, 32, 0.55);
        --glass-raised: rgba(40, 40, 46, 0.6);
        --glass-line: rgba(255, 255, 255, 0.06);
        --glass-sheen: linear-gradient(180deg, rgba(255, 255, 255, 0.06), rgba(255, 255, 255, 0) 45%);
        --text: #F2F2F3;
        --text-muted: #8A8A90;
        --line: #26262A;
        /* focus-area accents — must match App\Enums\FocusArea::accentHex() */
        --grip: #F5A524;
        --pullups: #22C55E;
        --core: #3B82F6;
        --legs: #14B8A6;
        /* app accent — outlines, focus, active states, primary CTA */
        --accent: var(--legs);
        /* semantic status aliases — one source for feedback colors */
        --danger: #EF4444;
        --success: var(--pullups);
        --warn: var(--grip);
        --danger-soft: #FCA5A5;
        --success-soft: #86EFAC;
        --warn-soft: #FBBF24;
    }

    * { box-sizing: border-box; }

    /* accent focus outline everywhere — replaces the UA blue, one source */
    :focus-visible { outline: 2px solid var(--accent); outline-offset: 2px; }

    /* no page scroll behind a popup: native <dialog> or the runner overlay (.is-locked) */
    html.is-locked, html:has(dialog[open]) { overflow: hidden; overscroll-behavior: none; }
    dialog::backdrop { background: rgba(0, 0, 0, .55); backdrop-filter: blur(4px); -webkit-backdrop-filter: blur(4px); }

    /* solid base behind the fixed gradient — prevents flash on overscroll */
    html { background: var(--bg); }
    html, body {
        margin: 0;
        min-height: 100vh;
        /* fixed so it stays put as content scrolls, WHOOP-style */
        background: var(--bg-grad) no-repeat fixed;
        color: var(--text);
        font-family: ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
        -webkit-font-smoothing: antialiased;
    }

    .dyno-wrap { max-width: 640px; margin: 0 auto; padding: 20px 16px 120px; }
    /* brand bar */
    .dyno-header { display: flex; align-items: center; padding: 2px 0 18px; }
    .dyno-brand { display: inline-flex; text-decoration: none; color: var(--ink); }
    .dyno-wordmark { height: 60px; width: auto; display: block; color: var(--ink); }

    .card {
        /* layered: top sheen over translucent fill; sheen paints above the
           blurred backdrop but always behind card content (no pseudo-element) */
        background: var(--glass-sheen), var(--glass);
        border: 1px solid var(--glass-line);
        border-radius: 20px;
        padding: 20px;
        margin-bottom: 14px;
        backdrop-filter: blur(20px) saturate(1.3);
        -webkit-backdrop-filter: blur(20px) saturate(1.3);
        box-shadow: 0 4px 24px rgba(0, 0, 0, 0.35);
    }
    .card--raised { background: var(--glass-sheen), var(--glass-raised); }
    /* graceful fallback: opaque surfaces where backdrop-filter is unsupported */
    @supports not ((backdrop-filter: blur(1px)) or (-webkit-backdrop-filter: blur(1px))) {
        .card { background: var(--surface); }
        .card--raised { background: var(--surface-raised); }
    }

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

    /* native chevron hugs the edge and can't be padded — draw our own */
    .dyno-select {
        appearance: none; -webkit-appearance: none; width: 100%; min-height: 44px;
        background: var(--bg) url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8' viewBox='0 0 12 8'%3E%3Cpath d='M1 1.5l5 5 5-5' fill='none' stroke='%238A8A90' stroke-width='1.6' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E") no-repeat right 14px center;
        color: var(--text); border: 1px solid var(--line); border-radius: 12px;
        padding: 10px 40px 10px 12px; font-size: 15px;
    }

    .btn {
        appearance: none; border: 0; cursor: pointer;
        background: var(--surface-raised); color: var(--text);
        border-radius: 14px; padding: 16px 20px; font-size: 16px; font-weight: 600;
        min-height: 56px; /* large touch target — chalky hands, mid-session */
        display: inline-flex; align-items: center; justify-content: center; gap: 10px;
        transition: transform .06s ease, background .15s ease;
    }
    .btn:active { transform: scale(.98); }
    .btn:focus-visible { outline: 3px solid var(--accent); outline-offset: 2px; }
    .btn--full { width: 100%; }
    .btn--primary { background: var(--text); color: var(--bg); }
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
    .set-check[aria-pressed="true"] { background: var(--success); border-color: var(--success); }
    .set-row.done .set-label { color: var(--text-muted); text-decoration: line-through; }

    /* circular progress arc */
    .arc { transform: rotate(-90deg); }
    .arc__track { stroke: var(--line); }
    .arc__fill { stroke-linecap: round; transition: stroke-dashoffset .3s ease; }


    /* flash toast — slides down from the top, glass pill (see components/auth-session-status) */
    .toast {
        position: fixed; z-index: 60; left: 16px; right: 16px; top: calc(16px + env(safe-area-inset-top));
        margin: 0 auto; width: fit-content; max-width: 480px;
        display: flex; align-items: center; gap: 10px;
        padding: 12px 18px 12px 12px; border-radius: 999px; cursor: pointer;
        background: rgba(30, 30, 33, .92); backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px);
        border: 1px solid var(--glass-line); box-shadow: 0 8px 30px rgba(0, 0, 0, .45);
        color: var(--text); font-size: 14px; font-weight: 600; line-height: 1.3;
        opacity: 0; transform: translateY(-12px);
        transition: opacity .25s ease, transform .25s ease;
    }
    .toast.is-shown { opacity: 1; transform: none; }
    .toast__icon {
        flex: none; width: 26px; height: 26px; border-radius: 50%;
        display: inline-flex; align-items: center; justify-content: center;
        background: color-mix(in srgb, var(--success) 18%, transparent); color: var(--success);
    }

    /* round glass icon button — runner back control */
    .icon-btn {
        width: 44px; height: 44px; border-radius: 50%; flex: none;
        display: inline-flex; align-items: center; justify-content: center;
        background: var(--glass); border: 1px solid var(--glass-line);
        color: var(--text); text-decoration: none;
        transition: transform .06s ease, background .15s ease;
    }
    .icon-btn:active { transform: scale(.94); background: var(--glass-raised); }

    /* full-screen countdown. --phase is the live colour: the workout's focus
       accent while working, neutral during rest. */
    .timer-overlay {
        --phase: var(--text);
        position: fixed; inset: 0; z-index: 50; overflow: hidden;
        display: flex; flex-direction: column; align-items: center; justify-content: center;
        gap: 28px; padding: 24px 24px calc(24px + env(safe-area-inset-bottom));
        background: var(--bg); text-align: center;
    }
    .timer-overlay__glow {
        position: absolute; inset: -20%; pointer-events: none;
        background: radial-gradient(circle at 50% 45%, color-mix(in srgb, var(--phase) 28%, transparent) 0%, transparent 55%);
        transition: background .4s ease;
    }
    .timer-overlay > :not(.timer-overlay__glow) { position: relative; }
    .timer-pill {
        font-size: 13px; font-weight: 800; letter-spacing: .14em; text-transform: uppercase;
        padding: 8px 16px; border-radius: 999px; color: var(--phase);
        background: color-mix(in srgb, var(--phase) 14%, transparent);
        border: 1px solid color-mix(in srgb, var(--phase) 35%, transparent);
    }
    .timer-ring { position: relative; width: min(78vw, 46vh, 360px); aspect-ratio: 1; }
    .timer-ring svg { width: 100%; height: 100%; transform: rotate(-90deg); overflow: visible; }
    .timer-ring__track { fill: none; stroke: rgba(255,255,255,.07); stroke-width: 10; }
    .timer-ring__fill {
        fill: none; stroke: var(--phase); stroke-width: 10; stroke-linecap: round;
        filter: drop-shadow(0 0 10px color-mix(in srgb, var(--phase) 70%, transparent));
        transition: stroke-dashoffset 1s linear, stroke .4s ease;
    }
    .timer-ring__center { position: absolute; inset: 0; display: flex; flex-direction: column; align-items: center; justify-content: center; }
    .timer-ring__num {
        font-variant-numeric: tabular-nums; font-weight: 800; letter-spacing: -0.05em; line-height: 1;
        font-size: min(30vw, 18vh, 140px); color: var(--text);
    }
    .timer-ring__unit { margin-top: 6px; font-size: 13px; font-weight: 700; letter-spacing: .14em; text-transform: uppercase; color: var(--text-muted); }
    .timer-ring.is-final .timer-ring__num { color: var(--phase); animation: timer-pulse 1s ease-out infinite; }
    @keyframes timer-pulse { 0% { transform: scale(1.12); } 60%, 100% { transform: scale(1); } }
    .timer-meta { display: flex; flex-direction: column; align-items: center; gap: 10px; }
    .timer-reps { display: flex; flex-wrap: wrap; justify-content: center; gap: 6px; max-width: 280px; }
    .timer-rep { width: 18px; height: 6px; border-radius: 3px; background: rgba(255,255,255,.12); transition: background .3s ease; }
    .timer-rep.is-done { background: color-mix(in srgb, var(--phase) 55%, transparent); }
    .timer-rep.is-current { background: var(--phase); box-shadow: 0 0 8px var(--phase); }
    .timer-ex { font-size: 15px; font-weight: 600; color: var(--text-muted); }
    .timer-stop {
        appearance: none; cursor: pointer; display: inline-flex; align-items: center; gap: 8px;
        min-height: 52px; padding: 12px 26px; border-radius: 999px;
        font-size: 15px; font-weight: 700; color: var(--text);
        background: var(--glass); border: 1px solid var(--glass-line); backdrop-filter: blur(12px);
    }
    .timer-stop:active { transform: scale(.97); }

    .pill { font-size: 12px; font-weight: 700; padding: 4px 10px; border-radius: 999px; }

    /* bottom tab bar — single source (was duplicated across layouts). Floats
       above the bottom edge, aligned to the content column. */
    /* box model pinned so the bar renders identically whether or not a page
       also loads Tailwind (Preflight flips box-sizing/line-height) — e.g. Profile. */
    .tabbar {
        box-sizing: border-box; line-height: 1.3;
        position: fixed; left: 0; right: 0; bottom: calc(16px + env(safe-area-inset-bottom));
        /* inset 12px per side from the content column so the bar reads as floating */
        margin: 0 auto; max-width: 584px; width: calc(100% - 56px);
        display: flex; justify-content: space-around;
        background: rgba(20,20,22,.92); backdrop-filter: blur(12px);
        border: 1px solid var(--line); border-radius: 20px; padding: 10px 8px;
        box-shadow: 0 8px 30px rgba(0,0,0,.45);
    }
    .tabbar *, .tabbar *::before, .tabbar *::after { box-sizing: border-box; }
    .tab { flex: 1; text-align: center; text-decoration: none; color: var(--text-muted);
           font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: .08em;
           line-height: 1.3; padding: 6px; border-radius: 12px; }
    .tab[aria-current="page"] { color: var(--text); }
    .tab-ico { display: block; margin: 0 auto 3px; }

    @media (prefers-reduced-motion: reduce) {
        .btn, .arc__fill, .icon-btn, .timer-ring__fill, .timer-overlay__glow, .toast { transition: none; }
        .timer-ring.is-final .timer-ring__num { animation: none; }
    }
</style>
