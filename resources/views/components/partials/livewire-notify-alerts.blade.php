<div
    x-data="{
        alerts: [],
        addAlert(detail) {
            const id = crypto.randomUUID ? crypto.randomUUID() : `${Date.now()}-${Math.random()}`;
            const allowed = ['success', 'error', 'warning', 'info'];
            const variant = allowed.includes(detail?.type) ? detail.type : 'success';
            const message = detail?.message ?? '';
            const timeout = Number.isFinite(detail?.timeout) ? Number(detail.timeout) : 5000;

            this.alerts.push({ id, variant, message, timeout });

            if (timeout > 0) {
                setTimeout(() => this.dismiss(id), timeout + 100); // allow alert fade-out
            }
        },
        dismiss(id) {
            this.alerts = this.alerts.filter(alert => alert.id !== id);
        },
        init() {
            window.addEventListener('notify:show', event => this.addAlert(event.detail ?? {}));
        }
    }"
    class="pointer-events-none fixed bottom-4 right-4 z-50 flex w-full max-w-sm flex-col gap-3 px-4 sm:px-0"
>
    <template x-for="alert in alerts" :key="alert.id">
        <div class="pointer-events-auto">
            <template x-if="alert.variant === 'success'">
                <x-alert variant="success" :timeout="0" class="mb-0">
                    <span x-text="alert.message"></span>
                </x-alert>
            </template>

            <template x-if="alert.variant === 'error'">
                <x-alert variant="error" :timeout="0" class="mb-0">
                    <span x-text="alert.message"></span>
                </x-alert>
            </template>

            <template x-if="alert.variant === 'warning'">
                <x-alert variant="warning" :timeout="0" class="mb-0">
                    <span x-text="alert.message"></span>
                </x-alert>
            </template>

            <template x-if="alert.variant === 'info'">
                <x-alert variant="info" :timeout="0" class="mb-0">
                    <span x-text="alert.message"></span>
                </x-alert>
            </template>
        </div>
    </template>
</div>

@push('scripts')
    <script>
        document.addEventListener('livewire:init', () => {
            Livewire.on('notify', (payload = {}) => {
                window.dispatchEvent(new CustomEvent('notify:show', { detail: payload }));
            });
        });
    </script>
@endpush

