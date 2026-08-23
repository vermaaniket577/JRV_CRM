<script setup>
import { ref, computed } from 'vue';
import { useForm, Head, Link } from '@inertiajs/vue3';
import Navbar from '@/Components/Navbar.vue';
import { 
  Cog6ToothIcon, 
  GlobeAltIcon, 
  CreditCardIcon, 
  ShareIcon, 
  CodeBracketIcon, 
  KeyIcon, 
  CheckCircleIcon, 
  ClipboardDocumentIcon, 
  ArrowTopRightOnSquareIcon,
  ShieldCheckIcon,
  PhoneIcon,
  EnvelopeIcon,
  BuildingOffice2Icon,
  SparklesIcon,
  CheckIcon,
  QrCodeIcon,
  BuildingLibraryIcon,
  PhotoIcon,
  TrashIcon,
  ArrowUpTrayIcon,
  EyeIcon,
  DocumentDuplicateIcon,
  PrinterIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
  general: {
    type: Object,
    default: () => ({})
  },
  payments: {
    type: Object,
    default: () => ({})
  },
  social: {
    type: Object,
    default: () => ({})
  },
  integration: {
    type: Object,
    default: () => ({})
  },
  user: {
    type: Object,
    default: () => ({})
  },
  tenant: {
    type: Object,
    default: () => null
  }
});

const activeTab = ref('general');
const embedPlatform = ref('wordpress');
const selectedLang = ref('universal_js');
const isTestingPing = ref(false);
const testPingResult = ref(null);
const appUrl = typeof window !== 'undefined' ? window.location.origin : 'http://127.0.0.1:8001';
const copiedField = ref('');

const universalScriptTag = computed(() => `<script src="${appUrl}/js/jrv-crm-autocapture.js" data-crm-token="${props.tenant?.id || '7'}" data-business-name="${generalForm.business_name || 'Admissions Dekho'}" async><\/script>`);

const floatingWidgetScriptTag = computed(() => `<script src="${appUrl}/js/jrv-crm-autocapture.js" data-crm-token="${props.tenant?.id || '7'}" data-business-name="${generalForm.business_name || 'Admissions Dekho'}" data-brand-color="${generalForm.brand_color || '#dc2626'}" data-widget="true" data-button-text="🎓 Admission Inquiry" async><\/script>`);

const iframeSnippet = computed(() => `<iframe src="${appUrl}/embed/register" width="100%" height="700" frameborder="0" style="border:none; border-radius:16px; box-shadow:0 10px 30px rgba(0,0,0,0.1);"></iframe>`);

const jsSnippet = computed(() => `fetch('${appUrl}/api/v1/integration/auto-capture', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({
    first_name: 'John',
    last_name: 'Doe',
    phone: '+91 9876543210',
    email: 'john@example.com',
    gender: 'Male',
    city: 'Mumbai',
    tenant_token: '${props.tenant?.id || '7'}'
  })
});`);

const phpSnippet = computed(() => `<?php
\$payload = [
    'first_name' => 'John',
    'last_name' => 'Doe',
    'phone' => '+91 9876543210',
    'email' => 'john@example.com',
    'gender' => 'Male',
    'city' => 'Mumbai',
    'tenant_token' => '${props.tenant?.id || '7'}'
];

\$ch = curl_init('${appUrl}/api/v1/integration/auto-capture');
curl_setopt(\$ch, CURLOPT_POSTFIELDS, json_encode(\$payload));
curl_setopt(\$ch, CURLOPT_HTTPHEADER, ['Content-Type:application/json']);
curl_setopt(\$ch, CURLOPT_RETURNTRANSFER, true);
\$response = curl_exec(\$ch);
curl_close(\$ch);
?>`);

const pySnippet = computed(() => `import requests

payload = {
    "first_name": "John",
    "last_name": "Doe",
    "phone": "+91 9876543210",
    "email": "john@example.com",
    "gender": "Male",
    "city": "Mumbai",
    "tenant_token": "${props.tenant?.id || '7'}"
}

response = requests.post("${appUrl}/api/v1/integration/auto-capture", json=payload)
print(response.json())`);

const reactSnippet = computed(() => `const handleSubmit = async (e) => {
  e.preventDefault();
  const response = await fetch('${appUrl}/api/v1/integration/auto-capture', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ ...formData, tenant_token: '${props.tenant?.id || '7'}' })
  });
  const data = await response.json();
  console.log('Synced into CRM:', data);
};`);

const curlSnippet = computed(() => `curl -X POST ${appUrl}/api/v1/integration/auto-capture \\
  -H "Content-Type: application/json" \\
  -d '{"first_name":"John","last_name":"Doe","phone":"+919876543210","email":"john@example.com","gender":"Male","city":"Mumbai","tenant_token":"${props.tenant?.id || '7'}"}'`);

const sendTestPing = async () => {
  isTestingPing.value = true;
  testPingResult.value = null;
  try {
    const res = await fetch('/api/v1/integration/test-ping', {
      method: 'POST',
      headers: { 
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      },
      body: JSON.stringify({ website_url: generalForm.website_url || appUrl, tenant_token: props.tenant?.id || '7' })
    });
    if (res.ok) {
      testPingResult.value = await res.json();
    }
  } catch (e) {
    console.error('Test Ping Error:', e);
  } finally {
    isTestingPing.value = false;
  }
};

const simulationForm = ref({
  name: 'Demo Student Lead',
  phone: '+91 9876543210',
  email: 'demo_student@example.com',
  message: 'Inquiring about admission criteria & courses',
});
const isSimulating = ref(false);
const simulationResult = ref(null);

const sendSimulationLead = async () => {
  isSimulating.value = true;
  simulationResult.value = null;
  try {
    const res = await fetch('/api/v1/integration/auto-capture', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      },
      body: JSON.stringify({
        ...simulationForm.value,
        tenant_token: props.tenant?.id || '7',
        website_source: generalForm.website_url || appUrl,
      }),
    });
    const data = await res.json();
    if (res.ok && data.success) {
      simulationResult.value = data;
    }
  } catch (e) {
    console.error('Simulation error:', e);
  } finally {
    isSimulating.value = false;
  }
};

// Forms
const generalForm = useForm({
  business_name: props.general.business_name || '',
  business_icon: props.general.business_icon || '⚡',
  brand_color: props.general.brand_color || '#dc2626',
  logo_url: props.general.logo_url || '',
  website_url: props.general.website_url || '',
  tagline: props.general.tagline || '',
  support_email: props.general.support_email || '',
  contact_phone: props.general.contact_phone || '',
  whatsapp_number: props.general.whatsapp_number || '',
  address: props.general.address || '',
  city: props.general.city || '',
  state: props.general.state || '',
  country: props.general.country || 'India',
  postal_code: props.general.postal_code || '',
});

const qrPreview = ref(props.payments.upi_qr_code_url || '');
const fileInputRef = ref(null);
const activePreviewTab = ref('qr'); // 'qr' or 'bank'

const paymentForm = useForm({
  gateway: props.payments.gateway || 'upi',
  is_live: props.payments.is_live ?? true,
  currency: props.payments.currency || 'INR',
  razorpay_key: props.payments.razorpay_key || '',
  razorpay_secret: props.payments.razorpay_secret || '',
  razorpay_webhook_secret: props.payments.razorpay_webhook_secret || '',
  stripe_key: props.payments.stripe_key || '',
  stripe_secret: props.payments.stripe_secret || '',
  upi_id: props.payments.upi_id || '',
  upi_merchant_name: props.payments.upi_merchant_name || props.general?.business_name || '',
  upi_qr_code_url: props.payments.upi_qr_code_url || '',
  qr_code_file: null,
  remove_qr_code: false,
  bank_name: props.payments.bank_name || '',
  bank_account_holder: props.payments.bank_account_holder || props.general?.business_name || '',
  bank_account_number: props.payments.bank_account_number || '',
  bank_ifsc_code: props.payments.bank_ifsc_code || '',
  bank_account_type: props.payments.bank_account_type || 'Current Account',
  bank_branch: props.payments.bank_branch || '',
  bank_swift_code: props.payments.bank_swift_code || '',
  bank_instructions: props.payments.bank_instructions || 'Please share transaction UTR / payment receipt screenshot after transfer.',
});

const handleQrFileUpload = (event) => {
  const file = event.target.files?.[0];
  if (!file) return;
  paymentForm.qr_code_file = file;
  paymentForm.remove_qr_code = false;
  
  const reader = new FileReader();
  reader.onload = (e) => {
    qrPreview.value = e.target.result;
  };
  reader.readAsDataURL(file);
};

const removeQrCode = () => {
  paymentForm.qr_code_file = null;
  paymentForm.upi_qr_code_url = '';
  paymentForm.remove_qr_code = true;
  qrPreview.value = '';
  if (fileInputRef.value) {
    fileInputRef.value.value = '';
  }
};

const socialForm = useForm({
  instagram: props.social.instagram || '',
  facebook: props.social.facebook || '',
  youtube: props.social.youtube || '',
  linkedin: props.social.linkedin || '',
  twitter: props.social.twitter || '',
  whatsapp_channel: props.social.whatsapp_channel || '',
});

const passwordForm = useForm({
  current_password: '',
  new_password: '',
  new_password_confirmation: '',
});

const saveGeneral = () => {
  generalForm.post('/system-settings/general', { preserveScroll: true });
};

const savePayments = () => {
  paymentForm.post('/system-settings/payments', { 
    preserveScroll: true,
    forceFormData: true,
  });
};

const saveSocial = () => {
  socialForm.post('/system-settings/social', { preserveScroll: true });
};

const savePassword = () => {
  passwordForm.post('/system-settings/password', {
    preserveScroll: true,
    onSuccess: () => passwordForm.reset(),
  });
};

const copyToClipboard = (text, fieldName) => {
  navigator.clipboard.writeText(text);
  copiedField.value = fieldName;
  setTimeout(() => {
    copiedField.value = '';
  }, 2500);
};

const paymentEmbedType = ref('button'); // 'link', 'button', 'widget', 'iframe', 'standee'
const customFeeAmount = ref(1000);
const customFeePurpose = ref('Course & Admission Fee');

const publicPaymentUrl = computed(() => {
  const origin = typeof window !== 'undefined' ? window.location.origin : 'http://localhost';
  const tenantId = props.tenant?.id || props.user?.tenant_id || 7;
  return `${origin}/embed/pay?tenant_id=${tenantId}`;
});

const parameterizedPaymentUrl = computed(() => {
  const base = publicPaymentUrl.value;
  const amt = encodeURIComponent(customFeeAmount.value || 1000);
  const purp = encodeURIComponent(customFeePurpose.value || 'Fee Payment');
  return `${base}&amount=${amt}&purpose=${purp}`;
});

const htmlPaymentButtonSnippet = computed(() => {
  const url = parameterizedPaymentUrl.value;
  return `<!-- 1-Click JRV CRM Website Payment Button -->\n<a href="${url}" target="_blank" style="background:#dc2626;color:#ffffff;padding:14px 28px;border-radius:14px;font-family:sans-serif;font-size:14px;font-weight:bold;text-decoration:none;display:inline-flex;align-items:center;gap:8px;box-shadow:0 10px 20px rgba(220,38,38,0.3);transition:all 0.3s ease;">\n  <span>💳 Pay ₹${Number(customFeeAmount.value || 1000).toLocaleString('en-IN')} Online</span>\n</a>`;
});

const iframePaymentSnippet = computed(() => {
  return `<!-- 1-Click JRV CRM Responsive Payment Checkout Form -->\n<iframe src="${parameterizedPaymentUrl.value}" width="100%" height="750" frameborder="0" style="border-radius:24px;box-shadow:0 20px 40px rgba(0,0,0,0.1);border:1px solid #e2e8f0;" allow="payment; camera;"></iframe>`;
});

const jsPaymentWidgetSnippet = computed(() => {
  const origin = typeof window !== 'undefined' ? window.location.origin : 'http://localhost';
  const tenantId = props.tenant?.id || props.user?.tenant_id || 7;
  const bizName = props.general?.business_name || 'Admissions Dekho';
  return `<!-- 1-Line JRV CRM Instant Payment Modal Engine -->\n<script src="${origin}/js/jrv-crm-autocapture.js" data-tenant-id="${tenantId}" data-business-name="${bizName}" data-brand-color="#dc2626" data-widget="pay"><\/script>\n\n<!-- Trigger with ANY Button -->\n<button data-crm-pay="${customFeeAmount.value || 1000}" data-purpose="${customFeePurpose.value || 'Admission Fee'}" style="background:#dc2626;color:#fff;padding:12px 24px;border:none;border-radius:12px;font-weight:bold;cursor:pointer;">\n  💳 Pay ₹${Number(customFeeAmount.value || 1000).toLocaleString('en-IN')} Online (Modal Popup)\n</button>`;
});

const printStandeeQr = () => {
  const printWin = window.open('', '_blank');
  const upiId = paymentForm.upi_id || 'admissionsdekho@okaxis';
  const merchant = paymentForm.upi_merchant_name || props.general?.business_name || 'Admissions Dekho';
  const qrImg = qrPreview.value || `https://api.qrserver.com/v1/create-qr-code/?size=400x400&data=${encodeURIComponent('upi://pay?pa=' + upiId + '&pn=' + encodeURIComponent(merchant))}`;
  
  printWin.document.write(`
    <!DOCTYPE html>
    <html>
      <head>
        <title>Print Reception Payment Standee - ${merchant}</title>
        <style>
          @page { size: auto; margin: 15mm; }
          body { font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; text-align: center; padding: 20px; background: #fff; color: #0f172a; }
          .card { max-width: 440px; margin: 0 auto; border: 4px solid #dc2626; border-radius: 32px; padding: 36px 28px; box-shadow: 0 15px 35px rgba(0,0,0,0.08); background: #ffffff; }
          .header { font-size: 26px; font-weight: 900; margin-bottom: 6px; color: #0f172a; letter-spacing: -0.5px; }
          .sub { font-size: 13px; font-weight: 600; color: #64748b; margin-bottom: 24px; }
          .qr-box { background: #f8fafc; padding: 18px; border-radius: 24px; border: 2px dashed #cbd5e1; display: inline-block; margin-bottom: 20px; }
          .qr-img { width: 250px; height: 250px; object-fit: contain; }
          .upi-id { font-size: 16px; font-weight: 900; font-family: monospace; background: #fee2e2; color: #991b1b; padding: 10px 20px; border-radius: 14px; display: inline-block; margin-bottom: 14px; border: 1px solid #fecaca; }
          .apps { font-size: 12px; font-weight: 800; color: #334155; margin-top: 6px; }
          .footer { margin-top: 24px; font-size: 11px; color: #94a3b8; font-weight: 600; border-top: 1px solid #e2e8f0; padding-top: 16px; }
        </style>
      </head>
      <body>
        <div class="card">
          <div class="header">⚡ ${merchant}</div>
          <div class="sub">Scan with any UPI App to Pay Online Instantly</div>
          <div class="qr-box">
            <img src="${qrImg}" class="qr-img" />
          </div>
          <div>
            <div class="upi-id">${upiId}</div>
          </div>
          <div class="apps">Accepted on: Google Pay • PhonePe • Paytm • BHIM • Cred</div>
          <div class="footer">Official Payment Gateway • Powered by JRV CRM</div>
        </div>
        <script>
          window.onload = function() { window.print(); }
        <\/script>
      </body>
    </html>
  `);
  printWin.document.close();
};
</script>

<template>
  <Head title="System Settings & Website Integration - JRV CRM" />

  <div class="min-h-screen bg-[#f8fafc] text-slate-900 flex font-sans w-full max-w-full overflow-x-hidden">
    <Navbar />

    <main class="flex-1 min-w-0 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8 overflow-x-hidden">
      
      <!-- Top Title Header -->
      <div class="bg-white border border-slate-200/90 rounded-3xl p-6 md:p-8 shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div class="space-y-1.5">
          <div class="flex items-center gap-3">
            <div class="w-12 h-12 rounded-2xl bg-red-50 border border-red-200 flex items-center justify-center text-red-600 text-xl font-black shadow-xs">
              <Cog6ToothIcon class="w-7 h-7" />
            </div>
            <div>
              <div class="flex items-center gap-2.5">
                <h1 class="text-2xl md:text-3xl font-black text-slate-900 tracking-tight">
                  System Settings & Website Hub
                </h1>
                <span class="px-2.5 py-0.5 text-[10px] font-extrabold uppercase tracking-wider bg-red-50 text-red-700 border border-red-200 rounded-full">
                  User Panel
                </span>
              </div>
              <p class="text-xs text-slate-500 font-medium">
                Configure your business branding, integrate website flow, connect payment gateways, manage social links, and update security credentials.
              </p>
            </div>
          </div>
        </div>

        <div class="flex items-center gap-2.5">
          <button 
            @click="activeTab = 'integration'"
            type="button"
            class="px-4 py-2 bg-gradient-to-r from-red-600 to-indigo-600 hover:from-red-700 hover:to-indigo-700 text-white font-extrabold text-xs rounded-xl shadow-md shadow-red-600/20 flex items-center gap-1.5 transition cursor-pointer"
          >
            <SparklesIcon class="w-4 h-4 text-amber-300" />
            <span>⚡ 1-Click Link Website</span>
          </button>

          <a 
            v-if="general.website_url"
            :href="general.website_url" 
            target="_blank" 
            class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs rounded-xl border border-slate-300 flex items-center gap-2 transition shadow-2xs"
          >
            <GlobeAltIcon class="w-4 h-4 text-slate-500" />
            <span>Visit Website</span>
            <ArrowTopRightOnSquareIcon class="w-3.5 h-3.5" />
          </a>
        </div>
      </div>

      <!-- Settings Navigation Tabs -->
      <div class="flex items-center space-x-2 border-b border-slate-200 pb-2 overflow-x-auto">
        <button 
          @click="activeTab = 'general'"
          :class="[
            'px-4 py-2.5 rounded-xl font-bold text-xs transition flex items-center gap-2 whitespace-nowrap cursor-pointer',
            activeTab === 'general' ? 'bg-red-600 text-white shadow-md shadow-red-600/20' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'
          ]"
        >
          <GlobeAltIcon class="w-4 h-4" />
          Website & Branding
        </button>

        <button 
          @click="activeTab = 'payments'"
          :class="[
            'px-4 py-2.5 rounded-xl font-bold text-xs transition flex items-center gap-2 whitespace-nowrap cursor-pointer',
            activeTab === 'payments' ? 'bg-red-600 text-white shadow-md shadow-red-600/20' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'
          ]"
        >
          <CreditCardIcon class="w-4 h-4" />
          Payment Gateway & QR
        </button>

        <button 
          @click="activeTab = 'social'"
          :class="[
            'px-4 py-2.5 rounded-xl font-bold text-xs transition flex items-center gap-2 whitespace-nowrap cursor-pointer',
            activeTab === 'social' ? 'bg-red-600 text-white shadow-md shadow-red-600/20' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'
          ]"
        >
          <ShareIcon class="w-4 h-4" />
          Social Media Links
        </button>

        <button 
          @click="activeTab = 'integration'"
          :class="[
            'px-4 py-2.5 rounded-xl font-black text-xs transition flex items-center gap-2 whitespace-nowrap cursor-pointer',
            activeTab === 'integration' ? 'bg-indigo-600 text-white shadow-md shadow-indigo-600/20 ring-2 ring-indigo-400/30' : 'text-indigo-600 bg-indigo-50/70 hover:bg-indigo-100 font-bold'
          ]"
        >
          <CodeBracketIcon class="w-4 h-4" />
          <span>⚡ 1-Click Link Website</span>
        </button>

        <button 
          @click="activeTab = 'security'"
          :class="[
            'px-4 py-2.5 rounded-xl font-bold text-xs transition flex items-center gap-2 whitespace-nowrap cursor-pointer',
            activeTab === 'security' ? 'bg-red-600 text-white shadow-md shadow-red-600/20' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'
          ]"
        >
          <KeyIcon class="w-4 h-4" />
          Security & Password
        </button>
      </div>

      <!-- TAB 1: Website Branding & Contact Details -->
      <div v-if="activeTab === 'general'" class="bg-white border border-slate-200 rounded-3xl p-6 sm:p-8 shadow-xs space-y-6">
        
        <!-- Company Subdomain & Dedicated Database Information Banner -->
        <div class="bg-gradient-to-r from-slate-900 to-slate-800 text-white rounded-2xl p-5 sm:p-6 space-y-4 shadow-sm border border-slate-700">
          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div class="space-y-1">
              <div class="flex items-center gap-2">
                <GlobeAltIcon class="w-5 h-5 text-red-500" />
                <span class="text-xs font-black uppercase tracking-wider text-slate-300">Dedicated Company Subdomain</span>
                <span class="px-2 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">
                  Active
                </span>
              </div>
              <div class="text-base sm:text-lg font-mono font-black text-white flex items-center gap-2">
                <span>https://{{ $page.props.current_tenant?.subdomain || 'admissionsdekho' }}.jrvcrm.com</span>
              </div>
              <p class="text-xs text-slate-400 font-medium">Your organization's isolated CRM instance and public checkout forms run on this subdomain.</p>
            </div>

            <div class="flex items-center gap-2 shrink-0">
              <button 
                type="button"
                @click="copyToClipboard(`https://${$page.props.current_tenant?.subdomain || 'admissionsdekho'}.jrvcrm.com`, 'subdomain_url')"
                class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-white text-xs font-bold rounded-xl border border-slate-600 transition flex items-center gap-1.5 cursor-pointer"
              >
                <CheckCircleIcon v-if="copiedField === 'subdomain_url'" class="w-4 h-4 text-emerald-400" />
                <ClipboardDocumentIcon v-else class="w-4 h-4" />
                <span>{{ copiedField === 'subdomain_url' ? 'Copied Subdomain!' : 'Copy Subdomain Link' }}</span>
              </button>
            </div>
          </div>

          <!-- Isolated Database Spec Grid -->
          <div class="pt-3 border-t border-slate-700/80 grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs">
            <div class="bg-slate-800/80 p-3 rounded-xl border border-slate-700">
              <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Dedicated Database</span>
              <span class="font-mono font-bold text-red-400 text-xs truncate block">{{ $page.props.current_tenant?.database_name || 'crm_tenant_7_admissionsdekho' }}</span>
            </div>
            <div class="bg-slate-800/80 p-3 rounded-xl border border-slate-700">
              <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Isolation Mode</span>
              <span class="font-bold text-emerald-400 text-xs flex items-center gap-1">
                <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
                <span>Multi-Tenant Isolated</span>
              </span>
            </div>
            <div class="bg-slate-800/80 p-3 rounded-xl border border-slate-700">
              <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">DB Engine & Host</span>
              <span class="font-bold text-slate-200 text-xs">MySQL 8.0 (127.0.0.1:3306)</span>
            </div>
            <div class="bg-slate-800/80 p-3 rounded-xl border border-slate-700">
              <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Schema Health</span>
              <span class="font-bold text-emerald-400 text-xs">✓ Tables Migrated</span>
            </div>
          </div>
        </div>

        <div class="flex items-center justify-between border-b border-slate-100 pb-4">
          <div>
            <h2 class="text-lg font-black text-slate-900">Website Branding & Contact Details</h2>
            <p class="text-xs text-slate-500 font-medium">Customize how your brand, logo, and contact info appear across your CRM and website embeds.</p>
          </div>
          <button 
            @click="saveGeneral" 
            :disabled="generalForm.processing"
            class="px-5 py-2 bg-red-600 hover:bg-red-700 text-white text-xs font-bold rounded-xl shadow-md shadow-red-600/20 flex items-center gap-2 transition cursor-pointer"
          >
            <CheckCircleIcon class="w-4 h-4" />
            <span>Save Branding</span>
          </button>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div class="space-y-1.5">
            <label class="text-xs font-bold text-slate-700">Business / Organization Name</label>
            <input v-model="generalForm.business_name" type="text" class="w-full bg-slate-50 border border-slate-300 rounded-xl px-4 py-2.5 text-xs font-semibold text-slate-900 focus:bg-white focus:border-red-500 outline-none" />
          </div>

          <div class="space-y-1.5">
            <label class="text-xs font-bold text-slate-700">Tagline / Motto</label>
            <input v-model="generalForm.tagline" type="text" class="w-full bg-slate-50 border border-slate-300 rounded-xl px-4 py-2.5 text-xs font-semibold text-slate-900 focus:bg-white focus:border-red-500 outline-none" />
          </div>

          <div class="space-y-1.5">
            <label class="text-xs font-bold text-slate-700">Official Website URL</label>
            <input v-model="generalForm.website_url" type="url" placeholder="https://yourwebsite.com" class="w-full bg-slate-50 border border-slate-300 rounded-xl px-4 py-2.5 text-xs font-semibold text-slate-900 focus:bg-white focus:border-red-500 outline-none" />
          </div>

          <div class="space-y-1.5">
            <label class="text-xs font-bold text-slate-700">Logo Image URL</label>
            <input v-model="generalForm.logo_url" type="text" placeholder="https://yourwebsite.com/logo.png" class="w-full bg-slate-50 border border-slate-300 rounded-xl px-4 py-2.5 text-xs font-semibold text-slate-900 focus:bg-white focus:border-red-500 outline-none" />
          </div>

          <div class="space-y-1.5">
            <label class="text-xs font-bold text-slate-700">Official Support Email</label>
            <input v-model="generalForm.support_email" type="email" class="w-full bg-slate-50 border border-slate-300 rounded-xl px-4 py-2.5 text-xs font-semibold text-slate-900 focus:bg-white focus:border-red-500 outline-none" />
          </div>

          <div class="space-y-1.5">
            <label class="text-xs font-bold text-slate-700">Support Phone Number</label>
            <input v-model="generalForm.contact_phone" type="text" class="w-full bg-slate-50 border border-slate-300 rounded-xl px-4 py-2.5 text-xs font-semibold text-slate-900 focus:bg-white focus:border-red-500 outline-none" />
          </div>

          <div class="space-y-1.5">
            <label class="text-xs font-bold text-slate-700">Official WhatsApp Business Number</label>
            <input v-model="generalForm.whatsapp_number" type="text" placeholder="+91 9876543210" class="w-full bg-slate-50 border border-slate-300 rounded-xl px-4 py-2.5 text-xs font-semibold text-slate-900 focus:bg-white focus:border-red-500 outline-none" />
          </div>

          <div class="space-y-1.5">
            <label class="text-xs font-bold text-slate-700">Business Icon / Emoji</label>
            <input v-model="generalForm.business_icon" type="text" class="w-full bg-slate-50 border border-slate-300 rounded-xl px-4 py-2.5 text-xs font-semibold text-slate-900 focus:bg-white focus:border-red-500 outline-none" />
          </div>
        </div>

        <div class="pt-4 border-t border-slate-100 space-y-4">
          <h3 class="text-xs font-black uppercase tracking-wider text-slate-400">Headquarters / Physical Address</h3>
          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="sm:col-span-2 space-y-1.5">
              <label class="text-xs font-bold text-slate-700">Street Address</label>
              <input v-model="generalForm.address" type="text" class="w-full bg-slate-50 border border-slate-300 rounded-xl px-4 py-2 text-xs font-semibold text-slate-900 focus:bg-white focus:border-red-500 outline-none" />
            </div>
            <div class="space-y-1.5">
              <label class="text-xs font-bold text-slate-700">City</label>
              <input v-model="generalForm.city" type="text" class="w-full bg-slate-50 border border-slate-300 rounded-xl px-4 py-2 text-xs font-semibold text-slate-900 focus:bg-white focus:border-red-500 outline-none" />
            </div>
            <div class="space-y-1.5">
              <label class="text-xs font-bold text-slate-700">State</label>
              <input v-model="generalForm.state" type="text" class="w-full bg-slate-50 border border-slate-300 rounded-xl px-4 py-2 text-xs font-semibold text-slate-900 focus:bg-white focus:border-red-500 outline-none" />
            </div>
          </div>
        </div>
      </div>

      <!-- TAB 2: Payment Gateway Integration -->
      <div v-if="activeTab === 'payments'" class="bg-white border border-slate-200 rounded-3xl p-6 sm:p-8 shadow-xs space-y-6">
        <div class="flex items-center justify-between border-b border-slate-100 pb-4">
          <div>
            <h2 class="text-lg font-black text-slate-900">Website Payment Gateway Integration</h2>
            <p class="text-xs text-slate-500 font-medium">Accept customer registration fees, membership upgrades, and payments directly on your website.</p>
          </div>
          <button 
            @click="savePayments" 
            :disabled="paymentForm.processing"
            class="px-5 py-2 bg-red-600 hover:bg-red-700 text-white text-xs font-bold rounded-xl shadow-md shadow-red-600/20 flex items-center gap-2 transition cursor-pointer"
          >
            <CheckCircleIcon class="w-4 h-4" />
            <span>Save Payment Keys</span>
          </button>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
          <div 
            @click="paymentForm.gateway = 'razorpay'"
            :class="[
              'p-4 rounded-2xl border cursor-pointer transition flex flex-col justify-between space-y-2',
              paymentForm.gateway === 'razorpay' ? 'bg-red-50/60 border-red-500 ring-2 ring-red-500/20' : 'bg-slate-50 border-slate-200'
            ]"
          >
            <div class="flex items-center justify-between">
              <span class="font-extrabold text-sm text-slate-900">Razorpay (India / UPI)</span>
              <span class="text-xl">💳</span>
            </div>
            <p class="text-[11px] text-slate-500 font-medium">Cards, UPI, Netbanking & Wallets</p>
          </div>

          <div 
            @click="paymentForm.gateway = 'stripe'"
            :class="[
              'p-4 rounded-2xl border cursor-pointer transition flex flex-col justify-between space-y-2',
              paymentForm.gateway === 'stripe' ? 'bg-red-50/60 border-red-500 ring-2 ring-red-500/20' : 'bg-slate-50 border-slate-200'
            ]"
          >
            <div class="flex items-center justify-between">
              <span class="font-extrabold text-sm text-slate-900">Stripe (Global)</span>
              <span class="text-xl">🌍</span>
            </div>
            <p class="text-[11px] text-slate-500 font-medium">International Cards & Multi-Currency</p>
          </div>

          <div 
            @click="paymentForm.gateway = 'upi'"
            :class="[
              'p-4 rounded-2xl border cursor-pointer transition flex flex-col justify-between space-y-2',
              paymentForm.gateway === 'upi' ? 'bg-red-50/60 border-red-500 ring-2 ring-red-500/20' : 'bg-slate-50 border-slate-200'
            ]"
          >
            <div class="flex items-center justify-between">
              <span class="font-extrabold text-sm text-slate-900">Direct UPI / QR</span>
              <span class="text-xl">📱</span>
            </div>
            <p class="text-[11px] text-slate-500 font-medium">0% fee instant bank settlement</p>
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 pt-4 border-t border-slate-100">
          <div class="space-y-1.5">
            <label class="text-xs font-bold text-slate-700">Environment Mode</label>
            <div class="flex items-center gap-3">
              <label class="inline-flex items-center gap-2 cursor-pointer">
                <input type="radio" :value="false" v-model="paymentForm.is_live" class="text-red-600" />
                <span class="text-xs font-bold text-slate-700">Test / Sandbox Mode</span>
              </label>
              <label class="inline-flex items-center gap-2 cursor-pointer">
                <input type="radio" :value="true" v-model="paymentForm.is_live" class="text-red-600" />
                <span class="text-xs font-bold text-emerald-700">Live Production Mode</span>
              </label>
            </div>
          </div>

          <div class="space-y-1.5">
            <label class="text-xs font-bold text-slate-700">Default Currency</label>
            <select v-model="paymentForm.currency" class="w-full bg-slate-50 border border-slate-300 rounded-xl px-4 py-2 text-xs font-bold text-slate-900">
              <option value="INR">INR (₹) - Indian Rupee</option>
              <option value="USD">USD ($) - US Dollar</option>
              <option value="EUR">EUR (€) - Euro</option>
              <option value="AED">AED (د.إ) - UAE Dirham</option>
              <option value="GBP">GBP (£) - British Pound</option>
            </select>
          </div>

          <!-- Razorpay Keys -->
          <template v-if="paymentForm.gateway === 'razorpay'">
            <div class="space-y-1.5">
              <label class="text-xs font-bold text-slate-700">Razorpay Key ID</label>
              <input v-model="paymentForm.razorpay_key" type="text" placeholder="rzp_live_xxxxxxxx" class="w-full bg-slate-50 border border-slate-300 rounded-xl px-4 py-2 text-xs font-mono text-slate-900" />
            </div>
            <div class="space-y-1.5">
              <label class="text-xs font-bold text-slate-700">Razorpay Key Secret</label>
              <input v-model="paymentForm.razorpay_secret" type="password" placeholder="••••••••••••••••" class="w-full bg-slate-50 border border-slate-300 rounded-xl px-4 py-2 text-xs font-mono text-slate-900" />
            </div>
            <div class="md:col-span-2 space-y-1.5">
              <label class="text-xs font-bold text-slate-700">Razorpay Webhook Secret</label>
              <input v-model="paymentForm.razorpay_webhook_secret" type="text" class="w-full bg-slate-50 border border-slate-300 rounded-xl px-4 py-2 text-xs font-mono text-slate-900" />
            </div>
          </template>

          <!-- Stripe Keys -->
          <template v-if="paymentForm.gateway === 'stripe'">
            <div class="space-y-1.5">
              <label class="text-xs font-bold text-slate-700">Stripe Publishable Key</label>
              <input v-model="paymentForm.stripe_key" type="text" placeholder="pk_live_xxxxxxxx" class="w-full bg-slate-50 border border-slate-300 rounded-xl px-4 py-2 text-xs font-mono text-slate-900" />
            </div>
            <div class="space-y-1.5">
              <label class="text-xs font-bold text-slate-700">Stripe Secret Key</label>
              <input v-model="paymentForm.stripe_secret" type="password" placeholder="sk_live_xxxxxxxx" class="w-full bg-slate-50 border border-slate-300 rounded-xl px-4 py-2 text-xs font-mono text-slate-900" />
            </div>
          </template>

          <!-- UPI & Manual Bank Transfer Configuration -->
          <template v-if="paymentForm.gateway === 'upi'">
            <!-- UPI Settings & QR Code Upload -->
            <div class="md:col-span-2 bg-slate-50 border border-slate-200 rounded-2xl p-5 space-y-4">
              <div class="flex items-center justify-between border-b border-slate-200 pb-3">
                <div class="flex items-center gap-2">
                  <div class="w-8 h-8 rounded-xl bg-red-100 text-red-600 flex items-center justify-center font-bold">
                    <QrCodeIcon class="w-5 h-5" />
                  </div>
                  <div>
                    <h3 class="text-sm font-black text-slate-900">Direct UPI & QR Code Settings</h3>
                    <p class="text-[11px] text-slate-500 font-medium">Configure your UPI ID and upload custom payment QR code image for instant settlements.</p>
                  </div>
                </div>
                <span class="px-2.5 py-1 bg-emerald-100 text-emerald-800 text-[10px] font-extrabold rounded-full">
                  0% Gateway Fee
                </span>
              </div>

              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="space-y-1.5">
                  <label class="text-xs font-bold text-slate-700">UPI Virtual Payment Address (VPA / UPI ID)</label>
                  <div class="relative">
                    <input 
                      v-model="paymentForm.upi_id" 
                      type="text" 
                      placeholder="e.g. admissionsdekho@okhdfcbank" 
                      class="w-full bg-white border border-slate-300 rounded-xl px-4 py-2.5 text-xs font-mono text-slate-900 font-bold focus:ring-2 focus:ring-red-500" 
                    />
                    <button 
                      v-if="paymentForm.upi_id"
                      type="button"
                      @click="copyToClipboard(paymentForm.upi_id, 'upi_id')"
                      class="absolute right-2 top-1/2 -translate-y-1/2 px-2 py-1 bg-slate-100 hover:bg-slate-200 text-slate-700 text-[10px] font-bold rounded-lg transition flex items-center gap-1"
                    >
                      <CheckIcon v-if="copiedField === 'upi_id'" class="w-3 h-3 text-emerald-600" />
                      <ClipboardDocumentIcon v-else class="w-3 h-3" />
                      <span>{{ copiedField === 'upi_id' ? 'Copied!' : 'Copy' }}</span>
                    </button>
                  </div>
                  <p class="text-[10px] text-slate-500">Google Pay, PhonePe, Paytm, BHIM or any UPI handle.</p>
                </div>

                <div class="space-y-1.5">
                  <label class="text-xs font-bold text-slate-700">Merchant / Payee Display Name</label>
                  <input 
                    v-model="paymentForm.upi_merchant_name" 
                    type="text" 
                    placeholder="e.g. Admissions Dekho"
                    class="w-full bg-white border border-slate-300 rounded-xl px-4 py-2.5 text-xs font-semibold text-slate-900 focus:ring-2 focus:ring-red-500" 
                  />
                  <p class="text-[10px] text-slate-500">Official business name shown when users scan the QR code.</p>
                </div>
              </div>

              <!-- QR Code Upload & Preview Section -->
              <div class="pt-2 border-t border-slate-200/80">
                <label class="text-xs font-bold text-slate-800 block mb-2">Upload UPI Payment QR Code Image</label>
                
                <input 
                  ref="fileInputRef" 
                  type="file" 
                  accept="image/png, image/jpeg, image/jpg, image/webp, image/svg+xml" 
                  @change="handleQrFileUpload" 
                  class="hidden" 
                />

                <div class="grid grid-cols-1 sm:grid-cols-12 gap-4 items-start">
                  <!-- Upload Box -->
                  <div 
                    @click="$refs.fileInputRef.click()"
                    class="sm:col-span-7 border-2 border-dashed border-slate-300 hover:border-red-500 bg-white hover:bg-red-50/30 rounded-2xl p-6 text-center cursor-pointer transition-all flex flex-col items-center justify-center space-y-2 group"
                  >
                    <div class="w-12 h-12 rounded-2xl bg-red-50 group-hover:bg-red-100 text-red-600 flex items-center justify-center transition">
                      <ArrowUpTrayIcon class="w-6 h-6" />
                    </div>
                    <div>
                      <span class="text-xs font-bold text-slate-900 group-hover:text-red-600 block">
                        Click here to upload QR Code image
                      </span>
                      <span class="text-[11px] text-slate-500">
                        Supports PNG, JPG, JPEG, WEBP, SVG (Max 5MB)
                      </span>
                    </div>
                  </div>

                  <!-- QR Code Preview Card -->
                  <div class="sm:col-span-5 bg-white border border-slate-200 rounded-2xl p-4 flex flex-col items-center text-center space-y-2.5 shadow-xs">
                    <span class="text-[10px] font-black uppercase tracking-wider text-slate-400">QR Code Preview</span>
                    
                    <!-- Custom Uploaded QR Preview -->
                    <div v-if="qrPreview" class="relative group">
                      <img 
                        :src="qrPreview" 
                        alt="UPI QR Code" 
                        class="w-36 h-36 object-contain rounded-xl border border-slate-200 p-1.5 bg-white shadow-xs" 
                      />
                      <div class="mt-2 flex items-center gap-1.5 justify-center">
                        <button 
                          type="button" 
                          @click="$refs.fileInputRef.click()" 
                          class="px-2.5 py-1 bg-slate-100 hover:bg-slate-200 text-slate-700 text-[10px] font-bold rounded-lg transition"
                        >
                          Change
                        </button>
                        <button 
                          type="button" 
                          @click="removeQrCode" 
                          class="px-2.5 py-1 bg-rose-50 hover:bg-rose-100 text-rose-700 text-[10px] font-bold rounded-lg transition flex items-center gap-1"
                        >
                          <TrashIcon class="w-3 h-3" />
                          <span>Remove</span>
                        </button>
                      </div>
                      <span class="inline-block mt-1 text-[9px] font-bold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-md">
                        ✓ Active Custom QR Code
                      </span>
                    </div>

                    <!-- Auto-Generated Preview fallback if no custom QR is uploaded -->
                    <div v-else-if="paymentForm.upi_id" class="flex flex-col items-center space-y-2">
                      <img 
                        :src="`https://api.qrserver.com/v1/create-qr-code/?size=180x180&data=upi://pay?pa=${paymentForm.upi_id}&pn=${encodeURIComponent(paymentForm.upi_merchant_name || 'Merchant')}&cu=${paymentForm.currency || 'INR'}`" 
                        alt="Dynamic UPI QR Code" 
                        class="w-36 h-36 object-contain rounded-xl border border-slate-200 p-1.5 bg-white shadow-xs" 
                      />
                      <span class="text-[9px] font-bold text-sky-600 bg-sky-50 px-2 py-0.5 rounded-md">
                        ⚡ Auto-Generated from UPI ID
                      </span>
                    </div>

                    <!-- Empty State -->
                    <div v-else class="py-6 flex flex-col items-center text-slate-400 space-y-1">
                      <QrCodeIcon class="w-10 h-10 stroke-1 text-slate-300" />
                      <span class="text-[11px] font-medium text-slate-400">No QR Code uploaded yet</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Manual Bank Account Details Card -->
            <div class="md:col-span-2 bg-slate-50 border border-slate-200 rounded-2xl p-5 space-y-4">
              <div class="flex items-center justify-between border-b border-slate-200 pb-3">
                <div class="flex items-center gap-2">
                  <div class="w-8 h-8 rounded-xl bg-indigo-100 text-indigo-600 flex items-center justify-center font-bold">
                    <BuildingLibraryIcon class="w-5 h-5" />
                  </div>
                  <div>
                    <h3 class="text-sm font-black text-slate-900">Manual Bank Account Details (NEFT / IMPS / RTGS)</h3>
                    <p class="text-[11px] text-slate-500 font-medium">Provide your official corporate/business bank account details for direct wire transfers.</p>
                  </div>
                </div>
                <span class="px-2.5 py-1 bg-indigo-100 text-indigo-800 text-[10px] font-extrabold rounded-full">
                  Direct Bank Wire
                </span>
              </div>

              <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
                <div class="space-y-1.5">
                  <label class="text-xs font-bold text-slate-700">Bank Name</label>
                  <input 
                    v-model="paymentForm.bank_name" 
                    type="text" 
                    placeholder="e.g. HDFC Bank, SBI, ICICI" 
                    class="w-full bg-white border border-slate-300 rounded-xl px-4 py-2.5 text-xs font-semibold text-slate-900 focus:ring-2 focus:ring-indigo-500" 
                  />
                </div>

                <div class="space-y-1.5">
                  <label class="text-xs font-bold text-slate-700">Account Holder / Beneficiary Name</label>
                  <input 
                    v-model="paymentForm.bank_account_holder" 
                    type="text" 
                    placeholder="e.g. Admissions Dekho Pvt Ltd" 
                    class="w-full bg-white border border-slate-300 rounded-xl px-4 py-2.5 text-xs font-semibold text-slate-900 focus:ring-2 focus:ring-indigo-500" 
                  />
                </div>

                <div class="space-y-1.5">
                  <label class="text-xs font-bold text-slate-700">Bank Account Number</label>
                  <input 
                    v-model="paymentForm.bank_account_number" 
                    type="text" 
                    placeholder="e.g. 50200012345678" 
                    class="w-full bg-white border border-slate-300 rounded-xl px-4 py-2.5 text-xs font-mono font-bold text-slate-900 focus:ring-2 focus:ring-indigo-500" 
                  />
                </div>

                <div class="space-y-1.5">
                  <label class="text-xs font-bold text-slate-700">IFSC Code</label>
                  <input 
                    v-model="paymentForm.bank_ifsc_code" 
                    type="text" 
                    placeholder="e.g. HDFC0001234" 
                    @input="paymentForm.bank_ifsc_code = paymentForm.bank_ifsc_code.toUpperCase()"
                    class="w-full bg-white border border-slate-300 rounded-xl px-4 py-2.5 text-xs font-mono font-bold text-slate-900 uppercase focus:ring-2 focus:ring-indigo-500" 
                  />
                </div>

                <div class="space-y-1.5">
                  <label class="text-xs font-bold text-slate-700">Account Type</label>
                  <select 
                    v-model="paymentForm.bank_account_type" 
                    class="w-full bg-white border border-slate-300 rounded-xl px-4 py-2.5 text-xs font-bold text-slate-900 focus:ring-2 focus:ring-indigo-500"
                  >
                    <option value="Current Account">Current Account (Corporate)</option>
                    <option value="Savings Account">Savings Account</option>
                    <option value="Overdraft (OD)">Overdraft Account (OD)</option>
                    <option value="Cash Credit (CC)">Cash Credit (CC)</option>
                  </select>
                </div>

                <div class="space-y-1.5">
                  <label class="text-xs font-bold text-slate-700">Branch Name & City</label>
                  <input 
                    v-model="paymentForm.bank_branch" 
                    type="text" 
                    placeholder="e.g. Connaught Place, New Delhi" 
                    class="w-full bg-white border border-slate-300 rounded-xl px-4 py-2.5 text-xs font-semibold text-slate-900 focus:ring-2 focus:ring-indigo-500" 
                  />
                </div>

                <div class="space-y-1.5 sm:col-span-2 md:col-span-1">
                  <label class="text-xs font-bold text-slate-700">SWIFT / BIC Code (Optional)</label>
                  <input 
                    v-model="paymentForm.bank_swift_code" 
                    type="text" 
                    placeholder="e.g. HDFCINBBXXX" 
                    @input="paymentForm.bank_swift_code = paymentForm.bank_swift_code.toUpperCase()"
                    class="w-full bg-white border border-slate-300 rounded-xl px-4 py-2.5 text-xs font-mono font-bold text-slate-900 uppercase focus:ring-2 focus:ring-indigo-500" 
                  />
                </div>

                <div class="space-y-1.5 sm:col-span-2 md:col-span-2">
                  <label class="text-xs font-bold text-slate-700">Payment Instructions / Notes for Customers</label>
                  <input 
                    v-model="paymentForm.bank_instructions" 
                    type="text" 
                    placeholder="e.g. Please share transaction screenshot or UTR number via WhatsApp after transfer." 
                    class="w-full bg-white border border-slate-300 rounded-xl px-4 py-2.5 text-xs font-medium text-slate-900 focus:ring-2 focus:ring-indigo-500" 
                  />
                </div>
              </div>
            </div>

            <!-- Customer Live Checkout Preview Card -->
            <div class="md:col-span-2 bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900 rounded-3xl p-6 text-white shadow-xl space-y-4">
              <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-700 pb-3">
                <div class="flex items-center gap-2">
                  <span class="text-xs font-black text-amber-400 uppercase tracking-widest">Live Preview</span>
                  <span class="text-xs text-slate-400">| How customers will view your payment options</span>
                </div>
                <div class="flex items-center gap-2">
                  <button 
                    type="button" 
                    @click="activePreviewTab = 'qr'"
                    :class="['px-3 py-1 text-xs font-bold rounded-lg transition', activePreviewTab === 'qr' ? 'bg-red-600 text-white shadow' : 'bg-slate-800 text-slate-300 hover:bg-slate-700']"
                  >
                    UPI & QR View
                  </button>
                  <button 
                    type="button" 
                    @click="activePreviewTab = 'bank'"
                    :class="['px-3 py-1 text-xs font-bold rounded-lg transition', activePreviewTab === 'bank' ? 'bg-indigo-600 text-white shadow' : 'bg-slate-800 text-slate-300 hover:bg-slate-700']"
                  >
                    Bank Transfer View
                  </button>
                </div>
              </div>

              <!-- Preview 1: UPI & QR Code View -->
              <div v-if="activePreviewTab === 'qr'" class="grid grid-cols-1 sm:grid-cols-12 gap-6 items-center">
                <div class="sm:col-span-4 flex flex-col items-center justify-center p-3 bg-white rounded-2xl shadow-inner text-slate-900">
                  <img 
                    v-if="qrPreview" 
                    :src="qrPreview" 
                    alt="Scan & Pay QR" 
                    class="w-40 h-40 object-contain rounded-lg" 
                  />
                  <img 
                    v-else-if="paymentForm.upi_id"
                    :src="`https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=upi://pay?pa=${paymentForm.upi_id}&pn=${encodeURIComponent(paymentForm.upi_merchant_name || 'Merchant')}&cu=${paymentForm.currency || 'INR'}`"
                    alt="Scan & Pay QR" 
                    class="w-40 h-40 object-contain rounded-lg" 
                  />
                  <div v-else class="w-40 h-40 flex items-center justify-center text-slate-400 font-bold text-xs">
                    Scan & Pay QR Code
                  </div>
                  <span class="text-[10px] font-black text-slate-600 mt-2 uppercase tracking-wide">
                    Scan with Any UPI App
                  </span>
                </div>

                <div class="sm:col-span-8 space-y-3">
                  <div>
                    <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Merchant Payee</span>
                    <h4 class="text-lg font-black text-white">{{ paymentForm.upi_merchant_name || generalForm.business_name || 'Admissions Dekho' }}</h4>
                  </div>

                  <div class="bg-slate-800/80 border border-slate-700 rounded-xl p-3 flex items-center justify-between gap-2">
                    <div>
                      <span class="text-[10px] text-slate-400 block font-semibold">UPI Virtual ID</span>
                      <code class="text-xs font-mono font-bold text-emerald-400">{{ paymentForm.upi_id || 'business@okhdfcbank' }}</code>
                    </div>
                    <button 
                      type="button" 
                      @click="copyToClipboard(paymentForm.upi_id, 'preview_upi')" 
                      class="px-3 py-1.5 bg-slate-700 hover:bg-slate-600 text-white text-[11px] font-bold rounded-lg transition flex items-center gap-1"
                    >
                      <CheckIcon v-if="copiedField === 'preview_upi'" class="w-3.5 h-3.5 text-emerald-400" />
                      <DocumentDuplicateIcon v-else class="w-3.5 h-3.5" />
                      <span>{{ copiedField === 'preview_upi' ? 'Copied!' : 'Copy UPI' }}</span>
                    </button>
                  </div>

                  <p class="text-xs text-slate-300">
                    {{ paymentForm.bank_instructions || 'Please share your transaction screenshot or UTR number after completing the payment.' }}
                  </p>
                </div>
              </div>

              <!-- Preview 2: Bank Transfer Details View -->
              <div v-if="activePreviewTab === 'bank'" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3">
                <div class="bg-slate-800/90 border border-slate-700 rounded-xl p-3">
                  <span class="text-[10px] font-bold text-slate-400 uppercase">Bank Name</span>
                  <div class="text-sm font-bold text-white mt-0.5">{{ paymentForm.bank_name || 'HDFC Bank' }}</div>
                </div>

                <div class="bg-slate-800/90 border border-slate-700 rounded-xl p-3">
                  <span class="text-[10px] font-bold text-slate-400 uppercase">Account Holder</span>
                  <div class="text-sm font-bold text-white mt-0.5">{{ paymentForm.bank_account_holder || 'Admissions Dekho' }}</div>
                </div>

                <div class="bg-slate-800/90 border border-slate-700 rounded-xl p-3 flex items-center justify-between">
                  <div>
                    <span class="text-[10px] font-bold text-slate-400 uppercase">Account Number</span>
                    <div class="text-sm font-mono font-bold text-emerald-400 mt-0.5">{{ paymentForm.bank_account_number || '50200012345678' }}</div>
                  </div>
                  <button 
                    type="button" 
                    @click="copyToClipboard(paymentForm.bank_account_number, 'preview_acc')" 
                    class="p-1.5 bg-slate-700 hover:bg-slate-600 rounded-lg text-slate-300"
                    title="Copy Account Number"
                  >
                    <CheckIcon v-if="copiedField === 'preview_acc'" class="w-4 h-4 text-emerald-400" />
                    <ClipboardDocumentIcon v-else class="w-4 h-4" />
                  </button>
                </div>

                <div class="bg-slate-800/90 border border-slate-700 rounded-xl p-3 flex items-center justify-between">
                  <div>
                    <span class="text-[10px] font-bold text-slate-400 uppercase">IFSC Code</span>
                    <div class="text-sm font-mono font-bold text-emerald-400 mt-0.5">{{ paymentForm.bank_ifsc_code || 'HDFC0001234' }}</div>
                  </div>
                  <button 
                    type="button" 
                    @click="copyToClipboard(paymentForm.bank_ifsc_code, 'preview_ifsc')" 
                    class="p-1.5 bg-slate-700 hover:bg-slate-600 rounded-lg text-slate-300"
                    title="Copy IFSC Code"
                  >
                    <CheckIcon v-if="copiedField === 'preview_ifsc'" class="w-4 h-4 text-emerald-400" />
                    <ClipboardDocumentIcon v-else class="w-4 h-4" />
                  </button>
                </div>

                <div class="bg-slate-800/90 border border-slate-700 rounded-xl p-3">
                  <span class="text-[10px] font-bold text-slate-400 uppercase">Account Type</span>
                  <div class="text-sm font-bold text-white mt-0.5">{{ paymentForm.bank_account_type || 'Current Account' }}</div>
                </div>

                <div class="bg-slate-800/90 border border-slate-700 rounded-xl p-3">
                  <span class="text-[10px] font-bold text-slate-400 uppercase">Branch</span>
                  <div class="text-sm font-bold text-white mt-0.5">{{ paymentForm.bank_branch || 'Connaught Place' }}</div>
                </div>
              </div>
            </div>

            <!-- 1-CLICK WEBSITE PAYMENT INTEGRATION HUB -->
            <div class="md:col-span-2 bg-gradient-to-br from-red-50/70 via-white to-amber-50/50 border-2 border-red-200 rounded-3xl p-6 sm:p-8 shadow-md space-y-6">
              <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-red-100 pb-5">
                <div class="space-y-1">
                  <div class="flex items-center gap-2.5">
                    <div class="w-9 h-9 rounded-xl bg-red-600 text-white flex items-center justify-center text-lg shadow-sm">
                      ⚡
                    </div>
                    <div>
                      <h3 class="text-base font-black text-slate-900 tracking-tight">1-Click Website Payment Integration Hub</h3>
                      <p class="text-xs text-slate-500 font-medium">Add payment buttons, checkout modals, and fee collection to ANY external website instantly.</p>
                    </div>
                  </div>
                </div>

                <div class="flex items-center gap-2">
                  <button 
                    type="button" 
                    @click="printStandeeQr"
                    class="px-4 py-2 bg-slate-900 hover:bg-black text-white text-xs font-bold rounded-xl shadow-xs transition flex items-center gap-1.5 cursor-pointer"
                  >
                    <PrinterIcon class="w-4 h-4" />
                    <span>🖨️ Print Standee QR</span>
                  </button>
                  <a 
                    :href="parameterizedPaymentUrl" 
                    target="_blank"
                    class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-xs font-bold rounded-xl shadow-md shadow-red-600/20 transition flex items-center gap-1.5 cursor-pointer"
                  >
                    <ArrowTopRightOnSquareIcon class="w-4 h-4" />
                    <span>Open Live Checkout</span>
                  </a>
                </div>
              </div>

              <!-- Quick Parameter Configurator -->
              <div class="bg-white border border-slate-200/90 rounded-2xl p-4 sm:p-5 shadow-xs grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                  <label class="text-xs font-bold text-slate-700 block mb-1">Default Payment Amount (₹)</label>
                  <div class="relative">
                    <span class="absolute left-3.5 top-1/2 -translate-y-1/2 font-bold text-slate-400">₹</span>
                    <input 
                      v-model="customFeeAmount" 
                      type="number" 
                      min="1" 
                      class="w-full bg-slate-50 border border-slate-300 rounded-xl pl-8 pr-3 py-2 text-xs font-bold text-slate-900 focus:bg-white focus:outline-none focus:border-red-600"
                    />
                  </div>
                </div>

                <div>
                  <label class="text-xs font-bold text-slate-700 block mb-1">Fee Purpose / Label</label>
                  <input 
                    v-model="customFeePurpose" 
                    type="text" 
                    placeholder="e.g. Admission Registration & Processing Fee" 
                    class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2 text-xs font-bold text-slate-900 focus:bg-white focus:outline-none focus:border-red-600"
                  />
                </div>
              </div>

              <!-- Integration Method Tabs -->
              <div class="space-y-3">
                <div class="flex flex-wrap gap-2">
                  <button 
                    type="button" 
                    @click="paymentEmbedType = 'button'"
                    :class="['px-3.5 py-2 text-xs font-bold rounded-xl transition cursor-pointer flex items-center gap-1.5', paymentEmbedType === 'button' ? 'bg-red-600 text-white shadow-sm' : 'bg-white border border-slate-200 text-slate-700 hover:bg-slate-50']"
                  >
                    <span>📋 1-Click Pay Button (HTML)</span>
                  </button>

                  <button 
                    type="button" 
                    @click="paymentEmbedType = 'widget'"
                    :class="['px-3.5 py-2 text-xs font-bold rounded-xl transition cursor-pointer flex items-center gap-1.5', paymentEmbedType === 'widget' ? 'bg-red-600 text-white shadow-sm' : 'bg-white border border-slate-200 text-slate-700 hover:bg-slate-50']"
                  >
                    <span>✨ In-Page Modal Engine (JS)</span>
                  </button>

                  <button 
                    type="button" 
                    @click="paymentEmbedType = 'iframe'"
                    :class="['px-3.5 py-2 text-xs font-bold rounded-xl transition cursor-pointer flex items-center gap-1.5', paymentEmbedType === 'iframe' ? 'bg-red-600 text-white shadow-sm' : 'bg-white border border-slate-200 text-slate-700 hover:bg-slate-50']"
                  >
                    <span>📱 Full Page Checkout iFrame</span>
                  </button>

                  <button 
                    type="button" 
                    @click="paymentEmbedType = 'link'"
                    :class="['px-3.5 py-2 text-xs font-bold rounded-xl transition cursor-pointer flex items-center gap-1.5', paymentEmbedType === 'link' ? 'bg-red-600 text-white shadow-sm' : 'bg-white border border-slate-200 text-slate-700 hover:bg-slate-50']"
                  >
                    <span>🔗 Shareable Payment URL</span>
                  </button>
                </div>

                <!-- Code Container -->
                <div class="relative bg-slate-900 text-slate-100 rounded-2xl p-4 sm:p-5 border border-slate-800 font-mono text-xs shadow-inner">
                  <div class="flex items-center justify-between pb-3 border-b border-slate-800 mb-3 text-[11px] text-slate-400">
                    <span>
                      <span v-if="paymentEmbedType === 'button'">Ready-to-use HTML Button Code (Paste anywhere on WordPress / Webflow / HTML)</span>
                      <span v-else-if="paymentEmbedType === 'widget'">1-Line Auto-Capture & Popup Modal Script</span>
                      <span v-else-if="paymentEmbedType === 'iframe'">Embedded iFrame Code</span>
                      <span v-else>Direct Payment Link (Send on WhatsApp / Email / SMS)</span>
                    </span>
                    <button 
                      type="button"
                      @click="copyToClipboard(
                        paymentEmbedType === 'button' ? htmlPaymentButtonSnippet :
                        paymentEmbedType === 'widget' ? jsPaymentWidgetSnippet :
                        paymentEmbedType === 'iframe' ? iframePaymentSnippet :
                        parameterizedPaymentUrl,
                        'payment_embed'
                      )"
                      class="px-3 py-1 bg-red-600 hover:bg-red-700 text-white rounded-lg transition font-sans font-bold flex items-center gap-1 cursor-pointer"
                    >
                      <CheckIcon v-if="copiedField === 'payment_embed'" class="w-3.5 h-3.5 text-white" />
                      <DocumentDuplicateIcon v-else class="w-3.5 h-3.5" />
                      <span>{{ copiedField === 'payment_embed' ? 'Copied to Clipboard!' : 'Copy Code' }}</span>
                    </button>
                  </div>

                  <pre class="overflow-x-auto whitespace-pre-wrap leading-relaxed select-all text-emerald-300 font-medium">
<template v-if="paymentEmbedType === 'button'">{{ htmlPaymentButtonSnippet }}</template>
<template v-else-if="paymentEmbedType === 'widget'">{{ jsPaymentWidgetSnippet }}</template>
<template v-else-if="paymentEmbedType === 'iframe'">{{ iframePaymentSnippet }}</template>
<template v-else>{{ parameterizedPaymentUrl }}</template></pre>
                </div>

                <!-- Live Button Preview -->
                <div v-if="paymentEmbedType === 'button'" class="p-4 bg-white border border-slate-200 rounded-2xl flex flex-wrap items-center justify-between gap-4">
                  <div class="space-y-0.5">
                    <span class="text-[10px] font-bold text-slate-400 uppercase">Live Button Preview</span>
                    <p class="text-xs text-slate-600">This is how the button renders on external websites:</p>
                  </div>
                  <a 
                    :href="parameterizedPaymentUrl" 
                    target="_blank" 
                    class="px-6 py-3 bg-red-600 hover:bg-red-700 text-white font-black text-xs uppercase tracking-wider rounded-xl shadow-lg shadow-red-600/25 flex items-center gap-2 transition"
                  >
                    <span>💳 Pay ₹{{ Number(customFeeAmount || 1000).toLocaleString('en-IN') }} Online</span>
                  </a>
                </div>
              </div>
            </div>
          </template>
        </div>
      </div>

      <!-- TAB 3: Social Media Accounts -->
      <div v-if="activeTab === 'social'" class="bg-white border border-slate-200 rounded-3xl p-6 sm:p-8 shadow-xs space-y-6">
        <div class="flex items-center justify-between border-b border-slate-100 pb-4">
          <div>
            <h2 class="text-lg font-black text-slate-900">Manage Website Social Media Channels</h2>
            <p class="text-xs text-slate-500 font-medium">Link your business social profiles so they appear in candidate emails, invoices, and website headers.</p>
          </div>
          <button 
            @click="saveSocial" 
            :disabled="socialForm.processing"
            class="px-5 py-2 bg-red-600 hover:bg-red-700 text-white text-xs font-bold rounded-xl shadow-md shadow-red-600/20 flex items-center gap-2 transition cursor-pointer"
          >
            <CheckCircleIcon class="w-4 h-4" />
            <span>Save Social Links</span>
          </button>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div class="space-y-1.5">
            <label class="text-xs font-bold text-slate-700">Instagram Profile URL</label>
            <input v-model="socialForm.instagram" type="url" placeholder="https://instagram.com/yourbusiness" class="w-full bg-slate-50 border border-slate-300 rounded-xl px-4 py-2.5 text-xs font-semibold text-slate-900 focus:bg-white focus:border-red-500 outline-none" />
          </div>

          <div class="space-y-1.5">
            <label class="text-xs font-bold text-slate-700">Facebook Page URL</label>
            <input v-model="socialForm.facebook" type="url" placeholder="https://facebook.com/yourbusiness" class="w-full bg-slate-50 border border-slate-300 rounded-xl px-4 py-2.5 text-xs font-semibold text-slate-900 focus:bg-white focus:border-red-500 outline-none" />
          </div>

          <div class="space-y-1.5">
            <label class="text-xs font-bold text-slate-700">YouTube Channel URL</label>
            <input v-model="socialForm.youtube" type="url" placeholder="https://youtube.com/@yourchannel" class="w-full bg-slate-50 border border-slate-300 rounded-xl px-4 py-2.5 text-xs font-semibold text-slate-900 focus:bg-white focus:border-red-500 outline-none" />
          </div>

          <div class="space-y-1.5">
            <label class="text-xs font-bold text-slate-700">LinkedIn Company Page URL</label>
            <input v-model="socialForm.linkedin" type="url" placeholder="https://linkedin.com/company/yourbusiness" class="w-full bg-slate-50 border border-slate-300 rounded-xl px-4 py-2.5 text-xs font-semibold text-slate-900 focus:bg-white focus:border-red-500 outline-none" />
          </div>

          <div class="space-y-1.5">
            <label class="text-xs font-bold text-slate-700">Twitter / X Profile URL</label>
            <input v-model="socialForm.twitter" type="url" placeholder="https://x.com/yourbusiness" class="w-full bg-slate-50 border border-slate-300 rounded-xl px-4 py-2.5 text-xs font-semibold text-slate-900 focus:bg-white focus:border-red-500 outline-none" />
          </div>

          <div class="space-y-1.5">
            <label class="text-xs font-bold text-slate-700">WhatsApp Community Channel Link</label>
            <input v-model="socialForm.whatsapp_channel" type="url" placeholder="https://whatsapp.com/channel/..." class="w-full bg-slate-50 border border-slate-300 rounded-xl px-4 py-2.5 text-xs font-semibold text-slate-900 focus:bg-white focus:border-red-500 outline-none" />
          </div>
        </div>
      </div>

      <!-- TAB 4: Extremely Easy 1-Click Website Integration Engine (Any Language) -->
      <div v-if="activeTab === 'integration'" class="space-y-8">
        
        <!-- Welcome & 1-Click Quick Start Banner -->
        <div class="bg-gradient-to-r from-red-600 via-rose-600 to-indigo-700 rounded-3xl p-6 sm:p-8 text-white shadow-xl space-y-6">
          <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6">
            <div class="space-y-2 max-w-2xl">
              <span class="px-3 py-1 bg-white/20 backdrop-blur-md rounded-full text-[11px] font-extrabold tracking-wide uppercase inline-flex items-center gap-1.5">
                <SparklesIcon class="w-3.5 h-3.5 text-amber-300" />
                <span>1-Click Universal Website Linker</span>
              </span>
              <h2 class="text-2xl sm:text-3xl font-black tracking-tight">Connect Your Website to CRM in 1 Click</h2>
              <p class="text-xs sm:text-sm text-red-50 font-medium leading-relaxed">
                Connect your website built in <strong>WordPress, HTML, PHP, React, Next.js, Webflow, Wix, or Shopify</strong>. Any customer filling an enquiry or registration form automatically lands inside your CRM in real-time with zero manual entry!
              </p>
            </div>

            <!-- Direct Link Quick Action Box -->
            <div class="bg-white/10 backdrop-blur-md border border-white/20 rounded-2xl p-4 flex flex-col sm:flex-row items-center gap-3 shrink-0">
              <div class="text-left">
                <span class="text-[10px] font-bold text-red-100 uppercase block">No Website? No Problem!</span>
                <span class="text-xs font-black text-white">Direct Online Form Link</span>
              </div>
              <div class="flex items-center gap-2">
                <a 
                  :href="appUrl + '/embed/register'" 
                  target="_blank" 
                  class="px-4 py-2 bg-white hover:bg-red-50 text-red-600 font-extrabold text-xs rounded-xl shadow-md flex items-center gap-1.5 transition"
                >
                  <span>Open Form</span>
                  <ArrowTopRightOnSquareIcon class="w-3.5 h-3.5" />
                </a>
                <button 
                  @click="copyToClipboard(appUrl + '/embed/register', 'direct_url')"
                  class="px-4 py-2 bg-black/20 hover:bg-black/30 text-white font-bold text-xs rounded-xl border border-white/30 flex items-center gap-1.5 transition cursor-pointer"
                >
                  <CheckIcon v-if="copiedField === 'direct_url'" class="w-4 h-4 text-emerald-300 stroke-[3]" />
                  <ClipboardDocumentIcon v-else class="w-4 h-4" />
                  <span>{{ copiedField === 'direct_url' ? 'Copied!' : 'Copy Link' }}</span>
                </button>
              </div>
            </div>
          </div>

          <!-- 1-Click Interactive Website URL Verifier -->
          <div class="bg-black/25 backdrop-blur-md border border-white/25 rounded-2xl p-5 space-y-3">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
              <span class="text-xs font-black uppercase tracking-wider text-red-100 flex items-center gap-2">
                <ShieldCheckIcon class="w-4 h-4 text-emerald-300" />
                <span>Live Website Connection Verifier</span>
              </span>
              <span class="text-[11px] text-red-100 font-medium">Verify your website endpoint instantly</span>
            </div>

            <div class="flex flex-col sm:flex-row gap-3">
              <input 
                v-model="generalForm.website_url" 
                type="url" 
                placeholder="Enter your website URL (e.g. https://mybusiness.com or https://admissionsdekho.com)" 
                class="flex-1 bg-white text-slate-900 placeholder:text-slate-400 font-semibold text-xs px-4 py-3 rounded-xl border border-white/40 focus:outline-none focus:ring-2 focus:ring-amber-300"
              />
              
              <button 
                @click="sendTestPing" 
                :disabled="isTestingPing"
                class="px-6 py-3 bg-amber-400 hover:bg-amber-300 text-slate-950 font-black text-xs rounded-xl shadow-lg flex items-center justify-center gap-2 transition cursor-pointer shrink-0 disabled:opacity-75"
              >
                <div v-if="isTestingPing" class="w-4 h-4 border-2 border-slate-900 border-t-transparent rounded-full animate-spin"></div>
                <CheckCircleIcon v-else class="w-4 h-4" />
                <span>{{ isTestingPing ? 'Validating Link...' : '🧪 Test Live Website Connection' }}</span>
              </button>
            </div>

            <!-- Live Test Result Banner -->
            <div v-if="testPingResult" class="p-4 bg-emerald-500/95 border border-emerald-300 rounded-xl text-white text-xs font-semibold flex items-center justify-between gap-3 transition shadow-md">
              <div class="flex items-center gap-2.5">
                <span class="text-xl">✅</span>
                <div>
                  <strong class="block text-sm font-black">Connection Verified & Active!</strong>
                  <span class="text-[11px] text-emerald-100">
                    Test ping confirmed from {{ testPingResult.synced_record?.source }}. Ready to receive live leads.
                  </span>
                </div>
              </div>
              <Link href="/tenant/crm-records" class="px-3.5 py-1.5 bg-white text-emerald-800 text-xs font-black rounded-lg shadow-xs hover:bg-emerald-50 transition">
                View CRM Directory &rarr;
              </Link>
            </div>
          </div>
        </div>

        <!-- 4 EFFORTLESS 1-CLICK INTEGRATION OPTIONS -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

          <!-- OPTION 1: WordPress 1-Click Plugin -->
          <div class="bg-white border border-slate-200 rounded-3xl p-6 shadow-xs flex flex-col justify-between space-y-4 hover:border-red-300 transition">
            <div class="space-y-2.5">
              <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                  <div class="w-10 h-10 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center font-black text-lg">
                    WP
                  </div>
                  <div>
                    <h3 class="text-sm font-black text-slate-900">WordPress (1-Click Plugin)</h3>
                    <p class="text-[11px] text-slate-500">For Elementor, Contact Form 7, WPForms, Gravity Forms & Ninja Forms</p>
                  </div>
                </div>
                <span class="px-2 py-0.5 bg-indigo-100 text-indigo-700 text-[10px] font-extrabold rounded-full">
                  Easiest for WP
                </span>
              </div>

              <p class="text-xs text-slate-600 leading-relaxed">
                Download your pre-configured WordPress plugin. Upload it in your WordPress admin panel (<code class="text-[10px] text-indigo-600 font-bold bg-indigo-50 px-1 py-0.5 rounded">Plugins > Add New > Upload</code>) and activate it. Everything syncs automatically with 0 configuration!
              </p>
            </div>

            <div class="space-y-3 pt-2">
              <a 
                href="/api/v1/integration/download-wordpress-plugin" 
                download="jrv-crm-website-sync.php"
                class="w-full py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-extrabold text-xs rounded-xl shadow-md flex items-center justify-center gap-2 transition"
              >
                <ArrowUpTrayIcon class="w-4 h-4 rotate-180" />
                <span>⬇ 1-Click Download WordPress Plugin</span>
              </a>

              <div class="text-[10px] text-slate-400 text-center font-medium">
                ✓ Auto-detects all WordPress forms • Zero coding needed
              </div>
            </div>
          </div>

          <!-- OPTION 2: Universal 1-Line Script (Any Website) -->
          <div class="bg-white border border-slate-200 rounded-3xl p-6 shadow-xs flex flex-col justify-between space-y-4 hover:border-red-300 transition">
            <div class="space-y-2.5">
              <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                  <div class="w-10 h-10 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center font-black text-lg">
                    ⚡
                  </div>
                  <div>
                    <h3 class="text-sm font-black text-slate-900">Universal 1-Line Script</h3>
                    <p class="text-[11px] text-slate-500">Works on HTML, PHP, React, Next.js, Webflow, Wix, Shopify</p>
                  </div>
                </div>
                <span class="px-2 py-0.5 bg-emerald-100 text-emerald-700 text-[10px] font-extrabold rounded-full">
                  1-Line Embed
                </span>
              </div>

              <p class="text-xs text-slate-600 leading-relaxed">
                Paste this single tag right before the closing <code class="text-[10px] text-emerald-700 font-bold bg-emerald-50 px-1 py-0.5 rounded">&lt;/body&gt;</code> tag on your website. It automatically captures any existing form submissions without altering your design!
              </p>
            </div>

            <div class="space-y-3 pt-2">
              <button 
                @click="copyToClipboard(universalScriptTag, 'universal_script')"
                class="w-full py-3 bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold text-xs rounded-xl shadow-md flex items-center justify-center gap-2 transition cursor-pointer"
              >
                <CheckIcon v-if="copiedField === 'universal_script'" class="w-4 h-4 stroke-[3]" />
                <ClipboardDocumentIcon v-else class="w-4 h-4" />
                <span>{{ copiedField === 'universal_script' ? '✓ Script Copied to Clipboard!' : '📋 1-Click Copy Universal Script' }}</span>
              </button>

              <pre class="p-3 bg-slate-900 text-emerald-400 font-mono text-[11px] rounded-xl overflow-x-auto select-all whitespace-pre-wrap break-all">{{ universalScriptTag }}</pre>
            </div>
          </div>

          <!-- OPTION 3: Floating Lead & Inquiry Widget -->
          <div class="bg-white border border-slate-200 rounded-3xl p-6 shadow-xs flex flex-col justify-between space-y-4 hover:border-red-300 transition">
            <div class="space-y-2.5">
              <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                  <div class="w-10 h-10 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center font-black text-lg">
                    💬
                  </div>
                  <div>
                    <h3 class="text-sm font-black text-slate-900">Floating Inquiry Widget (No Forms Needed)</h3>
                    <p class="text-[11px] text-slate-500">Adds an instant popup contact button to any website</p>
                  </div>
                </div>
                <span class="px-2 py-0.5 bg-amber-100 text-amber-700 text-[10px] font-extrabold rounded-full">
                  Instant Widget
                </span>
              </div>

              <p class="text-xs text-slate-600 leading-relaxed">
                Don't have an inquiry form on your website? Paste this code to add an animated floating inquiry button with a complete, mobile-friendly popup lead capture form!
              </p>
            </div>

            <div class="space-y-3 pt-2">
              <button 
                @click="copyToClipboard(floatingWidgetScriptTag, 'floating_widget')"
                class="w-full py-3 bg-amber-500 hover:bg-amber-600 text-slate-950 font-black text-xs rounded-xl shadow-md flex items-center justify-center gap-2 transition cursor-pointer"
              >
                <CheckIcon v-if="copiedField === 'floating_widget'" class="w-4 h-4 stroke-[3]" />
                <ClipboardDocumentIcon v-else class="w-4 h-4" />
                <span>{{ copiedField === 'floating_widget' ? '✓ Widget Code Copied!' : '✨ 1-Click Copy Floating Widget Code' }}</span>
              </button>

              <pre class="p-3 bg-slate-900 text-amber-300 font-mono text-[11px] rounded-xl overflow-x-auto select-all whitespace-pre-wrap break-all">{{ floatingWidgetScriptTag }}</pre>
            </div>
          </div>

          <!-- OPTION 4: Responsive iFrame & Direct Hosted Link -->
          <div class="bg-white border border-slate-200 rounded-3xl p-6 shadow-xs flex flex-col justify-between space-y-4 hover:border-red-300 transition">
            <div class="space-y-2.5">
              <div class="flex items-center justify-between">
                <div class="flex items-center gap-2">
                  <div class="w-10 h-10 rounded-2xl bg-rose-50 text-rose-600 flex items-center justify-center font-black text-lg">
                    🔗
                  </div>
                  <div>
                    <h3 class="text-sm font-black text-slate-900">Embed iFrame & WhatsApp Link</h3>
                    <p class="text-[11px] text-slate-500">Embed a full form page or share direct link on social media</p>
                  </div>
                </div>
                <span class="px-2 py-0.5 bg-rose-100 text-rose-700 text-[10px] font-extrabold rounded-full">
                  Share & Embed
                </span>
              </div>

              <p class="text-xs text-slate-600 leading-relaxed">
                Embed a clean registration form on any page via iFrame, or share the direct link on WhatsApp, Instagram bio, brochures, and Google My Business profile.
              </p>
            </div>

            <div class="space-y-3 pt-2">
              <button 
                @click="copyToClipboard(iframeSnippet, 'iframe_code')"
                class="w-full py-3 bg-rose-600 hover:bg-rose-700 text-white font-extrabold text-xs rounded-xl shadow-md flex items-center justify-center gap-2 transition cursor-pointer"
              >
                <CheckIcon v-if="copiedField === 'iframe_code'" class="w-4 h-4 stroke-[3]" />
                <ClipboardDocumentIcon v-else class="w-4 h-4" />
                <span>{{ copiedField === 'iframe_code' ? '✓ iFrame Snippet Copied!' : '📋 1-Click Copy iFrame Code' }}</span>
              </button>

              <pre class="p-3 bg-slate-900 text-rose-300 font-mono text-[11px] rounded-xl overflow-x-auto select-all whitespace-pre-wrap break-all">{{ iframeSnippet }}</pre>
            </div>
          </div>

          <!-- OPTION 5: 1-Click Online Payment & Fee Checkout Button -->
          <div class="md:col-span-2 bg-gradient-to-r from-red-500/10 via-amber-500/10 to-transparent border-2 border-red-200 rounded-3xl p-6 shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-6 hover:border-red-400 transition">
            <div class="space-y-2">
              <div class="flex items-center gap-2.5">
                <div class="w-10 h-10 rounded-2xl bg-red-600 text-white flex items-center justify-center text-xl font-bold shadow-md shadow-red-600/25">
                  💳
                </div>
                <div>
                  <h3 class="text-sm font-black text-slate-900">Option 5: 1-Click Website Payment & Fee Collection Gateway</h3>
                  <p class="text-xs text-slate-500 font-medium">Accept UPI QR, Bank Transfer, and Card payments directly on your website</p>
                </div>
              </div>
              <p class="text-xs text-slate-600 max-w-2xl leading-relaxed">
                Add an instant <strong>"Pay ₹1,000 Online"</strong> button or full checkout popup to your website. Receipts are automatically generated and transactions log into your CRM directory!
              </p>
            </div>

            <div class="flex flex-wrap items-center gap-3 shrink-0">
              <button 
                type="button" 
                @click="copyToClipboard(htmlPaymentButtonSnippet, 'payment_btn_opt5')"
                class="px-5 py-3 bg-red-600 hover:bg-red-700 text-white font-black text-xs uppercase tracking-wider rounded-2xl shadow-lg shadow-red-600/20 transition flex items-center gap-2 cursor-pointer"
              >
                <CheckIcon v-if="copiedField === 'payment_btn_opt5'" class="w-4 h-4 stroke-[3]" />
                <DocumentDuplicateIcon v-else class="w-4 h-4" />
                <span>{{ copiedField === 'payment_btn_opt5' ? '✓ Button HTML Copied!' : '📋 Copy Pay Button Code' }}</span>
              </button>

              <button 
                type="button" 
                @click="activeTab = 'payments'"
                class="px-5 py-3 bg-slate-900 hover:bg-black text-white font-bold text-xs rounded-2xl shadow-sm transition flex items-center gap-1.5 cursor-pointer"
              >
                <CreditCardIcon class="w-4 h-4 text-amber-400" />
                <span>Configure Payments & QR &rarr;</span>
              </button>
            </div>
          </div>

        </div>

        <!-- LIVE LEAD SIMULATOR / TEST SANDBOX -->
        <div class="bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900 rounded-3xl p-6 sm:p-8 text-white shadow-xl space-y-6">
          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-700 pb-4">
            <div class="space-y-1">
              <div class="flex items-center gap-2">
                <span class="text-lg">🧪</span>
                <h3 class="text-base font-black text-white">Live Lead Simulator (Try It Now)</h3>
              </div>
              <p class="text-xs text-slate-400">
                Test submitting an inquiry right now to watch how leads instantly flow into your CRM database:
              </p>
            </div>
            <span class="px-3 py-1 bg-emerald-500/20 text-emerald-300 border border-emerald-500/40 text-[10px] font-extrabold rounded-full shrink-0">
              Live Test Sandbox
            </span>
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
            <div class="space-y-1.5">
              <label class="text-[11px] font-bold text-slate-300">Candidate / Student Name</label>
              <input v-model="simulationForm.name" type="text" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3.5 py-2 text-xs font-semibold text-white focus:border-red-500 outline-none" />
            </div>

            <div class="space-y-1.5">
              <label class="text-[11px] font-bold text-slate-300">Phone / WhatsApp</label>
              <input v-model="simulationForm.phone" type="text" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3.5 py-2 text-xs font-semibold text-white focus:border-red-500 outline-none" />
            </div>

            <div class="space-y-1.5">
              <label class="text-[11px] font-bold text-slate-300">Email Address</label>
              <input v-model="simulationForm.email" type="email" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3.5 py-2 text-xs font-semibold text-white focus:border-red-500 outline-none" />
            </div>

            <div class="space-y-1.5">
              <label class="text-[11px] font-bold text-slate-300">Inquiry / Course Message</label>
              <input v-model="simulationForm.message" type="text" class="w-full bg-slate-800 border border-slate-700 rounded-xl px-3.5 py-2 text-xs font-semibold text-white focus:border-red-500 outline-none" />
            </div>
          </div>

          <div class="flex flex-col sm:flex-row items-center justify-between gap-4 pt-2">
            <button 
              @click="sendSimulationLead"
              :disabled="isSimulating"
              class="w-full sm:w-auto px-6 py-3 bg-red-600 hover:bg-red-700 text-white font-black text-xs rounded-xl shadow-lg flex items-center justify-center gap-2 transition cursor-pointer disabled:opacity-75"
            >
              <div v-if="isSimulating" class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></div>
              <span v-else>🚀</span>
              <span>{{ isSimulating ? 'Sending to CRM...' : 'Send Test Lead to CRM' }}</span>
            </button>

            <!-- Simulation Feedback Result -->
            <div v-if="simulationResult" class="p-3 bg-emerald-500/90 border border-emerald-300 rounded-xl text-white text-xs font-semibold flex items-center gap-3">
              <span>✅ Lead created in CRM (Ref: {{ simulationResult.member_code }})</span>
              <Link href="/tenant/crm-records" class="px-2.5 py-1 bg-white text-emerald-800 text-[10px] font-black rounded-lg hover:bg-emerald-50 transition">
                View in CRM &rarr;
              </Link>
            </div>
          </div>
        </div>

        <!-- DEVELOPER MULTI-LANGUAGE BACKEND SNIPPETS -->
        <div class="bg-white border border-slate-200 rounded-3xl p-6 sm:p-8 shadow-xs space-y-6">
          <div class="border-b border-slate-100 pb-4">
            <h3 class="text-lg font-black text-slate-900">Custom Developer REST API & Webhooks</h3>
            <p class="text-xs text-slate-500 font-medium">If your website uses a custom backend, select your programming language below for instant copy-paste code:</p>
          </div>

          <!-- Language Selector -->
          <div class="flex items-center gap-2 overflow-x-auto pb-2">
            <button 
              @click="selectedLang = 'universal_js'"
              :class="[
                'px-4 py-2 rounded-xl text-xs font-bold transition whitespace-nowrap cursor-pointer',
                selectedLang === 'universal_js' ? 'bg-red-600 text-white shadow-xs' : 'bg-slate-100 text-slate-700 hover:bg-slate-200'
              ]"
            >
              JavaScript / HTML
            </button>

            <button 
              @click="selectedLang = 'php'"
              :class="[
                'px-4 py-2 rounded-xl text-xs font-bold transition whitespace-nowrap cursor-pointer',
                selectedLang === 'php' ? 'bg-red-600 text-white shadow-xs' : 'bg-slate-100 text-slate-700 hover:bg-slate-200'
              ]"
            >
              PHP / Laravel
            </button>

            <button 
              @click="selectedLang = 'python'"
              :class="[
                'px-4 py-2 rounded-xl text-xs font-bold transition whitespace-nowrap cursor-pointer',
                selectedLang === 'python' ? 'bg-red-600 text-white shadow-xs' : 'bg-slate-100 text-slate-700 hover:bg-slate-200'
              ]"
            >
              Python / Django / Flask
            </button>

            <button 
              @click="selectedLang = 'react'"
              :class="[
                'px-4 py-2 rounded-xl text-xs font-bold transition whitespace-nowrap cursor-pointer',
                selectedLang === 'react' ? 'bg-red-600 text-white shadow-xs' : 'bg-slate-100 text-slate-700 hover:bg-slate-200'
              ]"
            >
              React / Next.js
            </button>

            <button 
              @click="selectedLang = 'curl'"
              :class="[
                'px-4 py-2 rounded-xl text-xs font-bold transition whitespace-nowrap cursor-pointer',
                selectedLang === 'curl' ? 'bg-red-600 text-white shadow-xs' : 'bg-slate-100 text-slate-700 hover:bg-slate-200'
              ]"
            >
              cURL Command
            </button>
          </div>

          <!-- JavaScript Snippet -->
          <div v-if="selectedLang === 'universal_js'" class="space-y-3">
            <div class="flex items-center justify-between">
              <span class="text-xs font-bold text-slate-700">Native JavaScript (Fetch API)</span>
              <button 
                @click="copyToClipboard(jsSnippet, 'js_code')"
                class="px-3 py-1 bg-white hover:bg-slate-100 text-slate-700 border border-slate-300 text-[11px] font-bold rounded-lg flex items-center gap-1.5 cursor-pointer"
              >
                <CheckIcon v-if="copiedField === 'js_code'" class="w-3.5 h-3.5 text-emerald-600 stroke-[3]" />
                <ClipboardDocumentIcon v-else class="w-3.5 h-3.5" />
                <span>{{ copiedField === 'js_code' ? 'Copied!' : 'Copy JavaScript' }}</span>
              </button>
            </div>
            <pre class="p-4 bg-slate-900 text-emerald-300 font-mono text-xs rounded-2xl overflow-x-auto max-w-full whitespace-pre-wrap break-all select-all">{{ jsSnippet }}</pre>
          </div>

          <!-- PHP Snippet -->
          <div v-if="selectedLang === 'php'" class="space-y-3">
            <div class="flex items-center justify-between">
              <span class="text-xs font-bold text-slate-700">PHP (cURL)</span>
              <button 
                @click="copyToClipboard(phpSnippet, 'php_code')"
                class="px-3 py-1 bg-white hover:bg-slate-100 text-slate-700 border border-slate-300 text-[11px] font-bold rounded-lg flex items-center gap-1.5 cursor-pointer"
              >
                <CheckIcon v-if="copiedField === 'php_code'" class="w-3.5 h-3.5 text-emerald-600 stroke-[3]" />
                <ClipboardDocumentIcon v-else class="w-3.5 h-3.5" />
                <span>{{ copiedField === 'php_code' ? 'Copied!' : 'Copy PHP Code' }}</span>
              </button>
            </div>
            <pre class="p-4 bg-slate-900 text-amber-300 font-mono text-xs rounded-2xl overflow-x-auto max-w-full whitespace-pre-wrap break-all select-all">{{ phpSnippet }}</pre>
          </div>

          <!-- Python Snippet -->
          <div v-if="selectedLang === 'python'" class="space-y-3">
            <div class="flex items-center justify-between">
              <span class="text-xs font-bold text-slate-700">Python (requests library)</span>
              <button 
                @click="copyToClipboard(pySnippet, 'py_code')"
                class="px-3 py-1 bg-white hover:bg-slate-100 text-slate-700 border border-slate-300 text-[11px] font-bold rounded-lg flex items-center gap-1.5 cursor-pointer"
              >
                <CheckIcon v-if="copiedField === 'py_code'" class="w-3.5 h-3.5 text-emerald-600 stroke-[3]" />
                <ClipboardDocumentIcon v-else class="w-3.5 h-3.5" />
                <span>{{ copiedField === 'py_code' ? 'Copied!' : 'Copy Python' }}</span>
              </button>
            </div>
            <pre class="p-4 bg-slate-900 text-sky-300 font-mono text-xs rounded-2xl overflow-x-auto max-w-full whitespace-pre-wrap break-all select-all">{{ pySnippet }}</pre>
          </div>

          <!-- React Snippet -->
          <div v-if="selectedLang === 'react'" class="space-y-3">
            <div class="flex items-center justify-between">
              <span class="text-xs font-bold text-slate-700">React / Next.js Form Handler</span>
              <button 
                @click="copyToClipboard(reactSnippet, 'react_code')"
                class="px-3 py-1 bg-white hover:bg-slate-100 text-slate-700 border border-slate-300 text-[11px] font-bold rounded-lg flex items-center gap-1.5 cursor-pointer"
              >
                <CheckIcon v-if="copiedField === 'react_code'" class="w-3.5 h-3.5 text-emerald-600 stroke-[3]" />
                <ClipboardDocumentIcon v-else class="w-3.5 h-3.5" />
                <span>{{ copiedField === 'react_code' ? 'Copied!' : 'Copy React' }}</span>
              </button>
            </div>
            <pre class="p-4 bg-slate-900 text-indigo-300 font-mono text-xs rounded-2xl overflow-x-auto max-w-full whitespace-pre-wrap break-all select-all">{{ reactSnippet }}</pre>
          </div>

          <!-- cURL Snippet -->
          <div v-if="selectedLang === 'curl'" class="space-y-3">
            <div class="flex items-center justify-between">
              <span class="text-xs font-bold text-slate-700">cURL Terminal Command</span>
              <button 
                @click="copyToClipboard(curlSnippet, 'curl_code')"
                class="px-3 py-1 bg-white hover:bg-slate-100 text-slate-700 border border-slate-300 text-[11px] font-bold rounded-lg flex items-center gap-1.5 cursor-pointer"
              >
                <CheckIcon v-if="copiedField === 'curl_code'" class="w-3.5 h-3.5 text-emerald-600 stroke-[3]" />
                <ClipboardDocumentIcon v-else class="w-3.5 h-3.5" />
                <span>{{ copiedField === 'curl_code' ? 'Copied!' : 'Copy cURL' }}</span>
              </button>
            </div>
            <pre class="p-4 bg-slate-900 text-purple-300 font-mono text-xs rounded-2xl overflow-x-auto max-w-full whitespace-pre-wrap break-all select-all">{{ curlSnippet }}</pre>
          </div>
        </div>

        <!-- Live Interactive Preview Box -->
        <div class="bg-white border border-slate-200 rounded-3xl p-6 sm:p-8 shadow-xs space-y-4">
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-2.5">
              <div class="w-8 h-8 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold">
                <CheckCircleIcon class="w-5 h-5" />
              </div>
              <div>
                <h3 class="text-sm font-black text-slate-900">Live Hosted Form Preview</h3>
                <p class="text-[11px] text-slate-500 font-medium">This is exactly how students and website visitors experience your branded registration form:</p>
              </div>
            </div>

            <a 
              :href="appUrl + '/embed/register'" 
              target="_blank" 
              class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs rounded-xl border border-slate-300 flex items-center gap-1.5 transition"
            >
              <span>Test in Full Screen</span>
              <ArrowTopRightOnSquareIcon class="w-3.5 h-3.5" />
            </a>
          </div>

          <div class="rounded-2xl border border-slate-200 overflow-hidden shadow-inner bg-slate-50">
            <iframe 
              :src="appUrl + '/embed/register'" 
              class="w-full h-[600px] border-none"
              title="Live Registration Form Preview"
            ></iframe>
          </div>
        </div>

      </div>

      <!-- TAB 5: Account Security & Password Reset -->
      <div v-if="activeTab === 'security'" class="bg-white border border-slate-200 rounded-3xl p-6 sm:p-8 shadow-xs space-y-6 max-w-2xl">
        <div class="border-b border-slate-100 pb-4">
          <h2 class="text-lg font-black text-slate-900">Reset Account Password</h2>
          <p class="text-xs text-slate-500 font-medium">Update the login password for your CRM user account ({{ user.email }}).</p>
        </div>

        <form @submit.prevent="savePassword" class="space-y-4">
          <div class="space-y-1.5">
            <label class="text-xs font-bold text-slate-700">Current Password</label>
            <input v-model="passwordForm.current_password" type="password" required class="w-full bg-slate-50 border border-slate-300 rounded-xl px-4 py-2.5 text-xs text-slate-900 focus:bg-white focus:border-red-500 outline-none" />
            <span v-if="passwordForm.errors.current_password" class="text-xs text-red-600 font-bold block">{{ passwordForm.errors.current_password }}</span>
          </div>

          <div class="space-y-1.5">
            <label class="text-xs font-bold text-slate-700">New Password</label>
            <input v-model="passwordForm.new_password" type="password" required class="w-full bg-slate-50 border border-slate-300 rounded-xl px-4 py-2.5 text-xs text-slate-900 focus:bg-white focus:border-red-500 outline-none" />
            <span v-if="passwordForm.errors.new_password" class="text-xs text-red-600 font-bold block">{{ passwordForm.errors.new_password }}</span>
          </div>

          <div class="space-y-1.5">
            <label class="text-xs font-bold text-slate-700">Confirm New Password</label>
            <input v-model="passwordForm.new_password_confirmation" type="password" required class="w-full bg-slate-50 border border-slate-300 rounded-xl px-4 py-2.5 text-xs text-slate-900 focus:bg-white focus:border-red-500 outline-none" />
          </div>

          <div class="pt-3">
            <button 
              type="submit"
              :disabled="passwordForm.processing"
              class="px-6 py-2.5 bg-red-600 hover:bg-red-700 text-white text-xs font-bold rounded-xl shadow-md shadow-red-600/20 flex items-center gap-2 transition cursor-pointer"
            >
              <KeyIcon class="w-4 h-4" />
              <span>Update Password</span>
            </button>
          </div>
        </form>
      </div>

    </main>
  </div>
</template>
