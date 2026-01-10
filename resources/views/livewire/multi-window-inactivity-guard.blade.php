<div
    class="inactivity-guard"
    :class="{ 'modal-active': modalOpen }"
    wire:ignore
    x-data="{
            inactivityTimer: null,
            logoutTimer: null,
            storageKey: 'last_activity_timestamp',
            checkInterval: null,
            modalOpen: false,
            countdown: {{ $notice_timeout/1000 }},
            countdownInterval: null,
            inactivityTimeout: {{ $inactivity_timeout }},
            logoutTimeout: {{ $notice_timeout }},
            interactionEvents: {{ $interaction_events }},

            init() {
                this.updateActivity();

                this.interactionEvents.forEach(event => {
                    window.addEventListener(event, () => this.updateActivity());
                });

                window.addEventListener('storage', (e) => {
                    if (e.key === this.storageKey) {
                        this.handleActivityFromOtherTab();
                    }
                });

                this.startMonitoring();
            },

            updateActivity() {
                const now = Date.now();
                localStorage.setItem(this.storageKey, now.toString());
                this.resetInactivityTimer();
            },

            handleActivityFromOtherTab() {
                this.resetInactivityTimer();
            },

            resetInactivityTimer() {
                clearTimeout(this.inactivityTimer);
                clearTimeout(this.logoutTimer);

                if (this.modalOpen) {
                    this.closeModal();
                }

                this.inactivityTimer = setTimeout(() => {
                    this.showInactivityModal();
                }, this.inactivityTimeout);
            },

            startMonitoring() {
                this.checkInterval = setInterval(() => {
                    // Don't interfere if modal is already open and countdown is running
                    if (this.modalOpen) {
                        return;
                    }

                    const lastActivity = parseInt(localStorage.getItem(this.storageKey) || '0');
                    const now = Date.now();
                    const timeSinceActivity = now - lastActivity;

                    if (timeSinceActivity >= this.inactivityTimeout) {
                        this.showInactivityModal();
                    }
                }, 5000);
            },

            showInactivityModal() {
                // Prevent showing modal multiple times
                if (this.modalOpen) {
                    return;
                }

                if (this.logoutTimeout < 1) {
                    this.performLogout();
                    return;
                }

                this.modalOpen = true;
                this.countdown = this.logoutTimeout / 1000;
                this.$dispatch('open-modal', { id: 'inactivityModal' });

                this.countdownInterval = setInterval(() => {
                    this.countdown--;
                    if (this.countdown <= 0) {
                        clearInterval(this.countdownInterval);
                    }
                }, 1000);

                this.logoutTimer = setTimeout(() => {
                    this.performLogout();
                }, this.logoutTimeout);
            },

            closeModal() {
                this.modalOpen = false;
                clearInterval(this.countdownInterval);
                this.$dispatch('close-modal', { id: 'inactivityModal' });
            },

            async performLogout() {
                localStorage.removeItem(this.storageKey);
                const loginUrl = await @this.call('logout');
                // Force full page redirect to prevent login appearing in modal
                window.location.href = loginUrl;
            },

            resumeActivities() {
                this.closeModal();
                this.updateActivity();
            },

            destroy() {
                clearTimeout(this.inactivityTimer);
                clearTimeout(this.logoutTimer);
                clearInterval(this.checkInterval);
                clearInterval(this.countdownInterval);
            }
        }"
>
    <x-filament::modal
        id="inactivityModal"
        width="lg"
        :close-button="false"
        :close-by-clicking-away="false"
    >
        <x-slot name="heading">Session Timeout Warning</x-slot>

        <x-slot name="description">
            Your session is about to expire due to inactivity. Click "Stay Logged In" to continue your session.
        </x-slot>

        <x-slot name="footer">
            <x-filament::button type="button" x-on:click="resumeActivities()">
                Stay Logged In
            </x-filament::button>

            <x-filament::button color="danger" disabled>
                <div style="opacity: 0.8; cursor: not-allowed;">
                    Logging out in <span x-text="countdown"></span>s
                </div>
            </x-filament::button>
        </x-slot>
    </x-filament::modal>
</div>
