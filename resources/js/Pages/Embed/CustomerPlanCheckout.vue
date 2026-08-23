<script setup>
import { ref, computed } from 'vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import {
  QrCodeIcon,
  BuildingLibraryIcon,
  CreditCardIcon,
  ShieldCheckIcon,
  CheckBadgeIcon,
  DocumentDuplicateIcon,
  ArrowTopRightOnSquareIcon,
  PrinterIcon,
  SparklesIcon,
  CheckCircleIcon,
  ClockIcon,
  InformationCircleIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
  plan: Object,
  activeInstallment: Object,
  business: Object,
  payment: Object,
});

const page = usePage();
const activeMethod = ref('upi');
const selectedInstallmentId = ref(props.activeInstallment?.id || props.plan.installments[0]?.id);
const copiedKey = ref('');

const currentInstallment = computed(() => {
  return props.plan.installments.find(i => i.id === selectedInstallmentId.value) || props.plan.installments[0];
});

const form = useForm({
  installment_id: selectedInstallmentId.value,
  payment_method: 'upi',
  transaction_ref: '',
  utr_number: '',
  notes: '',
  proof_file: null,
});

const handleFileChange = (e) => {
  const file = e.target.files[0];
  if (file) {
    form.proof_file = file;
  }
};

const copyToClipboard = (text, key) => {
  if (!text) return;
  navigator.clipboard.writeText(text);
  copiedKey.value = key;
  setTimeout(() => { copiedKey.value = ''; }, 2000);
};

// Dynamic UPI URI with current installment amount and invoice reference
const upiUri = computed(() => {
  const pa = encodeURIComponent(props.payment?.upi_id || 'admissionsdekho@okaxis');
  const pn = encodeURIComponent(props.payment?.upi_merchant_name || props.business?.name || 'Merchant');
  const am = encodeURIComponent(currentInstallment.value?.amount || 1000);
  const cu = 'INR';
  const tn = encodeURIComponent(`${props.plan.invoice_number} ${currentInstallment.value?.title || 'Fee'}`);
  return `upi://pay?pa=${pa}&pn=${pn}&am=${am}&cu=${cu}&tn=${tn}`;
});

const generatedQrCodeUrl = computed(() => {
  if (props.payment?.upi_qr_code_url) {
    return props.payment.upi_qr_code_url;
  }
  const data = encodeURIComponent(upiUri.value);
  return `https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=${data}&margin=10`;
});

const submitPayment = () => {
  form.installment_id = selectedInstallmentId.value;
  form.payment_method = activeMethod.value;
  form.post(`/pay/plan/${props.plan.payment_token}`, {
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
  <Head :title="`Pay ${business.name} - ${plan.invoice_number}`" />

  <div class="min-h-screen bg-slate-100/80 p-3 sm:p-6 lg:p-10 font-sans text-slate-900 flex items-center justify-center">
    <div class="max-w-4xl w-full bg-white rounded-3xl shadow-xl border border-slate-200 overflow-hidden">
      
      <!-- TOP HEADER -->
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
                Verified Checkout
              </span>
            </div>
            <p class="text-xs text-slate-400 font-medium">Customer Payment Portal • {{ plan.invoice_number }}</p>
          </div>
        </div>

        <div class="flex items-center gap-3 text-xs text-slate-300">
          <div class="bg-slate-800/80 px-3 py-1.5 rounded-xl border border-slate-700 font-medium">
            256-Bit SSL Encrypted
          </div>
        </div>
      </div>

      <!-- SUCCESS RECEIPT VIEW -->
      <div v-if="receiptData" class="p-6 sm:p-10 space-y-6 bg-slate-50 print:p-0 print:bg-white animate-fadeIn">
        <div class="bg-white border border-emerald-200 rounded-3xl p-6 sm:p-8 shadow-md text-center space-y-4 max-w-xl mx-auto">
          <div class="w-16 h-16 bg-emerald-100 text-emerald-600 rounded-full flex items-center justify-center mx-auto text-3xl shadow-xs">
            ✓
          </div>
          <div class="space-y-1">
            <span class="text-xs font-black text-emerald-600 uppercase tracking-widest">Payment Received & Verified</span>
            <h2 class="text-2xl font-black text-slate-900">₹{{ Number(receiptData.amount).toLocaleString('en-IN') }}</h2>
            <p class="text-xs font-medium text-slate-500">{{ receiptData.purpose }}</p>
          </div>

          <div class="bg-slate-50 border border-slate-200 rounded-2xl p-4 text-left space-y-2 text-xs">
            <div class="flex justify-between border-b border-slate-200 pb-2">
              <span class="text-slate-500 font-medium">Receipt No:</span>
              <span class="font-mono font-bold text-slate-900">{{ receiptData.receipt_number }}</span>
            </div>
            <div class="flex justify-between border-b border-slate-200 pb-2">
              <span class="text-slate-500 font-medium">Transaction / UTR:</span>
              <span class="font-mono font-bold text-emerald-700">{{ receiptData.transaction_id }}</span>
            </div>
            <div class="flex justify-between border-b border-slate-200 pb-2">
              <span class="text-slate-500 font-medium">Customer Name:</span>
              <span class="font-bold text-slate-900">{{ receiptData.customer_name }}</span>
            </div>
            <div class="flex justify-between border-b border-slate-200 pb-2">
              <span class="text-slate-500 font-medium">Invoice Number:</span>
              <span class="font-mono font-bold text-slate-800">{{ plan.invoice_number }}</span>
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
              View Updated Plan
            </button>
          </div>
        </div>
      </div>

      <!-- MAIN CHECKOUT WORKFLOW -->
      <div v-else class="grid grid-cols-1 lg:grid-cols-12 divide-y lg:divide-y-0 lg:divide-x divide-slate-200">
        
        <!-- LEFT COLUMN: Plan Breakdown (5 cols) -->
        <div class="lg:col-span-5 p-6 sm:p-8 bg-slate-50/60 space-y-6">
          <div>
            <span class="text-[11px] font-black text-red-600 uppercase tracking-wider">Invoice Details</span>
            <h2 class="text-base font-black text-slate-900">{{ plan.title }}</h2>
            <p class="text-xs text-slate-500 font-medium">Billed to <strong>{{ plan.customer_name }}</strong></p>
          </div>

          <!-- Total & Progress Card -->
          <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-xs space-y-3">
            <div class="flex justify-between items-center">
              <div>
                <span class="text-[10px] font-bold text-slate-400 uppercase">Total Plan Value</span>
                <div class="text-2xl font-black text-slate-900">₹{{ Number(plan.total_amount).toLocaleString('en-IN') }}</div>
              </div>
              <span class="px-2.5 py-1 rounded-full text-[10px] font-black uppercase" :class="plan.status === 'paid' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800'">
                {{ plan.status.replace('_', ' ') }}
              </span>
            </div>

            <div class="space-y-1 pt-2 border-t border-slate-100">
              <div class="flex justify-between text-xs font-bold text-slate-600">
                <span>Paid: ₹{{ Number(plan.paid_amount).toLocaleString('en-IN') }}</span>
                <span>Due: ₹{{ Number(plan.pending_amount).toLocaleString('en-IN') }}</span>
              </div>
              <div class="w-full bg-slate-100 h-2 rounded-full overflow-hidden">
                <div class="bg-emerald-500 h-full rounded-full transition-all duration-300" :style="{ width: `${plan.paid_percent}%` }"></div>
              </div>
            </div>
          </div>

          <!-- Installment Picker (If Multi-Installments) -->
          <div v-if="plan.installments.length > 1" class="space-y-2">
            <label class="text-xs font-bold text-slate-700 block">Select Installment to Pay Now:</label>
            <div class="space-y-2">
              <div 
                v-for="inst in plan.installments" 
                :key="inst.id"
                @click="inst.status !== 'paid' && (selectedInstallmentId = inst.id)"
                :class="[
                  'p-3.5 rounded-xl border transition flex items-center justify-between cursor-pointer',
                  inst.status === 'paid' ? 'bg-emerald-50/60 border-emerald-200 opacity-80 cursor-default' :
                  selectedInstallmentId === inst.id ? 'bg-red-50 border-red-500 ring-2 ring-red-500/20' : 'bg-white border-slate-200 hover:border-slate-300'
                ]"
              >
                <div>
                  <strong class="text-xs font-bold text-slate-900 block">{{ inst.title }}</strong>
                  <span class="text-[11px] text-slate-500">Due: {{ inst.due_date || 'Immediate' }}</span>
                </div>
                <div class="text-right">
                  <div class="font-black text-slate-900 text-sm">₹{{ Number(inst.amount).toLocaleString('en-IN') }}</div>
                  <span class="text-[10px] font-extrabold uppercase" :class="inst.status === 'paid' ? 'text-emerald-700' : 'text-amber-600'">
                    {{ inst.status === 'paid' ? '✓ Paid' : 'Payable' }}
                  </span>
                </div>
              </div>
            </div>
          </div>

          <!-- Payer info note -->
          <div class="p-3.5 bg-slate-100 rounded-xl text-xs text-slate-600 space-y-1">
            <div>Customer Phone: <strong>{{ plan.customer_phone }}</strong></div>
            <div>Email: <strong>{{ plan.customer_email }}</strong></div>
            <div class="text-[11px] text-slate-400 pt-1 italic">{{ plan.notes }}</div>
          </div>

        </div>

        <!-- RIGHT COLUMN: Payment Method Select & QR (7 cols) -->
        <div class="lg:col-span-7 p-6 sm:p-8 space-y-6">
          <div>
            <span class="text-[11px] font-black text-red-600 uppercase tracking-wider">Complete Payment</span>
            <h2 class="text-base font-black text-slate-900">
              Paying: {{ currentInstallment.title }} (₹{{ Number(currentInstallment.amount).toLocaleString('en-IN') }})
            </h2>
            <p class="text-xs text-slate-500 font-medium">Choose your payment mode below to proceed.</p>
          </div>

          <!-- Channel Tabs -->
          <div class="grid grid-cols-2 gap-2 p-1 bg-slate-100 rounded-2xl">
            <button 
              type="button" 
              @click="activeMethod = 'upi'"
              :class="activeMethod === 'upi' ? 'bg-white text-slate-900 shadow-sm font-bold' : 'text-slate-600 hover:text-slate-900 font-medium'"
              class="py-2.5 px-3 rounded-xl text-xs flex items-center justify-center gap-1.5 transition cursor-pointer"
            >
              <QrCodeIcon class="w-4 h-4 text-red-600" />
              <span>UPI & QR Scan</span>
            </button>

            <button 
              type="button" 
              @click="activeMethod = 'bank_transfer'"
              :class="activeMethod === 'bank_transfer' ? 'bg-white text-slate-900 shadow-sm font-bold' : 'text-slate-600 hover:text-slate-900 font-medium'"
              class="py-2.5 px-3 rounded-xl text-xs flex items-center justify-center gap-1.5 transition cursor-pointer"
            >
              <BuildingLibraryIcon class="w-4 h-4 text-blue-600" />
              <span>Direct Bank Transfer</span>
            </button>
          </div>

          <!-- UPI METHOD -->
          <div v-if="activeMethod === 'upi'" class="space-y-4 animate-fadeIn text-xs">
            <div class="bg-gradient-to-b from-red-50/50 to-white border border-red-100 rounded-2xl p-5 text-center space-y-3">
              <span class="text-xs font-bold text-slate-700">Scan via GPay, PhonePe, Paytm or BHIM</span>

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
                    class="px-2.5 py-1 bg-red-600 hover:bg-red-700 text-white font-bold rounded-lg transition cursor-pointer"
                  >
                    {{ copiedKey === 'upi_id' ? 'Copied!' : 'Copy' }}
                  </button>
                </div>
              </div>

              <div class="pt-1 sm:hidden">
                <a 
                  :href="upiUri" 
                  class="w-full py-3 bg-red-600 text-white font-bold rounded-xl shadow-md flex items-center justify-center gap-2 text-xs"
                >
                  <ArrowTopRightOnSquareIcon class="w-4 h-4" />
                  <span>Open in UPI App</span>
                </a>
              </div>
            </div>

            <!-- UTR submission -->
            <div class="bg-slate-50 border border-slate-200 rounded-2xl p-4 space-y-3">
              <label class="font-bold text-slate-700 block">
                Enter 12-Digit UTR / Transaction ID *
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

          <!-- BANK TRANSFER METHOD -->
          <div v-else-if="activeMethod === 'bank_transfer'" class="space-y-4 animate-fadeIn text-xs">
            <div class="bg-gradient-to-b from-blue-50/50 to-white border border-blue-100 rounded-2xl p-5 space-y-3">
              <div class="flex items-center gap-2">
                <BuildingLibraryIcon class="w-5 h-5 text-blue-600" />
                <h4 class="font-black text-slate-900">Corporate Bank Transfer Details</h4>
              </div>

              <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                <div class="p-2.5 bg-white border border-slate-200 rounded-xl">
                  <span class="text-[10px] text-slate-400 block font-bold">Bank Name</span>
                  <span class="font-bold text-slate-800">{{ payment.bank_name }}</span>
                </div>

                <div class="p-2.5 bg-white border border-slate-200 rounded-xl">
                  <span class="text-[10px] text-slate-400 block font-bold">Account Type</span>
                  <span class="font-bold text-slate-800">{{ payment.bank_account_type }}</span>
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

                <div class="p-2.5 bg-white border border-slate-200 rounded-xl">
                  <span class="text-[10px] text-slate-400 block font-bold">Branch</span>
                  <span class="font-bold text-slate-800">{{ payment.bank_branch }}</span>
                </div>
              </div>
            </div>

            <div class="bg-slate-50 border border-slate-200 rounded-2xl p-4 space-y-2">
              <label class="font-bold text-slate-700 block">IMPS / NEFT Reference Number *</label>
              <input 
                v-model="form.transaction_ref" 
                type="text" 
                placeholder="e.g. N1234567890123" 
                required 
                class="w-full bg-white border border-slate-300 rounded-xl px-3.5 py-2.5 font-mono font-bold text-slate-900 focus:outline-none focus:border-blue-600"
              />
            </div>
          </div>

          <!-- SUBMIT ACTION -->
          <button 
            type="button" 
            @click="submitPayment"
            :disabled="form.processing || currentInstallment.status === 'paid'"
            class="w-full py-4 bg-red-600 hover:bg-red-700 text-white font-black text-xs uppercase tracking-wider rounded-2xl shadow-lg shadow-red-600/25 transition disabled:opacity-50 cursor-pointer flex items-center justify-center gap-2"
          >
            <div v-if="form.processing" class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></div>
            <SparklesIcon v-else class="w-4 h-4" />
            <span>{{ form.processing ? 'Verifying...' : `Confirm & Pay (₹${Number(currentInstallment.amount).toLocaleString('en-IN')}) 🚀` }}</span>
          </button>

          <div class="text-[11px] text-slate-400 text-center font-medium pt-2 border-t border-slate-100 flex items-center justify-center gap-1.5">
            <CheckCircleIcon class="w-3.5 h-3.5 text-emerald-500" />
            <span>Official Invoice Checkout for <strong>{{ business.name }}</strong></span>
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
