<script setup>
import { ref, computed } from 'vue';
import { useForm, Head, Link, router } from '@inertiajs/vue3';
import Navbar from '@/Components/Navbar.vue';
import GlobalSearchModal from '@/Components/GlobalSearchModal.vue';
import Loader from '@/Components/Loader.vue';
import { 
  ChartBarIcon, 
  UserGroupIcon, 
  FunnelIcon, 
  CreditCardIcon, 
  UsersIcon, 
  ChartPieIcon, 
  Cog6ToothIcon, 
  CurrencyRupeeIcon, 
  ArrowTrendingUpIcon, 
  CheckCircleIcon, 
  ClockIcon, 
  ArrowPathIcon,
  PlusIcon,
  MagnifyingGlassIcon,
  ChevronLeftIcon,
  ChevronRightIcon,
  SparklesIcon,
  BuildingOfficeIcon,
  XMarkIcon,
  RocketLaunchIcon,
  EnvelopeIcon,
  ArrowTopRightOnSquareIcon,
  ShieldCheckIcon,
  TagIcon,
  BriefcaseIcon,
  BanknotesIcon,
  AdjustmentsHorizontalIcon,
  CheckIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
  stats: {
    type: Object,
    default: () => ({
      total_sales: '₹0.00',
      total_sales_count: 0,
      active_subscriptions: 0,
      mrr: '₹0.00',
      conversion_rate: '0%',
    }),
  },
  transactions: {
    type: Array,
    default: () => [],
  },
  leads: {
    type: Array,
    default: () => [],
  },
  plans: {
    type: Array,
    default: () => [],
  },
  customers: {
    type: Array,
    default: () => [],
  },
  tenants: {
    type: Array,
    default: () => [],
  },
  filters: {
    type: Object,
    default: () => ({}),
  },
});

const isHovered = ref(false);
const isPinnedOpen = ref(false);
const isExpanded = computed(() => isPinnedOpen.value || isHovered.value);
const activeTab = ref(props.filters?.tab || 'dashboard');
const selectedPlanFilter = ref(props.filters?.plan || 'all');
const selectedStatusFilter = ref(props.filters?.status || 'all');
const searchQuery = ref(props.filters?.search || '');
const leadSearch = ref('');
const leadStageFilter = ref('all');
const customerSearch = ref('');
const isSearchOpen = ref(false);
const isAddLeadModalOpen = ref(false);
const isPageLoading = ref(false);
const movingLeadId = ref(null);

const navItems = [
  { key: 'dashboard', label: 'Dashboard', icon: ChartBarIcon },
  { key: 'leads', label: 'Leads', icon: UserGroupIcon },
  { key: 'deals', label: 'Deals Pipeline', icon: FunnelIcon },
  { key: 'subscriptions', label: 'CRM Subscriptions', icon: CreditCardIcon },
  { key: 'customers', label: 'Customers', icon: UsersIcon },
  { key: 'analytics', label: 'Analytics', icon: ChartPieIcon },
  { key: 'settings', label: 'Settings', icon: Cog6ToothIcon },
];

const switchTab = (tabKey) => {
  activeTab.value = tabKey;
  if (typeof window !== 'undefined') {
    const url = new URL(window.location.href);
    url.searchParams.set('tab', tabKey);
    window.history.replaceState({}, '', url.toString());
  }
};

const applyFilters = () => {
  router.get('/crm-sales-panel', {
    plan: selectedPlanFilter.value,
    status: selectedStatusFilter.value,
    search: searchQuery.value,
    tab: activeTab.value,
  }, { 
    preserveState: true, 
    replace: true,
    onStart: () => { isPageLoading.value = true; },
    onFinish: () => { isPageLoading.value = false; }
  });
};

const setPlanFilter = (plan) => {
  selectedPlanFilter.value = plan;
  applyFilters();
};

const setStatusFilter = (status) => {
  selectedStatusFilter.value = status;
  applyFilters();
};

const newLeadForm = useForm({
  customer_name: '',
  company_name: '',
  email: '',
  industry: 'Technology',
  deal_stage: 'New',
  estimated_mrr: 149.00,
});

const submitLead = () => {
  newLeadForm.post('/crm-sales-panel/leads', {
    onSuccess: () => {
      isAddLeadModalOpen.value = false;
      newLeadForm.reset();
    },
  });
};

const updateStage = (leadId, newStage) => {
  movingLeadId.value = leadId;
  router.patch(`/crm-sales-panel/leads/${leadId}/stage`, {
    deal_stage: newStage,
  }, { 
    preserveState: true,
    onFinish: () => { movingLeadId.value = null; }
  });
};

const pipelineStages = ['New', 'Qualified', 'Proposal', 'Won'];

const getStageLeads = (stage) => {
  return (props.leads || []).filter(l => l.deal_stage === stage);
};

const getStageTotal = (stage) => {
  const stageLeads = getStageLeads(stage);
  return stageLeads.reduce((acc, curr) => acc + Number(curr.estimated_mrr || 0), 0);
};

const filteredLeads = computed(() => {
  let list = props.leads || [];
  if (leadStageFilter.value !== 'all') {
    list = list.filter(l => l.deal_stage === leadStageFilter.value);
  }
  if (leadSearch.value.trim()) {
    const q = leadSearch.value.toLowerCase();
    list = list.filter(l => 
      (l.customer_name && l.customer_name.toLowerCase().includes(q)) ||
      (l.company_name && l.company_name.toLowerCase().includes(q)) ||
      (l.email && l.email.toLowerCase().includes(q)) ||
      (l.industry && l.industry.toLowerCase().includes(q))
    );
  }
  return list;
});

const filteredCustomers = computed(() => {
  let list = props.customers || [];
  if (customerSearch.value.trim()) {
    const q = customerSearch.value.toLowerCase();
    list = list.filter(c => 
      (c.customer_name && c.customer_name.toLowerCase().includes(q)) ||
      (c.customer_email && c.customer_email.toLowerCase().includes(q)) ||
      (c.plan_tier && c.plan_tier.toLowerCase().includes(q))
    );
  }
  return list;
});

const filteredTransactions = computed(() => {
  let list = props.transactions || [];
  if (selectedPlanFilter.value !== 'all') {
    list = list.filter(t => t.plan_tier === selectedPlanFilter.value);
  }
  if (selectedStatusFilter.value !== 'all') {
    list = list.filter(t => t.payment_status === selectedStatusFilter.value);
  }
  if (searchQuery.value.trim()) {
    const q = searchQuery.value.toLowerCase();
    list = list.filter(t => 
      (t.customer_name && t.customer_name.toLowerCase().includes(q)) ||
      (t.customer_email && t.customer_email.toLowerCase().includes(q)) ||
      (t.transaction_code && t.transaction_code.toLowerCase().includes(q))
    );
  }
  return list;
});

const planStats = computed(() => {
  const txs = (props.transactions || []).filter(t => t.payment_status === 'Paid');
  const starter = txs.filter(t => t.plan_tier === 'Starter').reduce((acc, t) => acc + Number(t.amount || 0), 0);
  const pro = txs.filter(t => t.plan_tier === 'Pro').reduce((acc, t) => acc + Number(t.amount || 0), 0);
  const enterprise = txs.filter(t => t.plan_tier === 'Enterprise').reduce((acc, t) => acc + Number(t.amount || 0), 0);
  const total = starter + pro + enterprise || 1;
  return {
    starter,
    pro,
    enterprise,
    starterPct: Math.round((starter / total) * 100),
    proPct: Math.round((pro / total) * 100),
    enterprisePct: Math.round((enterprise / total) * 100),
  };
});
</script>

<template>
  <Head title="CRM Sales & Subscription Control Panel" />

  <div class="h-screen bg-slate-100 flex font-sans text-slate-900 overflow-hidden">
    <!-- Navbar (Fixed Left Vertical Sidebar) -->
    <Navbar @open-search="isSearchOpen = true" />

    <!-- RIGHT MAIN CONTENT AREA -->
    <div class="flex-1 flex min-w-0 h-screen overflow-hidden">
      <!-- SIDEBAR NAVIGATION (Sub-Sidebar: Closed by default, Opens on Hover) -->
      <aside 
        @mouseenter="isHovered = true"
        @mouseleave="isHovered = false"
        :class="[
          'bg-white border-r border-slate-200 hover:border-red-500 shrink-0 transition-all duration-300 ease-in-out sticky top-0 h-screen overflow-y-auto font-sans z-20 select-none flex flex-col justify-between group/subsidebar',
          isExpanded ? 'w-64 p-4 shadow-xl' : 'w-20 p-3 shadow-xs'
        ]"
      >
        <div class="space-y-6">
          <!-- Open / Close Toggle Button (Locks open or returns to hover-only) -->
          <button 
            @click.stop="isPinnedOpen = !isPinnedOpen"
            class="absolute -right-3 top-7 w-6 h-6 rounded-full bg-white border border-slate-200 hover:border-red-500 shadow-md flex items-center justify-center text-slate-500 hover:text-red-600 transition-all z-50 cursor-pointer"
            :title="isPinnedOpen ? 'Collapse to hover-only mode' : 'Pin sidebar open'"
          >
            <ChevronLeftIcon v-if="isExpanded" class="w-3.5 h-3.5 stroke-[2.5]" />
            <ChevronRightIcon v-else class="w-3.5 h-3.5 stroke-[2.5]" />
          </button>

          <!-- Header Section -->
          <div class="space-y-1 pb-3 border-b border-slate-100 overflow-hidden">
            <template v-if="isExpanded">
              <div class="text-[10px] font-black text-red-600 uppercase tracking-widest px-1 transition-opacity duration-200">
                CRM Sales Control
              </div>
              <h2 class="text-base font-extrabold text-slate-900 px-1 tracking-tight truncate transition-opacity duration-200">
                Sales Control Panel
              </h2>
            </template>
            <template v-else>
              <div class="flex flex-col items-center justify-center py-1 text-center" title="Sales Control Panel (Hover mouse to open)">
                <span class="text-red-600 font-black text-sm">⚡</span>
                <span class="text-[9px] font-bold text-slate-400 uppercase tracking-tighter">Sales</span>
              </div>
            </template>
          </div>

          <!-- Dynamic List Navigation Items -->
          <nav class="space-y-1.5">
            <button
              v-for="item in navItems"
              :key="item.key"
              @click="switchTab(item.key)"
              :title="item.label"
              :class="[
                'w-full flex items-center rounded-xl font-bold text-xs transition-all cursor-pointer',
                isExpanded ? 'gap-3 px-3.5 py-2.5 text-left' : 'justify-center p-2.5',
                activeTab === item.key 
                  ? 'bg-red-50 text-red-600 border border-red-200 shadow-2xs font-extrabold ring-1 ring-red-300' 
                  : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'
              ]"
            >
              <component :is="item.icon" :class="['w-5 h-5 shrink-0', activeTab === item.key ? 'text-red-600' : 'text-slate-400']" />
              <span 
                v-if="isExpanded"
                class="truncate transition-opacity duration-200"
              >
                {{ item.label }}
              </span>
            </button>
          </nav>
        </div>

        <!-- Sub-sidebar footer info -->
        <div 
          v-if="isExpanded"
          class="pt-3 border-t border-slate-100 text-[11px] text-slate-400 space-y-1 transition-opacity duration-200"
        >
          <div class="flex items-center justify-between text-[10px] font-semibold text-slate-500">
            <span>Currency</span>
            <span class="font-bold text-slate-800">INR (₹)</span>
          </div>
          <div class="flex items-center justify-between text-[10px] font-semibold text-slate-500">
            <span>Client Portals</span>
            <span class="text-emerald-600 font-bold">Online 🟢</span>
          </div>
        </div>
      </aside>

      <!-- MAIN CONTENT WINDOW (Displays matching view according to clicked list item) -->
      <main class="flex-1 p-8 space-y-8 overflow-y-auto h-screen">

        <!-- ==================================================================== -->
        <!-- 1. TAB: DASHBOARD                                                   -->
        <!-- ==================================================================== -->
        <template v-if="activeTab === 'dashboard'">
          <!-- Header -->
          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-200 pb-5">
            <div>
              <h1 class="text-2xl font-black text-slate-900 tracking-tight">CRM Sales & Subscription Dashboard</h1>
              <p class="text-xs text-slate-500 font-medium mt-0.5">Real-time workspace activity, revenue generation, active client subscriptions, and pipeline health.</p>
            </div>
            <div class="flex items-center gap-2.5">
              <button 
                @click="isAddLeadModalOpen = true"
                class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-extrabold text-xs rounded-xl shadow-xs flex items-center gap-1.5 transition cursor-pointer"
              >
                <PlusIcon class="w-4 h-4 stroke-[3]" />
                <span>Add Deal Lead</span>
              </button>
            </div>
          </div>

          <!-- TOP STATS CARDS -->
          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Total Sales Card -->
            <div class="relative overflow-hidden p-4 rounded-2xl bg-white border border-slate-200 shadow-xs space-y-2 hover:shadow-md transition-all">
              <div class="flex items-center justify-between">
                <span class="text-[11px] font-extrabold uppercase tracking-wider text-slate-500">Total Sales</span>
                <div class="w-8 h-8 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-600 flex items-center justify-center">
                  <CurrencyRupeeIcon class="w-4 h-4 stroke-[2.5]" />
                </div>
              </div>
              <div>
                <div class="text-2xl font-black text-slate-900 tracking-tight">{{ stats.total_sales }}</div>
                <div class="flex items-center gap-1 text-[11px] text-emerald-600 font-bold mt-0.5">
                  <ArrowTrendingUpIcon class="w-3.5 h-3.5" />
                  <span>{{ stats.total_sales_count }} successful payments</span>
                </div>
              </div>
            </div>

            <!-- Active CRM Subscriptions Card -->
            <div class="relative overflow-hidden p-4 rounded-2xl bg-white border border-slate-200 shadow-xs space-y-2 hover:shadow-md transition-all">
              <div class="flex items-center justify-between">
                <span class="text-[11px] font-extrabold uppercase tracking-wider text-slate-500">Active Subscriptions</span>
                <div class="w-8 h-8 rounded-xl bg-indigo-50 border border-indigo-200 text-indigo-600 flex items-center justify-center">
                  <CreditCardIcon class="w-4 h-4 stroke-[2.5]" />
                </div>
              </div>
              <div>
                <div class="text-2xl font-black text-slate-900 tracking-tight">{{ stats.active_subscriptions }}</div>
                <div class="flex items-center gap-1 text-[11px] text-indigo-600 font-bold mt-0.5">
                  <CheckCircleIcon class="w-3.5 h-3.5" />
                  <span>Active Client Workspaces</span>
                </div>
              </div>
            </div>

            <!-- Monthly Recurring Revenue Card -->
            <div class="relative overflow-hidden p-4 rounded-2xl bg-white border border-slate-200 shadow-xs space-y-2 hover:shadow-md transition-all">
              <div class="flex items-center justify-between">
                <span class="text-[11px] font-extrabold uppercase tracking-wider text-slate-500">Monthly Revenue (MRR)</span>
                <div class="w-8 h-8 rounded-xl bg-purple-50 border border-purple-200 text-purple-600 flex items-center justify-center">
                  <ArrowTrendingUpIcon class="w-4 h-4 stroke-[2.5]" />
                </div>
              </div>
              <div>
                <div class="text-2xl font-black text-slate-900 tracking-tight">{{ stats.mrr }}</div>
                <div class="flex items-center gap-1 text-[11px] text-purple-600 font-bold mt-0.5">
                  <SparklesIcon class="w-3.5 h-3.5" />
                  <span>Predictable monthly billing</span>
                </div>
              </div>
            </div>

            <!-- Conversion Rate Card -->
            <div class="relative overflow-hidden p-4 rounded-2xl bg-white border border-slate-200 shadow-xs space-y-2 hover:shadow-md transition-all">
              <div class="flex items-center justify-between">
                <span class="text-[11px] font-extrabold uppercase tracking-wider text-slate-500">Conversion Rate</span>
                <div class="w-8 h-8 rounded-xl bg-amber-50 border border-amber-200 text-amber-600 flex items-center justify-center">
                  <FunnelIcon class="w-4 h-4 stroke-[2.5]" />
                </div>
              </div>
              <div>
                <div class="text-2xl font-black text-slate-900 tracking-tight">{{ stats.conversion_rate }}</div>
                <div class="flex items-center gap-1 text-[11px] text-amber-600 font-bold mt-0.5">
                  <ClockIcon class="w-3.5 h-3.5" />
                  <span>Lead-to-deal conversion</span>
                </div>
              </div>
            </div>
          </div>

          <!-- Quick Navigation Shortcuts -->
          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <button 
              @click="switchTab('deals')"
              class="p-4 bg-white border border-slate-200 hover:border-red-400 rounded-2xl text-left transition hover:shadow-xs group cursor-pointer"
            >
              <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-700 group-hover:text-red-600">Visual Deals Pipeline</span>
                <FunnelIcon class="w-4 h-4 text-slate-400 group-hover:text-red-500" />
              </div>
              <p class="text-[11px] text-slate-500 mt-1">Manage {{ leads.length }} deals across 4 pipeline stages.</p>
            </button>

            <button 
              @click="switchTab('subscriptions')"
              class="p-4 bg-white border border-slate-200 hover:border-indigo-400 rounded-2xl text-left transition hover:shadow-xs group cursor-pointer"
            >
              <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-700 group-hover:text-indigo-600">CRM Subscriptions</span>
                <CreditCardIcon class="w-4 h-4 text-slate-400 group-hover:text-indigo-500" />
              </div>
              <p class="text-[11px] text-slate-500 mt-1">Review {{ transactions.length }} customer subscriptions & invoices.</p>
            </button>

            <button 
              @click="switchTab('customers')"
              class="p-4 bg-white border border-slate-200 hover:border-emerald-400 rounded-2xl text-left transition hover:shadow-xs group cursor-pointer"
            >
              <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-700 group-hover:text-emerald-600">Customers Directory</span>
                <UsersIcon class="w-4 h-4 text-slate-400 group-hover:text-emerald-500" />
              </div>
              <p class="text-[11px] text-slate-500 mt-1">Browse {{ customers.length }} clients & active workspaces.</p>
            </button>

            <button 
              @click="switchTab('analytics')"
              class="p-4 bg-white border border-slate-200 hover:border-purple-400 rounded-2xl text-left transition hover:shadow-xs group cursor-pointer"
            >
              <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-slate-700 group-hover:text-purple-600">Sales Analytics</span>
                <ChartPieIcon class="w-4 h-4 text-slate-400 group-hover:text-purple-500" />
              </div>
              <p class="text-[11px] text-slate-500 mt-1">Explore revenue distribution & funnel velocity.</p>
            </button>
          </div>

          <!-- Preview Deals Section -->
          <div class="bg-white border border-slate-200 rounded-3xl p-6 shadow-xs space-y-4">
            <div class="flex items-center justify-between">
              <div>
                <h3 class="text-lg font-bold text-slate-900">Deals Pipeline Snapshot</h3>
                <p class="text-xs text-slate-500 font-medium">Quick glance at active lead stages.</p>
              </div>
              <button 
                @click="switchTab('deals')"
                class="text-xs font-bold text-red-600 hover:text-red-700 flex items-center gap-1 cursor-pointer"
              >
                <span>Open Kanban Board</span>
                <ChevronRightIcon class="w-3.5 h-3.5 stroke-[2.5]" />
              </button>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
              <div 
                v-for="stage in pipelineStages" 
                :key="stage"
                class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200 text-center space-y-1"
              >
                <div class="text-[10px] font-extrabold uppercase tracking-wider text-slate-500">{{ stage }}</div>
                <div class="text-xl font-black text-slate-900">{{ getStageLeads(stage).length }}</div>
                <div class="text-[10px] font-bold text-emerald-600">₹{{ Number(getStageTotal(stage)).toLocaleString('en-IN') }}</div>
              </div>
            </div>
          </div>

          <!-- Preview Transactions Table -->
          <div class="bg-white border border-slate-200 rounded-3xl p-6 shadow-xs space-y-4">
            <div class="flex items-center justify-between">
              <div>
                <h3 class="text-lg font-bold text-slate-900">Recent Customer Transactions</h3>
                <p class="text-xs text-slate-500 font-medium">Latest invoices and recurring subscription payments.</p>
              </div>
              <button 
                @click="switchTab('subscriptions')"
                class="text-xs font-bold text-indigo-600 hover:text-indigo-700 flex items-center gap-1 cursor-pointer"
              >
                <span>View All Invoices</span>
                <ChevronRightIcon class="w-3.5 h-3.5 stroke-[2.5]" />
              </button>
            </div>

            <div class="overflow-x-auto">
              <table class="w-full text-left text-xs">
                <thead>
                  <tr class="border-b border-slate-200 text-slate-400 font-extrabold uppercase text-[10px]">
                    <th class="pb-3">Customer</th>
                    <th class="pb-3">Plan</th>
                    <th class="pb-3">Amount</th>
                    <th class="pb-3">Status</th>
                    <th class="pb-3 text-right">Date</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                  <tr v-for="t in transactions.slice(0, 5)" :key="t.id" class="hover:bg-slate-50/80">
                    <td class="py-3 font-bold text-slate-800">{{ t.customer_name }}</td>
                    <td class="py-3">
                      <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-700">
                        {{ t.plan_tier }}
                      </span>
                    </td>
                    <td class="py-3 font-extrabold text-slate-900">₹{{ Number(t.amount).toLocaleString('en-IN') }}</td>
                    <td class="py-3">
                      <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-emerald-50 text-emerald-700 border border-emerald-200">
                        {{ t.payment_status }}
                      </span>
                    </td>
                    <td class="py-3 text-right text-slate-500">{{ t.purchase_date ? t.purchase_date.split(' ')[0] : 'Recent' }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </template>

        <!-- ==================================================================== -->
        <!-- 2. TAB: LEADS                                                       -->
        <!-- ==================================================================== -->
        <template v-else-if="activeTab === 'leads'">
          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-200 pb-5">
            <div>
              <div class="flex items-center gap-2">
                <h1 class="text-2xl font-black text-slate-900 tracking-tight">CRM Leads & Opportunities</h1>
                <span class="px-2.5 py-0.5 rounded-full text-xs font-extrabold bg-red-50 text-red-600 border border-red-200">
                  {{ filteredLeads.length }} Leads
                </span>
              </div>
              <p class="text-xs text-slate-500 font-medium mt-0.5">Manage customer inquiries, qualifying deals, and assign stage values.</p>
            </div>
            <button 
              @click="isAddLeadModalOpen = true"
              class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-extrabold text-xs rounded-xl shadow-xs flex items-center gap-1.5 transition cursor-pointer shrink-0"
            >
              <PlusIcon class="w-4 h-4 stroke-[3]" />
              <span>Add Deal Lead</span>
            </button>
          </div>

          <!-- Filter & Search Bar -->
          <div class="bg-white border border-slate-200 rounded-2xl p-4 shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-3">
            <div class="relative flex-1">
              <MagnifyingGlassIcon class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2" />
              <input
                v-model="leadSearch"
                type="text"
                placeholder="Search lead by customer, company, email, industry..."
                class="w-full bg-slate-50 border border-slate-200 rounded-xl pl-9 pr-4 py-2 text-xs text-slate-900 placeholder-slate-400 font-medium focus:outline-none focus:border-red-500 focus:bg-white"
              />
            </div>

            <!-- Stage Filter Pills -->
            <div class="flex items-center gap-1 bg-slate-100 p-1 rounded-xl shrink-0 overflow-x-auto">
              <button
                v-for="st in ['all', 'New', 'Qualified', 'Proposal', 'Won']"
                :key="st"
                @click="leadStageFilter = st"
                :class="[
                  'px-3 py-1 rounded-lg text-xs font-bold transition cursor-pointer capitalize whitespace-nowrap',
                  leadStageFilter === st ? 'bg-white text-slate-900 shadow-2xs' : 'text-slate-500 hover:text-slate-800'
                ]"
              >
                {{ st }}
              </button>
            </div>
          </div>

          <!-- Leads Table -->
          <div class="bg-white border border-slate-200 rounded-3xl p-6 shadow-xs space-y-4">
            <div class="overflow-x-auto">
              <table class="w-full text-left text-xs">
                <thead>
                  <tr class="border-b border-slate-200 text-slate-400 font-extrabold uppercase text-[10px]">
                    <th class="pb-3">Lead Customer</th>
                    <th class="pb-3">Company & Industry</th>
                    <th class="pb-3">Email</th>
                    <th class="pb-3">Stage</th>
                    <th class="pb-3">Est. MRR</th>
                    <th class="pb-3 text-right">Actions</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                  <tr v-for="lead in filteredLeads" :key="lead.id" class="hover:bg-slate-50/80 transition">
                    <td class="py-3.5">
                      <div class="font-black text-slate-900 text-xs">{{ lead.customer_name }}</div>
                      <div class="text-[10px] text-slate-400">ID #{{ lead.id }}</div>
                    </td>
                    <td class="py-3.5">
                      <div class="font-bold text-slate-700">{{ lead.company_name }}</div>
                      <span class="inline-block px-2 py-0.2 rounded bg-slate-100 text-[10px] text-slate-500 mt-0.5">
                        {{ lead.industry }}
                      </span>
                    </td>
                    <td class="py-3.5">
                      <a :href="'mailto:' + lead.email" class="text-indigo-600 hover:underline font-medium">
                        {{ lead.email }}
                      </a>
                    </td>
                    <td class="py-3.5">
                      <span 
                        :class="[
                          'px-2.5 py-0.5 rounded-full text-[10px] font-extrabold border',
                          lead.deal_stage === 'Won' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' :
                          lead.deal_stage === 'Proposal' ? 'bg-purple-50 text-purple-700 border-purple-200' :
                          lead.deal_stage === 'Qualified' ? 'bg-indigo-50 text-indigo-700 border-indigo-200' :
                          'bg-amber-50 text-amber-700 border-amber-200'
                        ]"
                      >
                        {{ lead.deal_stage }}
                      </span>
                    </td>
                    <td class="py-3.5 font-extrabold text-slate-900">
                      ₹{{ Number(lead.estimated_mrr || 0).toLocaleString('en-IN') }}<span class="text-[10px] font-normal text-slate-400">/mo</span>
                    </td>
                    <td class="py-3.5 text-right">
                      <button 
                        v-if="lead.deal_stage !== 'Won'"
                        @click="updateStage(lead.id, lead.deal_stage === 'New' ? 'Qualified' : lead.deal_stage === 'Qualified' ? 'Proposal' : 'Won')"
                        :disabled="movingLeadId === lead.id"
                        class="px-2.5 py-1 bg-red-50 hover:bg-red-100 text-red-700 text-xs font-bold rounded-lg border border-red-200 transition cursor-pointer inline-flex items-center gap-1"
                      >
                        <Loader v-if="movingLeadId === lead.id" size="sm" color="red" />
                        <span v-else>Advance →</span>
                      </button>
                      <span v-else class="text-emerald-600 font-bold text-xs flex items-center justify-end gap-1">
                        <CheckCircleIcon class="w-4 h-4" />
                        <span>Closed Won</span>
                      </span>
                    </td>
                  </tr>
                  <tr v-if="filteredLeads.length === 0">
                    <td colspan="6" class="py-12 text-center text-slate-400 text-xs font-medium">
                      No leads matching your search criteria.
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </template>

        <!-- ==================================================================== -->
        <!-- 3. TAB: DEALS PIPELINE                                              -->
        <!-- ==================================================================== -->
        <template v-else-if="activeTab === 'deals'">
          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-200 pb-5">
            <div>
              <h1 class="text-2xl font-black text-slate-900 tracking-tight">Visual Deals Pipeline</h1>
              <p class="text-xs text-slate-500 font-medium mt-0.5">Track and move lead opportunities across deal conversion stages.</p>
            </div>

            <div class="flex items-center gap-2">
              <Link 
                href="/deals" 
                class="px-3 py-2 bg-white hover:bg-slate-50 border border-slate-200 text-slate-700 font-bold text-xs rounded-xl flex items-center gap-1.5 transition"
              >
                <span>Full Deals Hub</span>
                <ArrowTopRightOnSquareIcon class="w-3.5 h-3.5 text-slate-400" />
              </Link>
              <button 
                @click="isAddLeadModalOpen = true"
                class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-extrabold text-xs rounded-xl shadow-xs flex items-center gap-1.5 transition cursor-pointer"
              >
                <PlusIcon class="w-4 h-4 stroke-[3]" />
                <span>Add Deal Lead</span>
              </button>
            </div>
          </div>

          <!-- 4 Stage Columns -->
          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div 
              v-for="stage in pipelineStages" 
              :key="stage"
              class="p-4 rounded-3xl bg-white border border-slate-200 shadow-xs space-y-3"
            >
              <div class="flex items-center justify-between pb-2 border-b border-slate-200">
                <div>
                  <span class="text-xs font-extrabold uppercase tracking-wider text-slate-700">{{ stage }}</span>
                  <div class="text-[10px] font-bold text-emerald-600">₹{{ Number(getStageTotal(stage)).toLocaleString('en-IN') }}</div>
                </div>
                <span class="px-2 py-0.5 rounded-full bg-slate-100 text-[10px] font-bold text-slate-600 border border-slate-200">
                  {{ getStageLeads(stage).length }}
                </span>
              </div>

              <!-- Deal Cards inside stage -->
              <div class="space-y-3 min-h-[140px]">
                <div 
                  v-for="lead in getStageLeads(stage)" 
                  :key="lead.id"
                  class="p-3.5 rounded-2xl bg-slate-50/90 hover:bg-white border border-slate-200/80 hover:border-red-300 shadow-2xs hover:shadow-xs space-y-2.5 transition-all group"
                >
                  <!-- Header: Customer Name & MRR Badge -->
                  <div class="flex items-start justify-between gap-2">
                    <div class="min-w-0">
                      <h4 class="font-extrabold text-xs text-slate-900 leading-snug group-hover:text-red-600 transition-colors">
                        {{ lead.customer_name }}
                      </h4>
                      <p class="text-[10px] font-semibold text-slate-500 truncate mt-0.5">{{ lead.company_name }}</p>
                    </div>
                    <span class="px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200/80 text-[10px] font-black shrink-0">
                      ₹{{ Number(lead.estimated_mrr || 0).toLocaleString('en-IN') }}/mo
                    </span>
                  </div>

                  <!-- Footer: Industry Tag & Move Button -->
                  <div class="pt-2 border-t border-slate-200/70 flex items-center justify-between gap-2 text-[10px]">
                    <span class="px-2 py-0.5 rounded-md bg-white text-slate-600 font-bold border border-slate-200 text-[9px] truncate">
                      {{ lead.industry }}
                    </span>

                    <button 
                      v-if="stage !== 'Won'" 
                      @click="updateStage(lead.id, stage === 'New' ? 'Qualified' : stage === 'Qualified' ? 'Proposal' : 'Won')"
                      :disabled="movingLeadId === lead.id"
                      class="px-2.5 py-1 rounded-lg bg-indigo-50 hover:bg-indigo-100 text-indigo-700 font-extrabold text-[10px] flex items-center gap-1 transition-all cursor-pointer shrink-0"
                    >
                      <Loader v-if="movingLeadId === lead.id" size="sm" color="indigo" />
                      <template v-else>
                        <span>Move</span>
                        <ChevronRightIcon class="w-3 h-3 stroke-[3]" />
                      </template>
                    </button>
                    <span v-else class="text-emerald-600 font-bold text-[10px] flex items-center gap-0.5">
                      <CheckIcon class="w-3 h-3 stroke-[3]" />
                      <span>Won</span>
                    </span>
                  </div>
                </div>

                <div v-if="getStageLeads(stage).length === 0" class="py-8 text-center text-xs text-slate-400 font-medium">
                  No deals in {{ stage }}
                </div>
              </div>
            </div>
          </div>
        </template>

        <!-- ==================================================================== -->
        <!-- 4. TAB: CRM SUBSCRIPTIONS                                            -->
        <!-- ==================================================================== -->
        <template v-else-if="activeTab === 'subscriptions'">
          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-200 pb-5">
            <div>
              <h1 class="text-2xl font-black text-slate-900 tracking-tight">CRM Subscriptions & Pricing Plans</h1>
              <p class="text-xs text-slate-500 font-medium mt-0.5">Manage subscription tiers, recurring billing, and client payment history.</p>
            </div>

            <Link 
              href="/payment-plans" 
              class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-extrabold text-xs rounded-xl shadow-xs flex items-center gap-1.5 transition"
            >
              <CreditCardIcon class="w-4 h-4" />
              <span>Customer Invoices Hub</span>
            </Link>
          </div>

          <!-- CRM Plans Grid -->
          <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
            <div 
              v-for="plan in plans" 
              :key="plan.id"
              class="p-6 bg-white border border-slate-200 rounded-3xl shadow-xs space-y-4 hover:shadow-md transition relative"
            >
              <div class="flex items-center justify-between">
                <span class="text-xs font-black uppercase tracking-wider text-red-600">{{ plan.name }}</span>
                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                  Active
                </span>
              </div>

              <div>
                <div class="text-3xl font-black text-slate-900">₹{{ Number(plan.price_monthly).toLocaleString('en-IN') }}<span class="text-xs font-normal text-slate-400">/mo</span></div>
                <div class="text-[11px] text-slate-500 font-medium mt-0.5">Annual billing: ₹{{ Number(plan.price_annual).toLocaleString('en-IN') }}/yr</div>
              </div>

              <div class="pt-3 border-t border-slate-100 space-y-2 text-xs text-slate-600">
                <div class="flex items-center gap-2">
                  <CheckIcon class="w-4 h-4 text-emerald-600 shrink-0 stroke-[3]" />
                  <span>Up to <strong>{{ plan.max_users }} team users</strong></span>
                </div>
                <div class="flex items-center gap-2">
                  <CheckIcon class="w-4 h-4 text-emerald-600 shrink-0 stroke-[3]" />
                  <span>Up to <strong>{{ Number(plan.max_contacts).toLocaleString('en-IN') }} contacts</strong></span>
                </div>
                <div class="flex items-center gap-2">
                  <CheckIcon class="w-4 h-4 text-emerald-600 shrink-0 stroke-[3]" />
                  <span><strong>{{ plan.storage_limit_gb }} GB</strong> Cloud Storage</span>
                </div>
              </div>
            </div>
          </div>

          <!-- Transactions Ledger -->
          <div class="relative bg-white border border-slate-200 rounded-3xl p-6 shadow-xs space-y-5">
            <Loader v-if="isPageLoading" overlay text="Refreshing subscription data..." />
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
              <div>
                <h2 class="text-lg font-extrabold text-slate-900">Subscription Transactions Ledger</h2>
                <p class="text-xs text-slate-500 font-medium">Filter accounts by subscription tier and payment status.</p>
              </div>

              <!-- Search Bar -->
              <div class="relative w-full md:w-72">
                <MagnifyingGlassIcon class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2" />
                <input
                  v-model="searchQuery"
                  @input="applyFilters"
                  type="text"
                  placeholder="Search customer, email..."
                  class="w-full bg-slate-50 border border-slate-200 rounded-xl pl-10 pr-4 py-2 text-xs text-slate-900 placeholder-slate-400 font-medium focus:outline-none focus:border-indigo-500 focus:bg-white"
                />
              </div>
            </div>

            <!-- TIER & STATUS FILTER BUTTONS ROW -->
            <div class="flex flex-wrap items-center justify-between gap-4 pt-2 border-t border-slate-200">
              <!-- Tier Filter -->
              <div class="flex items-center gap-1.5 bg-slate-100 p-1 rounded-2xl border border-slate-200">
                <span class="text-[10px] font-extrabold text-slate-500 uppercase tracking-wider px-2">Tier:</span>
                <button
                  v-for="tier in ['all', 'Starter', 'Pro', 'Enterprise']"
                  :key="tier"
                  @click="setPlanFilter(tier)"
                  :class="[
                    'px-3 py-1.5 rounded-xl text-xs font-bold transition-all cursor-pointer capitalize',
                    selectedPlanFilter === tier ? 'bg-indigo-600 text-white shadow-xs' : 'text-slate-600 hover:text-slate-900'
                  ]"
                >
                  {{ tier }}
                </button>
              </div>

              <!-- Status Filter -->
              <div class="flex items-center gap-1.5 bg-slate-100 p-1 rounded-2xl border border-slate-200">
                <span class="text-[10px] font-extrabold text-slate-500 uppercase tracking-wider px-2">Status:</span>
                <button
                  v-for="status in ['all', 'Paid', 'Pending', 'Refunded']"
                  :key="status"
                  @click="setStatusFilter(status)"
                  :class="[
                    'px-3 py-1.5 rounded-xl text-xs font-bold transition-all cursor-pointer capitalize',
                    selectedStatusFilter === status ? 'bg-emerald-600 text-white shadow-xs' : 'text-slate-600 hover:text-slate-900'
                  ]"
                >
                  {{ status }}
                </button>
              </div>
            </div>

            <!-- Table -->
            <div class="overflow-x-auto">
              <table class="w-full text-left text-xs">
                <thead>
                  <tr class="border-b border-slate-200 text-slate-400 font-extrabold uppercase text-[10px]">
                    <th class="pb-3">Customer</th>
                    <th class="pb-3">Email</th>
                    <th class="pb-3">Tier</th>
                    <th class="pb-3">Amount</th>
                    <th class="pb-3">Payment Method</th>
                    <th class="pb-3">Status</th>
                    <th class="pb-3 text-right">Date</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                  <tr v-for="t in filteredTransactions" :key="t.id" class="hover:bg-slate-50/80 transition">
                    <td class="py-3 font-bold text-slate-900">{{ t.customer_name }}</td>
                    <td class="py-3 text-slate-600">{{ t.customer_email }}</td>
                    <td class="py-3">
                      <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-800">
                        {{ t.plan_tier }}
                      </span>
                    </td>
                    <td class="py-3 font-extrabold text-slate-900">₹{{ Number(t.amount).toLocaleString('en-IN') }}</td>
                    <td class="py-3 text-slate-500">{{ t.payment_method }}</td>
                    <td class="py-3">
                      <span 
                        :class="[
                          'px-2.5 py-0.5 rounded-full text-[10px] font-extrabold border',
                          t.payment_status === 'Paid' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' :
                          t.payment_status === 'Pending' ? 'bg-amber-50 text-amber-700 border-amber-200' :
                          'bg-red-50 text-red-700 border-red-200'
                        ]"
                      >
                        {{ t.payment_status }}
                      </span>
                    </td>
                    <td class="py-3 text-right text-slate-400">{{ t.purchase_date }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </template>

        <!-- ==================================================================== -->
        <!-- 5. TAB: CUSTOMERS                                                   -->
        <!-- ==================================================================== -->
        <template v-else-if="activeTab === 'customers'">
          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-200 pb-5">
            <div>
              <div class="flex items-center gap-2">
                <h1 class="text-2xl font-black text-slate-900 tracking-tight">Customer Directory & Client Workspaces</h1>
                <span class="px-2.5 py-0.5 rounded-full text-xs font-extrabold bg-emerald-50 text-emerald-700 border border-emerald-200">
                  {{ filteredCustomers.length }} Customers
                </span>
              </div>
              <p class="text-xs text-slate-500 font-medium mt-0.5">Manage onboarded organizations, customer contacts, and active client workspaces.</p>
            </div>

            <div class="relative w-full sm:w-72">
              <MagnifyingGlassIcon class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2" />
              <input
                v-model="customerSearch"
                type="text"
                placeholder="Search customers by name, email, plan..."
                class="w-full bg-white border border-slate-200 rounded-xl pl-10 pr-4 py-2 text-xs text-slate-900 placeholder-slate-400 font-medium focus:outline-none focus:border-red-500"
              />
            </div>
          </div>

          <!-- Customer Cards & Table -->
          <div class="bg-white border border-slate-200 rounded-3xl p-6 shadow-xs space-y-4">
            <div class="overflow-x-auto">
              <table class="w-full text-left text-xs">
                <thead>
                  <tr class="border-b border-slate-200 text-slate-400 font-extrabold uppercase text-[10px]">
                    <th class="pb-3">Organization / Client</th>
                    <th class="pb-3">Primary Contact Email</th>
                    <th class="pb-3">Subscribed Tier</th>
                    <th class="pb-3">Total Invoiced</th>
                    <th class="pb-3">Billing Status</th>
                    <th class="pb-3">Last Activity</th>
                    <th class="pb-3 text-right">Actions</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                  <tr v-for="cust in filteredCustomers" :key="cust.customer_email" class="hover:bg-slate-50/80 transition">
                    <td class="py-3.5">
                      <div class="font-extrabold text-slate-900 text-xs">{{ cust.customer_name }}</div>
                      <div class="text-[10px] text-slate-400 font-mono">{{ cust.orders_count }} subscription order(s)</div>
                    </td>
                    <td class="py-3.5">
                      <a :href="'mailto:' + cust.customer_email" class="text-indigo-600 hover:underline font-medium">
                        {{ cust.customer_email }}
                      </a>
                    </td>
                    <td class="py-3.5">
                      <span 
                        :class="[
                          'px-2.5 py-0.5 rounded-full text-[10px] font-extrabold border',
                          cust.plan_tier === 'Enterprise' ? 'bg-purple-50 text-purple-700 border-purple-200' :
                          cust.plan_tier === 'Pro' ? 'bg-indigo-50 text-indigo-700 border-indigo-200' :
                          'bg-slate-100 text-slate-700 border-slate-200'
                        ]"
                      >
                        {{ cust.plan_tier }}
                      </span>
                    </td>
                    <td class="py-3.5 font-black text-slate-900">
                      ₹{{ Number(cust.total_spent || 0).toLocaleString('en-IN') }}
                    </td>
                    <td class="py-3.5">
                      <span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold bg-emerald-50 text-emerald-700 border border-emerald-200">
                        {{ cust.payment_status }}
                      </span>
                    </td>
                    <td class="py-3.5 text-slate-500 text-[11px]">
                      {{ cust.last_activity ? cust.last_activity.split(' ')[0] : 'Recent' }}
                    </td>
                    <td class="py-3.5 text-right">
                      <a 
                        :href="'mailto:' + cust.customer_email"
                        class="px-2.5 py-1 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-lg border border-slate-200 transition inline-flex items-center gap-1"
                      >
                        <EnvelopeIcon class="w-3.5 h-3.5" />
                        <span>Contact</span>
                      </a>
                    </td>
                  </tr>
                  <tr v-if="filteredCustomers.length === 0">
                    <td colspan="7" class="py-12 text-center text-slate-400 text-xs font-medium">
                      No customers found matching "{{ customerSearch }}".
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <!-- Active Client Tenant Workspaces Grid -->
          <div class="space-y-3">
            <h2 class="text-base font-extrabold text-slate-900">Active Tenant Dedicated Workspaces</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
              <div 
                v-for="t in tenants" 
                :key="t.id"
                class="p-4 rounded-2xl bg-white border border-slate-200 shadow-2xs space-y-2 hover:shadow-xs transition"
              >
                <div class="flex items-center justify-between">
                  <span class="font-extrabold text-xs text-slate-900 truncate">{{ t.name }}</span>
                  <span class="px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-emerald-50 text-emerald-700 border border-emerald-200 uppercase">
                    {{ t.status }}
                  </span>
                </div>
                <div class="text-xs font-mono text-red-600 truncate">
                  {{ t.subdomain ? `${t.subdomain}.jrvcrm.com` : 'Dedicated Cloud Workspace' }}
                </div>
                <div class="text-[10px] text-slate-400 pt-1 border-t border-slate-100 flex items-center justify-between">
                  <span>Workspace ID: #{{ t.id }}</span>
                  <span>Joined {{ t.created_at ? t.created_at.split('T')[0] : 'Recent' }}</span>
                </div>
              </div>
            </div>
          </div>
        </template>

        <!-- ==================================================================== -->
        <!-- 6. TAB: ANALYTICS                                                   -->
        <!-- ==================================================================== -->
        <template v-else-if="activeTab === 'analytics'">
          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-200 pb-5">
            <div>
              <h1 class="text-2xl font-black text-slate-900 tracking-tight">Sales & Performance Analytics</h1>
              <p class="text-xs text-slate-500 font-medium mt-0.5">Comprehensive insights on recurring revenue, conversion rates, and tier distribution.</p>
            </div>
          </div>

          <!-- KPI Row -->
          <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="p-5 rounded-2xl bg-white border border-slate-200 shadow-xs space-y-1">
              <span class="text-[11px] font-extrabold uppercase text-slate-500">Monthly Run Rate (MRR)</span>
              <div class="text-2xl font-black text-slate-900">{{ stats.mrr }}</div>
              <p class="text-[11px] text-emerald-600 font-bold">+12% growth quarter-over-quarter</p>
            </div>

            <div class="p-5 rounded-2xl bg-white border border-slate-200 shadow-xs space-y-1">
              <span class="text-[11px] font-extrabold uppercase text-slate-500">Annual Run Rate (ARR)</span>
              <div class="text-2xl font-black text-indigo-600">
                ₹{{ (parseFloat(stats.mrr.replace(/[^0-9.]/g, '') || 0) * 12).toLocaleString('en-IN') }}
              </div>
              <p class="text-[11px] text-slate-500 font-medium">Estimated 12-month recurring revenue</p>
            </div>

            <div class="p-5 rounded-2xl bg-white border border-slate-200 shadow-xs space-y-1">
              <span class="text-[11px] font-extrabold uppercase text-slate-500">Total Paying Accounts</span>
              <div class="text-2xl font-black text-emerald-600">{{ stats.total_sales_count }}</div>
              <p class="text-[11px] text-slate-500 font-medium">100% active subscription status</p>
            </div>
          </div>

          <!-- Revenue Distribution by Plan Tier -->
          <div class="bg-white border border-slate-200 rounded-3xl p-6 shadow-xs space-y-5">
            <h3 class="text-lg font-bold text-slate-900">Revenue Breakdown by Plan Tier</h3>

            <div class="space-y-4">
              <!-- Enterprise -->
              <div class="space-y-1.5">
                <div class="flex items-center justify-between text-xs font-bold">
                  <span class="text-purple-700">Enterprise Plan</span>
                  <span class="text-slate-900">₹{{ Number(planStats.enterprise).toLocaleString('en-IN') }} ({{ planStats.enterprisePct }}%)</span>
                </div>
                <div class="w-full bg-slate-100 h-2.5 rounded-full overflow-hidden">
                  <div class="bg-purple-600 h-full rounded-full transition-all duration-500" :style="{ width: planStats.enterprisePct + '%' }"></div>
                </div>
              </div>

              <!-- Pro -->
              <div class="space-y-1.5">
                <div class="flex items-center justify-between text-xs font-bold">
                  <span class="text-indigo-700">Pro Plan</span>
                  <span class="text-slate-900">₹{{ Number(planStats.pro).toLocaleString('en-IN') }} ({{ planStats.proPct }}%)</span>
                </div>
                <div class="w-full bg-slate-100 h-2.5 rounded-full overflow-hidden">
                  <div class="bg-indigo-600 h-full rounded-full transition-all duration-500" :style="{ width: planStats.proPct + '%' }"></div>
                </div>
              </div>

              <!-- Starter -->
              <div class="space-y-1.5">
                <div class="flex items-center justify-between text-xs font-bold">
                  <span class="text-emerald-700">Starter Plan</span>
                  <span class="text-slate-900">₹{{ Number(planStats.starter).toLocaleString('en-IN') }} ({{ planStats.starterPct }}%)</span>
                </div>
                <div class="w-full bg-slate-100 h-2.5 rounded-full overflow-hidden">
                  <div class="bg-emerald-600 h-full rounded-full transition-all duration-500" :style="{ width: planStats.starterPct + '%' }"></div>
                </div>
              </div>
            </div>
          </div>
        </template>

        <!-- ==================================================================== -->
        <!-- 7. TAB: SETTINGS                                                    -->
        <!-- ==================================================================== -->
        <template v-else-if="activeTab === 'settings'">
          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-200 pb-5">
            <div>
              <h1 class="text-2xl font-black text-slate-900 tracking-tight">Sales Control & CRM Configuration</h1>
              <p class="text-xs text-slate-500 font-medium mt-0.5">Configure sales default targets, currencies, and link directly to global system settings.</p>
            </div>
          </div>

          <!-- Configuration Cards -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <div class="p-6 bg-white border border-slate-200 rounded-3xl shadow-xs space-y-4">
              <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-red-50 text-red-600 flex items-center justify-center font-bold">
                  <CurrencyRupeeIcon class="w-6 h-6 stroke-[2.5]" />
                </div>
                <div>
                  <h3 class="font-bold text-sm text-slate-900">Currency & Deal Valuation</h3>
                  <p class="text-xs text-slate-500">Default currency for deal estimation and MRR.</p>
                </div>
              </div>
              <div class="p-3 rounded-xl bg-slate-50 border border-slate-200 text-xs font-mono text-slate-700 flex items-center justify-between">
                <span>Active Currency</span>
                <span class="font-bold text-slate-900">Indian Rupee (INR ₹)</span>
              </div>
            </div>

            <div class="p-6 bg-white border border-slate-200 rounded-3xl shadow-xs space-y-4">
              <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center font-bold">
                  <FunnelIcon class="w-6 h-6 stroke-[2.5]" />
                </div>
                <div>
                  <h3 class="font-bold text-sm text-slate-900">Pipeline Stages</h3>
                  <p class="text-xs text-slate-500">Configured stages for visual conversion.</p>
                </div>
              </div>
              <div class="flex items-center gap-1 text-[11px] font-bold text-slate-700 flex-wrap">
                <span class="px-2 py-1 rounded-lg bg-slate-100">1. New</span>
                <span>→</span>
                <span class="px-2 py-1 rounded-lg bg-slate-100">2. Qualified</span>
                <span>→</span>
                <span class="px-2 py-1 rounded-lg bg-slate-100">3. Proposal</span>
                <span>→</span>
                <span class="px-2 py-1 rounded-lg bg-emerald-50 text-emerald-700 border border-emerald-200">4. Won</span>
              </div>
            </div>
          </div>

          <!-- Quick Links to Other Management Hubs -->
          <div class="bg-white border border-slate-200 rounded-3xl p-6 shadow-xs space-y-4">
            <h3 class="text-base font-extrabold text-slate-900">Connected Management Hubs</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
              <Link 
                href="/settings" 
                class="p-4 rounded-2xl bg-slate-50 hover:bg-red-50/50 border border-slate-200 hover:border-red-200 transition flex items-center justify-between group cursor-pointer"
              >
                <div class="flex items-center gap-3">
                  <Cog6ToothIcon class="w-5 h-5 text-slate-400 group-hover:text-red-600 transition" />
                  <div>
                    <div class="font-bold text-xs text-slate-900 group-hover:text-red-600 transition">System Settings & Website Integration</div>
                    <div class="text-[10px] text-slate-500">Business branding, UPI QR code, payment gateways</div>
                  </div>
                </div>
                <ChevronRightIcon class="w-4 h-4 text-slate-400 group-hover:text-red-600 transition" />
              </Link>

              <Link 
                href="/tenant/crm-records" 
                class="p-4 rounded-2xl bg-slate-50 hover:bg-red-50/50 border border-slate-200 hover:border-red-200 transition flex items-center justify-between group cursor-pointer"
              >
                <div class="flex items-center gap-3">
                  <ChartBarIcon class="w-5 h-5 text-slate-400 group-hover:text-red-600 transition" />
                  <div>
                    <div class="font-bold text-xs text-slate-900 group-hover:text-red-600 transition">Dynamic CRM Database & Schema</div>
                    <div class="text-[10px] text-slate-500">Upload database, custom columns, records manager</div>
                  </div>
                </div>
                <ChevronRightIcon class="w-4 h-4 text-slate-400 group-hover:text-red-600 transition" />
              </Link>

              <Link 
                href="/payment-plans" 
                class="p-4 rounded-2xl bg-slate-50 hover:bg-red-50/50 border border-slate-200 hover:border-red-200 transition flex items-center justify-between group cursor-pointer"
              >
                <div class="flex items-center gap-3">
                  <CreditCardIcon class="w-5 h-5 text-slate-400 group-hover:text-red-600 transition" />
                  <div>
                    <div class="font-bold text-xs text-slate-900 group-hover:text-red-600 transition">Payment Plans & Customer Invoices</div>
                    <div class="text-[10px] text-slate-500">Track client fee installments and payment receipts</div>
                  </div>
                </div>
                <ChevronRightIcon class="w-4 h-4 text-slate-400 group-hover:text-red-600 transition" />
              </Link>

              <Link 
                href="/tenant/settings/navigation" 
                class="p-4 rounded-2xl bg-slate-50 hover:bg-red-50/50 border border-slate-200 hover:border-red-200 transition flex items-center justify-between group cursor-pointer"
              >
                <div class="flex items-center gap-3">
                  <AdjustmentsHorizontalIcon class="w-5 h-5 text-slate-400 group-hover:text-red-600 transition" />
                  <div>
                    <div class="font-bold text-xs text-slate-900 group-hover:text-red-600 transition">Navigation Menu Customizer</div>
                    <div class="text-[10px] text-slate-500">Reorder, enable, or hide sidebar modules</div>
                  </div>
                </div>
                <ChevronRightIcon class="w-4 h-4 text-slate-400 group-hover:text-red-600 transition" />
              </Link>
            </div>
          </div>
        </template>

      </main>

      <!-- ADD LEAD MODAL (Available across all tabs) -->
      <div v-if="isAddLeadModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs font-sans">
        <div class="bg-white border border-slate-200 rounded-3xl p-6 w-full max-w-md shadow-2xl space-y-5 animate-in fade-in zoom-in duration-150">
          <div class="flex items-center justify-between border-b border-slate-200 pb-3">
            <div>
              <h3 class="text-base font-extrabold text-slate-900">Add Deal to Pipeline</h3>
              <p class="text-xs text-slate-500">Create a new customer lead to track conversion.</p>
            </div>
            <button @click="isAddLeadModalOpen = false" class="text-slate-400 hover:text-slate-600 cursor-pointer">
              <XMarkIcon class="w-5 h-5" />
            </button>
          </div>

          <form @submit.prevent="submitLead" class="space-y-3.5">
            <div class="space-y-1">
              <label class="block text-xs font-extrabold uppercase text-slate-500">Contact / Customer Name *</label>
              <input v-model="newLeadForm.customer_name" type="text" required class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2 text-xs text-slate-900 focus:outline-none focus:border-red-500 focus:bg-white" />
            </div>

            <div class="space-y-1">
              <label class="block text-xs font-extrabold uppercase text-slate-500">Company Name *</label>
              <input v-model="newLeadForm.company_name" type="text" required class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2 text-xs text-slate-900 focus:outline-none focus:border-red-500 focus:bg-white" />
            </div>

            <div class="space-y-1">
              <label class="block text-xs font-extrabold uppercase text-slate-500">Email Address *</label>
              <input v-model="newLeadForm.email" type="email" required class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2 text-xs text-slate-900 focus:outline-none focus:border-red-500 focus:bg-white" />
            </div>

            <div class="grid grid-cols-2 gap-3">
              <div class="space-y-1">
                <label class="block text-xs font-extrabold uppercase text-slate-500">Industry</label>
                <input v-model="newLeadForm.industry" type="text" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2 text-xs text-slate-900 focus:outline-none focus:border-red-500 focus:bg-white" />
              </div>

              <div class="space-y-1">
                <label class="block text-xs font-extrabold uppercase text-slate-500">Est. MRR (₹)</label>
                <input v-model="newLeadForm.estimated_mrr" type="number" step="0.01" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2 text-xs text-slate-900 focus:outline-none focus:border-red-500 focus:bg-white" />
              </div>
            </div>

            <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-200">
              <button type="button" @click="isAddLeadModalOpen = false" class="px-4 py-2 bg-slate-100 text-xs font-bold text-slate-700 rounded-xl border border-slate-200 cursor-pointer">Cancel</button>
              <button type="submit" :disabled="newLeadForm.processing" class="px-6 py-2 bg-red-600 hover:bg-red-700 text-white font-extrabold text-xs rounded-xl shadow-xs flex items-center gap-2 cursor-pointer">
                <Loader v-if="newLeadForm.processing" size="sm" color="white" text="Adding..." />
                <span v-else>Add Lead</span>
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- Modals -->
    <GlobalSearchModal :is-open="isSearchOpen" @close="isSearchOpen = false" />
  </div>
</template>

<style scoped>
aside::-webkit-scrollbar {
  width: 4px;
}
aside::-webkit-scrollbar-track {
  background: transparent;
}
aside::-webkit-scrollbar-thumb {
  background: #cbd5e1;
  border-radius: 4px;
}
aside::-webkit-scrollbar-thumb:hover {
  background: #ef4444;
}
</style>
