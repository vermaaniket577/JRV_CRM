<script setup>
import { ref, computed } from 'vue';
import { usePage, useForm, Link } from '@inertiajs/vue3';
import { 
  ShieldCheckIcon, 
  CheckIcon, 
  XMarkIcon, 
  AdjustmentsVerticalIcon,
  LockClosedIcon
} from '@heroicons/vue/24/outline';

const page = usePage();
const isDismissed = ref(false);
const showCustomizer = ref(false);

const cookieSettings = computed(() => page.props.cookie_settings || {});
const shouldShowBanner = computed(() => {
  return !cookieSettings.value.consent_given && !isDismissed.value;
});

const consentForm = useForm({
  analytics: true,
  marketing: false,
  preferences: true,
});

const acceptAll = () => {
  consentForm.analytics = true;
  consentForm.marketing = true;
  consentForm.preferences = true;
  submitConsent();
};

const acceptEssentialOnly = () => {
  consentForm.analytics = false;
  consentForm.marketing = false;
  consentForm.preferences = true;
  submitConsent();
};

const saveCustomConsent = () => {
  submitConsent();
};

const submitConsent = () => {
  consentForm.post('/settings/session-cookies/consent', {
    preserveScroll: true,
    onSuccess: () => {
      isDismissed.value = true;
    }
  });
};
</script>

<template>
  <Transition
    enter-active-class="transition duration-300 ease-out"
    enter-from-class="transform translate-y-full opacity-0"
    enter-to-class="transform translate-y-0 opacity-100"
    leave-active-class="transition duration-200 ease-in"
    leave-from-class="transform translate-y-0 opacity-100"
    leave-to-class="transform translate-y-full opacity-0"
  >
    <div 
      v-if="shouldShowBanner" 
      class="fixed bottom-4 left-4 right-4 md:left-8 md:right-auto md:max-w-2xl z-50 bg-slate-900/95 backdrop-blur-md border border-slate-700/80 rounded-2xl shadow-2xl p-6 text-slate-100"
    >
      <div class="flex items-start space-x-4">
        <div class="p-3 bg-indigo-500/10 border border-indigo-500/20 rounded-xl text-indigo-400 shrink-0">
          <ShieldCheckIcon class="w-7 h-7" />
        </div>
        <div class="flex-1 space-y-2">
          <div class="flex items-center justify-between">
            <h3 class="text-base font-semibold text-white flex items-center gap-2">
              Privacy & Cookie Consent
              <span class="px-2 py-0.5 text-xs font-medium bg-indigo-500/20 text-indigo-300 rounded-full border border-indigo-500/30">GDPR Compliant</span>
            </h3>
            <button @click="isDismissed = true" class="text-slate-400 hover:text-slate-200 transition">
              <XMarkIcon class="w-5 h-5" />
            </button>
          </div>
          
          <p class="text-xs text-slate-300 leading-relaxed">
            JRV CRM uses cookies to keep your session secure, remember your workspace preferences, and analyze system performance. You can manage your preferences at any time in <Link href="/settings/session-cookies" class="text-indigo-400 hover:underline font-medium">Session & Cookie Settings</Link>.
          </p>

          <!-- Customizer Panel -->
          <div v-if="showCustomizer" class="mt-4 p-4 bg-slate-800/80 rounded-xl border border-slate-700 space-y-3">
            <div class="flex items-center justify-between text-xs">
              <div class="flex items-center gap-2">
                <LockClosedIcon class="w-4 h-4 text-emerald-400" />
                <span class="font-medium text-white">Essential & Security Cookies</span>
              </div>
              <span class="text-slate-400 font-mono text-[10px] bg-slate-700 px-2 py-0.5 rounded">Always Active</span>
            </div>
            
            <div class="flex items-center justify-between text-xs pt-2 border-t border-slate-700/60">
              <div>
                <span class="font-medium text-white">Workspace Preferences</span>
                <p class="text-[11px] text-slate-400">Remember dark mode & sidebar state</p>
              </div>
              <input type="checkbox" v-model="consentForm.preferences" class="rounded bg-slate-900 border-slate-600 text-indigo-500 focus:ring-indigo-500" />
            </div>

            <div class="flex items-center justify-between text-xs pt-2 border-t border-slate-700/60">
              <div>
                <span class="font-medium text-white">Performance Analytics</span>
                <p class="text-[11px] text-slate-400">Help us improve loading times</p>
              </div>
              <input type="checkbox" v-model="consentForm.analytics" class="rounded bg-slate-900 border-slate-600 text-indigo-500 focus:ring-indigo-500" />
            </div>

            <div class="flex items-center justify-between text-xs pt-2 border-t border-slate-700/60">
              <div>
                <span class="font-medium text-white">Marketing & Communications</span>
                <p class="text-[11px] text-slate-400">Personalized CRM feature updates</p>
              </div>
              <input type="checkbox" v-model="consentForm.marketing" class="rounded bg-slate-900 border-slate-600 text-indigo-500 focus:ring-indigo-500" />
            </div>

            <div class="pt-2 flex justify-end gap-2">
              <button 
                @click="saveCustomConsent" 
                :disabled="consentForm.processing"
                class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-500 text-white font-medium text-xs rounded-lg transition"
              >
                Save Preferences
              </button>
            </div>
          </div>

          <!-- Action Buttons -->
          <div class="pt-3 flex flex-wrap items-center gap-2">
            <button 
              @click="acceptAll" 
              :disabled="consentForm.processing"
              class="px-4 py-2 bg-indigo-600 hover:bg-indigo-500 text-white font-medium text-xs rounded-xl shadow-lg shadow-indigo-600/20 transition flex items-center gap-1.5"
            >
              <CheckIcon class="w-4 h-4" />
              Accept All Cookies
            </button>
            <button 
              @click="acceptEssentialOnly" 
              :disabled="consentForm.processing"
              class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-200 font-medium text-xs rounded-xl border border-slate-700 transition"
            >
              Essential Only
            </button>
            <button 
              @click="showCustomizer = !showCustomizer" 
              class="px-3 py-2 text-slate-400 hover:text-slate-200 font-medium text-xs transition flex items-center gap-1"
            >
              <AdjustmentsVerticalIcon class="w-4 h-4" />
              {{ showCustomizer ? 'Hide Details' : 'Customize' }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </Transition>
</template>
