<div
    wire:poll.15s="pollForNewOrders"
    x-data="{
        audio: null,
        audioUnlocked: false,
        unlockListenersRegistered: false,
        init() {
            if (this.unlockListenersRegistered) {
                return;
            }

            this.unlockListenersRegistered = true;

            const unlock = () => this.unlockSound();

            window.addEventListener('pointerdown', unlock, { once: true, passive: true });
            window.addEventListener('keydown', unlock, { once: true });
        },
        unlockSound() {
            if (this.audioUnlocked) {
                return;
            }

            this.audioUnlocked = true;

            if (@js($currentOrder !== null)) {
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
