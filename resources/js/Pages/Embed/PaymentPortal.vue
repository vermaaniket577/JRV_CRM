<script setup>
import { ref, computed } from 'vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { 
  QrCodeIcon, 
  BuildingLibraryIcon, 
  CreditCardIcon, 
  CheckBadgeIcon, 
  ShieldCheckIcon,
  DocumentDuplicateIcon, 
  ArrowTopRightOnSquareIcon,
  PrinterIcon,
  SparklesIcon,
  CheckCircleIcon,
  PhotoIcon,
  InformationCircleIcon,
  ArrowPathIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
  tenantId: [Number, String],
  business: {
    type: Object,
    default: () => ({
      name: 'Admissions Dekho',
      icon: '🎓',
      brand_color: '#dc2626',
      support_email: 'support@admissionsdekho.com',
      contact_phone: '+91 98765 43210',
      whatsapp_number: '+91 98765 43210',
    })
  },
  payment: {
    type: Object,
    default: () => ({
      gateway: 'upi',
      currency: 'INR',
      upi_id: 'admissionsdekho@okaxis',
      upi_merchant_name: 'Admissions Dekho',
      upi_qr_code_url: '',
      bank_name: 'HDFC Bank Ltd',
      bank_account_holder: 'Admissions Dekho Pvt Ltd',
      bank_account_number: '50200084920194',
      bank_ifsc_code: 'HDFC0001234',
      bank_account_type: 'Current Account',
      bank_branch: 'Connaught Place, New Delhi',
      bank_swift_code: 'HDFCINBB',
      bank_instructions: 'Please enter student name in remarks and enter the UTR number below.',
      razorpay_key: '',
      stripe_key: '',
    })
  },
  preset: {
    type: Object,
    default: () => ({
      amount: 1000,
      purpose: 'Admission Registration & Processing Fee',
      customer_name: '',
      customer_email: '',
      customer_phone: '',
      invoice_ref: 'INV-2026-001',
    })
  }
});

const page = usePage();
const activeMethod = ref('upi'); // 'upi', 'bank_transfer', 'gateway'
const copiedKey = ref('');
const isCustomAmount = ref(false);

const form = useForm({
  tenant_id: props.tenantId || 7,
  customer_name: props.preset?.customer_name || '',
  customer_email: props.preset?.customer_email || '',
  customer_phone: props.preset?.customer_phone || '',
  amount: props.preset?.amount || 1000,
  purpose: props.preset?.purpose || 'Admission Registration & Processing Fee',
  payment_method: 'upi',
  transaction_ref: '',
  utr_number: '',
  notes: '',
  proof_file: null,
});

const proofPreview = ref(null);

const handleFileChange = (e) => {
  const file = e.target.files[0];
  if (file) {
    form.proof_file = file;
    if (file.type.startsWith('image/')) {
      const reader = new FileReader();
      reader.onload = (re) => {
        proofPreview.value = re.target.result;
      };
      reader.readAsDataURL(file);
    } else {
      proofPreview.value = null;
    }
  }
};

const copyToClipboard = (text, key) => {
  if (!text) return;
  navigator.clipboard.writeText(text);
  copiedKey.value = key;
  setTimeout(() => {
    copiedKey.value = '';
  }, 2000);
};

// Dynamic UPI URI Generator with Amount & Note
const upiUri = computed(() => {
  const pa = encodeURIComponent(props.payment?.upi_id || 'admissionsdekho@okaxis');
  const pn = encodeURIComponent(props.payment?.upi_merchant_name || props.business?.name || 'Merchant');
  const am = encodeURIComponent(form.amount || 1000);
  const cu = 'INR';
  const tn = encodeURIComponent(form.purpose || 'Fee Payment');
  return `upi://pay?pa=${pa}&pn=${pn}&am=${am}&cu=${cu}&tn=${tn}`;
});

// Generated QR Code URL (using Google Charts API or fallback QR)
const generatedQrCodeUrl = computed(() => {
  if (props.payment?.upi_qr_code_url) {
    return props.payment.upi_qr_code_url;
  }
  const data = encodeURIComponent(upiUri.value);
  return `https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=${data}&margin=10`;
});

const submitPayment = () => {
  form.payment_method = activeMethod.value;
  form.post('/embed/pay', {
    forceFormData: true,
    preserveScroll: true,
  });
};

const receiptData = computed(() => page.props.flash?.receipt || null);

const printReceipt = () => {
  window.print();
};
</script>

<template>
  <Head :title="`Pay ${business.name} - Online Payment & Fee Checkout`" />

  <div class="min-h-screen bg-slate-100/80 p-3 sm:p-6 lg:p-10 font-sans text-slate-900 flex items-center justify-center">
    <div class="max-w-4xl w-full bg-white rounded-3xl shadow-xl border border-slate-200 overflow-hidden">
      
      <!-- TOP PORTAL HEADER -->
      <div class="bg-gradient-to-r from-slate-900 via-slate-800 to-slate-900 text-white p-5 sm:p-6 flex flex-wrap items-center justify-between gap-4 border-b border-slate-800">
        <div class="flex items-center gap-3.5">
          <div class="w-12 h-12 rounded-2xl bg-white/10 backdrop-blur-md border border-white/20 flex items-center justify-center text-2xl shadow-inner shrink-0">
            {{ business.icon || '🎓' }}
          </div>
          <div>
            <div class="flex items-center gap-2">
              <h1 class="text-base sm:text-lg font-black tracking-tight text-white">{{ business.name }}</h1>
              <span class="inline-flex items-center gap-1 bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 text-[10px] font-bold px-2 py-0.5 rounded-full">
                <ShieldCheckIcon class="w-3 h-3" />
                Verified Merchant
              </span>
            </div>
            <p class="text-xs text-slate-400 font-medium">Official Online Payment & Fee Collection Gateway</p>
          </div>
        </div>

        <div class="flex items-center gap-4 text-xs font-semibold text-slate-300">
          <div class="hidden sm:flex items-center gap-1.5 bg-slate-800/80 px-3 py-1.5 rounded-xl border border-slate-700">
            <CheckBadgeIcon class="w-4 h-4 text-emerald-400" />
            <span>256-Bit SSL Encrypted</span>
          </div>
          <a :href="`mailto:${business.support_email}`" class="text-slate-400 hover:text-white transition">
            Help & Support
          </a>
        </div>
      </div>

      <!-- SUCCESS RECEIPT VIEW -->
      <div v-if="receiptData" class="p-6 sm:p-10 space-y-6 bg-slate-50 print:p-0 print:bg-white animate-fadeIn">
        <div class="bg-white border border-emerald-200 rounded-3xl p-6 sm:p-8 shadow-md text-center space-y-4 max-w-xl mx-auto">
          <div class="w-16 h-16 bg-emerald-100 text-emerald-600 rounded-full flex items-center justify-center mx-auto text-3xl shadow-xs">
            ✓
          </div>
          <div class="space-y-1">
            <span class="text-xs font-black text-emerald-600 uppercase tracking-widest">Payment Received & Logged</span>
            <h2 class="text-2xl font-black text-slate-900">₹{{ Number(receiptData.amount).toLocaleString('en-IN') }}</h2>
            <p class="text-xs font-medium text-slate-500">{{ receiptData.purpose }}</p>
          </div>

          <!-- Receipt Details Table -->
          <div class="bg-slate-50 border border-slate-200/90 rounded-2xl p-4 text-left space-y-2.5 text-xs">
            <div class="flex justify-between border-b border-slate-200 pb-2">
              <span class="text-slate-500 font-medium">Receipt Number:</span>
              <span class="font-mono font-bold text-slate-900">{{ receiptData.receipt_number }}</span>
            </div>
            <div class="flex justify-between border-b border-slate-200 pb-2">
              <span class="text-slate-500 font-medium">Transaction / UTR:</span>
              <span class="font-mono font-bold text-emerald-700">{{ receiptData.transaction_id }}</span>
            </div>
            <div class="flex justify-between border-b border-slate-200 pb-2">
              <span class="text-slate-500 font-medium">Payer Name:</span>
              <span class="font-bold text-slate-900">{{ receiptData.customer_name }}</span>
            </div>
            <div class="flex justify-between border-b border-slate-200 pb-2">
              <span class="text-slate-500 font-medium">Phone & Email:</span>
              <span class="font-medium text-slate-700">{{ receiptData.customer_phone }} • {{ receiptData.customer_email }}</span>
            </div>
            <div class="flex justify-between border-b border-slate-200 pb-2">
              <span class="text-slate-500 font-medium">Payment Mode:</span>
              <span class="font-bold text-slate-800">{{ receiptData.payment_method }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-slate-500 font-medium">Date & Time:</span>
              <span class="font-medium text-slate-700">{{ receiptData.timestamp }}</span>
            </div>
          </div>

          <div class="flex items-center justify-center gap-3 pt-2 print:hidden">
            <button 
              @click="printReceipt" 
              class="px-5 py-2.5 bg-slate-900 hover:bg-black text-white font-bold text-xs rounded-xl shadow-xs transition flex items-center gap-2 cursor-pointer"
            >
              <PrinterIcon class="w-4 h-4" />
              <span>Print Official Receipt</span>
            </button>
            <button 
              @click="window.location.reload()" 
              class="px-5 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs rounded-xl transition cursor-pointer"
            >
              Make Another Payment
            </button>
          </div>
        </div>
      </div>

      <!-- MAIN CHECKOUT WORKFLOW -->
      <div v-else class="grid grid-cols-1 lg:grid-cols-12 divide-y lg:divide-y-0 lg:divide-x divide-slate-200">
        
        <!-- LEFT COLUMN: Order & Payer Information (5 cols) -->
        <div class="lg:col-span-5 p-6 sm:p-8 bg-slate-50/60 space-y-6">
          <div>
            <span class="text-[11px] font-black text-red-600 uppercase tracking-wider">Step 1 of 2</span>
            <h2 class="text-base font-black text-slate-900">Payment & Payer Details</h2>
            <p class="text-xs text-slate-500 font-medium">Confirm the fee amount and enter your contact details.</p>
          </div>

          <!-- Fee Summary Card -->
          <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-xs space-y-3">
            <div class="flex items-start justify-between">
              <div>
                <span class="text-[11px] font-bold text-slate-400 uppercase">Payment Purpose</span>
                <h3 class="text-sm font-black text-slate-800 leading-snug">{{ form.purpose }}</h3>
              </div>
              <span class="px-2 py-0.5 bg-red-50 text-red-600 font-mono text-[10px] font-bold rounded-md border border-red-100">
                {{ preset.invoice_ref }}
              </span>
            </div>

            <div class="pt-3 border-t border-slate-100">
              <label class="text-[11px] font-bold text-slate-500 block mb-1">Total Payable Amount (₹)</label>
              <div class="relative">
                <span class="absolute left-3.5 top-1/2 -translate-y-1/2 font-black text-lg text-slate-500">₹</span>
                <input 
                  v-model="form.amount" 
                  type="number" 
                  min="1" 
                  required 
                  class="w-full bg-slate-50 border border-slate-300 rounded-xl pl-8 pr-4 py-2.5 text-xl font-black text-slate-900 focus:bg-white focus:outline-none focus:border-red-600 focus:ring-2 focus:ring-red-100"
                />
              </div>
            </div>
          </div>

          <!-- Customer Form Fields -->
          <div class="space-y-3 text-xs">
            <div>
              <label class="font-bold text-slate-700 block mb-1">Full Name / Student Name *</label>
              <input 
                v-model="form.customer_name" 
                type="text" 
                placeholder="e.g. Rahul Sharma" 
                required 
                class="w-full bg-white border border-slate-300 rounded-xl px-3.5 py-2.5 font-bold text-slate-900 focus:outline-none focus:border-red-600"
              />
            </div>

            <div>
              <label class="font-bold text-slate-700 block mb-1">Phone / WhatsApp Number *</label>
              <input 
                v-model="form.customer_phone" 
                type="tel" 
                placeholder="e.g. +91 98765 43210" 
                required 
                class="w-full bg-white border border-slate-300 rounded-xl px-3.5 py-2.5 font-bold text-slate-900 focus:outline-none focus:border-red-600"
              />
            </div>

            <div>
              <label class="font-bold text-slate-700 block mb-1">Email Address (For Instant Receipt) *</label>
              <input 
                v-model="form.customer_email" 
                type="email" 
                placeholder="e.g. rahul@example.com" 
                required 
                class="w-full bg-white border border-slate-300 rounded-xl px-3.5 py-2.5 font-bold text-slate-900 focus:outline-none focus:border-red-600"
              />
            </div>
          </div>

          <!-- Security note -->
          <div class="p-3.5 bg-slate-100 rounded-xl text-[11px] text-slate-500 font-medium flex items-center gap-2">
            <ShieldCheckIcon class="w-4 h-4 text-emerald-600 shrink-0" />
            <span>Instant CRM receipt will be generated immediately after payment submission.</span>
          </div>
        </div>

        <!-- RIGHT COLUMN: Payment Method Select & Checkout (7 cols) -->
        <div class="lg:col-span-7 p-6 sm:p-8 space-y-6">
          <div>
            <span class="text-[11px] font-black text-red-600 uppercase tracking-wider">Step 2 of 2</span>
            <h2 class="text-base font-black text-slate-900">Choose Payment Method</h2>
            <p class="text-xs text-slate-500 font-medium">Select your preferred payment channel to complete the transaction.</p>
          </div>

          <!-- Payment Channel Tabs -->
          <div class="grid grid-cols-2 sm:grid-cols-3 gap-2 p-1 bg-slate-100 rounded-2xl">
            <button 
              type="button" 
              @click="activeMethod = 'upi'"
              :class="activeMethod === 'upi' ? 'bg-white text-slate-900 shadow-sm font-bold' : 'text-slate-600 hover:text-slate-900 font-medium'"
              class="py-2.5 px-3 rounded-xl text-xs flex items-center justify-center gap-1.5 transition cursor-pointer"
            >
              <QrCodeIcon class="w-4 h-4 text-red-600" />
              <span>Scan UPI QR</span>
            </button>

            <button 
              type="button" 
              @click="activeMethod = 'bank_transfer'"
              :class="activeMethod === 'bank_transfer' ? 'bg-white text-slate-900 shadow-sm font-bold' : 'text-slate-600 hover:text-slate-900 font-medium'"
              class="py-2.5 px-3 rounded-xl text-xs flex items-center justify-center gap-1.5 transition cursor-pointer"
            >
              <BuildingLibraryIcon class="w-4 h-4 text-blue-600" />
              <span>Bank Transfer</span>
            </button>

            <button 
              type="button" 
              @click="activeMethod = 'card'"
              :class="activeMethod === 'card' ? 'bg-white text-slate-900 shadow-sm font-bold' : 'text-slate-600 hover:text-slate-900 font-medium'"
              class="col-span-2 sm:col-span-1 py-2.5 px-3 rounded-xl text-xs flex items-center justify-center gap-1.5 transition cursor-pointer"
            >
              <CreditCardIcon class="w-4 h-4 text-emerald-600" />
              <span>Cards / NetBanking</span>
            </button>
          </div>

          <!-- METHOD 1: UPI SCAN & PAY -->
          <div v-if="activeMethod === 'upi'" class="space-y-4 animate-fadeIn text-xs">
            <div class="bg-gradient-to-b from-red-50/50 to-white border border-red-100 rounded-2xl p-5 text-center space-y-3">
              <div class="flex items-center justify-center gap-2">
                <span class="text-xs font-bold text-slate-700">Scan via GPay, PhonePe, Paytm, BHIM</span>
              </div>

              <!-- High-res QR Code Display -->
              <div class="w-44 h-44 bg-white p-2.5 mx-auto rounded-2xl shadow-sm border border-slate-200 flex items-center justify-center">
                <img :src="generatedQrCodeUrl" alt="UPI QR Code" class="w-full h-full object-contain rounded-lg" />
              </div>

              <div class="space-y-1">
                <div class="text-[11px] font-bold text-slate-400 uppercase">UPI ID / VPA</div>
                <div class="flex items-center justify-center gap-2">
                  <span class="font-mono font-bold text-slate-900 text-sm bg-slate-100 px-3 py-1 rounded-lg border border-slate-200">
                    {{ payment.upi_id }}
                  </span>
                  <button 
                    type="button" 
                    @click="copyToClipboard(payment.upi_id, 'upi_id')"
                    class="px-2.5 py-1 bg-red-600 hover:bg-red-700 text-white font-bold rounded-lg transition cursor-pointer flex items-center gap-1"
                  >
                    <DocumentDuplicateIcon class="w-3.5 h-3.5" />
                    <span>{{ copiedKey === 'upi_id' ? 'Copied!' : 'Copy' }}</span>
                  </button>
                </div>
              </div>

              <!-- Mobile Intent Deep-Link -->
              <div class="pt-1 sm:hidden">
                <a 
                  :href="upiUri" 
                  class="w-full py-3 bg-red-600 text-white font-bold rounded-xl shadow-md flex items-center justify-center gap-2 text-xs"
                >
                  <ArrowTopRightOnSquareIcon class="w-4 h-4" />
                  <span>Open in UPI App (GPay / PhonePe)</span>
                </a>
              </div>
            </div>

            <!-- UTR / Reference submission -->
            <div class="bg-slate-50 border border-slate-200 rounded-2xl p-4 space-y-3">
              <label class="font-bold text-slate-700 block">
                Enter 12-Digit UTR / Transaction ID (From your UPI app) *
              </label>
              <input 
                v-model="form.utr_number" 
                type="text" 
                placeholder="e.g. 423589123456" 
                required 
                class="w-full bg-white border border-slate-300 rounded-xl px-3.5 py-2.5 font-mono font-bold text-slate-900 focus:outline-none focus:border-red-600"
              />

              <div>
                <label class="font-bold text-slate-700 block mb-1">Upload Payment Screenshot (Optional)</label>
                <input 
                  type="file" 
                  accept="image/*,.pdf" 
                  @change="handleFileChange"
                  class="w-full text-xs text-slate-500 file:mr-3 file:py-2 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-red-50 file:text-red-700 hover:file:bg-red-100 cursor-pointer"
                />
              </div>
            </div>
          </div>

          <!-- METHOD 2: BANK TRANSFER (NEFT / IMPS / RTGS) -->
          <div v-else-if="activeMethod === 'bank_transfer'" class="space-y-4 animate-fadeIn text-xs">
            <div class="bg-gradient-to-b from-blue-50/50 to-white border border-blue-100 rounded-2xl p-5 space-y-3">
              <div class="flex items-center gap-2">
                <BuildingLibraryIcon class="w-5 h-5 text-blue-600" />
                <h4 class="font-black text-slate-900">Corporate Bank Account Details</h4>
              </div>

              <!-- Bank details Grid with 1-click copy -->
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                <div class="p-2.5 bg-white border border-slate-200 rounded-xl flex items-center justify-between">
                  <div>
                    <span class="text-[10px] text-slate-400 block font-bold">Bank Name</span>
                    <span class="font-bold text-slate-800">{{ payment.bank_name }}</span>
                  </div>
                </div>

                <div class="p-2.5 bg-white border border-slate-200 rounded-xl flex items-center justify-between">
                  <div>
                    <span class="text-[10px] text-slate-400 block font-bold">Account Type</span>
                    <span class="font-bold text-slate-800">{{ payment.bank_account_type }}</span>
                  </div>
                </div>

                <div class="p-2.5 bg-white border border-slate-200 rounded-xl flex items-center justify-between col-span-1 sm:col-span-2">
                  <div>
                    <span class="text-[10px] text-slate-400 block font-bold">Account Holder Name</span>
                    <span class="font-bold text-slate-900">{{ payment.bank_account_holder }}</span>
                  </div>
                  <button 
                    type="button" 
                    @click="copyToClipboard(payment.bank_account_holder, 'holder')"
                    class="text-[11px] font-bold text-blue-600 hover:text-blue-800 cursor-pointer"
                  >
                    {{ copiedKey === 'holder' ? 'Copied!' : 'Copy' }}
                  </button>
                </div>

                <div class="p-2.5 bg-white border border-slate-200 rounded-xl flex items-center justify-between col-span-1 sm:col-span-2">
                  <div>
                    <span class="text-[10px] text-slate-400 block font-bold">Account Number</span>
                    <span class="font-mono font-bold text-slate-900 text-sm">{{ payment.bank_account_number }}</span>
                  </div>
                  <button 
                    type="button" 
                    @click="copyToClipboard(payment.bank_account_number, 'acc')"
                    class="text-[11px] font-bold text-blue-600 hover:text-blue-800 cursor-pointer"
                  >
                    {{ copiedKey === 'acc' ? 'Copied!' : 'Copy' }}
                  </button>
                </div>

                <div class="p-2.5 bg-white border border-slate-200 rounded-xl flex items-center justify-between">
                  <div>
                    <span class="text-[10px] text-slate-400 block font-bold">IFSC Code</span>
                    <span class="font-mono font-bold text-slate-900">{{ payment.bank_ifsc_code }}</span>
                  </div>
                  <button 
                    type="button" 
                    @click="copyToClipboard(payment.bank_ifsc_code, 'ifsc')"
                    class="text-[11px] font-bold text-blue-600 hover:text-blue-800 cursor-pointer"
                  >
                    {{ copiedKey === 'ifsc' ? 'Copied!' : 'Copy' }}
                  </button>
                </div>

                <div class="p-2.5 bg-white border border-slate-200 rounded-xl flex items-center justify-between">
                  <div>
                    <span class="text-[10px] text-slate-400 block font-bold">SWIFT Code</span>
                    <span class="font-mono font-bold text-slate-900">{{ payment.bank_swift_code }}</span>
                  </div>
                  <button 
                    type="button" 
                    @click="copyToClipboard(payment.bank_swift_code, 'swift')"
                    class="text-[11px] font-bold text-blue-600 hover:text-blue-800 cursor-pointer"
                  >
                    {{ copiedKey === 'swift' ? 'Copied!' : 'Copy' }}
                  </button>
                </div>
              </div>

              <p class="text-[11px] text-slate-500 italic">{{ payment.bank_instructions }}</p>
            </div>

            <!-- IMPS / NEFT Reference submission -->
            <div class="bg-slate-50 border border-slate-200 rounded-2xl p-4 space-y-3">
              <label class="font-bold text-slate-700 block">
                IMPS / NEFT / RTGS Transaction Reference Number *
              </label>
              <input 
                v-model="form.transaction_ref" 
                type="text" 
                placeholder="e.g. N1234567890123" 
                required 
                class="w-full bg-white border border-slate-300 rounded-xl px-3.5 py-2.5 font-mono font-bold text-slate-900 focus:outline-none focus:border-blue-600"
              />

              <div>
                <label class="font-bold text-slate-700 block mb-1">Upload Transfer Slip / Receipt Screenshot (Optional)</label>
                <input 
                  type="file" 
                  accept="image/*,.pdf" 
                  @change="handleFileChange"
                  class="w-full text-xs text-slate-500 file:mr-3 file:py-2 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 cursor-pointer"
                />
              </div>
            </div>
          </div>

          <!-- METHOD 3: CARDS & NETBANKING -->
          <div v-else-if="activeMethod === 'card'" class="space-y-4 animate-fadeIn text-xs">
            <div class="bg-gradient-to-b from-emerald-50/50 to-white border border-emerald-100 rounded-2xl p-5 space-y-3 text-center">
              <CreditCardIcon class="w-10 h-10 text-emerald-600 mx-auto" />
              <h4 class="font-black text-slate-900 text-sm">Credit / Debit Card, NetBanking & Wallets</h4>
              <p class="text-xs text-slate-500">Pay securely via Visa, MasterCard, RuPay, NetBanking or UPI.</p>

              <div class="p-3 bg-emerald-50 rounded-xl text-emerald-800 text-[11px] font-medium border border-emerald-200">
                You will be redirected to the secure 3D-Secure payment gateway to complete this transaction.
              </div>
            </div>

            <!-- Alternative Quick Ref -->
            <div class="bg-slate-50 border border-slate-200 rounded-2xl p-4 space-y-2">
              <label class="font-bold text-slate-700 block">Card Payment Reference / Transaction ID</label>
              <input 
                v-model="form.transaction_ref" 
                type="text" 
                placeholder="e.g. pay_N2389148" 
                class="w-full bg-white border border-slate-300 rounded-xl px-3.5 py-2.5 font-mono font-bold text-slate-900"
              />
            </div>
          </div>

          <!-- SUBMIT PAYMENT ACTION BUTTON -->
          <button 
            type="button" 
            @click="submitPayment"
            :disabled="form.processing || !form.customer_name || !form.customer_phone || !form.customer_email"
            class="w-full py-4 bg-red-600 hover:bg-red-700 text-white font-black text-xs uppercase tracking-wider rounded-2xl shadow-lg shadow-red-600/25 transition disabled:opacity-50 cursor-pointer flex items-center justify-center gap-2"
          >
            <div v-if="form.processing" class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></div>
            <SparklesIcon v-else class="w-4 h-4" />
            <span>{{ form.processing ? 'Verifying & Generating Receipt...' : `Confirm & Submit Payment (₹${Number(form.amount || 0).toLocaleString('en-IN')}) 🚀` }}</span>
          </button>

          <!-- Footer note -->
          <div class="text-[11px] text-slate-400 text-center font-medium pt-2 border-t border-slate-100 flex items-center justify-center gap-1.5">
            <CheckCircleIcon class="w-3.5 h-3.5 text-emerald-500" />
            <span>Official Checkout Gateway for <strong>{{ business.name }}</strong> CRM</span>
          </div>

        </div>

      </div>

    </div>
  </div>
</template>

<style scoped>
@keyframes fadeIn {
  from { opacity: 0; transform: translateY(6px); }
  to { opacity: 1; transform: translateY(0); }
}
.animate-fadeIn {
  animation: fadeIn 0.25s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}
</style>
