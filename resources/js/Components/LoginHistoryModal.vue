<script setup>
import { ref, watch } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import { 
  XMarkIcon, 
  ClockIcon, 
  ComputerDesktopIcon, 
  DevicePhoneMobileIcon, 
  ShieldCheckIcon,
  ArrowPathIcon,
  TrashIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
  isOpen: {
    type: Boolean,
    default: false,
  }
});

const emit = defineEmits(['close']);

const loginSessions = ref([]);
const isLoading = ref(false);
const isRevoking = ref(false);
const revokedMessage = ref('');

const fetchSessions = async () => {
  isLoading.value = true;
  try {
    const res = await fetch('/settings/session-cookies/active-sessions');
    if (res.ok) {
      const data = await res.json();
      if (data.sessions && data.sessions.length > 0) {
        loginSessions.value = data.sessions;
      }
    }
  } catch (e) {
    console.error('Could not fetch active sessions:', e);
  } finally {
    isLoading.value = false;
  }
};

watch(() => props.isOpen, (newVal) => {
  if (newVal) {
    fetchSessions();
  }
});

const revokeOtherSessions = () => {
  if (!confirm('Are you sure you want to revoke all other active sessions?')) return;
  isRevoking.value = true;
  router.post('/settings/session-cookies/revoke-sessions', {}, {
    preserveScroll: true,
    onSuccess: () => {
      fetchSessions();
      isRevoking.value = false;
      revokedMessage.value = 'All other active device sessions have been securely terminated.';
      setTimeout(() => revokedMessage.value = '', 4000);
    },
    onError: () => {
      isRevoking.value = false;
    }
  });
};

const terminateSession = (id) => {
  if (!confirm('Terminate this device session?')) return;
  router.delete(`/settings/session-cookies/session/${id}`, {
    preserveScroll: true,
    onSuccess: () => {
      fetchSessions();
      revokedMessage.value = 'Device session terminated.';
      setTimeout(() => revokedMessage.value = '', 4000);
    }
  });
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
            <h3 class="text-base font-black text-slate-900">Active Sessions & Login Security</h3>
            <p class="text-xs text-slate-500 font-medium">Real-time devices and active browser sessions logged into JRV CRM.</p>
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

        <!-- Loading State -->
        <div v-if="isLoading && loginSessions.length === 0" class="py-8 text-center text-slate-400 text-xs flex flex-col items-center gap-2">
          <ArrowPathIcon class="w-6 h-6 animate-spin text-indigo-500" />
          <span>Retrieving active database sessions...</span>
        </div>

        <!-- Sessions List -->
        <div v-else class="space-y-3">
          <div 
            v-for="session in loginSessions" 
            :key="session.id"
            :class="[
              'p-4 rounded-2xl border transition-all flex items-start justify-between gap-3',
              session.is_current ? 'bg-indigo-50/50 border-indigo-200' : 'bg-slate-50 border-slate-200'
            ]"
          >
            <div class="flex items-start gap-3 min-w-0">
              <div :class="['w-9 h-9 rounded-xl flex items-center justify-center shrink-0 font-bold', session.is_current ? 'bg-indigo-600 text-white' : 'bg-slate-200 text-slate-600']">
                <ComputerDesktopIcon v-if="session.device_type === 'Desktop'" class="w-5 h-5" />
                <DevicePhoneMobileIcon v-else class="w-5 h-5" />
              </div>

              <div class="space-y-0.5 min-w-0">
                <div class="flex items-center gap-2 flex-wrap">
                  <span class="text-xs font-black text-slate-900 truncate">{{ session.browser }} on {{ session.platform }}</span>
                  <span v-if="session.is_current" class="px-2 py-0.5 bg-emerald-100 text-emerald-800 text-[9px] font-black rounded-full uppercase tracking-wider">
                    Current Device
                  </span>
                </div>

                <p class="text-[11px] text-slate-500 font-medium font-mono">IP: {{ session.ip_address }}</p>
                <p class="text-[10px] text-slate-400 font-semibold flex items-center gap-1">
                  <ClockIcon class="w-3 h-3 text-slate-400" />
                  <span>Last active: {{ session.last_activity_human || session.last_activity }}</span>
                </p>
              </div>
            </div>

            <div class="text-right shrink-0 flex flex-col items-end gap-1.5">
              <span :class="['text-[10px] font-extrabold px-2 py-0.5 rounded-full', session.is_current ? 'bg-indigo-100 text-indigo-700' : 'bg-slate-200 text-slate-600']">
                {{ session.is_current ? 'Active' : 'Remote Session' }}
              </span>

              <button 
                v-if="!session.is_current"
                @click="terminateSession(session.id)"
                class="text-[10px] text-red-600 hover:text-red-800 font-bold hover:underline flex items-center gap-0.5 cursor-pointer"
                title="Terminate this session"
              >
                <TrashIcon class="w-3 h-3" />
                <span>End</span>
              </button>
            </div>
          </div>
        </div>

        <!-- Quick link to full manager -->
        <div class="pt-2 text-center">
          <Link 
            href="/settings/session-cookies" 
            @click="emit('close')"
            class="text-xs text-indigo-600 hover:text-indigo-800 font-bold inline-flex items-center gap-1 hover:underline"
          >
            <ShieldCheckIcon class="w-4 h-4" />
            <span>Manage All Cookies & Privacy Preferences →</span>
          </Link>
        </div>

      </div>

      <!-- Modal Footer -->
      <div class="p-6 border-t border-slate-100 flex items-center justify-between gap-3 bg-slate-50/50 rounded-b-3xl">
        <button 
          @click="revokeOtherSessions"
          :disabled="isRevoking || loginSessions.length <= 1"
          class="px-4 py-2 bg-white hover:bg-red-50 text-red-600 hover:text-red-700 font-bold text-xs rounded-xl border border-red-200 transition flex items-center gap-1.5 disabled:opacity-40 cursor-pointer shadow-2xs"
        >
          <ArrowPathIcon v-if="isRevoking" class="w-3.5 h-3.5 animate-spin" />
          <span>{{ isRevoking ? 'Revoking...' : 'Terminate Other Sessions' }}</span>
        </button>

        <button 
          @click="emit('close')" 
          type="button" 
          class="px-5 py-2 bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs rounded-xl transition cursor-pointer shadow-sm"
        >
          Done
        </button>
      </div>

    </div>
  </div>
</template>
