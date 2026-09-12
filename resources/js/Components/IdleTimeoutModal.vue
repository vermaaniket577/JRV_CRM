<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import { 
  ClockIcon, 
  ShieldExclamationIcon, 
  ArrowRightOnRectangleIcon, 
  ArrowPathIcon,
  CheckCircleIcon
} from '@heroicons/vue/24/outline';

const page = usePage();

// Configuration
const DEFAULT_TIMEOUT_MINUTES = 15;
const WARNING_SECONDS = 60;

const isModalOpen = ref(false);
const remainingSeconds = ref(WARNING_SECONDS);
const isLoggingOut = ref(false);
const isKeepingAlive = ref(false);

let checkInterval = null;
let countdownInterval = null;
let lastRecordedActivity = Date.now();

// Get configured timeout in milliseconds
const getTimeoutMs = () => {
  const saved = localStorage.getItem('crm_idle_timeout_minutes');
  if (saved === 'disabled') return null;
  const mins = saved ? parseInt(saved, 10) : DEFAULT_TIMEOUT_MINUTES;
  return (isNaN(mins) || mins <= 0 ? DEFAULT_TIMEOUT_MINUTES : mins) * 60 * 1000;
};

// Check if user is authenticated
const isAuthenticated = computed(() => {
  return Boolean(page.props.auth?.user || page.props.auth?.is_master_admin);
});

// Logout endpoint based on user type/page
const logoutUrl = computed(() => {
  const url = page.url || '';
  if (url.startsWith('/admin') && !url.startsWith('/admin/login')) {
    return '/admin/logout';
  }
  return '/logout';
});

// Record user activity
const recordActivity = () => {
  const now = Date.now();
  // Throttle writes to localStorage (once every 2 seconds)
  if (now - lastRecordedActivity > 2000) {
    lastRecordedActivity = now;
    try {
      localStorage.setItem('crm_last_activity', now.toString());
    } catch (e) {
      // ignore storage quota errors
    }
  }
};

// Throttled event handler for user interactions
let activityThrottleTimeout = null;
const handleUserInteraction = () => {
  // If modal is open, user interaction does not automatically dismiss it;
  // they must explicitly click "Stay Logged In" or "Logout"
  if (isModalOpen.value) return;

  if (!activityThrottleTimeout) {
    recordActivity();
    activityThrottleTimeout = setTimeout(() => {
      activityThrottleTimeout = null;
    }, 1000);
  }
};

// Start warning countdown
const showWarning = () => {
  if (isModalOpen.value) return;
  isModalOpen.value = true;
  remainingSeconds.value = WARNING_SECONDS;

  clearInterval(countdownInterval);
  countdownInterval = setInterval(() => {
    if (remainingSeconds.value > 1) {
      remainingSeconds.value--;
    } else {
      remainingSeconds.value = 0;
      clearInterval(countdownInterval);
      performAutoLogout();
    }
  }, 1000);
};

// Check inactivity status
const checkInactivity = () => {
  if (!isAuthenticated.value || isModalOpen.value || isLoggingOut.value) {
    return;
  }

  const timeoutMs = getTimeoutMs();
  if (!timeoutMs) return; // Inactivity auto-logout disabled

  const storedActivity = parseInt(localStorage.getItem('crm_last_activity') || '0', 10);
  const effectiveLastActivity = Math.max(lastRecordedActivity, storedActivity);
  const now = Date.now();
  const elapsed = now - effectiveLastActivity;

  // If elapsed time reached timeout minus warning window, open warning modal
  const warningThreshold = timeoutMs - (WARNING_SECONDS * 1000);
  if (elapsed >= warningThreshold) {
    const secondsLeft = Math.max(1, Math.round((timeoutMs - elapsed) / 1000));
    remainingSeconds.value = Math.min(secondsLeft, WARNING_SECONDS);
    showWarning();
  }
};

// "Stay Logged In" Action
const stayLoggedIn = async () => {
  isKeepingAlive.value = true;
  try {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    await fetch('/keep-alive', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrfToken || '',
        'Accept': 'application/json',
      },
    });
  } catch (e) {
    // Session keep-alive ping fallback
  } finally {
    isKeepingAlive.value = false;
  }

  const now = Date.now();
  lastRecordedActivity = now;
  try {
    localStorage.setItem('crm_last_activity', now.toString());
  } catch (e) {}

  clearInterval(countdownInterval);
  isModalOpen.value = false;
};

// Execute Auto-Logout
const performAutoLogout = () => {
  if (isLoggingOut.value) return;
  isLoggingOut.value = true;
  clearInterval(countdownInterval);
  clearInterval(checkInterval);

  try {
    sessionStorage.setItem('crm_logout_reason', 'inactivity');
  } catch (e) {}

  router.post(logoutUrl.value, { reason: 'inactivity' }, {
    onFinish: () => {
      isModalOpen.value = false;
    }
  });
};

// Immediate Logout button
const handleImmediateLogout = () => {
  performAutoLogout();
};

// Storage event listener for multi-tab sync
const handleStorageEvent = (event) => {
  if (event.key === 'crm_last_activity') {
    const updated = parseInt(event.newValue || '0', 10);
    if (updated > lastRecordedActivity) {
      lastRecordedActivity = updated;
      if (isModalOpen.value) {
        clearInterval(countdownInterval);
        isModalOpen.value = false;
      }
    }
  } else if (event.key === 'crm_idle_timeout_minutes') {
    // Timeout preference changed in another tab
    lastRecordedActivity = Date.now();
  }
};

// Listen for preview test events from Settings page
const handlePreviewEvent = () => {
  remainingSeconds.value = 30;
  isModalOpen.value = true;
  clearInterval(countdownInterval);
  countdownInterval = setInterval(() => {
    if (remainingSeconds.value > 1) {
      remainingSeconds.value--;
    } else {
      remainingSeconds.value = 0;
      clearInterval(countdownInterval);
    }
  }, 1000);
};

const activityEvents = ['mousemove', 'mousedown', 'keydown', 'scroll', 'touchstart', 'click'];

onMounted(() => {
  // Initialize activity timestamp
  const now = Date.now();
  lastRecordedActivity = now;
  if (!localStorage.getItem('crm_last_activity')) {
    try {
      localStorage.setItem('crm_last_activity', now.toString());
    } catch (e) {}
  }

  // Bind activity listeners
  activityEvents.forEach(evt => {
    window.addEventListener(evt, handleUserInteraction, { passive: true });
  });

  // Cross-tab sync listener
  window.addEventListener('storage', handleStorageEvent);

  // Preview trigger listener
  window.addEventListener('crm:preview-idle-modal', handlePreviewEvent);

  // Run periodic inactivity checker every 2 seconds
  checkInterval = setInterval(checkInactivity, 2000);
});

onUnmounted(() => {
  activityEvents.forEach(evt => {
    window.removeEventListener(evt, handleUserInteraction);
  });
  window.removeEventListener('storage', handleStorageEvent);
  window.removeEventListener('crm:preview-idle-modal', handlePreviewEvent);
  clearInterval(checkInterval);
  clearInterval(countdownInterval);
});

// Format formatted countdown mm:ss
const formattedCountdown = computed(() => {
  const m = Math.floor(remainingSeconds.value / 60);
  const s = remainingSeconds.value % 60;
  return `${String(m).padStart(2, '0')}:${String(s).padStart(2, '0')}`;
});

// Progress percentage for progress bar
const progressPercentage = computed(() => {
  return Math.max(0, Math.min(100, (remainingSeconds.value / WARNING_SECONDS) * 100));
});
</script>

<template>
  <!-- Inactivity Warning Dialog Modal -->
  <Teleport to="body">
    <div 
      v-if="isModalOpen" 
      class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-md transition-all duration-300 animate-fade-in"
    >
      <div 
        class="relative w-full max-w-md bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 sm:p-8 shadow-2xl shadow-red-500/10 text-slate-900 dark:text-white space-y-6 overflow-hidden"
      >
        <!-- Top subtle pulsing ambient glow -->
        <div class="absolute -top-12 left-1/2 -translate-x-1/2 w-48 h-48 bg-amber-500/15 rounded-full blur-3xl pointer-events-none"></div>

        <!-- Header: Warning Icon & Title -->
        <div class="flex flex-col items-center text-center space-y-3 pt-2">
          <div class="relative">
            <div class="w-16 h-16 rounded-2xl bg-amber-500/10 dark:bg-amber-400/15 border border-amber-500/30 flex items-center justify-center text-amber-600 dark:text-amber-400 animate-pulse">
              <ClockIcon class="w-8 h-8" />
            </div>
            <div class="absolute -bottom-1 -right-1 p-1 bg-red-600 rounded-full text-white shadow">
              <ShieldExclamationIcon class="w-3.5 h-3.5" />
            </div>
          </div>

          <div class="space-y-1">
            <h3 class="text-xl font-bold tracking-tight text-slate-900 dark:text-white">
              Session Inactivity Warning
            </h3>
            <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 max-w-xs mx-auto">
              No activity has been detected recently. To protect your CRM data, your session will automatically terminate.
            </p>
          </div>
        </div>

        <!-- Timer Countdown Card -->
        <div class="p-4 rounded-2xl bg-slate-50 dark:bg-slate-800/80 border border-slate-200/80 dark:border-slate-700/60 text-center space-y-3">
          <div class="text-xs font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-400">
            Auto-Logout in
          </div>
          <div class="font-mono text-4xl font-extrabold text-amber-600 dark:text-amber-400 tracking-tight">
            {{ formattedCountdown }}
          </div>

          <!-- Progress Bar -->
          <div class="w-full h-2 bg-slate-200 dark:bg-slate-700 rounded-full overflow-hidden">
            <div 
              class="h-full transition-all duration-1000 ease-linear rounded-full"
              :class="remainingSeconds <= 15 ? 'bg-red-600' : 'bg-amber-500'"
              :style="{ width: `${progressPercentage}%` }"
            ></div>
          </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex flex-col sm:flex-row items-center gap-3 pt-2">
          <button
            type="button"
            @click="stayLoggedIn"
            :disabled="isKeepingAlive || isLoggingOut"
            class="w-full sm:flex-1 py-3 px-4 bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800 text-white text-sm font-semibold rounded-xl shadow-lg shadow-indigo-600/25 flex items-center justify-center gap-2 transition cursor-pointer disabled:opacity-50"
          >
            <ArrowPathIcon v-if="isKeepingAlive" class="w-4 h-4 animate-spin" />
            <CheckCircleIcon v-else class="w-4 h-4" />
            <span>Stay Logged In</span>
          </button>

          <button
            type="button"
            @click="handleImmediateLogout"
            :disabled="isLoggingOut"
            class="w-full sm:w-auto py-3 px-4 bg-slate-100 hover:bg-red-50 dark:bg-slate-800 dark:hover:bg-red-950/40 text-slate-700 hover:text-red-600 dark:text-slate-300 dark:hover:text-red-400 text-sm font-medium rounded-xl border border-slate-200 dark:border-slate-700 hover:border-red-200 dark:hover:border-red-800/50 flex items-center justify-center gap-2 transition cursor-pointer disabled:opacity-50"
          >
            <ArrowRightOnRectangleIcon class="w-4 h-4" />
            <span>Logout Now</span>
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<style scoped>
@keyframes fadeIn {
  from {
    opacity: 0;
    transform: scale(0.98);
  }
  to {
    opacity: 1;
    transform: scale(1);
  }
}
.animate-fade-in {
  animation: fadeIn 0.2s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}
</style>
