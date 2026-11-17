<div
    wire:ignore
    data-csrf="{{ csrf_token() }}"
    x-data="{
        audio: null,
        keepAliveAudio: null,
        audioUnlocked: false,
        pollTimer: null,
        watchdogTimer: null,
        wakeLock: null,
        sessionExpired: false,
        watching: true,
        lastCheckLabel: '',
        lastOkAt: Date.now(),
        orders: [],
        accepting: false,
        pingUrl: @js(route('admin.kitchen.pending-orders')),
        acceptUrlTemplate: @js(url('/admin/kitchen/orders/__ID__/accept')),
        loginUrl: @js(url('/admin/user/login/login')),
        csrf: @js(csrf_token()),
        init() {
            this.startWatching();
            this.registerUnlock();

            document.addEventListener('visibilitychange', () => {
                if (document.visibilityState === 'visible' && ! this.sessionExpired) {
                    this.pollRemote();
                    this.requestWakeLock();
                }
            });
        },
        registerUnlock() {
            const unlock = () => this.unlockAudio();
            window.addEventListener('pointerdown', unlock, { once: true, passive: true });
            window.addEventListener('keydown', unlock, { once: true });
        },
        startWatching() {
            this.pollRemote();

            if (this.pollTimer) {
                clearInterval(this.pollTimer);
            }

            if (this.watchdogTimer) {
                clearInterval(this.watchdogTimer);
            }

            this.pollTimer = setInterval(() => this.pollRemote(), 4000);
            this.watchdogTimer = setInterval(() => this.checkWatchdog(), 2000);
        },
        csrfHeaders() {
            const token = this.$el?.dataset?.csrf || this.csrf;
            const xsrf = decodeURIComponent(
                (document.cookie.match(/(?:^|; )XSRF-TOKEN=([^;]*)/) || [])[1] || ''
            );

            return {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': token,
                'X-XSRF-TOKEN': xsrf,
            };
        },
        async pollRemote() {
            if (this.sessionExpired) {
                return;
            }

            try {
                const response = await fetch(this.pingUrl, {
                    method: 'POST',
                    headers: this.csrfHeaders(),
                    credentials: 'same-origin',
                    cache: 'no-store',
                    body: '{}',
                });

                if ([401, 403, 419].includes(response.status) || response.redirected) {
                    this.markDisconnected();
                    return;
                }

                if (! response.ok) {
                    return;
                }

                const data = await response.json();
                this.lastOkAt = Date.now();
                this.watching = true;
                this.lastCheckLabel = new Date().toLocaleTimeString('bg-BG', { hour: '2-digit', minute: '2-digit', second: '2-digit' });

                if (data.csrf) {
                    this.csrf = data.csrf;
                    if (this.$el) {
                        this.$el.dataset.csrf = data.csrf;
                    }
                }

                const incoming = Array.isArray(data.orders) ? data.orders : [];
                const hadOrders = this.orders.length;
                const knownIds = this.orders.map((order) => Number(order.id));
                const hasNew = incoming.some((order) => ! knownIds.includes(Number(order.id)));

                this.orders = incoming;

                if (hasNew || (hadOrders === 0 && incoming.length > 0)) {
                    this.startSound();
                    this.notifyBrowser(incoming[incoming.length - 1]);
                }

                if (incoming.length === 0) {
                    this.stopSound();
                }
            } catch (error) {
                // Network blips are retried; the watchdog handles a dead connection.
            }
        },
        checkWatchdog() {
            if (this.sessionExpired) {
                return;
            }

            if (Date.now() - this.lastOkAt > 20000) {
                this.markDisconnected();
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
        unlockAudio() {
            if (this.audioUnlocked) {
                return;
            }

            this.audioUnlocked = true;
            this.startKeepAliveAudio();
            this.requestWakeLock();

            if (this.orders.length > 0 || this.sessionExpired) {
                this.startSound();
            }

            if (window.Notification && Notification.permission === 'default') {
                Notification.requestPermission().catch(() => {});
            }
        },
        startKeepAliveAudio() {
            if (this.keepAliveAudio) {
                return;
            }

            this.keepAliveAudio = new Audio('data:audio/wav;base64,UklGRigAAABXQVZFZm10IBIAAAABAAEARKwAAIhYAQACABAAAABkYXRhAgAAAAEA');
            this.keepAliveAudio.loop = true;
            this.keepAliveAudio.volume = 0.01;
            this.keepAliveAudio.play().catch(() => {});
        },
        async requestWakeLock() {
            if (! navigator.wakeLock || document.visibilityState !== 'visible') {
                return;
            }

            try {
                this.wakeLock = await navigator.wakeLock.request('screen');
            } catch (error) {
                // Wake Lock is a bonus; polling still works without it.
            }
        },
        notifyBrowser(order) {
            if (! order || ! window.Notification || Notification.permission !== 'granted') {
                return;
            }

            try {
                new Notification('Нова поръчка Allo! Pizza', {
                    body: order.order_number + ' — ' + (order.customer_name || ''),
                    tag: 'allo-kitchen-order',
                    requireInteraction: true,
                });
            } catch (error) {
                // Ignore notification failures.
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
            if (! this.audioUnlocked) {
                return;
            }

            this.getAudio().play().catch(() => {});
        },
        stopSound() {
            if (this.audio === null) {
                return;
            }

            this.audio.pause();
            this.audio.currentTime = 0;
        },
        currentOrder() {
            return this.orders[0] || null;
        },
        async acceptCurrent() {
            const order = this.currentOrder();

            if (! order || this.accepting) {
                return;
            }

            this.accepting = true;

            try {
                const response = await fetch(this.acceptUrlTemplate.replace('__ID__', order.id), {
                    method: 'POST',
                    headers: this.csrfHeaders(),
                    credentials: 'same-origin',
                    cache: 'no-store',
                    body: '{}',
                });

                if ([401, 403, 419].includes(response.status) || response.redirected) {
                    this.markDisconnected();
                    return;
                }
            } finally {
                this.accepting = false;
            }

            this.orders = this.orders.filter((item) => Number(item.id) !== Number(order.id));

            if (this.orders.length === 0) {
                this.stopSound();
            }

            this.pollRemote();
        },
    }"
>
    <style>[x-cloak]{display:none!important}</style>

    <div
        x-show="! audioUnlocked && ! sessionExpired"
        x-cloak
        class="fixed bottom-4 right-4 z-[10000]"
    >
        <button
            type="button"
            x-on:pointerdown="unlockAudio()"
            class="rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white shadow-lg hover:bg-primary-500"
        >
            Кликни за звук на поръчки
        </button>
    </div>

    <div
        x-show="watching && ! sessionExpired"
        x-cloak
        class="pointer-events-none fixed bottom-4 left-4 z-[9998]"
    >
        <div class="inline-flex items-center gap-2 rounded-full bg-white/95 px-3 py-1.5 text-xs font-medium text-gray-700 shadow-md ring-1 ring-gray-950/10 dark:bg-gray-900/95 dark:text-gray-200 dark:ring-white/10">
            <span class="h-2 w-2 animate-pulse rounded-full bg-green-500"></span>
            <span>Следим за поръчки</span>
            <span class="text-gray-400" x-show="lastCheckLabel" x-text="'· ' + lastCheckLabel"></span>
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
                Сесията е изтекла или проверката за поръчки е спряла. Влезте отново веднага, за да не пропускате поръчки.
            </p>
            <a
                x-bind:href="loginUrl"
                class="mt-6 inline-flex w-full items-center justify-center rounded-xl bg-primary-600 px-6 py-4 text-xl font-bold text-white hover:bg-primary-500"
            >
                Влез отново
            </a>
        </div>
    </div>

    <template x-if="orders.length > 0">
        <div
            class="fixed inset-0 z-[9999] flex items-center justify-center bg-gray-950/70 p-4"
            role="dialog"
            aria-modal="true"
            aria-labelledby="new-order-alert-title"
        >
            <div class="flex max-h-[90vh] w-full max-w-2xl flex-col overflow-hidden rounded-xl bg-white shadow-2xl ring-1 ring-gray-950/10 dark:bg-gray-900 dark:ring-white/10">
                <div class="border-b border-gray-200 bg-primary-600 px-6 py-4 dark:border-white/10">
                    <h2 id="new-order-alert-title" class="text-xl font-bold text-white" x-text="'Нова поръчка — ' + orders[0].order_number"></h2>
                    <p class="mt-1 text-sm text-white/90">
                        <span x-text="orders[0].created_at"></span>
                        <span x-show="orders.length > 1" x-text="' · още ' + (orders.length - 1) + ' в опашката'"></span>
                    </p>
                </div>

                <div class="overflow-y-auto px-6 py-4">
                    <div class="space-y-4 text-sm text-gray-700 dark:text-gray-200">
                        <div class="rounded-lg bg-gray-50 p-4 dark:bg-white/5">
                            <p class="font-semibold text-gray-950 dark:text-white" x-text="orders[0].customer_name"></p>
                            <p class="mt-1">
                                <a class="text-primary-600 hover:underline dark:text-primary-400" x-bind:href="'tel:' + orders[0].customer_phone" x-text="orders[0].customer_phone"></a>
                            </p>
                            <p class="mt-1" x-show="orders[0].customer_email" x-text="orders[0].customer_email"></p>
                            <p class="mt-2"><span class="font-medium">Получаване:</span> <span x-text="orders[0].delivery_type"></span></p>
                            <p class="mt-1" x-show="orders[0].delivery_address"><span class="font-medium">Адрес:</span> <span x-text="orders[0].delivery_address"></span></p>
                            <p class="mt-1"><span class="font-medium">Плащане:</span> <span x-text="orders[0].payment_method"></span></p>
                            <p class="mt-2 rounded-md bg-amber-50 p-2 text-amber-900 dark:bg-amber-500/10 dark:text-amber-200" x-show="orders[0].customer_note">
                                <span class="font-medium">Бележка:</span> <span x-text="orders[0].customer_note"></span>
                            </p>
                        </div>

                        <div>
                            <h3 class="mb-2 font-semibold text-gray-950 dark:text-white">Артикули</h3>
                            <ul class="divide-y divide-gray-200 rounded-lg border border-gray-200 dark:divide-white/10 dark:border-white/10">
                                <template x-for="item in orders[0].items" :key="item.product_name + item.variant_name + item.quantity">
                                    <li class="flex items-start justify-between gap-4 px-4 py-3">
                                        <div>
                                            <p class="font-medium text-gray-950 dark:text-white">
                                                <span x-text="item.product_name"></span>
                                                <span class="font-normal text-gray-500 dark:text-gray-400" x-show="item.variant_name" x-text="' (' + item.variant_name + ')'"></span>
                                                <span class="text-gray-500 dark:text-gray-400" x-text="' × ' + item.quantity"></span>
                                            </p>
                                            <template x-for="option in item.options" :key="option.name">
                                                <p class="text-xs" :class="option.removed ? 'text-gray-500 line-through dark:text-gray-400' : 'text-gray-600 dark:text-gray-300'">
                                                    <span x-text="(option.extra ? '+ ' : '− ') + option.name"></span>
                                                    <span x-show="option.price" x-text="' (' + option.price + ')'"></span>
                                                </p>
                                            </template>
                                            <p class="mt-1 text-xs italic text-gray-500 dark:text-gray-400" x-show="item.note" x-text="'„' + item.note + '“'"></p>
                                        </div>
                                        <p class="shrink-0 font-medium text-gray-950 dark:text-white" x-text="item.total"></p>
                                    </li>
                                </template>
                            </ul>
                        </div>

                        <dl class="grid grid-cols-2 gap-2 rounded-lg bg-gray-50 p-4 dark:bg-white/5">
                            <dt class="text-gray-600 dark:text-gray-400">Междинна сума</dt>
                            <dd class="text-right font-medium" x-text="orders[0].subtotal"></dd>
                            <template x-if="orders[0].discount">
                                <dt class="text-gray-600 dark:text-gray-400">Отстъпка <span x-show="orders[0].promo_code" x-text="'(' + orders[0].promo_code + ')'"></span></dt>
                            </template>
                            <template x-if="orders[0].discount">
                                <dd class="text-right font-medium" x-text="'−' + orders[0].discount"></dd>
                            </template>
                            <dt class="text-gray-600 dark:text-gray-400">Доставка</dt>
                            <dd class="text-right font-medium" x-text="orders[0].delivery_fee"></dd>
                            <dt class="text-lg font-bold text-gray-950 dark:text-white">Общо</dt>
                            <dd class="text-right text-lg font-bold text-primary-600 dark:text-primary-400" x-text="orders[0].total"></dd>
                        </dl>
                    </div>
                </div>

                <div class="border-t border-gray-200 bg-gray-50 px-6 py-5 dark:border-white/10 dark:bg-gray-950">
                    <button
                        type="button"
                        x-on:click="acceptCurrent()"
                        x-bind:disabled="accepting"
                        class="new-order-accept-btn"
                    >
                        <span x-show="! accepting">Приета</span>
                        <span x-show="accepting">Обработва се...</span>
                    </button>
                </div>
            </div>
        </div>
    </template>
</div>
