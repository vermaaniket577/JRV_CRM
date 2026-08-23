<script setup>
import { ref, computed, watch } from 'vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import Navbar from '@/Components/Navbar.vue';
import {
  CreditCardIcon,
  PlusIcon,
  MagnifyingGlassIcon,
  CurrencyRupeeIcon,
  CheckCircleIcon,
  ClockIcon,
  ExclamationTriangleIcon,
  DocumentDuplicateIcon,
  ArrowTopRightOnSquareIcon,
  ShareIcon,
  CalendarIcon,
  UserIcon,
  PhoneIcon,
  EnvelopeIcon,
  SparklesIcon,
  XMarkIcon,
  TrashIcon,
  EyeIcon,
  ChevronRightIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
  plans: Object,
  contacts: Array,
  filters: Object,
  kpis: Object,
  business: Object,
});

const search = ref(props.filters.search || '');
const currentStatus = ref(props.filters.status || 'all');
const isCreateModalOpen = ref(false);
const copiedToken = ref('');

// Filter handling
watch([search, currentStatus], () => {
  router.get('/payment-plans', {
    search: search.value,
    status: currentStatus.value,
  }, {
    preserveState: true,
    replace: true,
  });
});

// Create Plan Form
const createForm = useForm({
  contact_id: '',
  customer_name: '',
  customer_email: '',
  customer_phone: '',
  title: 'B.Tech Admission & Semester Fee',
  total_amount: 15000,
  currency: 'INR',
  plan_type: 'one_time', // 'one_time' or 'installments'
  due_date: new Date(Date.now() + 7 * 86400000).toISOString().split('T')[0],
  notes: 'Please verify payment screenshot or transaction UTR number upon transfer.',
  installments: [
    { title: 'Installment 1: Seat Confirmation Token', amount: 5000, due_date: new Date(Date.now() + 7 * 86400000).toISOString().split('T')[0] },
    { title: 'Installment 2: Semester 1 Balance', amount: 10000, due_date: new Date(Date.now() + 30 * 86400000).toISOString().split('T')[0] }
  ]
});

// Auto-fill customer details when selecting existing contact
const onSelectContact = (e) => {
  const cId = e.target.value;
  if (!cId) return;
  const found = props.contacts.find(c => c.id == cId);
  if (found) {
    createForm.customer_name = `${found.first_name} ${found.last_name || ''}`.trim();
    createForm.customer_email = found.email || '';
    createForm.customer_phone = found.phone || '';
  }
};

// Installments calculation helper
const setInstallmentCount = (count) => {
  const total = Number(createForm.total_amount) || 0;
  const each = Math.floor(total / count);
  const remainder = total - (each * count);
  
  createForm.installments = [];
  for (let i = 1; i <= count; i++) {
    const amt = i === 1 ? (each + remainder) : each;
    const dueDate = new Date(Date.now() + (i * 30 - 23) * 86400000).toISOString().split('T')[0];
    createForm.installments.push({
      title: `Installment ${i}`,
      amount: amt,
      due_date: dueDate
    });
  }
};

const addInstallmentRow = () => {
  const i = createForm.installments.length + 1;
  const dueDate = new Date(Date.now() + (i * 30) * 86400000).toISOString().split('T')[0];
  createForm.installments.push({
    title: `Installment ${i}`,
    amount: 1000,
    due_date: dueDate
  });
};

const removeInstallmentRow = (idx) => {
  createForm.installments.splice(idx, 1);
};

const installmentSum = computed(() => {
  if (createForm.plan_type === 'one_time') return createForm.total_amount;
  return createForm.installments.reduce((acc, curr) => acc + (Number(curr.amount) || 0), 0);
});

const submitCreatePlan = () => {
  createForm.post('/payment-plans', {
    onSuccess: () => {
      isCreateModalOpen.value = false;
      createForm.reset();
    }
  });
};

const copyPaymentLink = (token) => {
  const origin = typeof window !== 'undefined' ? window.location.origin : '';
  const url = `${origin}/pay/plan/${token}`;
  navigator.clipboard.writeText(url);
  copiedToken.value = token;
  setTimeout(() => { copiedToken.value = ''; }, 2500);
};

const getWhatsAppShareLink = (plan) => {
  const origin = typeof window !== 'undefined' ? window.location.origin : '';
  const url = `${origin}/pay/plan/${plan.payment_token}`;
  const text = encodeURIComponent(
    `Hello ${plan.customer_name},\n\nYour payment plan for *${plan.title}* from *${props.business.name}* is ready.\n\n` +
    `• Invoice No: ${plan.invoice_number}\n` +
    `• Total Amount: ₹${Number(plan.total_amount).toLocaleString('en-IN')}\n` +
    `• Due Date: ${plan.due_date || 'Due on Receipt'}\n\n` +
    `👉 Click here to view invoice & pay online:\n${url}\n\nThank you!`
  );
  const cleanPhone = (plan.customer_phone || '').replace(/[^0-9]/g, '');
  return `https://wa.me/${cleanPhone}?text=${text}`;
};
</script>

<template>
  <Head title="Customer Payment Plans & Invoices - JRV CRM" />

  <div class="min-h-screen bg-[#f8fafc] text-slate-900 flex font-sans w-full max-w-full overflow-x-hidden">
    <Navbar />

    <main class="flex-1 min-w-0 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8 overflow-x-hidden">
      
      <!-- Top Title Header -->
      <div class="bg-white border border-slate-200/90 rounded-3xl p-6 md:p-8 shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div class="space-y-1.5">
          <div class="flex items-center gap-3">
            <div class="w-12 h-12 rounded-2xl bg-red-50 border border-red-200 flex items-center justify-center text-red-600 text-xl font-black shadow-xs">
              <CreditCardIcon class="w-7 h-7" />
            </div>
            <div>
              <div class="flex items-center gap-2">
                <h1 class="text-xl md:text-2xl font-black text-slate-900 tracking-tight">Customer Payment Plans & Invoices</h1>
                <span class="px-2.5 py-0.5 rounded-full text-[11px] font-black uppercase tracking-wider bg-red-100 text-red-700">
                  User Panel
                </span>
              </div>
              <p class="text-xs text-slate-500 font-medium">Create and send custom fee plans, split installments, and payment links for your students and clients.</p>
            </div>
          </div>
        </div>

        <button 
          @click="isCreateModalOpen = true"
          class="px-5 py-3 bg-red-600 hover:bg-red-700 text-white text-xs font-black uppercase tracking-wider rounded-2xl shadow-lg shadow-red-600/20 flex items-center justify-center gap-2 transition cursor-pointer shrink-0"
        >
          <PlusIcon class="w-4 h-4 stroke-[3]" />
          <span>+ Create Payment Plan</span>
        </button>
      </div>

      <!-- 4 KPI SUMMARY CARDS -->
      <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        
        <div class="bg-white border border-slate-200/90 rounded-3xl p-5 shadow-xs flex items-center justify-between">
          <div>
            <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Total Invoiced</span>
            <div class="text-xl font-black text-slate-900 mt-1">₹{{ Number(kpis.total_invoiced || 0).toLocaleString('en-IN') }}</div>
            <span class="text-[11px] text-slate-500 font-semibold">{{ kpis.total_plans_count }} Plans Created</span>
          </div>
          <div class="w-12 h-12 rounded-2xl bg-slate-100 text-slate-700 flex items-center justify-center text-xl font-bold">
            ₹
          </div>
        </div>

        <div class="bg-white border border-slate-200/90 rounded-3xl p-5 shadow-xs flex items-center justify-between">
          <div>
            <span class="text-[11px] font-bold text-emerald-600 uppercase tracking-wider">Collected Revenue</span>
            <div class="text-xl font-black text-emerald-600 mt-1">₹{{ Number(kpis.total_collected || 0).toLocaleString('en-IN') }}</div>
            <span class="text-[11px] text-emerald-700 font-semibold">{{ kpis.paid_plans_count }} Fully Paid</span>
          </div>
          <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
            <CheckCircleIcon class="w-6 h-6 stroke-[2]" />
          </div>
        </div>

        <div class="bg-white border border-slate-200/90 rounded-3xl p-5 shadow-xs flex items-center justify-between">
          <div>
            <span class="text-[11px] font-bold text-amber-600 uppercase tracking-wider">Pending Dues</span>
            <div class="text-xl font-black text-amber-600 mt-1">₹{{ Number(kpis.total_pending || 0).toLocaleString('en-IN') }}</div>
            <span class="text-[11px] text-amber-700 font-semibold">{{ kpis.pending_plans_count }} Awaiting Payment</span>
          </div>
          <div class="w-12 h-12 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center">
            <ClockIcon class="w-6 h-6 stroke-[2]" />
          </div>
        </div>

        <div class="bg-white border border-slate-200/90 rounded-3xl p-5 shadow-xs flex items-center justify-between">
          <div>
            <span class="text-[11px] font-bold text-red-600 uppercase tracking-wider">Overdue Invoices</span>
            <div class="text-xl font-black text-red-600 mt-1">{{ kpis.overdue_plans_count || 0 }}</div>
            <span class="text-[11px] text-red-700 font-semibold">Requires Follow-up</span>
          </div>
          <div class="w-12 h-12 rounded-2xl bg-red-50 text-red-600 flex items-center justify-center">
            <ExclamationTriangleIcon class="w-6 h-6 stroke-[2]" />
          </div>
        </div>

      </div>

      <!-- SEARCH & STATUS FILTER BAR -->
      <div class="bg-white border border-slate-200/90 rounded-3xl p-4 sm:p-5 shadow-xs flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        
        <!-- Status Tabs -->
        <div class="flex items-center gap-1.5 overflow-x-auto pb-1 sm:pb-0">
          <button 
            @click="currentStatus = 'all'"
            :class="['px-3.5 py-1.5 rounded-xl text-xs font-bold transition cursor-pointer whitespace-nowrap', currentStatus === 'all' ? 'bg-slate-900 text-white shadow-xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200']"
          >
            All ({{ kpis.total_plans_count }})
          </button>
          <button 
            @click="currentStatus = 'pending'"
            :class="['px-3.5 py-1.5 rounded-xl text-xs font-bold transition cursor-pointer whitespace-nowrap', currentStatus === 'pending' ? 'bg-amber-600 text-white shadow-xs' : 'bg-amber-50 text-amber-700 hover:bg-amber-100']"
          >
            Pending
          </button>
          <button 
            @click="currentStatus = 'partially_paid'"
            :class="['px-3.5 py-1.5 rounded-xl text-xs font-bold transition cursor-pointer whitespace-nowrap', currentStatus === 'partially_paid' ? 'bg-blue-600 text-white shadow-xs' : 'bg-blue-50 text-blue-700 hover:bg-blue-100']"
          >
            Partially Paid
          </button>
          <button 
            @click="currentStatus = 'paid'"
            :class="['px-3.5 py-1.5 rounded-xl text-xs font-bold transition cursor-pointer whitespace-nowrap', currentStatus === 'paid' ? 'bg-emerald-600 text-white shadow-xs' : 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100']"
          >
            Paid ({{ kpis.paid_plans_count }})
          </button>
        </div>

        <!-- Search Box -->
        <div class="relative min-w-[260px]">
          <MagnifyingGlassIcon class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2" />
          <input 
            v-model="search" 
            type="text" 
            placeholder="Search student, phone, invoice..." 
            class="w-full bg-slate-50 border border-slate-200 rounded-xl pl-9 pr-4 py-2 text-xs font-semibold text-slate-900 focus:bg-white focus:outline-none focus:border-red-500"
          />
        </div>

      </div>

      <!-- PAYMENT PLANS LIST TABLE -->
      <div class="bg-white border border-slate-200/90 rounded-3xl shadow-xs overflow-hidden">
        
        <div v-if="plans.data.length === 0" class="p-12 text-center space-y-3">
          <div class="w-16 h-16 bg-slate-100 text-slate-400 rounded-full flex items-center justify-center mx-auto text-2xl">
            💳
          </div>
          <h3 class="text-base font-black text-slate-800">No Payment Plans Found</h3>
          <p class="text-xs text-slate-500 max-w-sm mx-auto">Create custom fee installments or invoices for your students and clients with 1-click WhatsApp payment links.</p>
          <button 
            @click="isCreateModalOpen = true" 
            class="px-5 py-2.5 bg-red-600 hover:bg-red-700 text-white text-xs font-bold rounded-xl shadow-md transition cursor-pointer inline-flex items-center gap-1.5"
          >
            <PlusIcon class="w-4 h-4" />
            <span>Create First Payment Plan</span>
          </button>
        </div>

        <div v-else class="overflow-x-auto">
          <table class="w-full text-left border-collapse text-xs">
            <thead>
              <tr class="bg-slate-50/80 border-b border-slate-200 text-[11px] font-black text-slate-500 uppercase tracking-wider">
                <th class="py-3.5 px-4">Invoice #</th>
                <th class="py-3.5 px-4">Customer / Student</th>
                <th class="py-3.5 px-4">Plan Title</th>
                <th class="py-3.5 px-4">Amount & Paid Progress</th>
                <th class="py-3.5 px-4">Type</th>
                <th class="py-3.5 px-4">Due Date</th>
                <th class="py-3.5 px-4">Status</th>
                <th class="py-3.5 px-4 text-right">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr v-for="plan in plans.data" :key="plan.id" class="hover:bg-slate-50/70 transition">
                
                <!-- Invoice # -->
                <td class="py-3.5 px-4 font-mono font-bold text-slate-900">
                  <Link :href="`/payment-plans/${plan.id}`" class="text-red-600 hover:underline">
                    {{ plan.invoice_number }}
                  </Link>
                </td>

                <!-- Customer Details -->
                <td class="py-3.5 px-4">
                  <div class="font-bold text-slate-900">{{ plan.customer_name }}</div>
                  <div class="text-[11px] text-slate-500 flex items-center gap-2 mt-0.5">
                    <span>{{ plan.customer_phone }}</span>
                    <span>•</span>
                    <span>{{ plan.customer_email }}</span>
                  </div>
                </td>

                <!-- Plan Title -->
                <td class="py-3.5 px-4 font-semibold text-slate-800">
                  {{ plan.title }}
                </td>

                <!-- Amount & Progress -->
                <td class="py-3.5 px-4">
                  <div class="flex items-center gap-2 font-black text-slate-900">
                    <span>₹{{ Number(plan.total_amount).toLocaleString('en-IN') }}</span>
                    <span class="text-[10px] font-bold px-1.5 py-0.5 rounded-md" :class="plan.paid_amount >= plan.total_amount ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800'">
                      {{ plan.paid_percent }}% Paid
                    </span>
                  </div>
                  <!-- Mini Progress Bar -->
                  <div class="w-32 bg-slate-200 h-1.5 rounded-full overflow-hidden mt-1.5">
                    <div class="bg-emerald-500 h-full rounded-full transition-all duration-300" :style="{ width: `${plan.paid_percent}%` }"></div>
                  </div>
                  <span class="text-[10px] text-slate-400 font-medium">₹{{ Number(plan.paid_amount).toLocaleString('en-IN') }} collected</span>
                </td>

                <!-- Plan Type -->
                <td class="py-3.5 px-4">
                  <span class="px-2 py-0.5 rounded-md text-[10px] font-extrabold" :class="plan.plan_type === 'installments' ? 'bg-indigo-50 text-indigo-700 border border-indigo-200' : 'bg-slate-100 text-slate-700'">
                    {{ plan.plan_type === 'installments' ? `${plan.installments?.length || 0} Installments` : 'Lump Sum' }}
                  </span>
                </td>

                <!-- Due Date -->
                <td class="py-3.5 px-4 text-slate-600 font-medium">
                  {{ plan.due_date || 'Due on Receipt' }}
                </td>

                <!-- Status Badge -->
                <td class="py-3.5 px-4">
                  <span 
                    class="px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-wider"
                    :class="{
                      'bg-emerald-100 text-emerald-800 border border-emerald-200': plan.status === 'paid',
                      'bg-blue-100 text-blue-800 border border-blue-200': plan.status === 'partially_paid',
                      'bg-amber-100 text-amber-800 border border-amber-200': plan.status === 'pending',
                      'bg-red-100 text-red-800 border border-red-200': plan.status === 'overdue',
                    }"
                  >
                    {{ plan.status.replace('_', ' ') }}
                  </span>
                </td>

                <!-- Actions -->
                <td class="py-3.5 px-4 text-right">
                  <div class="flex items-center justify-end gap-1.5">
                    
                    <!-- Copy Link -->
                    <button 
                      @click="copyPaymentLink(plan.payment_token)"
                      class="p-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg transition cursor-pointer"
                      title="Copy Customer Payment Link"
                    >
                      <CheckCircleIcon v-if="copiedToken === plan.payment_token" class="w-4 h-4 text-emerald-600" />
                      <DocumentDuplicateIcon v-else class="w-4 h-4" />
                    </button>

                    <!-- WhatsApp Share -->
                    <a 
                      :href="getWhatsAppShareLink(plan)" 
                      target="_blank"
                      class="p-1.5 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 rounded-lg transition"
                      title="Share Payment Link on WhatsApp"
                    >
                      <ShareIcon class="w-4 h-4" />
                    </a>

                    <!-- View Details -->
                    <Link 
                      :href="`/payment-plans/${plan.id}`"
                      class="px-2.5 py-1.5 bg-slate-900 hover:bg-black text-white font-bold text-[11px] rounded-lg transition flex items-center gap-1"
                    >
                      <span>Manage</span>
                      <ChevronRightIcon class="w-3 h-3 stroke-[3]" />
                    </Link>

                  </div>
                </td>

              </tr>
            </tbody>
          </table>
        </div>

      </div>

    </main>

    <!-- CREATE PAYMENT PLAN MODAL -->
    <div v-if="isCreateModalOpen" class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs z-50 flex items-center justify-center p-3 sm:p-6 overflow-y-auto animate-fadeIn">
      <div class="bg-white rounded-3xl max-w-2xl w-full max-h-[90vh] shadow-2xl border border-slate-200 overflow-hidden flex flex-col my-auto">
        
        <!-- Modal Header -->
        <div class="bg-slate-900 text-white p-5 sm:p-6 flex items-center justify-between border-b border-slate-800">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-2xl bg-red-600 flex items-center justify-center text-lg shadow-md shadow-red-600/30">
              ⚡
            </div>
            <div>
              <h3 class="text-base font-black text-white">Create Customer Payment Plan & Invoice</h3>
              <p class="text-xs text-slate-400 font-medium">Generate a tokenized payment link with custom amounts or split installments.</p>
            </div>
          </div>
          <button @click="isCreateModalOpen = false" class="p-1.5 rounded-xl bg-slate-800 text-slate-400 hover:text-white transition cursor-pointer">
            <XMarkIcon class="w-5 h-5" />
          </button>
        </div>

        <!-- Modal Form Body -->
        <form @submit.prevent="submitCreatePlan" class="p-6 space-y-5 overflow-y-auto flex-1 text-xs">
          
          <!-- Select from Existing Contact OR Enter New -->
          <div class="bg-slate-50 border border-slate-200 rounded-2xl p-4 space-y-3">
            <div class="flex items-center justify-between">
              <label class="font-bold text-slate-700">Select Existing CRM Contact / Student (Optional)</label>
              <span class="text-[10px] text-slate-400">Auto-fills customer info</span>
            </div>
            <select v-model="createForm.contact_id" @change="onSelectContact" class="w-full bg-white border border-slate-300 rounded-xl px-3.5 py-2 font-medium text-slate-800 focus:outline-none focus:border-red-500">
              <option value="">-- Or enter new customer below --</option>
              <option v-for="c in contacts" :key="c.id" :value="c.id">
                {{ c.first_name }} {{ c.last_name || '' }} ({{ c.phone || c.email }})
              </option>
            </select>
          </div>

          <!-- Customer Name & Contacts -->
          <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <div>
              <label class="font-bold text-slate-700 block mb-1">Customer / Student Name *</label>
              <input v-model="createForm.customer_name" type="text" placeholder="e.g. Rahul Sharma" required class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3 py-2 font-bold text-slate-900 focus:bg-white focus:outline-none focus:border-red-500" />
            </div>
            <div>
              <label class="font-bold text-slate-700 block mb-1">Phone / WhatsApp *</label>
              <input v-model="createForm.customer_phone" type="tel" placeholder="+91 9876543210" required class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3 py-2 font-bold text-slate-900 focus:bg-white focus:outline-none focus:border-red-500" />
            </div>
            <div>
              <label class="font-bold text-slate-700 block mb-1">Email Address *</label>
              <input v-model="createForm.customer_email" type="email" placeholder="rahul@example.com" required class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3 py-2 font-bold text-slate-900 focus:bg-white focus:outline-none focus:border-red-500" />
            </div>
          </div>

          <!-- Plan Title & Total Amount -->
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
              <label class="font-bold text-slate-700 block mb-1">Payment Plan Title / Purpose *</label>
              <input v-model="createForm.title" type="text" placeholder="e.g. B.Tech Semester 1 Enrollment Fee" required class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2 font-bold text-slate-900 focus:bg-white focus:outline-none focus:border-red-500" />
            </div>
            <div>
              <label class="font-bold text-slate-700 block mb-1">Total Payable Amount (₹) *</label>
              <div class="relative">
                <span class="absolute left-3 top-1/2 -translate-y-1/2 font-bold text-slate-400">₹</span>
                <input v-model="createForm.total_amount" type="number" min="1" required class="w-full bg-slate-50 border border-slate-300 rounded-xl pl-7 pr-3 py-2 text-sm font-black text-slate-900 focus:bg-white focus:outline-none focus:border-red-500" />
              </div>
            </div>
          </div>

          <!-- Plan Type Selector -->
          <div>
            <label class="font-bold text-slate-700 block mb-1.5">Payment Structure</label>
            <div class="grid grid-cols-2 gap-3">
              <div 
                @click="createForm.plan_type = 'one_time'"
                :class="['p-3 rounded-xl border cursor-pointer transition flex items-center gap-2.5', createForm.plan_type === 'one_time' ? 'bg-red-50 border-red-500 text-red-900 ring-2 ring-red-500/20' : 'bg-slate-50 border-slate-200 text-slate-600']"
              >
                <div class="w-4 h-4 rounded-full border-2 flex items-center justify-center" :class="createForm.plan_type === 'one_time' ? 'border-red-600 bg-red-600 text-white' : 'border-slate-400'">
                  <div v-if="createForm.plan_type === 'one_time'" class="w-1.5 h-1.5 bg-white rounded-full"></div>
                </div>
                <div>
                  <strong class="block font-bold">One-Time Lump Sum</strong>
                  <span class="text-[10px] text-slate-500">Pay full amount at once</span>
                </div>
              </div>

              <div 
                @click="createForm.plan_type = 'installments'"
                :class="['p-3 rounded-xl border cursor-pointer transition flex items-center gap-2.5', createForm.plan_type === 'installments' ? 'bg-indigo-50 border-indigo-500 text-indigo-900 ring-2 ring-indigo-500/20' : 'bg-slate-50 border-slate-200 text-slate-600']"
              >
                <div class="w-4 h-4 rounded-full border-2 flex items-center justify-center" :class="createForm.plan_type === 'installments' ? 'border-indigo-600 bg-indigo-600 text-white' : 'border-slate-400'">
                  <div v-if="createForm.plan_type === 'installments'" class="w-1.5 h-1.5 bg-white rounded-full"></div>
                </div>
                <div>
                  <strong class="block font-bold">Split Milestone Installments</strong>
                  <span class="text-[10px] text-slate-500">Break fee into 2, 3 or more parts</span>
                </div>
              </div>
            </div>
          </div>

          <!-- Dynamic Installments Builder (When Installments Selected) -->
          <div v-if="createForm.plan_type === 'installments'" class="bg-indigo-50/50 border border-indigo-200 rounded-2xl p-4 space-y-3">
            <div class="flex items-center justify-between">
              <span class="font-black text-indigo-950 text-xs">Configure Installment Schedule</span>
              <div class="flex items-center gap-1.5">
                <span class="text-[11px] text-indigo-700 font-bold">Auto-Split:</span>
                <button type="button" @click="setInstallmentCount(2)" class="px-2 py-0.5 bg-white border border-indigo-300 rounded text-[10px] font-bold text-indigo-800 hover:bg-indigo-100">2 Parts</button>
                <button type="button" @click="setInstallmentCount(3)" class="px-2 py-0.5 bg-white border border-indigo-300 rounded text-[10px] font-bold text-indigo-800 hover:bg-indigo-100">3 Parts</button>
                <button type="button" @click="setInstallmentCount(4)" class="px-2 py-0.5 bg-white border border-indigo-300 rounded text-[10px] font-bold text-indigo-800 hover:bg-indigo-100">4 Parts</button>
              </div>
            </div>

            <div class="space-y-2">
              <div v-for="(inst, idx) in createForm.installments" :key="idx" class="flex items-center gap-2 bg-white p-2.5 rounded-xl border border-indigo-100 shadow-2xs">
                <span class="w-6 text-center font-bold text-indigo-600 text-xs">#{{ idx + 1 }}</span>
                <input v-model="inst.title" type="text" placeholder="Installment Title" class="flex-1 bg-slate-50 border border-slate-200 rounded-lg px-2.5 py-1.5 text-xs font-semibold" />
                <div class="relative w-28">
                  <span class="absolute left-2 top-1/2 -translate-y-1/2 text-slate-400 font-bold">₹</span>
                  <input v-model="inst.amount" type="number" min="1" class="w-full bg-slate-50 border border-slate-200 rounded-lg pl-5 pr-2 py-1.5 text-xs font-bold" />
                </div>
                <input v-model="inst.due_date" type="date" class="w-32 bg-slate-50 border border-slate-200 rounded-lg px-2 py-1.5 text-xs font-medium" />
                <button v-if="createForm.installments.length > 1" type="button" @click="removeInstallmentRow(idx)" class="text-slate-400 hover:text-red-600 p-1">
                  ✕
                </button>
              </div>
            </div>

            <div class="flex items-center justify-between pt-1 text-[11px]">
              <button type="button" @click="addInstallmentRow" class="text-indigo-600 font-bold hover:underline cursor-pointer">
                + Add Another Installment
              </button>
              <div class="font-bold" :class="installmentSum == createForm.total_amount ? 'text-emerald-700' : 'text-red-600'">
                Sum: ₹{{ Number(installmentSum).toLocaleString('en-IN') }} / ₹{{ Number(createForm.total_amount).toLocaleString('en-IN') }}
              </div>
            </div>
          </div>

          <!-- Due Date for Lump Sum -->
          <div v-else>
            <label class="font-bold text-slate-700 block mb-1">Due Date</label>
            <input v-model="createForm.due_date" type="date" class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2 font-bold text-slate-900 focus:bg-white focus:outline-none focus:border-red-500" />
          </div>

          <!-- Notes / Instructions -->
          <div>
            <label class="font-bold text-slate-700 block mb-1">Notes / Payment Terms for Customer</label>
            <textarea v-model="createForm.notes" rows="2" placeholder="e.g. Please enter student admission roll number in remarks..." class="w-full bg-slate-50 border border-slate-300 rounded-xl p-3 text-slate-800 focus:bg-white focus:outline-none focus:border-red-500"></textarea>
          </div>

          <!-- Footer Buttons -->
          <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-200">
            <button type="button" @click="isCreateModalOpen = false" class="px-5 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-xl transition cursor-pointer">
              Cancel
            </button>
            <button type="submit" :disabled="createForm.processing" class="px-6 py-2.5 bg-red-600 hover:bg-red-700 text-white font-black rounded-xl shadow-md transition disabled:opacity-50 cursor-pointer flex items-center gap-2">
              <SparklesIcon class="w-4 h-4" />
              <span>{{ createForm.processing ? 'Generating...' : '🚀 Generate Payment Plan & Link' }}</span>
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
