<script setup>
import { ref, computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import Navbar from '@/Components/Navbar.vue';
import {
  ArrowLeftIcon,
  PrinterIcon,
  ShareIcon,
  DocumentDuplicateIcon,
  CheckCircleIcon,
  ClockIcon,
  CreditCardIcon,
  BuildingLibraryIcon,
  PhoneIcon,
  EnvelopeIcon,
  UserIcon,
  ArrowTopRightOnSquareIcon,
  BanknotesIcon,
  TrashIcon,
  ShieldCheckIcon,
  SparklesIcon,
  XMarkIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
  plan: Object,
  business: Object,
});

const copiedLink = ref(false);
const activeRecordModal = ref(null); // installment object or null

const recordForm = useForm({
  payment_method: 'cash',
  transaction_ref: '',
  notes: 'Payment collected offline by CRM agent',
});

const copyPaymentLink = () => {
  const origin = typeof window !== 'undefined' ? window.location.origin : '';
  const url = `${origin}/pay/plan/${props.plan.payment_token}`;
  navigator.clipboard.writeText(url);
  copiedLink.value = true;
  setTimeout(() => { copiedLink.value = false; }, 2500);
};

const whatsappShareUrl = computed(() => {
  const origin = typeof window !== 'undefined' ? window.location.origin : '';
  const url = `${origin}/pay/plan/${props.plan.payment_token}`;
  const text = encodeURIComponent(
    `Hello ${props.plan.customer_name},\n\nYour payment plan for *${props.plan.title}* from *${props.business.name}* is ready.\n\n` +
    `• Invoice No: ${props.plan.invoice_number}\n` +
    `• Total Amount: ₹${Number(props.plan.total_amount).toLocaleString('en-IN')}\n` +
    `• Due Date: ${props.plan.due_date || 'Due on Receipt'}\n\n` +
    `👉 View Invoice & Pay Online:\n${url}\n\nThank you!`
  );
  const cleanPhone = (props.plan.customer_phone || '').replace(/[^0-9]/g, '');
  return `https://wa.me/${cleanPhone}?text=${text}`;
});

const openRecordModal = (inst) => {
  activeRecordModal.value = inst;
  recordForm.transaction_ref = `CASH-${Date.now().toString().slice(-6)}`;
};

const submitRecordPayment = () => {
  if (!activeRecordModal.value) return;
  recordForm.post(`/payment-plans/installments/${activeRecordModal.value.id}/mark-paid`, {
    onSuccess: () => {
      activeRecordModal.value = null;
    }
  });
};

const printInvoice = () => {
  window.print();
};
</script>

<template>
  <Head :title="`${plan.invoice_number} - ${plan.customer_name} Payment Plan`" />

  <div class="min-h-screen bg-[#f8fafc] text-slate-900 flex font-sans w-full max-w-full overflow-x-hidden print:bg-white print:p-0">
    <div class="print:hidden">
      <Navbar />
    </div>

    <main class="flex-1 min-w-0 max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6 overflow-x-hidden print:p-0 print:m-0 print:max-w-full">
      
      <!-- Back Navigation & Top Actions (Hidden in Print) -->
      <div class="flex flex-wrap items-center justify-between gap-4 print:hidden">
        <Link 
          href="/payment-plans"
          class="inline-flex items-center gap-2 text-xs font-bold text-slate-600 hover:text-slate-900 transition"
        >
          <ArrowLeftIcon class="w-4 h-4" />
          <span>&larr; Back to Payment Plans</span>
        </Link>

        <div class="flex items-center gap-2">
          <!-- Copy Link -->
          <button 
            @click="copyPaymentLink"
            class="px-3.5 py-2 bg-white border border-slate-200 hover:bg-slate-50 text-slate-700 font-bold text-xs rounded-xl shadow-xs transition flex items-center gap-1.5 cursor-pointer"
          >
            <CheckCircleIcon v-if="copiedLink" class="w-4 h-4 text-emerald-600" />
            <DocumentDuplicateIcon v-else class="w-4 h-4" />
            <span>{{ copiedLink ? 'Copied Link!' : 'Copy Payment Link' }}</span>
          </button>

          <!-- WhatsApp Share -->
          <a 
            :href="whatsappShareUrl"
            target="_blank"
            class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xl shadow-md shadow-emerald-600/20 transition flex items-center gap-1.5"
          >
            <ShareIcon class="w-4 h-4" />
            <span>Share on WhatsApp</span>
          </a>

          <!-- Print Invoice -->
          <button 
            @click="printInvoice"
            class="px-4 py-2 bg-slate-900 hover:bg-black text-white font-bold text-xs rounded-xl shadow-xs transition flex items-center gap-1.5 cursor-pointer"
          >
            <PrinterIcon class="w-4 h-4" />
            <span>Print Invoice</span>
          </button>
        </div>
      </div>

      <!-- INVOICE HEADER CARD (Visible on Screen & Print) -->
      <div class="bg-white border border-slate-200/90 rounded-3xl p-6 sm:p-8 shadow-xs space-y-6 print:border-none print:shadow-none print:p-0">
        
        <!-- Header with Business Brand & Invoice Info -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-6 border-b border-slate-100 pb-6 print:pb-4">
          <div class="flex items-center gap-4">
            <div class="w-14 h-14 rounded-2xl bg-red-50 border border-red-200 flex items-center justify-center text-3xl font-black text-red-600 shadow-xs print:w-12 print:h-12 print:text-2xl">
              {{ business.icon || '🎓' }}
            </div>
            <div>
              <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">{{ business.name }}</h1>
              <p class="text-xs text-slate-500 font-medium">{{ business.support_email }} • {{ business.contact_phone }}</p>
            </div>
          </div>

          <div class="text-left sm:text-right space-y-1">
            <span class="text-[11px] font-bold text-slate-400 uppercase tracking-widest block">Official Payment Invoice</span>
            <h2 class="text-lg sm:text-xl font-mono font-black text-slate-900">{{ plan.invoice_number }}</h2>
            <span 
              class="inline-block px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-wider"
              :class="{
                'bg-emerald-100 text-emerald-800': plan.status === 'paid',
                'bg-blue-100 text-blue-800': plan.status === 'partially_paid',
                'bg-amber-100 text-amber-800': plan.status === 'pending',
                'bg-red-100 text-red-800': plan.status === 'overdue',
              }"
            >
              {{ plan.status.replace('_', ' ') }}
            </span>
          </div>
        </div>

        <!-- Customer Details & Summary Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 bg-slate-50/80 border border-slate-200/80 rounded-2xl p-5 text-xs">
          
          <div class="space-y-1.5">
            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Billed To (Customer / Student)</span>
            <h3 class="text-base font-black text-slate-900">{{ plan.customer_name }}</h3>
            <div class="text-slate-600 font-medium space-y-0.5">
              <div>Phone / WhatsApp: <strong>{{ plan.customer_phone }}</strong></div>
              <div>Email: <strong>{{ plan.customer_email }}</strong></div>
              <div v-if="plan.contact_id" class="pt-1">
                <span class="px-2 py-0.5 bg-red-100 text-red-700 font-bold rounded text-[10px]">
                  CRM Contact #{{ plan.contact_id }}
                </span>
              </div>
            </div>
          </div>

          <div class="space-y-2 sm:text-right">
            <div>
              <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Plan & Purpose</span>
              <div class="text-sm font-black text-slate-900">{{ plan.title }}</div>
            </div>
            <div>
              <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Total Amount</span>
              <div class="text-2xl font-black text-slate-900">₹{{ Number(plan.total_amount).toLocaleString('en-IN') }}</div>
            </div>
            <div class="text-[11px] text-slate-500 font-medium">
              Due Date: <strong>{{ plan.due_date || 'Due on Receipt' }}</strong>
            </div>
          </div>

        </div>

        <!-- Paid Progress Bar (Hidden in Print) -->
        <div class="space-y-1.5 print:hidden">
          <div class="flex items-center justify-between text-xs font-bold">
            <span class="text-slate-600">Payment Collection Progress</span>
            <span class="text-slate-900">
              ₹{{ Number(plan.paid_amount).toLocaleString('en-IN') }} of ₹{{ Number(plan.total_amount).toLocaleString('en-IN') }} ({{ plan.paid_percent }}%)
            </span>
          </div>
          <div class="w-full bg-slate-100 h-3 rounded-full overflow-hidden border border-slate-200/80">
            <div class="bg-emerald-500 h-full rounded-full transition-all duration-500" :style="{ width: `${plan.paid_percent}%` }"></div>
          </div>
        </div>

        <!-- INSTALLMENT SCHEDULE & MILESTONES TABLE -->
        <div class="space-y-3">
          <h3 class="text-xs font-black text-slate-900 uppercase tracking-wider">Installment Schedule & Breakdown</h3>
          
          <div class="border border-slate-200 rounded-2xl overflow-hidden">
            <table class="w-full text-left border-collapse text-xs">
              <thead>
                <tr class="bg-slate-50 border-b border-slate-200 text-[10px] font-black text-slate-500 uppercase tracking-wider">
                  <th class="py-3 px-4">#</th>
                  <th class="py-3 px-4">Milestone / Title</th>
                  <th class="py-3 px-4">Due Date</th>
                  <th class="py-3 px-4">Amount</th>
                  <th class="py-3 px-4">Status</th>
                  <th class="py-3 px-4 text-right print:hidden">Action</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100">
                <tr v-for="inst in plan.installments" :key="inst.id" class="hover:bg-slate-50/50">
                  <td class="py-3.5 px-4 font-mono font-bold text-slate-400">#{{ inst.installment_number }}</td>
                  <td class="py-3.5 px-4 font-bold text-slate-900">{{ inst.title }}</td>
                  <td class="py-3.5 px-4 text-slate-600">{{ inst.due_date || 'Immediate' }}</td>
                  <td class="py-3.5 px-4 font-black text-slate-900">₹{{ Number(inst.amount).toLocaleString('en-IN') }}</td>
                  <td class="py-3.5 px-4">
                    <span 
                      class="px-2 py-0.5 rounded-md text-[10px] font-black uppercase tracking-wider"
                      :class="inst.status === 'paid' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800'"
                    >
                      {{ inst.status }}
                    </span>
                    <div v-if="inst.status === 'paid'" class="text-[10px] text-slate-400 mt-0.5 font-mono">
                      Ref: {{ inst.transaction_ref || 'VERIFIED' }}
                    </div>
                  </td>
                  <td class="py-3.5 px-4 text-right print:hidden">
                    <button 
                      v-if="inst.status !== 'paid'"
                      @click="openRecordModal(inst)"
                      class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-[11px] rounded-lg transition shadow-2xs flex items-center gap-1 ml-auto cursor-pointer"
                    >
                      <BanknotesIcon class="w-3.5 h-3.5" />
                      <span>Record Payment</span>
                    </button>
                    <span v-else class="text-[11px] font-bold text-emerald-700">
                      ✓ Settled
                    </span>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Corporate Bank & UPI Details for Wire Transfer (Printed on Invoice) -->
        <div class="p-5 bg-slate-50 border border-slate-200 rounded-2xl space-y-2 text-xs">
          <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest block">Bank Wire & UPI Remittance Details</span>
          <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 pt-1">
            <div>
              <span class="text-slate-400 text-[10px] block font-bold">Bank Name</span>
              <span class="font-bold text-slate-900">{{ business.bank_name }}</span>
            </div>
            <div>
              <span class="text-slate-400 text-[10px] block font-bold">Account Holder</span>
              <span class="font-bold text-slate-900">{{ business.bank_account_holder }}</span>
            </div>
            <div>
              <span class="text-slate-400 text-[10px] block font-bold">Account Number</span>
              <span class="font-mono font-bold text-slate-900">{{ business.bank_account_number }}</span>
            </div>
            <div>
              <span class="text-slate-400 text-[10px] block font-bold">IFSC / UPI ID</span>
              <span class="font-mono font-bold text-slate-900">{{ business.bank_ifsc_code }} • {{ business.upi_id }}</span>
            </div>
          </div>
        </div>

        <!-- Terms & Footer -->
        <div class="pt-4 border-t border-slate-100 flex flex-col sm:flex-row sm:items-center justify-between text-[11px] text-slate-500 gap-4">
          <div>
            <strong>Terms & Remarks:</strong> {{ plan.notes || 'Payment is non-refundable once admission seat is confirmed.' }}
          </div>
          <div class="text-right italic">
            Authorized Invoice Generated by <strong>{{ business.name }}</strong>
          </div>
        </div>

      </div>

    </main>

    <!-- RECORD PAYMENT MODAL -->
    <div v-if="activeRecordModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs z-50 flex items-center justify-center p-4 animate-fadeIn">
      <div class="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl border border-slate-200 space-y-4">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
          <div>
            <h3 class="text-base font-black text-slate-900">Record Offline Payment</h3>
            <p class="text-xs text-slate-500">Installment #{{ activeRecordModal.installment_number }} (₹{{ Number(activeRecordModal.amount).toLocaleString('en-IN') }})</p>
          </div>
          <button @click="activeRecordModal = null" class="text-slate-400 hover:text-slate-600 p-1">
            <XMarkIcon class="w-5 h-5" />
          </button>
        </div>

        <form @submit.prevent="submitRecordPayment" class="space-y-3 text-xs">
          <div>
            <label class="font-bold text-slate-700 block mb-1">Payment Method</label>
            <select v-model="recordForm.payment_method" class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3 py-2 font-bold text-slate-900 focus:bg-white focus:outline-none focus:border-emerald-600">
              <option value="cash">Cash in Hand</option>
              <option value="upi">Direct UPI (GPay / PhonePe / Paytm)</option>
              <option value="bank_transfer">Bank Wire (IMPS / NEFT / RTGS)</option>
              <option value="cheque">Cheque / Demand Draft</option>
              <option value="pos_card">POS Card Swipe</option>
            </select>
          </div>

          <div>
            <label class="font-bold text-slate-700 block mb-1">Transaction Ref / Cheque No / UTR *</label>
            <input v-model="recordForm.transaction_ref" type="text" required class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3 py-2 font-mono font-bold text-slate-900 focus:bg-white focus:outline-none focus:border-emerald-600" />
          </div>

          <div>
            <label class="font-bold text-slate-700 block mb-1">Remarks / Note</label>
            <textarea v-model="recordForm.notes" rows="2" class="w-full bg-slate-50 border border-slate-300 rounded-xl p-2.5 text-slate-800 focus:bg-white focus:outline-none focus:border-emerald-600"></textarea>
          </div>

          <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
            <button type="button" @click="activeRecordModal = null" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-xl">
              Cancel
            </button>
            <button type="submit" :disabled="recordForm.processing" class="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-black rounded-xl shadow-md transition">
              {{ recordForm.processing ? 'Saving...' : '✓ Confirm & Mark Paid' }}
            </button>
          </div>
        </form>
      </div>
    </div>

  </div>
</template>

<style scoped>
@keyframes fadeIn {
  from { opacity: 0; transform: scale(0.98); }
  to { opacity: 1; transform: scale(1); }
}
.animate-fadeIn {
  animation: fadeIn 0.2s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}
</style>
