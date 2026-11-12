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

    .timer-big { font-variant-numeric: tabular-nums; font-size: 72px; font-weight: 800; letter-spacing: -0.04em; }

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
        .btn, .arc__fill { transition: none; }
    }
</style>
