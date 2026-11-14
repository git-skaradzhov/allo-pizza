<div
    wire:poll.5s="pollForNewOrders"
    x-data="{
        audio: null,
        audioUnlocked: false,
        unlockListenersRegistered: false,
        pollTimer: null,
        sessionExpired: false,
        watching: true,
        pingUrl: @js(route('admin.kitchen.pending-orders')),
        loginUrl: @js(url('/admin/user/login/login')),
        init() {
            if (this.unlockListenersRegistered) {
                return;
            }

            this.unlockListenersRegistered = true;

            const unlock = () => this.unlockSound();

            window.addEventListener('pointerdown', unlock, { once: true, passive: true });
            window.addEventListener('keydown', unlock, { once: true });

            this.startWatching();

            document.addEventListener('visibilitychange', () => {
                if (document.visibilityState === 'visible' && ! this.sessionExpired) {
                    this.pollRemote();
                    $wire.pollForNewOrders();
                }
            });

            if (window.Livewire) {
                Livewire.hook('request', ({ fail }) => {
                    fail(({ status }) => {
                        if ([401, 403, 419].includes(status)) {
                            this.markDisconnected();
                        }
                    });
                });
            }
        },
        startWatching() {
            this.pollRemote();

            if (this.pollTimer) {
                clearInterval(this.pollTimer);
            }

            this.pollTimer = setInterval(() => this.pollRemote(), 5000);
        },
        knownIds() {
            const ids = ($wire.queue || []).map(Number);
            const current = $wire.currentOrder;

            if (current && current.id) {
                ids.push(Number(current.id));
            }

            return ids;
        },
        async pollRemote() {
            if (this.sessionExpired) {
                return;
            }

            try {
                const response = await fetch(this.pingUrl, {
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                    cache: 'no-store',
                });

                if ([401, 403, 419].includes(response.status) || response.redirected) {
                    this.markDisconnected();
                    return;
                }

                if (! response.ok) {
                    return;
                }

                const data = await response.json();
                this.watching = true;

                const incoming = (data.orderIds || []).map(Number);
                const known = this.knownIds();

                if (incoming.some((id) => ! known.includes(id))) {
                    $wire.pollForNewOrders();
                }
            } catch (error) {
                // Keep retrying on network blips; only auth failures are fatal.
            }
        },
        markDisconnected() {
            if (this.sessionExpired) {
                return;
            }

            this.sessionExpired = true;
            this.watching = false;
            this.startSound();
        },
        unlockSound() {
            if (this.audioUnlocked) {
                return;
            }

            this.audioUnlocked = true;

            if (@js($currentOrder !== null) || this.sessionExpired) {
                this.startSound();
            }
        },
        getAudio() {
            if (this.audio === null) {
                this.audio = new Audio(@js(asset('sounds/new-order.mp3')));
                this.audio.loop = true;
            }

            return this.audio;
        },
        startSound() {
            const audio = this.getAudio();

            audio.play().catch(() => {
                $wire.set('soundBlocked', true);
            });
        },
        stopSound() {
            if (this.audio === null) {
                return;
            }

            this.audio.pause();
            this.audio.currentTime = 0;
        },
    }"
    x-on:new-order-sound.window="startSound()"
    x-on:new-order-sound-stop.window="stopSound()"
    wire:ignore.self
>
    <style>[x-cloak]{display:none!important}</style>
    @if ($soundBlocked)
        <div class="fixed bottom-4 right-4 z-[10000]">
            <button
                type="button"
                x-on:pointerdown="unlockSound()"
                wire:click="enableSound"
                class="rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white shadow-lg hover:bg-primary-500"
            >
                Включи звук
            </button>
        </div>
    @endif

    <div
        x-show="watching && ! sessionExpired"
        x-cloak
        class="pointer-events-none fixed bottom-4 left-4 z-[9998]"
    >
        <div class="inline-flex items-center gap-2 rounded-full bg-white/95 px-3 py-1.5 text-xs font-medium text-gray-700 shadow-md ring-1 ring-gray-950/10 dark:bg-gray-900/95 dark:text-gray-200 dark:ring-white/10">
            <span class="h-2 w-2 animate-pulse rounded-full bg-green-500"></span>
            Следим за поръчки
        </div>
    </div>

    <div
        x-show="sessionExpired"
        x-cloak
        class="fixed inset-0 z-[10001] flex items-center justify-center bg-red-950/80 p-4"
        role="alertdialog"
        aria-modal="true"
        aria-labelledby="admin-session-expired-title"
    >
        <div class="w-full max-w-lg rounded-xl bg-white p-8 text-center shadow-2xl dark:bg-gray-900">
            <h2 id="admin-session-expired-title" class="text-2xl font-black text-red-600">
                Връзката с админа прекъсна
            </h2>
            <p class="mt-3 text-base text-gray-700 dark:text-gray-200">
                Сесията е изтекла, затова нови поръчки вече не изскачат. Влезте отново веднага, за да не пропускате поръчки.
            </p>
            <a
                x-bind:href="loginUrl"
                class="mt-6 inline-flex w-full items-center justify-center rounded-xl bg-primary-600 px-6 py-4 text-xl font-bold text-white hover:bg-primary-500"
            >
                Влез отново
            </a>
        </div>
    </div>

    @if ($currentOrder)
        <div
            class="fixed inset-0 z-[9999] flex items-center justify-center bg-gray-950/70 p-4"
            role="dialog"
            aria-modal="true"
            aria-labelledby="new-order-alert-title"
        >
            <div class="flex max-h-[90vh] w-full max-w-2xl flex-col overflow-hidden rounded-xl bg-white shadow-2xl ring-1 ring-gray-950/10 dark:bg-gray-900 dark:ring-white/10">
                <div class="border-b border-gray-200 bg-primary-600 px-6 py-4 dark:border-white/10">
                    <h2 id="new-order-alert-title" class="text-xl font-bold text-white">
                        Нова поръчка — {{ $currentOrder->order_number }}
                    </h2>
                    <p class="mt-1 text-sm text-white/90">
                        {{ $currentOrder->created_at?->format('d.m.Y H:i') }}
                        @if (count($queue) > 0)
                            · още {{ count($queue) }} в опашката
                        @endif
                    </p>
                </div>

                <div class="overflow-y-auto px-6 py-4">
                    @include('filament.partials.order-details', ['order' => $currentOrder])
                </div>

                <div class="border-t border-gray-200 bg-gray-50 px-6 py-5 dark:border-white/10 dark:bg-gray-950">
                    <button
                        type="button"
                        wire:click="acceptOrder"
                        wire:loading.attr="disabled"
                        wire:target="acceptOrder"
                        class="new-order-accept-btn"
                    >
                        <span wire:loading.remove wire:target="acceptOrder">Приета</span>
                        <span wire:loading wire:target="acceptOrder">Обработва се...</span>
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
