<script setup>
import { ref } from 'vue';
import { 
  XMarkIcon, 
  ClockIcon, 
  ComputerDesktopIcon, 
  DevicePhoneMobileIcon, 
  ShieldCheckIcon,
  ArrowPathIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
  isOpen: {
    type: Boolean,
    default: false,
  }
});

const emit = defineEmits(['close']);

const loginSessions = ref([
  {
    id: 1,
    device: 'Chrome 127 on Windows 11 (Current Device)',
    type: 'desktop',
    ip_address: '127.0.0.1 (Localhost / Corporate Network)',
    location: 'New Delhi, India',
    login_time: 'Today, ' + new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }),
    status: 'Current Active Session',
    is_current: true
  },
  {
    id: 2,
    device: 'Mobile Safari on iPhone 15 Pro',
    type: 'mobile',
    ip_address: '103.212.144.18',
    location: 'Mumbai, Maharashtra',
    login_time: 'Yesterday, 06:45 PM',
    status: 'Logged Out',
    is_current: false
  },
  {
    id: 3,
    device: 'Chrome on macOS Sonoma',
    type: 'desktop',
    ip_address: '122.161.50.92',
    location: 'Bengaluru, Karnataka',
    login_time: '12 Aug 2026, 11:20 AM',
    status: 'Expired',
    is_current: false
  }
]);

const isRevoking = ref(false);
const revokedMessage = ref('');

const revokeOtherSessions = () => {
  isRevoking.value = true;
  setTimeout(() => {
    loginSessions.value = loginSessions.value.filter(s => s.is_current);
    isRevoking.value = false;
    revokedMessage.value = 'All other active device sessions have been securely terminated.';
    setTimeout(() => revokedMessage.value = '', 4000);
  }, 600);
};
</script>

<template>
  <div v-if="isOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs overflow-y-auto">
    <div class="bg-white border border-slate-200 rounded-3xl max-w-lg w-full shadow-2xl flex flex-col max-h-[90vh] overflow-y-auto my-auto">
      
      <!-- Modal Header -->
      <div class="p-6 border-b border-slate-100 flex items-center justify-between sticky top-0 bg-white z-10">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center font-black">
            <ClockIcon class="w-5 h-5 stroke-[2.5]" />
          </div>
          <div>
            <h3 class="text-base font-black text-slate-900">User Account Login History</h3>
            <p class="text-xs text-slate-500 font-medium">Review your recent login sessions and active security devices.</p>
          </div>
        </div>

        <button @click="emit('close')" class="text-slate-400 hover:text-slate-600 p-1 rounded-lg cursor-pointer">
          <XMarkIcon class="w-5 h-5 stroke-[2.5]" />
        </button>
      </div>

      <!-- Modal Body -->
      <div class="p-6 space-y-4">
        
        <!-- Status Alert -->
        <div v-if="revokedMessage" class="p-3 bg-emerald-50 border border-emerald-200 rounded-2xl text-emerald-800 text-xs font-bold flex items-center gap-2">
          <ShieldCheckIcon class="w-4 h-4 text-emerald-600 shrink-0" />
          <span>{{ revokedMessage }}</span>
        </div>

        <!-- Sessions List -->
        <div class="space-y-3">
          <div 
            v-for="session in loginSessions" 
            :key="session.id"
            :class="[
              'p-4 rounded-2xl border transition-all flex items-start justify-between gap-3',
              session.is_current ? 'bg-indigo-50/50 border-indigo-200' : 'bg-slate-50 border-slate-200'
            ]"
          >
            <div class="flex items-start gap-3">
              <div :class="['w-9 h-9 rounded-xl flex items-center justify-center shrink-0 font-bold', session.is_current ? 'bg-indigo-600 text-white' : 'bg-slate-200 text-slate-600']">
                <ComputerDesktopIcon v-if="session.type === 'desktop'" class="w-5 h-5" />
                <DevicePhoneMobileIcon v-else class="w-5 h-5" />
              </div>

              <div class="space-y-0.5">
                <div class="flex items-center gap-2">
                  <span class="text-xs font-black text-slate-900">{{ session.device }}</span>
                  <span v-if="session.is_current" class="px-2 py-0.5 bg-emerald-100 text-emerald-800 text-[9px] font-black rounded-full uppercase tracking-wider">
                    Current Active
                  </span>
                </div>

                <p class="text-[11px] text-slate-500 font-medium">{{ session.ip_address }} • {{ session.location }}</p>
                <p class="text-[10px] text-slate-400 font-semibold">{{ session.login_time }}</p>
              </div>
            </div>

            <div class="text-right shrink-0">
              <span :class="['text-[10px] font-extrabold px-2 py-0.5 rounded-full', session.is_current ? 'bg-indigo-100 text-indigo-700' : 'bg-slate-200 text-slate-600']">
                {{ session.status }}
              </span>
            </div>
          </div>
        </div>

      </div>

      <!-- Modal Footer -->
      <div class="p-6 border-t border-slate-100 flex items-center justify-between gap-3 bg-slate-50/50 rounded-b-3xl">
        <button 
          @click="revokeOtherSessions"
          :disabled="isRevoking || loginSessions.length <= 1"
          class="px-4 py-2 bg-white hover:bg-red-50 text-red-600 hover:text-red-700 font-bold text-xs rounded-xl border border-red-200 transition flex items-center gap-1.5 disabled:opacity-40 cursor-pointer"
        >
          <ArrowPathIcon v-if="isRevoking" class="w-3.5 h-3.5 animate-spin" />
          <span>{{ isRevoking ? 'Revoking...' : 'Terminate Other Sessions' }}</span>
        </button>

        <button 
          @click="emit('close')" 
          type="button" 
          class="px-5 py-2 bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs rounded-xl transition cursor-pointer"
        >
          Done
        </button>
      </div>

    </div>
  </div>
</template>
