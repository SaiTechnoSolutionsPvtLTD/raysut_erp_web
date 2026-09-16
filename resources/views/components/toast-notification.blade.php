<style>
@keyframes toastFloat {
    0%, 100% {
        transform: translateY(0px);
    }
    50% {
        transform: translateY(-6px);
    }
}
.animate-toast-float {
    animation: toastFloat 3.5s ease-in-out infinite;
}
</style>

<div x-data="{
    toasts: [],
    addToast(toast) {
        if (!toast || (!toast.message && !toast.title)) return;
        const id = Date.now() + Math.random();
        const newToast = {
            id,
            type: toast.type || 'success',
            title: toast.title || (toast.type === 'error' ? 'Action Failed' : 'Success'),
            message: toast.message || '',
            timer: null,
            progress: 100
        };
        this.toasts.push(newToast);

        this.$nextTick(() => {
            this.startTimer(newToast);
        });
    },
    removeToast(id) {
        const index = this.toasts.findIndex(t => t.id === id);
        if (index !== -1) {
            if (this.toasts[index].timer) clearInterval(this.toasts[index].timer);
            this.toasts.splice(index, 1);
        }
    },
    startTimer(toast) {
        const duration = 4000;
        const interval = 40;
        const step = (interval / duration) * 100;
        
        toast.timer = setInterval(() => {
            toast.progress -= step;
            if (toast.progress <= 0) {
                clearInterval(toast.timer);
                this.removeToast(toast.id);
            }
        }, interval);
    },
    pauseTimer(toast) {
        if (toast.timer) clearInterval(toast.timer);
    },
    resumeTimer(toast) {
        if (toast.progress > 0) {
            this.startTimer(toast);
        }
    }
}"
@notify.window="addToast($event.detail)"
x-init="
    @if(session()->has('message'))
        addToast({ type: 'success', title: 'Operation Successful', message: '{{ session('message') }}' });
    @endif
    @if(session()->has('error'))
        addToast({ type: 'error', title: 'Submission Failed', message: '{{ session('error') }}' });
    @endif
"
class="fixed top-5 right-5 z-[9999] flex flex-col space-y-3 max-w-sm sm:max-w-md w-full pointer-events-none px-4 sm:px-0">
    
    <template x-for="toast in toasts" :key="toast.id">
        <div x-show="true"
             x-transition:enter="transition ease-out duration-300 transform"
             x-transition:enter-start="opacity-0 translate-y-4 translate-x-4 scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 translate-x-0 scale-100"
             x-transition:leave="transition ease-in duration-200 transform"
             x-transition:leave-start="opacity-100 translate-x-0 scale-100"
             x-transition:leave-end="opacity-0 translate-x-8 scale-90"
             @mouseenter="pauseTimer(toast)"
             @mouseleave="resumeTimer(toast)"
             class="pointer-events-auto relative overflow-hidden rounded-2xl shadow-xl hover:shadow-2xl border bg-white/95 backdrop-blur-md p-4 transition-all duration-300 group animate-toast-float"
             :class="{
                 'border-emerald-200/90 shadow-emerald-500/10': toast.type === 'success',
                 'border-rose-200/90 shadow-rose-500/10': toast.type === 'error',
                 'border-amber-200/90 shadow-amber-500/10': toast.type === 'warning',
                 'border-blue-200/90 shadow-blue-500/10': toast.type === 'info'
             }">
             
            <!-- Ambient Radial Glow Background -->
            <div class="absolute -top-12 -right-12 w-32 h-32 rounded-full blur-2xl opacity-30 pointer-events-none animate-pulse"
                 :class="{
                     'bg-emerald-400': toast.type === 'success',
                     'bg-rose-400': toast.type === 'error',
                     'bg-amber-400': toast.type === 'warning',
                     'bg-blue-400': toast.type === 'info'
                 }"></div>

            <div class="flex items-start space-x-3.5 relative z-10">
                <!-- Icon Badge -->
                <div class="p-2.5 rounded-xl shrink-0 border flex items-center justify-center shadow-2xs group-hover:scale-105 transition-transform duration-200"
                     :class="{
                         'bg-emerald-50 text-emerald-600 border-emerald-200': toast.type === 'success',
                         'bg-rose-50 text-rose-600 border-rose-200': toast.type === 'error',
                         'bg-amber-50 text-amber-600 border-amber-200': toast.type === 'warning',
                         'bg-blue-50 text-blue-600 border-blue-200': toast.type === 'info'
                     }">
                     
                    <!-- Success Icon -->
                    <template x-if="toast.type === 'success'">
                        <svg class="w-5 h-5 animate-bounce-once" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                        </svg>
                    </template>
                    
                    <!-- Error Icon -->
                    <template x-if="toast.type === 'error'">
                        <svg class="w-5 h-5 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                    </template>
                    
                    <!-- Warning / Info Icon -->
                    <template x-if="toast.type === 'warning' || toast.type === 'info'">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </template>
                </div>

                <!-- Toast Content Body -->
                <div class="flex-1 min-w-0 pt-0.5">
                    <div class="flex items-center justify-between gap-2">
                        <span class="text-[10px] font-extrabold uppercase tracking-widest px-2 py-0.5 rounded-md border"
                              :class="{
                                  'bg-emerald-50 text-emerald-700 border-emerald-200': toast.type === 'success',
                                  'bg-rose-50 text-rose-700 border-rose-200': toast.type === 'error',
                                  'bg-amber-50 text-amber-700 border-amber-200': toast.type === 'warning',
                                  'bg-blue-50 text-blue-700 border-blue-200': toast.type === 'info'
                              }" x-text="toast.type === 'error' ? 'Action Failed' : (toast.type === 'success' ? 'Success' : 'Notice')"></span>
                              
                        <button @click="removeToast(toast.id)" 
                                class="text-slate-400 hover:text-slate-700 p-1 rounded-lg hover:bg-slate-100 transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    <h4 class="font-bold text-sm text-slate-900 mt-1 truncate" x-text="toast.title"></h4>
                    <p class="text-xs text-slate-600 mt-0.5 leading-relaxed" x-text="toast.message"></p>
                </div>
            </div>

            <!-- Animated Progress Line -->
            <div class="absolute bottom-0 left-0 right-0 h-1 bg-slate-100 overflow-hidden">
                <div class="h-full transition-all duration-75 ease-linear"
                     :style="`width: ${toast.progress}%`"
                     :class="{
                         'bg-emerald-500': toast.type === 'success',
                         'bg-rose-500': toast.type === 'error',
                         'bg-amber-500': toast.type === 'warning',
                         'bg-blue-500': toast.type === 'info'
                     }"></div>
            </div>
        </div>
    </template>
</div>
