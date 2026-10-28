@php
    $giftService = app(\App\Services\GiftCouponService::class);
    $giftSettings = $giftService->settings();
    $giftEnabled = $giftSettings['enabled'];
    $giftMode = $giftSettings['mode'];
    $giftPrizes = collect($giftService->prizes())->pluck('label')->values()->all();
    $giftTitle = \App\Models\StoreSetting::get('gift_popup_title', 'Scratch & Win');
    $giftSubtitle = \App\Models\StoreSetting::get('gift_popup_subtitle', 'Scratch the card or spin the wheel to reveal your free gift.');
    $giftButton = \App\Models\StoreSetting::get('gift_popup_button', 'Claim my gift');
    $giftDelay = max(1, (int) \App\Models\StoreSetting::get('gift_popup_delay', 4));
    $giftTerms = \App\Models\StoreSetting::get('gift_popup_terms', '');
    $giftImage = \App\Support\Brand::giftImageUrl();
@endphp

@if ($giftEnabled && count($giftPrizes) > 0)
    <div id="gift-popup"
         class="fixed inset-0 z-[100] hidden items-center justify-center bg-charcoal/70 px-4 py-6 backdrop-blur-sm"
         role="dialog"
         aria-modal="true"
         aria-labelledby="gift-popup-title">
        <div id="gift-popup-card"
             class="relative w-full max-w-md overflow-hidden rounded-2xl bg-ivory shadow-2xl ring-1 ring-champagne/30 scale-95 opacity-0 transition-all duration-300">
            <button type="button"
                    id="gift-popup-close"
                    class="absolute right-3 top-3 z-20 flex h-9 w-9 items-center justify-center rounded-full bg-charcoal/50 text-ivory hover:bg-charcoal/70 transition-colors"
                    aria-label="Close gift popup">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>

            @if ($giftImage)
                <div class="relative h-36 md:h-44 overflow-hidden bg-charcoal">
                    <img src="{{ $giftImage }}"
                         alt="{{ $giftTitle }}"
                         class="h-full w-full object-cover"
                         loading="lazy">
                    <div class="absolute inset-0 bg-gradient-to-t from-charcoal/70 to-transparent"></div>
                </div>
            @else
                <div class="relative h-24 overflow-hidden bg-gradient-to-r from-charcoal via-charcoal-soft to-charcoal">
                    <div class="absolute inset-0 opacity-30" style="background-image: radial-gradient(circle at 20% 50%, #C8A968 0, transparent 45%), radial-gradient(circle at 80% 30%, #C8A968 0, transparent 40%);"></div>
                </div>
            @endif

            <div class="p-5 md:p-6 text-center">
                <p class="text-champagne-deep text-[10px] font-semibold uppercase tracking-[0.3em] mb-1">A little gift from us</p>
                <h2 id="gift-popup-title" class="font-serif text-2xl md:text-3xl">{{ $giftTitle }}</h2>
                <p class="mt-1 text-sm text-muted">{{ $giftSubtitle }}</p>

                @if ($giftMode === 'both')
                    <div id="gift-mode-tabs" class="mx-auto mt-4 inline-flex rounded-full bg-charcoal/5 p-1">
                        <button type="button" data-gift-mode="scratch" class="gift-tab rounded-full px-4 py-1.5 text-xs font-semibold transition-colors">Scratch</button>
                        <button type="button" data-gift-mode="spin" class="gift-tab rounded-full px-4 py-1.5 text-xs font-semibold transition-colors">Spin</button>
                    </div>
                @endif

                <div id="gift-play-area" class="mt-5">
                    {{-- Scratch card --}}
                    <div id="gift-scratch-panel" class="{{ $giftMode === 'spin' ? 'hidden' : '' }}">
                        <div id="gift-scratch-wrap"
                             class="relative mx-auto overflow-hidden rounded-xl border border-champagne/40 bg-gradient-to-br from-ivory-alt to-champagne/10"
                             style="aspect-ratio: 16 / 9;">
                            <div class="absolute inset-0 flex flex-col items-center justify-center px-6 text-center">
                                <span class="text-[10px] font-semibold uppercase tracking-[0.25em] text-champagne-deep">You won</span>
                                <p id="gift-prize-label" class="mt-1 font-serif text-xl md:text-2xl leading-tight">{{ $giftSettings['fallback_prize'] }}</p>
                                <p class="mt-2 text-xs text-muted">Use code</p>
                                <p id="gift-coupon" class="mt-1 rounded-md border border-dashed border-champagne-dark/50 bg-ivory px-3 py-1 font-mono text-sm font-bold tracking-widest text-charcoal">{{ $giftSettings['unique'] ? '••••••' : ($giftSettings['static_coupon'] ?: '••••••') }}</p>
                            </div>

                            <canvas id="gift-scratch"
                                    class="absolute inset-0 h-full w-full touch-none cursor-crosshair"
                                    aria-label="Scratch to reveal your gift"></canvas>
                        </div>
                        <p id="gift-hint" class="mt-3 text-xs text-muted">Scratch the gold card with your finger or mouse.</p>
                    </div>

                    {{-- Spin wheel --}}
                    <div id="gift-spin-panel" class="{{ in_array($giftMode, ['scratch', 'both'], true) ? 'hidden' : '' }}">
                        <div class="relative mx-auto w-56 sm:w-64">
                            <div class="pointer-events-none absolute left-1/2 top-0 z-10 -translate-x-1/2 -translate-y-1">
                                <div class="h-0 w-0 border-x-[9px] border-t-[16px] border-x-transparent border-t-charcoal drop-shadow"></div>
                            </div>
                            <div class="relative aspect-square overflow-hidden rounded-full border-4 border-champagne/60 bg-ivory shadow-inner">
                                <canvas id="gift-wheel" class="absolute inset-0 h-full w-full"></canvas>
                                <div class="pointer-events-none absolute left-1/2 top-1/2 flex h-12 w-12 -translate-x-1/2 -translate-y-1/2 items-center justify-center rounded-full border-2 border-champagne/60 bg-ivory text-[9px] font-bold uppercase tracking-widest text-champagne-deep">
                                    Spin
                                </div>
                            </div>
                        </div>
                        <button type="button" id="gift-spin-button" class="btn-gold mt-5 w-full justify-center">Spin the wheel</button>
                        <p id="gift-spin-hint" class="mt-3 text-xs text-muted">Spin once to reveal your free gift.</p>
                    </div>
                </div>

                <div id="gift-result" class="mt-5 hidden">
                    <p class="text-[10px] font-semibold uppercase tracking-[0.3em] text-champagne-deep">You won</p>
                    <p id="gift-result-prize" class="mt-1 font-serif text-2xl leading-tight"></p>
                    <p id="gift-result-discount" class="mt-1 text-xs text-muted"></p>

                    <div id="gift-code-box" class="mt-3 rounded-lg border border-dashed border-champagne-dark/50 bg-champagne/5 px-4 py-3">
                        <p class="text-[10px] uppercase tracking-[0.25em] text-muted">Your coupon code</p>
                        <p id="gift-result-code" class="mt-1 font-mono text-lg font-bold tracking-widest text-charcoal"></p>
                        <p id="gift-result-expiry" class="mt-1 text-[11px] text-muted"></p>
                    </div>

                    <a href="{{ route('shop.index') }}"
                       id="gift-claim"
                       class="btn-gold mt-4 w-full justify-center"
                       data-coupon="">
                        {{ $giftButton ?: 'Claim my gift' }}
                    </a>
                    <p id="gift-copied" class="mt-2 hidden text-xs font-medium text-emerald-700">Coupon copied — use it at checkout.</p>
                </div>

                <p id="gift-error" class="mt-3 hidden text-xs text-danger"></p>

                <button type="button" id="gift-popup-skip" class="mt-3 text-xs text-muted underline underline-offset-2 hover:text-charcoal">
                    No thanks, continue shopping
                </button>

                @if ($giftTerms)
                    <p class="mt-3 text-[10px] leading-relaxed text-muted/80">{{ $giftTerms }}</p>
                @endif
            </div>
        </div>
    </div>

    <script>
        (function () {
            const CONFIG = {
                claimUrl: @json(route('gift.claim')),
                eventUrl: @json(route('gift.event')),
                csrf: document.querySelector('meta[name="csrf-token"]')?.content || '',
                mode: @json($giftMode),
                prizes: @json($giftPrizes),
                fallbackCoupon: @json($giftSettings['static_coupon']),
                fallbackPrize: @json($giftSettings['fallback_prize']),
                unique: @json($giftSettings['unique']),
            };

            const STORAGE_KEY = 'haarsingharr_gift_popup_seen';
            const popup = document.getElementById('gift-popup');
            const card = document.getElementById('gift-popup-card');
            const scratchPanel = document.getElementById('gift-scratch-panel');
            const spinPanel = document.getElementById('gift-spin-panel');
            const scratchWrap = document.getElementById('gift-scratch-wrap');
            const scratchCanvas = document.getElementById('gift-scratch');
            const wheelCanvas = document.getElementById('gift-wheel');
            const spinButton = document.getElementById('gift-spin-button');
            const hint = document.getElementById('gift-hint');
            const result = document.getElementById('gift-result');
            const resultPrize = document.getElementById('gift-result-prize');
            const resultDiscount = document.getElementById('gift-result-discount');
            const resultCode = document.getElementById('gift-result-code');
            const resultExpiry = document.getElementById('gift-result-expiry');
            const codeBox = document.getElementById('gift-code-box');
            const claimButton = document.getElementById('gift-claim');
            const copied = document.getElementById('gift-copied');
            const errorBox = document.getElementById('gift-error');
            const playArea = document.getElementById('gift-play-area');
            const tabs = Array.from(document.querySelectorAll('[data-gift-mode]'));

            function storageGet(key) {
                try {
                    return window.sessionStorage.getItem(key);
                } catch (error) {
                    return null;
                }
            }

            function storageSet(key, value) {
                try {
                    window.sessionStorage.setItem(key, value);
                } catch (error) {
                    // Storage can be blocked in private browsing contexts.
                }
            }

            if (! popup || ! scratchCanvas || ! scratchWrap) return;
            if (storageGet(STORAGE_KEY) === '1') return;

            let activeMode = CONFIG.mode === 'both' ? 'scratch' : CONFIG.mode;
            let revealed = false;
            let drawing = false;
            let lastCheck = 0;
            let opened = false;
            let impressionTracked = false;
            let claimPromise = null;
            let scratchContext = null;
            let wheelDrawn = false;
            let spinning = false;

            function postJson(url, payload) {
                return fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': CONFIG.csrf,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify(payload || {}),
                }).then(async (response) => {
                    if (! response.ok) {
                        throw new Error('Request failed with status ' + response.status);
                    }
                    return response.json();
                });
            }

            function ensureClaim() {
                if (! claimPromise) {
                    claimPromise = postJson(CONFIG.claimUrl, { mode: activeMode }).catch((error) => {
                        claimPromise = null;
                        throw error;
                    });
                }
                return claimPromise;
            }

            function showError(message) {
                errorBox.textContent = message;
                errorBox.classList.remove('hidden');
            }

            function showResult(data) {
                if (revealed) return;
                revealed = true;

                const prize = data?.prize || CONFIG.fallbackPrize;
                const code = data?.code || CONFIG.fallbackCoupon;

                resultPrize.textContent = prize;
                resultDiscount.textContent = data?.discount || '';

                if (code) {
                    resultCode.textContent = code;
                    claimButton.dataset.coupon = code;
                    codeBox.classList.remove('hidden');
                } else {
                    resultCode.textContent = '';
                    claimButton.dataset.coupon = '';
                    codeBox.classList.add('hidden');
                }

                if (data?.expires_at) {
                    const expires = new Date(data.expires_at);
                    resultExpiry.textContent = Number.isNaN(expires.getTime())
                        ? ''
                        : 'Valid until ' + expires.toLocaleDateString(undefined, { day: 'numeric', month: 'short', year: 'numeric' });
                } else {
                    resultExpiry.textContent = data?.already_claimed ? 'This gift was already claimed in this session.' : '';
                }

                playArea.classList.add('hidden');
                document.getElementById('gift-mode-tabs')?.classList.add('hidden');
                result.classList.remove('hidden');
                storageSet(STORAGE_KEY, '1');

                if (data?.already_claimed) {
                    showError('This gift was already claimed in this browser session. Your original code is shown above.');
                }
            }

            function showFallback(error) {
                showResult({
                    prize: CONFIG.fallbackPrize,
                    code: CONFIG.fallbackCoupon,
                    discount: null,
                    expires_at: null,
                    already_claimed: false,
                });

                if (error) {
                    showError('We could not reach the gift server, so we are showing the current offer instead.');
                }
            }

            function revealWithClaim() {
                if (revealed) return;
                hint.textContent = 'Revealing your gift…';
                ensureClaim().then(showResult).catch(showFallback);
            }

            // ------------------------------------------------------------------
            // Scratch card
            // ------------------------------------------------------------------
            function paintScratch() {
                const rect = scratchWrap.getBoundingClientRect();
                if (! rect.width || ! rect.height) return;

                const dpr = Math.min(window.devicePixelRatio || 1, 2);
                scratchCanvas.width = Math.max(1, Math.round(rect.width * dpr));
                scratchCanvas.height = Math.max(1, Math.round(rect.height * dpr));

                scratchContext = scratchCanvas.getContext('2d');
                if (! scratchContext) return;

                const gradient = scratchContext.createLinearGradient(0, 0, scratchCanvas.width, scratchCanvas.height);
                gradient.addColorStop(0, '#C8A968');
                gradient.addColorStop(0.5, '#B8935A');
                gradient.addColorStop(1, '#DCC08A');
                scratchContext.fillStyle = gradient;
                scratchContext.fillRect(0, 0, scratchCanvas.width, scratchCanvas.height);

                scratchContext.globalAlpha = 0.25;
                scratchContext.strokeStyle = '#FFF8E7';
                scratchContext.lineWidth = 2;
                for (let x = -scratchCanvas.height; x < scratchCanvas.width; x += 26) {
                    scratchContext.beginPath();
                    scratchContext.moveTo(x, 0);
                    scratchContext.lineTo(x + scratchCanvas.height, scratchCanvas.height);
                    scratchContext.stroke();
                }
                scratchContext.globalAlpha = 1;

                scratchContext.fillStyle = 'rgba(31, 27, 22, 0.65)';
                scratchContext.font = `600 ${Math.max(14, Math.round(scratchCanvas.width * 0.035))}px system-ui, sans-serif`;
                scratchContext.textAlign = 'center';
                scratchContext.textBaseline = 'middle';
                scratchContext.fillText('SCRATCH HERE', scratchCanvas.width / 2, scratchCanvas.height / 2 - 8);
                scratchContext.font = `400 ${Math.max(10, Math.round(scratchCanvas.width * 0.022))}px system-ui, sans-serif`;
                scratchContext.fillText('✦ reveal your free gift ✦', scratchCanvas.width / 2, scratchCanvas.height / 2 + 20);
            }

            function clearPercent() {
                if (! scratchContext) return 0;
                const pixels = scratchContext.getImageData(0, 0, scratchCanvas.width, scratchCanvas.height).data;
                let clear = 0;
                let total = 0;
                for (let i = 3; i < pixels.length; i += 64) {
                    total++;
                    if (pixels[i] === 0) clear++;
                }
                return total ? clear / total : 0;
            }

            function scratch(event) {
                if (! scratchContext || revealed) return;

                const rect = scratchCanvas.getBoundingClientRect();
                const scaleX = scratchCanvas.width / rect.width;
                const scaleY = scratchCanvas.height / rect.height;
                const x = (event.clientX - rect.left) * scaleX;
                const y = (event.clientY - rect.top) * scaleY;
                const radius = Math.max(18, scratchCanvas.width * 0.055);

                scratchContext.globalCompositeOperation = 'destination-out';
                scratchContext.beginPath();
                scratchContext.arc(x, y, radius, 0, Math.PI * 2);
                scratchContext.fill();

                const now = Date.now();
                if (now - lastCheck > 220) {
                    lastCheck = now;
                    if (clearPercent() > 0.42) {
                        scratchCanvas.style.transition = 'opacity 300ms ease';
                        scratchCanvas.style.opacity = '0';
                        revealWithClaim();
                    }
                }
            }

            scratchCanvas.addEventListener('pointerdown', (event) => {
                drawing = true;
                scratchCanvas.setPointerCapture?.(event.pointerId);
                scratch(event);
            });
            scratchCanvas.addEventListener('pointermove', (event) => {
                if (drawing) scratch(event);
            });
            ['pointerup', 'pointercancel', 'pointerleave'].forEach((name) => {
                scratchCanvas.addEventListener(name, () => { drawing = false; });
            });

            // ------------------------------------------------------------------
            // Spin wheel
            // ------------------------------------------------------------------
            function wheelSegments() {
                const labels = CONFIG.prizes.length >= 2
                    ? CONFIG.prizes
                    : [CONFIG.prizes[0] || 'Gift', 'Try again', CONFIG.prizes[0] || 'Gift', 'Try again'];
                return labels;
            }

            function drawWheel() {
                const rect = wheelCanvas.parentElement.getBoundingClientRect();
                if (! rect.width) return;

                const dpr = Math.min(window.devicePixelRatio || 1, 2);
                const size = rect.width;
                wheelCanvas.width = Math.round(size * dpr);
                wheelCanvas.height = Math.round(size * dpr);

                const ctx = wheelCanvas.getContext('2d');
                if (! ctx) return;
                ctx.scale(dpr, dpr);

                const segments = wheelSegments();
                const n = segments.length;
                const angle = (Math.PI * 2) / n;
                const radius = size / 2;
                const centre = radius;
                const colors = ['#C8A968', '#1F1B16', '#E8D5A8', '#B8935A', '#F5EEE1', '#8C6A3F'];

                for (let i = 0; i < n; i++) {
                    const start = i * angle - Math.PI / 2;
                    const end = start + angle;

                    ctx.beginPath();
                    ctx.moveTo(centre, centre);
                    ctx.arc(centre, centre, radius - 2, start, end);
                    ctx.closePath();
                    ctx.fillStyle = colors[i % colors.length];
                    ctx.fill();
                    ctx.strokeStyle = 'rgba(255, 248, 231, 0.55)';
                    ctx.lineWidth = 1;
                    ctx.stroke();

                    const mid = start + angle / 2;
                    ctx.save();
                    ctx.translate(centre, centre);
                    ctx.rotate(mid);
                    ctx.textBaseline = 'middle';
                    ctx.fillStyle = i % colors.length === 1 || i % colors.length === 3 ? '#FFF8E7' : '#1F1B16';
                    ctx.font = `600 ${Math.max(9, Math.round(size * 0.036))}px system-ui, sans-serif`;
                    const label = segments[i].length > 22 ? segments[i].slice(0, 20) + '…' : segments[i];
                    if (mid > Math.PI / 2 && mid < (3 * Math.PI) / 2) {
                        ctx.rotate(Math.PI);
                        ctx.textAlign = 'left';
                        ctx.fillText(label, -radius + 18, 0);
                    } else {
                        ctx.textAlign = 'right';
                        ctx.fillText(label, radius - 18, 0);
                    }
                    ctx.restore();
                }

                wheelDrawn = true;
            }

            function spinTo(index, count) {
                const segment = 360 / count;
                const target = 360 * 6 + (360 - (index * segment + segment / 2));
                wheelCanvas.style.transition = 'transform 3s cubic-bezier(0.16, 1, 0.3, 1)';
                wheelCanvas.style.transform = `rotate(${target}deg)`;
            }

            function spin() {
                if (spinning || revealed) return;
                spinning = true;
                spinButton.disabled = true;
                spinButton.classList.add('opacity-60', 'cursor-not-allowed');
                document.getElementById('gift-spin-hint').textContent = 'Spinning…';

                if (! wheelDrawn) drawWheel();

                const segments = wheelSegments();

                ensureClaim()
                    .then((data) => {
                        let index = segments.indexOf(data.prize);
                        if (index < 0) index = Math.floor(Math.random() * segments.length);

                        spinTo(index, segments.length);
                        window.setTimeout(() => showResult(data), 3100);
                    })
                    .catch((error) => {
                        spinTo(Math.floor(Math.random() * segments.length), segments.length);
                        window.setTimeout(() => showFallback(error), 3100);
                    });
            }

            spinButton?.addEventListener('click', spin);

            // ------------------------------------------------------------------
            // Mode tabs
            // ------------------------------------------------------------------
            function setMode(mode) {
                activeMode = mode;

                if (scratchPanel) scratchPanel.classList.toggle('hidden', mode !== 'scratch');
                if (spinPanel) spinPanel.classList.toggle('hidden', mode !== 'spin');

                tabs.forEach((tab) => {
                    const active = tab.dataset.giftMode === mode;
                    tab.classList.toggle('bg-ivory', active);
                    tab.classList.toggle('text-charcoal', active);
                    tab.classList.toggle('shadow-sm', active);
                    tab.classList.toggle('text-muted', ! active);
                });

                if (mode === 'scratch') {
                    requestAnimationFrame(paintScratch);
                } else if (mode === 'spin') {
                    if (! spinning && ! revealed) {
                        wheelCanvas.style.transition = 'none';
                        wheelCanvas.style.transform = 'rotate(0deg)';
                    }
                    requestAnimationFrame(drawWheel);
                }
            }

            tabs.forEach((tab) => {
                tab.addEventListener('click', () => setMode(tab.dataset.giftMode));
            });

            // ------------------------------------------------------------------
            // Popup lifecycle
            // ------------------------------------------------------------------
            function trackImpression() {
                if (impressionTracked) return;
                impressionTracked = true;
                postJson(CONFIG.eventUrl, { event: 'impression', mode: activeMode }).catch(() => {});
            }

            function open() {
                if (opened) return;
                opened = true;
                storageSet(STORAGE_KEY, '1');
                popup.classList.remove('hidden');
                popup.classList.add('flex');

                requestAnimationFrame(() => {
                    card.classList.remove('scale-95', 'opacity-0');
                    card.classList.add('scale-100', 'opacity-100');

                    if (activeMode === 'spin') {
                        drawWheel();
                    } else {
                        paintScratch();
                    }

                    if (CONFIG.mode === 'both') {
                        setMode(activeMode);
                    }

                    trackImpression();
                });

                document.body.style.overflow = 'hidden';
            }

            function close() {
                popup.classList.add('hidden');
                popup.classList.remove('flex');
                document.body.style.overflow = '';
            }

            document.getElementById('gift-popup-close')?.addEventListener('click', close);
            document.getElementById('gift-popup-skip')?.addEventListener('click', close);

            popup.addEventListener('click', (event) => {
                if (event.target === popup) close();
            });

            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape' && ! popup.classList.contains('hidden')) close();
            });

            claimButton?.addEventListener('click', async () => {
                const code = claimButton.dataset.coupon;
                if (! code) return;
                try {
                    await navigator.clipboard.writeText(code);
                    copied?.classList.remove('hidden');
                } catch (e) {
                    // Clipboard may be unavailable; the code is still visible.
                }
            });

            window.addEventListener('resize', () => {
                if (! opened || revealed) return;
                if (activeMode === 'scratch') paintScratch();
                if (activeMode === 'spin') { wheelDrawn = false; drawWheel(); }
            });

            setTimeout(open, {{ $giftDelay }} * 1000);
        })();
    </script>
@endif
