<script setup>
import { ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import Navbar from '@/Components/Navbar.vue';
import Loader from '@/Components/Loader.vue';
import { 
  Cog6ToothIcon, 
  PhoneIcon, 
  EnvelopeIcon, 
  BuildingOfficeIcon, 
  ChatBubbleLeftRightIcon, 
  CreditCardIcon, 
  CheckCircleIcon,
  ShieldCheckIcon,
  SparklesIcon,
  ArrowLeftIcon,
  ClockIcon
} from '@heroicons/vue/24/outline';

const goBack = () => {
  if (window.history.length > 1) {
    window.history.back();
  } else {
    router.visit('/admin');
  }
};

const props = defineProps({
  settings: Object,
});

const activeTab = ref('support'); // 'support', 'whatsapp', 'payment'

const form = useForm({
  support_phone: props.settings?.support_phone || '+91 98765 43210',
  support_email: props.settings?.support_email || 'support@jrvcrm.com',
  contact_address: props.settings?.contact_address || 'JRV CRM Tower, Tech Park, Sector 62, Noida, UP, India',
  contact_hours: props.settings?.contact_hours || 'Mon - Sat: 9:00 AM - 7:00 PM IST',
  whatsapp_number: props.settings?.whatsapp_number || '+91 98765 43210',
  whatsapp_api_key: props.settings?.whatsapp_api_key || 'wa_live_sec_9872163541',
  whatsapp_auto_reply: props.settings?.whatsapp_auto_reply || 'Hello! Thank you for contacting JRV CRM Support. How can we assist your firm today?',
  payment_provider: props.settings?.payment_provider || 'razorpay',
  payment_key_id: props.settings?.payment_key_id || 'rzp_live_891237912',
  payment_secret_key: props.settings?.payment_secret_key || 'sec_rzp_9812739812739',
  payment_currency: props.settings?.payment_currency || 'INR',
  enable_live_payments: props.settings?.enable_live_payments || 'true',
});

const saveSettings = () => {
  form.post('/admin/settings', {
    preserveScroll: true,
    onSuccess: () => {
      alert('System Settings, Support Info & Integrations saved successfully!');
    },
  });
};
</script>

<template>
  <Head title="System Settings & API Integrations" />

  <div class="min-h-screen bg-slate-100 flex font-sans text-slate-900">
    <!-- Navbar (Fixed Left Vertical Sidebar) -->
    <Navbar />

    <!-- MAIN CONTENT -->
    <main class="flex-1 p-6 sm:p-8 space-y-6 overflow-y-auto max-w-5xl mx-auto w-full">
      <!-- Header -->
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-slate-200 pb-5">
        <div class="space-y-1">
          <div class="flex items-center gap-2">
            <button 
              @click="goBack"
              class="px-3 py-1 bg-white hover:bg-slate-100 border border-slate-200 text-slate-700 font-extrabold text-xs rounded-xl shadow-2xs flex items-center gap-1.5 cursor-pointer transition-all whitespace-nowrap"
            >
              <ArrowLeftIcon class="w-3.5 h-3.5 text-slate-500" />
              <span>Back</span>
            </button>
            <div class="inline-flex items-center gap-1.5 px-3 py-0.5 rounded-full bg-slate-200 text-slate-800 text-xs font-extrabold">
              <Cog6ToothIcon class="w-4 h-4 text-red-600" />
              <span>Master Admin Configuration</span>
            </div>
          </div>
          <h1 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">System Settings & Integrations</h1>
          <p class="text-slate-500 text-xs sm:text-sm font-medium">Configure customer support contact details, WhatsApp Business API, and online payment gateway credentials.</p>
        </div>

        <Link 
          href="/admin"
          class="px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white font-extrabold text-xs rounded-xl shadow-xs flex items-center gap-1.5 cursor-pointer transition-all w-fit"
        >
          <span>Back to Control Center</span>
        </Link>
      </div>

      <!-- Settings Navigation Tabs -->
      <div class="flex items-center gap-2 border-b border-slate-200 pb-1 overflow-x-auto">
        <button 
          @click="activeTab = 'support'"
          :class="[
            'px-4 py-2.5 rounded-xl font-extrabold text-xs transition-all cursor-pointer flex items-center gap-2 whitespace-nowrap',
            activeTab === 'support' ? 'bg-red-600 text-white shadow-xs' : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-200'
          ]"
        >
          <PhoneIcon class="w-4 h-4" />
          <span>Customer Support & Contact Us</span>
        </button>

        <button 
          @click="activeTab = 'whatsapp'"
          :class="[
            'px-4 py-2.5 rounded-xl font-extrabold text-xs transition-all cursor-pointer flex items-center gap-2 whitespace-nowrap',
            activeTab === 'whatsapp' ? 'bg-emerald-600 text-white shadow-xs' : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-200'
          ]"
        >
          <ChatBubbleLeftRightIcon class="w-4 h-4" />
          <span>WhatsApp Integration</span>
        </button>

        <button 
          @click="activeTab = 'payment'"
          :class="[
            'px-4 py-2.5 rounded-xl font-extrabold text-xs transition-all cursor-pointer flex items-center gap-2 whitespace-nowrap',
            activeTab === 'payment' ? 'bg-purple-600 text-white shadow-xs' : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-200'
          ]"
        >
          <CreditCardIcon class="w-4 h-4" />
          <span>Payment Gateway Credentials</span>
        </button>
      </div>

      <!-- Settings Form Body -->
      <form @submit.prevent="saveSettings" class="bg-white border border-slate-200 rounded-3xl p-6 shadow-2xs space-y-6">
        
        <!-- TAB 1: CUSTOMER SUPPORT DETAILS -->
        <div v-if="activeTab === 'support'" class="space-y-5">
          <div class="border-b border-slate-100 pb-3">
            <h3 class="text-base font-black text-slate-900 flex items-center gap-2">
              <PhoneIcon class="w-5 h-5 text-red-600" />
              <span>Customer Support & Contact Information</span>
            </h3>
            <p class="text-xs text-slate-500 font-medium mt-0.5">These contact details will be shown to users for support, inquiries, and plan upgrades.</p>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="space-y-1">
              <label class="block text-xs font-extrabold text-slate-700">Customer Support Phone Number</label>
              <input v-model="form.support_phone" type="text" required class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2 text-xs font-bold text-slate-900 focus:outline-none focus:border-red-500 focus:bg-white" />
            </div>

            <div class="space-y-1">
              <label class="block text-xs font-extrabold text-slate-700">Customer Support Email ID</label>
              <input v-model="form.support_email" type="email" required class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2 text-xs font-bold text-slate-900 focus:outline-none focus:border-red-500 focus:bg-white" />
            </div>

            <div class="space-y-1 sm:col-span-2">
              <label class="block text-xs font-extrabold text-slate-700">Office Address / Contact Us Location</label>
              <textarea v-model="form.contact_address" rows="2" class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2 text-xs font-bold text-slate-900 focus:outline-none focus:border-red-500 focus:bg-white"></textarea>
            </div>

            <div class="space-y-1 sm:col-span-2">
              <label class="block text-xs font-extrabold text-slate-700">Support Hours</label>
              <input v-model="form.contact_hours" type="text" class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2 text-xs font-bold text-slate-900 focus:outline-none focus:border-red-500 focus:bg-white" />
            </div>
          </div>
        </div>

        <!-- TAB 2: WHATSAPP BUSINESS INTEGRATION -->
        <div v-if="activeTab === 'whatsapp'" class="space-y-5">
          <div class="border-b border-slate-100 pb-3">
            <h3 class="text-base font-black text-slate-900 flex items-center gap-2">
              <ChatBubbleLeftRightIcon class="w-5 h-5 text-emerald-600" />
              <span>WhatsApp Business API Integration</span>
            </h3>
            <p class="text-xs text-slate-500 font-medium mt-0.5">Connect Meta WhatsApp Cloud API / WhatsApp Gateway for automated customer broadcasts and notification alerts.</p>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="space-y-1">
              <label class="block text-xs font-extrabold text-slate-700">WhatsApp Support Number</label>
              <input v-model="form.whatsapp_number" type="text" placeholder="+91 98765 43210" class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2 text-xs font-bold text-slate-900 focus:outline-none focus:border-emerald-500 focus:bg-white" />
            </div>

            <div class="space-y-1">
              <label class="block text-xs font-extrabold text-slate-700">WhatsApp API Access Token / Secret Key</label>
              <input v-model="form.whatsapp_api_key" type="password" placeholder="wa_live_sec_..." class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2 text-xs font-bold text-slate-900 focus:outline-none focus:border-emerald-500 focus:bg-white" />
            </div>

            <div class="space-y-1 sm:col-span-2">
              <label class="block text-xs font-extrabold text-slate-700">Automated Welcome Greeting Message</label>
              <textarea v-model="form.whatsapp_auto_reply" rows="3" class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2 text-xs font-bold text-slate-900 focus:outline-none focus:border-emerald-500 focus:bg-white"></textarea>
            </div>
          </div>
        </div>

        <!-- TAB 3: PAYMENT GATEWAY INTEGRATION -->
        <div v-if="activeTab === 'payment'" class="space-y-5">
          <div class="border-b border-slate-100 pb-3">
            <h3 class="text-base font-black text-slate-900 flex items-center gap-2">
              <CreditCardIcon class="w-5 h-5 text-purple-600" />
              <span>Online Payment Gateway Credentials</span>
            </h3>
            <p class="text-xs text-slate-500 font-medium mt-0.5">Integrate Razorpay, Stripe, or PhonePe/UPI for automated subscription plan billing and online checkout.</p>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="space-y-1">
              <label class="block text-xs font-extrabold text-slate-700">Payment Provider Gateway</label>
              <select v-model="form.payment_provider" class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3 py-2 text-xs font-bold text-slate-900 focus:outline-none focus:border-purple-500 focus:bg-white">
                <option value="razorpay">Razorpay Payment Gateway (India - UPI / NetBanking / Cards)</option>
                <option value="stripe">Stripe Payments (Global Credit Cards)</option>
                <option value="phonepe">PhonePe / PayTM / BHIM UPI Gateway</option>
              </select>
            </div>

            <div class="space-y-1">
              <label class="block text-xs font-extrabold text-slate-700">Default Currency</label>
              <input v-model="form.payment_currency" type="text" class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2 text-xs font-bold text-slate-900 focus:outline-none focus:border-purple-500 focus:bg-white" readonly />
            </div>

            <div class="space-y-1">
              <label class="block text-xs font-extrabold text-slate-700">Merchant Key ID / API Key</label>
              <input v-model="form.payment_key_id" type="text" placeholder="rzp_live_..." class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2 text-xs font-bold text-slate-900 focus:outline-none focus:border-purple-500 focus:bg-white" />
            </div>

            <div class="space-y-1">
              <label class="block text-xs font-extrabold text-slate-700">Secret Key</label>
              <input v-model="form.payment_secret_key" type="password" placeholder="sec_rzp_..." class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2 text-xs font-bold text-slate-900 focus:outline-none focus:border-purple-500 focus:bg-white" />
            </div>

            <div class="space-y-1 sm:col-span-2 pt-2">
              <label class="flex items-center gap-2 cursor-pointer">
                <input v-model="form.enable_live_payments" type="checkbox" true-value="true" false-value="false" class="w-4 h-4 text-purple-600 rounded focus:ring-purple-500 border-slate-300" />
                <span class="text-xs font-extrabold text-slate-900">Enable Live Online Payment Checkout for Subscriptions</span>
              </label>
            </div>
          </div>
        </div>

        <!-- Submit Bar -->
        <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-200">
          <button 
            type="submit" 
            :disabled="form.processing"
            class="px-6 py-2.5 bg-red-600 hover:bg-red-700 text-white font-extrabold text-xs rounded-xl shadow-md shadow-red-600/20 flex items-center gap-2 cursor-pointer transition-all"
          >
            <Loader v-if="form.processing" size="sm" color="white" text="Saving..." />
            <template v-else>
              <ShieldCheckIcon class="w-4 h-4" />
              <span>Save System Settings</span>
            </template>
          </button>
        </div>
      </form>
    </main>
  </div>
</template>
