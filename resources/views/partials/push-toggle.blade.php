@if (config('webpush.enabled'))
    <div class="card" id="push-card" style="display:none;">
        <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;">
            <div>
                <div style="font-weight:700;">Session reminders</div>
                <div class="muted" style="font-size:13px;margin-top:4px;" id="push-status">Get a nudge on days you're scheduled to train.</div>
            </div>
            <button type="button" class="btn btn--ghost" id="push-btn" style="min-height:44px;">Enable</button>
        </div>
    </div>

    <script>
        (function () {
            const card = document.getElementById('push-card');
            const btn = document.getElementById('push-btn');
            const status = document.getElementById('push-status');
            if (!('serviceWorker' in navigator) || !('PushManager' in window)) return;
            card.style.display = '';

            const csrf = document.querySelector('meta[name=csrf-token]')?.content || '';

            function b64ToUint8(base64) {
                const pad = '='.repeat((4 - base64.length % 4) % 4);
                const s = (base64 + pad).replace(/-/g, '+').replace(/_/g, '/');
                const raw = atob(s);
                return Uint8Array.from([...raw].map((c) => c.charCodeAt(0)));
            }

            async function refresh() {
                const reg = await navigator.serviceWorker.ready;
                const sub = await reg.pushManager.getSubscription();
                btn.textContent = sub ? 'Disable' : 'Enable';
                status.textContent = sub ? 'Reminders are on for this device.' : 'Get a nudge on days you’re scheduled to train.';
            }

            async function enable() {
                const perm = await Notification.requestPermission();
                if (perm !== 'granted') { status.textContent = 'Notifications were blocked in your browser.'; return; }
                const key = (await (await fetch('/api/push/key')).json()).key;
                const reg = await navigator.serviceWorker.ready;
                const sub = await reg.pushManager.subscribe({ userVisibleOnly: true, applicationServerKey: b64ToUint8(key) });
                await fetch('/api/push/subscribe', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
                    body: JSON.stringify(sub.toJSON()),
                });
                refresh();
            }

            async function disable() {
                const reg = await navigator.serviceWorker.ready;
                const sub = await reg.pushManager.getSubscription();
                if (sub) {
                    await fetch('/api/push/subscribe', {
                        method: 'DELETE',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
                        body: JSON.stringify({ endpoint: sub.endpoint }),
                    });
                    await sub.unsubscribe();
                }
                refresh();
            }

            btn.addEventListener('click', () => (btn.textContent === 'Enable' ? enable() : disable()).catch(() => {}));
            refresh();
        })();
    </script>
@endif
