<script setup>
import { ref } from 'vue';
import { useForm, Head, router } from '@inertiajs/vue3';
import Navbar from '@/Components/Navbar.vue';
import { 
  KeyIcon, 
  ShieldCheckIcon, 
  ComputerDesktopIcon, 
  DevicePhoneMobileIcon, 
  ClockIcon, 
  GlobeAltIcon, 
  TrashIcon, 
  ArrowPathIcon,
  CheckCircleIcon,
  AdjustmentsHorizontalIcon,
  EyeIcon,
  SunIcon,
  MoonIcon,
  SparklesIcon,
  ExclamationTriangleIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
  sessions: {
    type: Array,
    default: () => []
  },
  active_cookies: {
    type: Array,
    default: () => []
  },
  cookie_consent: {
    type: Object,
    default: () => ({})
  },
  session_config: {
    type: Object,
    default: () => ({})
  }
});

// Forms
const consentForm = useForm({
  analytics: props.cookie_consent.analytics ?? true,
  marketing: props.cookie_consent.marketing ?? false,
  preferences: props.cookie_consent.preferences ?? true,
});

const preferenceForm = useForm({
  theme: props.cookie_consent.theme ?? 'dark',
  sidebar_collapsed: props.cookie_consent.sidebar_collapsed ?? false,
  items_per_page: props.cookie_consent.items_per_page ?? 15,
});

const revokeSessionsForm = useForm({});
const clearCookiesForm = useForm({});

const saveConsent = () => {
  consentForm.post('/settings/session-cookies/consent', {
    preserveScroll: true,
  });
};

const savePreferences = () => {
  preferenceForm.post('/settings/session-cookies/preferences', {
    preserveScroll: true,
  });
};

const revokeOtherSessions = () => {
  if (confirm('Are you sure you want to revoke all other active browser sessions?')) {
    revokeSessionsForm.post('/settings/session-cookies/revoke-sessions', {
      preserveScroll: true,
    });
  }
};

const terminateSession = (id) => {
  if (confirm('Terminate this active device session? The user on that device will be signed out.')) {
    router.delete(`/settings/session-cookies/session/${id}`, {
      preserveScroll: true,
    });
  }
};

const clearNonEssentialCookies = () => {
  if (confirm('Clear all non-essential preference and tracking cookies stored by JRV CRM?')) {
    clearCookiesForm.post('/settings/session-cookies/clear-cookies', {
      preserveScroll: true,
    });
  }
};

const activeTab = ref('sessions');

// Inactivity Auto-Logout Management
const savedIdleTimeout = localStorage.getItem('crm_idle_timeout_minutes') || '15';
const idleTimeoutMinutes = ref(savedIdleTimeout);
const idleSuccessMsg = ref('');

const updateIdleTimeout = (mins) => {
  idleTimeoutMinutes.value = mins;
  localStorage.setItem('crm_idle_timeout_minutes', mins);
  idleSuccessMsg.value = `Inactivity timeout updated to ${mins === 'disabled' ? 'Disabled' : mins + ' minutes'}.`;
  setTimeout(() => idleSuccessMsg.value = '', 3500);
};

const triggerIdlePreview = () => {
  window.dispatchEvent(new CustomEvent('crm:preview-idle-modal'));
};
</script>

<template>
  <Head title="Session & Cookie Management - JRV CRM" />

  <div class="min-h-screen bg-slate-950 text-slate-100 flex flex-col font-sans">
    <Navbar />

    <main class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">
      
      <!-- Top Title Banner -->
      <div class="bg-gradient-to-r from-slate-900 via-indigo-950/40 to-slate-900 p-6 md:p-8 rounded-3xl border border-slate-800 shadow-xl flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div class="space-y-2">
          <div class="flex items-center gap-3">
            <div class="p-3 bg-indigo-500/10 border border-indigo-500/20 rounded-2xl text-indigo-400">
              <KeyIcon class="w-8 h-8" />
            </div>
            <div>
              <h1 class="text-2xl md:text-3xl font-bold text-white tracking-tight flex items-center gap-3">
                Session & Cookie Control Center
              </h1>
              <p class="text-sm text-slate-400">
                Manage active browser sessions, security cookies, privacy consent preferences, and client UI persistence.
              </p>
            </div>
          </div>
        </div>

        <!-- Quick Summary Stats -->
        <div class="flex items-center gap-3 bg-slate-900/80 p-3 rounded-2xl border border-slate-800">
          <div class="text-center px-4 py-1 border-r border-slate-800">
            <span class="text-xs text-slate-400 block">Active Sessions</span>
            <span class="text-lg font-bold text-indigo-400">{{ sessions.length }}</span>
          </div>
          <div class="text-center px-4 py-1 border-r border-slate-800">
            <span class="text-xs text-slate-400 block">Active Cookies</span>
            <span class="text-lg font-bold text-emerald-400">{{ active_cookies.length }}</span>
          </div>
          <div class="text-center px-4 py-1">
            <span class="text-xs text-slate-400 block">Session Driver</span>
            <span class="text-sm font-semibold text-slate-200 uppercase font-mono">{{ session_config.driver }}</span>
          </div>
        </div>
      </div>

      <!-- Navigation Tabs -->
      <div class="flex items-center space-x-2 border-b border-slate-800 pb-2">
        <button 
          @click="activeTab = 'sessions'" 
          :class="[
            'px-5 py-2.5 rounded-xl font-medium text-sm transition flex items-center gap-2',
            activeTab === 'sessions' 
              ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/30' 
              : 'text-slate-400 hover:text-slate-200 hover:bg-slate-900'
          ]"
        >
          <ComputerDesktopIcon class="w-4 h-4" />
          Active Sessions ({{ sessions.length }})
        </button>
        <button 
          @click="activeTab = 'cookies'" 
          :class="[
            'px-5 py-2.5 rounded-xl font-medium text-sm transition flex items-center gap-2',
            activeTab === 'cookies' 
              ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/30' 
              : 'text-slate-400 hover:text-slate-200 hover:bg-slate-900'
          ]"
        >
          <ShieldCheckIcon class="w-4 h-4" />
          Cookie Inspector & Privacy ({{ active_cookies.length }})
        </button>
        <button 
          @click="activeTab = 'preferences'" 
          :class="[
            'px-5 py-2.5 rounded-xl font-medium text-sm transition flex items-center gap-2',
            activeTab === 'preferences' 
              ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/30' 
              : 'text-slate-400 hover:text-slate-200 hover:bg-slate-900'
          ]"
        >
          <AdjustmentsHorizontalIcon class="w-4 h-4" />
          UI Cookie Preferences
        </button>
        <button 
          @click="activeTab = 'inactivity'" 
          :class="[
            'px-5 py-2.5 rounded-xl font-medium text-sm transition flex items-center gap-2',
            activeTab === 'inactivity' 
              ? 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/30' 
              : 'text-slate-400 hover:text-slate-200 hover:bg-slate-900'
          ]"
        >
          <ClockIcon class="w-4 h-4" />
          Inactivity Security
        </button>
      </div>

      <!-- Tab 1: Active Sessions -->
      <div v-if="activeTab === 'sessions'" class="space-y-6">
        <div class="flex items-center justify-between">
          <div>
            <h2 class="text-lg font-semibold text-white">Browser Sessions</h2>
            <p class="text-xs text-slate-400">Review devices and browsers logged into your CRM account.</p>
          </div>
          <button 
            @click="revokeOtherSessions"
            :disabled="revokeSessionsForm.processing || sessions.length <= 1"
            class="px-4 py-2 bg-red-600/10 hover:bg-red-600/20 text-red-400 border border-red-500/20 rounded-xl text-xs font-semibold transition flex items-center gap-2 disabled:opacity-50"
          >
            <TrashIcon class="w-4 h-4" />
            Revoke All Other Sessions
          </button>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div 
            v-for="s in sessions" 
            :key="s.id"
            :class="[
              'p-5 rounded-2xl border transition relative space-y-4',
              s.is_current 
                ? 'bg-slate-900/90 border-indigo-500/40 ring-1 ring-indigo-500/30' 
                : 'bg-slate-900/50 border-slate-800 hover:border-slate-700'
            ]"
          >
            <div class="flex items-start justify-between">
              <div class="flex items-center gap-3">
                <div class="p-3 bg-slate-800 rounded-xl text-slate-300">
                  <ComputerDesktopIcon v-if="s.device_type === 'Desktop'" class="w-6 h-6 text-indigo-400" />
                  <DevicePhoneMobileIcon v-else class="w-6 h-6 text-emerald-400" />
                </div>
                <div>
                  <h3 class="font-semibold text-sm text-white flex items-center gap-2">
                    {{ s.browser }} on {{ s.platform }}
                    <span 
                      v-if="s.is_current" 
                      class="px-2 py-0.5 text-[10px] font-bold bg-indigo-500/20 text-indigo-300 border border-indigo-500/30 rounded-full"
                    >
                      THIS DEVICE
                    </span>
                  </h3>
                  <p class="text-xs text-slate-400 font-mono mt-0.5">IP: {{ s.ip_address }}</p>
                </div>
              </div>

              <button
                v-if="!s.is_current"
                @click="terminateSession(s.id)"
                class="px-2.5 py-1 bg-red-500/10 hover:bg-red-500/20 text-red-400 border border-red-500/20 rounded-lg text-xs font-semibold transition flex items-center gap-1 cursor-pointer shrink-0"
                title="Revoke this device session"
              >
                <TrashIcon class="w-3.5 h-3.5" />
                <span>Terminate</span>
              </button>
            </div>

            <div class="grid grid-cols-2 gap-2 text-xs pt-3 border-t border-slate-800/80 text-slate-400">
              <div>
                <span class="block text-[10px] text-slate-500 uppercase">Last Activity</span>
                <span class="text-slate-300 font-medium flex items-center gap-1">
                  <ClockIcon class="w-3.5 h-3.5 text-indigo-400" />
                  {{ s.last_activity_human }}
                </span>
              </div>
              <div>
                <span class="block text-[10px] text-slate-500 uppercase">Session Hash</span>
                <span class="text-slate-300 font-mono text-[11px]">{{ s.id_preview }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Tab 2: Cookies & Privacy -->
      <div v-if="activeTab === 'cookies'" class="space-y-8">
        
        <!-- GDPR Cookie Consent Settings Card -->
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 space-y-6">
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
              <div class="p-2.5 bg-indigo-500/10 text-indigo-400 rounded-xl">
                <ShieldCheckIcon class="w-6 h-6" />
              </div>
              <div>
                <h3 class="text-base font-semibold text-white">Privacy & Cookie Consent Preferences</h3>
                <p class="text-xs text-slate-400">Manage how cookies are used for security, user experience, and analytics.</p>
              </div>
            </div>
            <button 
              @click="saveConsent" 
              :disabled="consentForm.processing"
              class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white font-medium text-xs rounded-xl shadow-lg shadow-indigo-600/20 transition flex items-center gap-2"
            >
              <CheckCircleIcon class="w-4 h-4" />
              Save Consent Preferences
            </button>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="p-4 bg-slate-950/60 border border-slate-800 rounded-xl space-y-2">
              <div class="flex items-center justify-between">
                <span class="text-sm font-semibold text-white">Essential Cookies</span>
                <span class="text-[10px] bg-emerald-500/20 text-emerald-300 px-2 py-0.5 rounded font-mono">REQUIRED</span>
              </div>
              <p class="text-xs text-slate-400">Session authentication, CSRF tokens, and security features.</p>
            </div>

            <div class="p-4 bg-slate-950/60 border border-slate-800 rounded-xl space-y-2">
              <div class="flex items-center justify-between">
                <span class="text-sm font-semibold text-white">Analytics Cookies</span>
                <input type="checkbox" v-model="consentForm.analytics" class="rounded bg-slate-900 border-slate-700 text-indigo-600 focus:ring-indigo-500" />
              </div>
              <p class="text-xs text-slate-400">Aggregated system usage statistics to improve CRM performance.</p>
            </div>

            <div class="p-4 bg-slate-950/60 border border-slate-800 rounded-xl space-y-2">
              <div class="flex items-center justify-between">
                <span class="text-sm font-semibold text-white">Marketing & Updates</span>
                <input type="checkbox" v-model="consentForm.marketing" class="rounded bg-slate-900 border-slate-700 text-indigo-600 focus:ring-indigo-500" />
              </div>
              <p class="text-xs text-slate-400">Personalized feature announcements and SaaS upgrades.</p>
            </div>
          </div>
        </div>

        <!-- Cookie Inspector Table -->
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 space-y-4">
          <div class="flex items-center justify-between">
            <div>
              <h3 class="text-base font-semibold text-white">Active Application HTTP Cookies</h3>
              <p class="text-xs text-slate-400">Live inspection of all cookies transmitted by your client browser.</p>
            </div>
            <button 
              @click="clearNonEssentialCookies"
              :disabled="clearCookiesForm.processing"
              class="px-3 py-1.5 bg-slate-800 hover:bg-slate-700 text-slate-300 border border-slate-700 rounded-xl text-xs font-medium transition"
            >
              Clear Non-Essential Cookies
            </button>
          </div>

          <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-300">
              <thead class="bg-slate-950/80 text-slate-400 uppercase text-[10px] tracking-wider border-b border-slate-800">
                <tr>
                  <th class="px-4 py-3">Cookie Name</th>
                  <th class="px-4 py-3">Category</th>
                  <th class="px-4 py-3">Sample Value</th>
                  <th class="px-4 py-3">Security Flags</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-800/60">
                <tr v-for="c in active_cookies" :key="c.name" class="hover:bg-slate-800/40">
                  <td class="px-4 py-3 font-mono font-semibold text-indigo-300">{{ c.name }}</td>
                  <td class="px-4 py-3">
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-medium bg-slate-800 text-slate-300 border border-slate-700">
                      {{ c.category }}
                    </span>
                  </td>
                  <td class="px-4 py-3 font-mono text-slate-400 max-w-xs truncate">{{ c.value_preview }}</td>
                  <td class="px-4 py-3 space-x-1">
                    <span v-if="c.is_httponly" class="px-1.5 py-0.5 bg-purple-500/20 text-purple-300 text-[9px] rounded font-mono">HttpOnly</span>
                    <span v-if="c.is_secure" class="px-1.5 py-0.5 bg-emerald-500/20 text-emerald-300 text-[9px] rounded font-mono">Secure</span>
                    <span v-if="!c.is_httponly && !c.is_secure" class="text-slate-500 text-[10px]">Client Accessible</span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Tab 3: UI Preference Cookies -->
      <div v-if="activeTab === 'preferences'" class="bg-slate-900 border border-slate-800 rounded-2xl p-6 space-y-6">
        <div class="flex items-center justify-between">
          <div>
            <h2 class="text-lg font-semibold text-white">Client UI Cookie Persistence</h2>
            <p class="text-xs text-slate-400">Configure client-side application settings saved directly into HTTP cookies.</p>
          </div>
          <button 
            @click="savePreferences" 
            :disabled="preferenceForm.processing"
            class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white font-medium text-xs rounded-xl shadow-lg shadow-indigo-600/20 transition flex items-center gap-2"
          >
            <CheckCircleIcon class="w-4 h-4" />
            Save Preferences
          </button>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div class="space-y-2">
            <label class="block text-xs font-semibold text-slate-300">Theme Preference (Cookie: `crm_theme`)</label>
            <div class="grid grid-cols-3 gap-3">
              <button 
                type="button"
                @click="preferenceForm.theme = 'dark'"
                :class="[
                  'p-3 rounded-xl border text-xs font-medium flex items-center justify-center gap-2 transition',
                  preferenceForm.theme === 'dark' ? 'bg-indigo-600/20 border-indigo-500 text-indigo-300' : 'bg-slate-950 border-slate-800 text-slate-400'
                ]"
              >
                <MoonIcon class="w-4 h-4" /> Dark Mode
              </button>
              <button 
                type="button"
                @click="preferenceForm.theme = 'light'"
                :class="[
                  'p-3 rounded-xl border text-xs font-medium flex items-center justify-center gap-2 transition',
                  preferenceForm.theme === 'light' ? 'bg-indigo-600/20 border-indigo-500 text-indigo-300' : 'bg-slate-950 border-slate-800 text-slate-400'
                ]"
              >
                <SunIcon class="w-4 h-4" /> Light Mode
              </button>
              <button 
                type="button"
                @click="preferenceForm.theme = 'system'"
                :class="[
                  'p-3 rounded-xl border text-xs font-medium flex items-center justify-center gap-2 transition',
                  preferenceForm.theme === 'system' ? 'bg-indigo-600/20 border-indigo-500 text-indigo-300' : 'bg-slate-950 border-slate-800 text-slate-400'
                ]"
              >
                <SparklesIcon class="w-4 h-4" /> Auto System
              </button>
            </div>
          </div>

          <div class="space-y-2">
            <label class="block text-xs font-semibold text-slate-300">Pagination Size (Cookie: `crm_items_per_page`)</label>
            <select v-model="preferenceForm.items_per_page" class="w-full bg-slate-950 border border-slate-800 rounded-xl px-4 py-2.5 text-xs text-white focus:ring-indigo-500">
              <option :value="10">10 Items per page</option>
              <option :value="15">15 Items per page</option>
              <option :value="25">25 Items per page</option>
              <option :value="50">50 Items per page</option>
            </select>
          </div>
        </div>
      </div>

      <!-- Tab 4: Inactivity & Auto-Logout Security -->
      <div v-if="activeTab === 'inactivity'" class="space-y-6">
        <div class="flex items-center justify-between">
          <div>
            <h2 class="text-lg font-semibold text-white flex items-center gap-2">
              <span>Automatic Inactivity Logout</span>
              <span class="px-2.5 py-0.5 text-[10px] font-bold bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 rounded-full">
                Active Guard
              </span>
            </h2>
            <p class="text-xs text-slate-400">
              Configure automatic session termination when there is no detected user activity in the CRM.
            </p>
          </div>

          <button
            type="button"
            @click="triggerIdlePreview"
            class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-semibold transition flex items-center gap-2 shadow cursor-pointer"
          >
            <ClockIcon class="w-4 h-4" />
            <span>Preview Inactivity Modal</span>
          </button>
        </div>

        <div v-if="idleSuccessMsg" class="p-3 bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 text-xs rounded-xl flex items-center gap-2">
          <CheckCircleIcon class="w-4 h-4 shrink-0" />
          <span>{{ idleSuccessMsg }}</span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
          <!-- Timeout Duration Configuration -->
          <div class="md:col-span-2 bg-slate-900 border border-slate-800 p-6 rounded-2xl space-y-5">
            <h3 class="text-sm font-semibold text-white">Inactivity Idle Duration</h3>
            <p class="text-xs text-slate-400 leading-relaxed">
              If no mouse movement, keyboard typing, clicks, or scrolling are detected within this period, a 60-second warning countdown will appear before securely logging out.
            </p>

            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
              <button
                type="button"
                @click="updateIdleTimeout('5')"
                :class="[
                  'p-4 rounded-xl border text-center transition space-y-1 cursor-pointer',
                  idleTimeoutMinutes === '5'
                    ? 'bg-indigo-600/20 border-indigo-500 text-indigo-300 ring-1 ring-indigo-500/50'
                    : 'bg-slate-950 border-slate-800 text-slate-400 hover:border-slate-700'
                ]"
              >
                <div class="text-lg font-bold">5 Mins</div>
                <div class="text-[10px] text-slate-400">High Security</div>
              </button>

              <button
                type="button"
                @click="updateIdleTimeout('15')"
                :class="[
                  'p-4 rounded-xl border text-center transition space-y-1 cursor-pointer relative',
                  idleTimeoutMinutes === '15'
                    ? 'bg-indigo-600/20 border-indigo-500 text-indigo-300 ring-1 ring-indigo-500/50'
                    : 'bg-slate-950 border-slate-800 text-slate-400 hover:border-slate-700'
                ]"
              >
                <span class="absolute -top-2 right-2 px-1.5 py-0.2 bg-indigo-500 text-white text-[9px] rounded-full font-bold">Default</span>
                <div class="text-lg font-bold">15 Mins</div>
                <div class="text-[10px] text-slate-400">Recommended</div>
              </button>

              <button
                type="button"
                @click="updateIdleTimeout('30')"
                :class="[
                  'p-4 rounded-xl border text-center transition space-y-1 cursor-pointer',
                  idleTimeoutMinutes === '30'
                    ? 'bg-indigo-600/20 border-indigo-500 text-indigo-300 ring-1 ring-indigo-500/50'
                    : 'bg-slate-950 border-slate-800 text-slate-400 hover:border-slate-700'
                ]"
              >
                <div class="text-lg font-bold">30 Mins</div>
                <div class="text-[10px] text-slate-400">Standard Office</div>
              </button>

              <button
                type="button"
                @click="updateIdleTimeout('60')"
                :class="[
                  'p-4 rounded-xl border text-center transition space-y-1 cursor-pointer',
                  idleTimeoutMinutes === '60'
                    ? 'bg-indigo-600/20 border-indigo-500 text-indigo-300 ring-1 ring-indigo-500/50'
                    : 'bg-slate-950 border-slate-800 text-slate-400 hover:border-slate-700'
                ]"
              >
                <div class="text-lg font-bold">60 Mins</div>
                <div class="text-[10px] text-slate-400">Extended</div>
              </button>
            </div>

            <div class="pt-2 border-t border-slate-800 flex items-center justify-between">
              <div>
                <div class="text-xs font-semibold text-slate-300">Disable Auto-Logout</div>
                <p class="text-[11px] text-slate-500">Not recommended for shared or public computers.</p>
              </div>
              <button
                type="button"
                @click="updateIdleTimeout(idleTimeoutMinutes === 'disabled' ? '15' : 'disabled')"
                :class="[
                  'px-3 py-1.5 rounded-lg text-xs font-medium border transition cursor-pointer',
                  idleTimeoutMinutes === 'disabled'
                    ? 'bg-red-600/20 text-red-400 border-red-500/40'
                    : 'bg-slate-800 text-slate-400 border-slate-700 hover:text-white'
                ]"
              >
                {{ idleTimeoutMinutes === 'disabled' ? 'Disabled (Click to Enable)' : 'Disable Auto-Logout' }}
              </button>
            </div>
          </div>

          <!-- Security Status Overview -->
          <div class="bg-slate-900 border border-slate-800 p-6 rounded-2xl space-y-4">
            <h3 class="text-sm font-semibold text-white flex items-center gap-2">
              <ShieldCheckIcon class="w-4 h-4 text-emerald-400" />
              <span>Inactivity Guard Status</span>
            </h3>

            <div class="space-y-3 text-xs">
              <div class="flex items-center justify-between p-2.5 bg-slate-950 rounded-xl border border-slate-800/80">
                <span class="text-slate-400">Status</span>
                <span class="font-semibold" :class="idleTimeoutMinutes !== 'disabled' ? 'text-emerald-400' : 'text-red-400'">
                  {{ idleTimeoutMinutes !== 'disabled' ? 'Active' : 'Disabled' }}
                </span>
              </div>

              <div class="flex items-center justify-between p-2.5 bg-slate-950 rounded-xl border border-slate-800/80">
                <span class="text-slate-400">Warning Window</span>
                <span class="font-mono text-amber-400 font-semibold">60 seconds</span>
              </div>

              <div class="flex items-center justify-between p-2.5 bg-slate-950 rounded-xl border border-slate-800/80">
                <span class="text-slate-400">Multi-Tab Sync</span>
                <span class="font-semibold text-indigo-400">Enabled</span>
              </div>

              <div class="flex items-center justify-between p-2.5 bg-slate-950 rounded-xl border border-slate-800/80">
                <span class="text-slate-400">Heartbeat Ping</span>
                <span class="font-semibold text-emerald-400">/keep-alive</span>
              </div>
            </div>

            <p class="text-[11px] text-slate-500 leading-normal pt-2">
              Activity across all tabs for your user session resets the timer synchronously.
            </p>
          </div>
        </div>
      </div>

    </main>
  </div>
</template>
