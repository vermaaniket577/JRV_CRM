<script setup>
import { ref, computed } from 'vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import Navbar from '@/Components/Navbar.vue';
import GlobalSearchModal from '@/Components/GlobalSearchModal.vue';
import { 
  BellIcon, 
  HomeIcon, 
  UserIcon, 
  SignalIcon, 
  DocumentTextIcon, 
  ArrowPathIcon,
  AcademicCapIcon,
  BriefcaseIcon,
  UserGroupIcon,
  GlobeAltIcon,
  AdjustmentsHorizontalIcon,
  EnvelopeIcon,
  PaperAirplaneIcon,
  CheckCircleIcon,
  ServerIcon,
  KeyIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
  settings: Object,
  logs: Object,
});

const page = usePage();
const customNav = computed(() => page.props.custom_nav || {});
const businessSettings = computed(() => page.props.business_settings || { business_name: 'JRV CRM', business_icon: '♥' });

const getNavLabel = (key, fallback) => customNav.value[key]?.label || fallback;
const getNavRoute = (key, fallback) => customNav.value[key]?.route || fallback;

const isSearchOpen = ref(false);

const form = useForm({
  official_email: props.settings.official_email || 'info@jainshadimilan.com',
  sender_name: props.settings.sender_name || 'JRV Matrimonial Team',
  mail_driver: props.settings.mail_driver || 'smtp',
  mail_host: props.settings.mail_host || 'smtp.gmail.com',
  mail_port: props.settings.mail_port || 587,
  mail_encryption: props.settings.mail_encryption || 'tls',
  mail_username: props.settings.mail_username || 'info@jainshadimilan.com',
  mail_password: '',
});

const testForm = useForm({
  test_email: 'admin@acme-saas.com',
});

const submitSettings = () => {
  form.post('/tenant/settings/mail', { preserveScroll: true });
};

const sendTest = () => {
  testForm.post('/tenant/settings/mail/send-test', { preserveScroll: true });
};
</script>

<template>
  <Head title="Official Mail ID & SMTP Integration Settings - JRV CRM" />

  <div class="min-h-screen bg-slate-100 flex font-sans text-slate-900">
    <!-- Navbar (Fixed Left Vertical Sidebar) -->
    <Navbar @open-search="isSearchOpen = true" />

    <!-- Main Content Area -->
    <div class="flex-1 flex flex-col min-w-0">
      <header class="bg-white border-b border-slate-200 px-6 py-3.5 flex flex-wrap items-center justify-between gap-3 shadow-xs">
        <div class="flex flex-wrap items-center gap-2">
          <button class="px-4 py-1.5 bg-red-600 text-white font-extrabold text-xs rounded-xl shadow-xs">My Work</button>
          <button class="px-4 py-1.5 bg-red-600 text-white font-extrabold text-xs rounded-xl shadow-xs">Login History</button>
          <button class="px-4 py-1.5 bg-red-600 text-white font-extrabold text-xs rounded-xl shadow-xs">My Task</button>
          <Link href="/tenant/settings/navigation" class="px-4 py-1.5 bg-indigo-600 text-white font-extrabold text-xs rounded-xl shadow-xs flex items-center gap-1.5">
            <AdjustmentsHorizontalIcon class="w-4 h-4" />
            <span>Customize Brand & Menu</span>
          </Link>
        </div>

        <div class="flex items-center gap-3">
          <div class="w-9 h-9 bg-red-600 rounded-full flex items-center justify-center text-white font-black text-sm shadow-md">
            {{ businessSettings.business_icon }}
          </div>
        </div>
      </header>

      <div class="p-8 space-y-6 flex-1 overflow-y-auto max-w-7xl mx-auto w-full">
        <div class="border-b border-slate-200 pb-6">
          <div class="flex items-center gap-2 text-xs font-bold text-red-600 uppercase tracking-wider">
            <EnvelopeIcon class="w-4 h-4" />
            <span>Official Email Settings</span>
          </div>
          <h1 class="text-3xl font-black tracking-tight text-slate-900 mt-0.5">
            Official Mail ID & SMTP Integration
          </h1>
          <p class="text-xs text-slate-600 mt-1">
            Configure your official community domain email address to send bio-data receipts, match alerts, and verification notifications directly to users.
          </p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
          <!-- 1. Official Mail & SMTP Configuration Form -->
          <div class="lg:col-span-7 bg-white border border-slate-200 rounded-3xl p-6 shadow-xs space-y-5">
            <div class="flex items-center gap-2 border-b border-slate-100 pb-3">
              <ServerIcon class="w-5 h-5 text-red-600" />
              <h3 class="text-base font-extrabold text-slate-900">Official Mail Credentials</h3>
            </div>

            <form @submit.prevent="submitSettings" class="space-y-4 text-xs">
              <div class="grid grid-cols-2 gap-4">
                <div>
                  <label class="font-extrabold text-slate-700 uppercase text-[10px]">Official Mail ID</label>
                  <input v-model="form.official_email" type="email" placeholder="info@jainshadimilan.com" required class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2 font-bold text-slate-900" />
                </div>
                <div>
                  <label class="font-extrabold text-slate-700 uppercase text-[10px]">Official Sender Name</label>
                  <input v-model="form.sender_name" type="text" placeholder="JRV Matrimonial Team" required class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2 font-bold text-slate-900" />
                </div>
              </div>

              <div class="grid grid-cols-3 gap-3">
                <div>
                  <label class="font-extrabold text-slate-700 uppercase text-[10px]">Mail Driver</label>
                  <select v-model="form.mail_driver" class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3 py-2 font-bold text-slate-900">
                    <option value="smtp">SMTP</option>
                    <option value="sendmail">Sendmail</option>
                    <option value="mailgun">Mailgun</option>
                  </select>
                </div>
                <div>
                  <label class="font-extrabold text-slate-700 uppercase text-[10px]">SMTP Host</label>
                  <input v-model="form.mail_host" type="text" placeholder="smtp.gmail.com" required class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3 py-2 font-bold text-slate-900" />
                </div>
                <div>
                  <label class="font-extrabold text-slate-700 uppercase text-[10px]">Port</label>
                  <input v-model="form.mail_port" type="number" placeholder="587" required class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3 py-2 font-bold text-slate-900" />
                </div>
              </div>

              <div class="grid grid-cols-3 gap-3">
                <div>
                  <label class="font-extrabold text-slate-700 uppercase text-[10px]">Encryption</label>
                  <select v-model="form.mail_encryption" class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3 py-2 font-bold text-slate-900">
                    <option value="tls">TLS</option>
                    <option value="ssl">SSL</option>
                  </select>
                </div>
                <div>
                  <label class="font-extrabold text-slate-700 uppercase text-[10px]">SMTP Username</label>
                  <input v-model="form.mail_username" type="text" placeholder="info@jainshadimilan.com" class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3 py-2 font-bold text-slate-900" />
                </div>
                <div>
                  <label class="font-extrabold text-slate-700 uppercase text-[10px]">SMTP Password</label>
                  <input v-model="form.mail_password" type="password" placeholder="••••••••••••" class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3 py-2 font-bold text-slate-900" />
                </div>
              </div>

              <div class="pt-2 flex justify-end">
                <button type="submit" :disabled="form.processing" class="px-6 py-2.5 bg-red-600 hover:bg-red-700 text-white font-black rounded-xl shadow-md transition-all">
                  Save Official Mail Configuration
                </button>
              </div>
            </form>
          </div>

          <!-- 2. Send Test Mail Card -->
          <div class="lg:col-span-5 bg-white border border-slate-200 rounded-3xl p-6 shadow-xs space-y-4 flex flex-col justify-between">
            <div class="space-y-3">
              <div class="flex items-center gap-2 border-b border-slate-100 pb-3">
                <PaperAirplaneIcon class="w-5 h-5 text-red-600" />
                <h3 class="text-base font-extrabold text-slate-900">Test Official Mail Delivery</h3>
              </div>
              <p class="text-xs text-slate-600">Send an instant verification test email from your official mail ID (<strong class="text-red-600">{{ form.official_email }}</strong>) to verify your credentials:</p>

              <form @submit.prevent="sendTest" class="space-y-3 text-xs">
                <div>
                  <label class="font-extrabold text-slate-700 uppercase text-[10px]">Recipient Email</label>
                  <input v-model="testForm.test_email" type="email" required class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2 font-bold text-slate-900" />
                </div>

                <button type="submit" :disabled="testForm.processing" class="w-full py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold rounded-xl shadow-md transition-all flex items-center justify-center gap-2">
                  <PaperAirplaneIcon class="w-4 h-4" />
                  <span>Send Test Email Now</span>
                </button>
              </form>
            </div>

            <div class="p-4 bg-emerald-50 rounded-2xl border border-emerald-200 text-xs text-emerald-800 space-y-1">
              <div class="font-extrabold flex items-center gap-1.5">
                <CheckCircleIcon class="w-4 h-4 text-emerald-600" />
                <span>Active Official Mail ID</span>
              </div>
              <p class="text-[11px]">Emails to members will be delivered with sender name <strong>"{{ form.sender_name }}"</strong>.</p>
            </div>
          </div>
        </div>

        <!-- 3. Recent Official Email Logs Table -->
        <div class="bg-white border border-slate-200 rounded-3xl overflow-hidden shadow-xs space-y-4">
          <div class="p-5 border-b border-slate-200 flex items-center justify-between">
            <h3 class="text-base font-bold text-slate-900">Official Email Delivery Logs</h3>
          </div>

          <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
              <thead>
                <tr class="bg-slate-50 border-b border-slate-200 text-slate-500 font-bold uppercase text-[10px]">
                  <th class="py-3 px-4">Recipient</th>
                  <th class="py-3 px-4">Subject</th>
                  <th class="py-3 px-4">Template</th>
                  <th class="py-3 px-4">Status</th>
                  <th class="py-3 px-4">Timestamp</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100">
                <tr v-for="log in logs.data" :key="log.id" class="hover:bg-slate-50">
                  <td class="py-3.5 px-4 font-bold text-slate-900">{{ log.recipient_name }} ({{ log.recipient_email }})</td>
                  <td class="py-3.5 px-4 font-semibold text-slate-700">{{ log.subject }}</td>
                  <td class="py-3.5 px-4">
                    <span class="px-2.5 py-0.5 bg-slate-100 text-slate-700 font-bold text-[10px] rounded-full border border-slate-200">
                      {{ log.template_name }}
                    </span>
                  </td>
                  <td class="py-3.5 px-4">
                    <span class="px-2.5 py-0.5 bg-emerald-100 text-emerald-800 border border-emerald-300 rounded-full text-[10px] font-black">
                      {{ log.status }}
                    </span>
                  </td>
                  <td class="py-3.5 px-4 text-slate-500 font-semibold">{{ new Date(log.created_at).toLocaleString() }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    <!-- Modals -->
    <GlobalSearchModal :is-open="isSearchOpen" @close="isSearchOpen = false" />
  </div>
</template>
