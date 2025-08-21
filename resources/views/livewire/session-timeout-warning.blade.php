<div x-data="{ 
    checkInterval: null,
    countdownInterval: null,
    localTimer: 60,
    
    init() {
        this.startChecking();
        this.$wire.on('sessionExtended', () => {
            this.startChecking();
        });
    },
    
    startChecking() {
        if (this.checkInterval) {
            clearInterval(this.checkInterval);
        }
        this.checkInterval = setInterval(() => {
            this.$wire.call('checkSession');
        }, 10000); // Check every 10 seconds
    },
    
    startCountdown() {
        this.localTimer = 60;
        if (this.countdownInterval) {
            clearInterval(this.countdownInterval);
        }
        this.countdownInterval = setInterval(() => {
            this.localTimer--;
            if (this.localTimer <= 0) {
                clearInterval(this.countdownInterval);
                // Call Livewire logout method first, then redirect
                this.$wire.call('logout');
            }
        }, 1000);
    }
}" 
x-init="
    $watch('$wire.showWarning', (value) => {
        if (value) {
            startCountdown();
        } else if (countdownInterval) {
            clearInterval(countdownInterval);
        }
    })
">
    <!-- Session Timeout Warning Modal -->
    @if($showWarning)
        <div class="fixed inset-0 z-50 overflow-y-auto" x-show="true" x-transition>
            <div class="flex items-center justify-center min-h-screen px-4 text-center">
                <div class="fixed inset-0 bg-black opacity-50"></div>
                
                <div class="relative bg-white rounded-lg shadow-xl max-w-md w-full p-6">
                    <div class="flex items-center justify-center w-12 h-12 mx-auto mb-4 bg-yellow-100 rounded-full">
                        <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.732-.833-2.5 0L4.314 15.5c-.77.833.192 2.5 1.732 2.5z"></path>
                        </svg>
                    </div>
                    
                    <h3 class="text-lg font-medium text-gray-900 mb-2">
                        Session Timeout Warning
                    </h3>
                    
                    <p class="text-sm text-gray-600 mb-4">
                        Your session will expire in <span class="font-semibold text-red-600" x-text="localTimer"></span> seconds due to inactivity.
                    </p>
                    
                    <p class="text-sm text-gray-600 mb-6">
                        Would you like to stay logged in?
                    </p>
                    
                    <div class="flex" style="gap: 12px; margin-top: 6px;">
                        <button 
                            wire:click="extendSession"
                            class="flex-1 px-4 py-2 bg-grcblue-600 text-white text-sm font-medium rounded-md hover:bg-grcblue-700 focus:outline-none focus:ring-2 focus:ring-blue-500"
                        >
                            Stay Logged In
                        </button>
                        
                        <button 
                            @click="$wire.call('logout')"
                            class="flex-1 px-4 py-2 bg-gray-300 text-gray-700 text-sm font-medium rounded-md hover:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-500"
                        >
                            Logout
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @else
        <!-- Hidden placeholder when no warning is shown -->
        <div style="display: none;"></div>
    @endif
</div>

