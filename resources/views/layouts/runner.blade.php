<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#0A0A0B">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Session' }} · Dyno</title>
    @include('partials.pwa-head')
    @include('partials.dyno-styles')
    @livewireStyles
</head>
<body>
    {{ $slot }}

    {{-- Alpine component: client-side timers, Web Audio cues, Screen Wake Lock.
         Defined in the layout (plain Blade) so it isn't processed by Livewire's
         component compiler, and registered once via alpine:init. --}}
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('runner', (config = {}) => ({
                // ---- set state (single source of truth; server seeds it) ----
                done: config.done || {},
                total: config.total || 0,
                workoutId: config.workoutId || null,

                mode: 'idle',        // idle | rest | interval
                remaining: 0,
                label: '',
                phase: '',
                rep: 0,
                totalReps: 0,
                exName: '',
                _tick: null,
                _ctx: null,
                _wake: null,

                completedCount() {
                    return Object.values(this.done).filter(Boolean).length;
                },
                arcOffset(circ) {
                    const pct = this.total > 0 ? this.completedCount() / this.total : 0;
                    return circ * (1 - pct);
                },

                // ---- set toggle → optimistic UI + persist (online) / queue (offline) ----
                toggle(weId, exId, setNum, restS) {
                    const key = weId + ':' + setNum;
                    const next = !this.done[key];
                    this.done[key] = next;
                    if (next && restS) this.startRest(restS);
                    this._persist({ workout_id: this.workoutId, workout_exercise_id: weId, set_number: setNum, completed: next });
                },
                _persist(item) {
                    if (navigator.onLine) {
                        this._send(item).catch(() => this._enqueue(item));
                    } else {
                        this._enqueue(item);
                    }
                },
                _send(item) {
                    return fetch('/api/set-log', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content || '',
                        },
                        body: JSON.stringify(item),
                    }).then((r) => { if (!r.ok) throw new Error('sync failed'); return r; });
                },
                _queueKey(i) { return i.workout_id + ':' + i.workout_exercise_id + ':' + i.set_number; },
                _readQueue() { try { return JSON.parse(localStorage.getItem('dyno.setqueue') || '[]'); } catch (e) { return []; } },
                _enqueue(item) {
                    const map = {};
                    this._readQueue().forEach((i) => { map[this._queueKey(i)] = i; });
                    map[this._queueKey(item)] = item; // keep the latest state per set
                    localStorage.setItem('dyno.setqueue', JSON.stringify(Object.values(map)));
                },
                flushQueue() {
                    if (!navigator.onLine) return;
                    const q = this._readQueue();
                    if (!q.length) return;
                    localStorage.removeItem('dyno.setqueue');
                    q.reduce((p, item) => p.then(() => this._send(item).catch(() => this._enqueue(item))), Promise.resolve());
                },

                // ---- Web Audio cues: users cannot look at the phone mid-hang ----
                beep(freq = 880, dur = 0.15, type = 'sine') {
                    try {
                        this._ctx = this._ctx || new (window.AudioContext || window.webkitAudioContext)();
                        const o = this._ctx.createOscillator();
                        const g = this._ctx.createGain();
                        o.type = type; o.frequency.value = freq;
                        o.connect(g); g.connect(this._ctx.destination);
                        const t = this._ctx.currentTime;
                        g.gain.setValueAtTime(0.0001, t);
                        g.gain.exponentialRampToValueAtTime(0.4, t + 0.01);
                        g.gain.exponentialRampToValueAtTime(0.0001, t + dur);
                        o.start(t); o.stop(t + dur + 0.02);
                    } catch (e) { /* audio best-effort */ }
                },

                // ---- Screen Wake Lock: don't let the phone sleep mid-protocol ----
                async acquireWake() {
                    try {
                        if ('wakeLock' in navigator) {
                            this._wake = await navigator.wakeLock.request('screen');
                            document.addEventListener('visibilitychange', () => {
                                if (document.visibilityState === 'visible' && this.mode !== 'idle' && !this._wake) {
                                    this.acquireWake();
                                }
                            });
                        }
                    } catch (e) { /* wake lock best-effort */ }
                },
                async releaseWake() {
                    try { if (this._wake) { await this._wake.release(); this._wake = null; } } catch (e) {}
                },

                // ---- Rest countdown ----
                startRest(seconds) {
                    if (!seconds) return;
                    this._clearTick();
                    this.mode = 'rest';
                    this.phase = 'rest';
                    this.label = 'Rest';
                    this.remaining = seconds;
                    this.beep(660, 0.12);
                    this._tick = setInterval(() => {
                        this.remaining--;
                        if (this.remaining <= 0) { this._done(990, 0.3); }
                        else if (this.remaining <= 3) { this.beep(700, 0.08); }
                    }, 1000);
                },

                // ---- Interval timer (hangboard): distinct state machine ----
                startInterval(work, rest, reps, name) {
                    this._clearTick();
                    this.mode = 'interval';
                    this.exName = name;
                    this.totalReps = reps;
                    this.rep = 1;
                    this._phase('work', work, rest, reps);
                },
                _phase(phase, work, rest, reps) {
                    this._clearTick();
                    this.phase = phase;
                    this.label = phase === 'work' ? ('Hang ' + this.rep + ' / ' + reps) : 'Off';
                    this.remaining = phase === 'work' ? work : rest;
                    this.beep(phase === 'work' ? 880 : 440, 0.18);
                    this._tick = setInterval(() => {
                        this.remaining--;
                        if (this.remaining <= 0) {
                            if (phase === 'work' && rest > 0) { this._phase('rest', work, rest, reps); }
                            else { this._advance(work, rest, reps); }
                        } else if (this.remaining <= 3) {
                            this.beep(760, 0.06);
                        }
                    }, 1000);
                },
                _advance(work, rest, reps) {
                    if (this.rep >= reps) { this._done(990, 0.4); return; }
                    this.rep++;
                    this._phase('work', work, rest, reps);
                },

                // ---- shared ----
                _clearTick() { if (this._tick) { clearInterval(this._tick); this._tick = null; } },
                _done(freq, dur) { this._clearTick(); this.beep(freq, dur); this.mode = 'idle'; this.phase = ''; },
                cancel() { this._clearTick(); this.mode = 'idle'; this.phase = ''; },
            }));
        });
    </script>
    @livewireScripts
</body>
</html>
