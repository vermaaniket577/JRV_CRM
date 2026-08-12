<script setup>
import { ref, computed } from 'vue';
import { useForm, Head, Link, router } from '@inertiajs/vue3';
import Navbar from '@/Components/Navbar.vue';
import LeftSidebar from '@/Components/LeftSidebar.vue';
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
  RocketLaunchIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
  stats: Object,
  transactions: Array,
  leads: Array,
  plans: Array,
  filters: Object,
});

const isSubSidebarCollapsed = ref(false);
const activeTab = ref(props.filters.tab || 'dashboard');
const selectedPlanFilter = ref(props.filters.plan || 'all');
const selectedStatusFilter = ref(props.filters.status || 'all');
const searchQuery = ref(props.filters.search || '');
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
  return props.leads.filter(l => l.deal_stage === stage);
};
</script>

<template>
  <Head title="CRM Sales & Subscription Control Panel" />

  <div class="min-h-screen bg-slate-100 flex font-sans text-slate-900">
    <!-- Navbar (Fixed Left Vertical Sidebar) -->
    <Navbar @open-search="isSearchOpen = true" />

    <!-- RIGHT MAIN CONTENT AREA -->
    <div class="flex-1 flex min-w-0">
      <!-- SIDEBAR NAVIGATION (Collapsible Sub-Sidebar with Open/Close Button) -->
      <aside 
        :class="[
          'bg-white border-r border-slate-200 hover:border-red-500 p-4 space-y-6 shrink-0 shadow-xs transition-all duration-100 ease-out relative group/subsidebar font-sans',
          isSubSidebarCollapsed ? 'w-20 hover:w-64' : 'w-64'
        ]"
      >
        <!-- Open / Close Toggle Button -->
        <button 
          @click="isSubSidebarCollapsed = !isSubSidebarCollapsed"
          class="absolute -right-3 top-7 w-6 h-6 rounded-full bg-white border border-slate-200 hover:border-red-500 shadow-md flex items-center justify-center text-slate-500 hover:text-red-600 transition-all z-50 cursor-pointer"
          :title="isSubSidebarCollapsed ? 'Expand Sales Panel' : 'Reduce Sales Panel Width'"
        >
          <ChevronLeftIcon v-if="!isSubSidebarCollapsed" class="w-3.5 h-3.5 stroke-[2.5]" />
          <ChevronRightIcon v-else class="w-3.5 h-3.5 stroke-[2.5]" />
        </button>

        <div class="space-y-1 pb-3 border-b border-slate-100">
          <div 
            class="text-[10px] font-black text-red-600 uppercase tracking-widest px-1 transition-opacity duration-75"
            :class="[isSubSidebarCollapsed ? 'opacity-0 group-hover/subsidebar:opacity-100 hidden group-hover/subsidebar:block' : 'opacity-100']"
          >
            CRM Sales Control
          </div>
          <h2 
            class="text-base font-extrabold text-slate-900 px-1 tracking-tight truncate transition-opacity duration-75"
            :class="[isSubSidebarCollapsed ? 'opacity-0 group-hover/subsidebar:opacity-100 hidden group-hover/subsidebar:block' : 'opacity-100']"
          >
            Sales Control Panel
          </h2>
        </div>

        <nav class="space-y-1.5">
          <button
            v-for="item in navItems"
            :key="item.key"
            @click="activeTab = item.key; applyFilters()"
            :title="item.label"
            :class="[
              'w-full flex items-center gap-3 px-3.5 py-2.5 rounded-xl font-bold text-xs transition-all cursor-pointer text-left',
              activeTab === item.key 
                ? 'bg-red-50 text-red-600 border border-red-200 shadow-2xs font-extrabold' 
                : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'
            ]"
          >
            <component :is="item.icon" :class="['w-4 h-4 shrink-0', activeTab === item.key ? 'text-red-600' : 'text-slate-400']" />
            <span 
              class="truncate transition-opacity duration-75"
              :class="[isSubSidebarCollapsed ? 'opacity-0 group-hover/subsidebar:opacity-100 hidden group-hover/subsidebar:inline' : 'opacity-100']"
            >
              {{ item.label }}
            </span>
          </button>
        </nav>

        <!-- Sidebar Promo Card -->
        <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200/80 space-y-2">
          <div class="flex items-center gap-2 text-slate-900 font-bold text-xs">
            <SparklesIcon class="w-4 h-4 text-red-600 shrink-0" />
            <span 
              class="truncate transition-opacity duration-75"
              :class="[isSubSidebarCollapsed ? 'opacity-0 group-hover/subsidebar:opacity-100 hidden group-hover/subsidebar:inline' : 'opacity-100']"
            >
              SaaS Engine Active
            </span>
          </div>
          <p 
            class="text-[11px] text-slate-500 font-medium leading-normal transition-opacity duration-75"
            :class="[isSubSidebarCollapsed ? 'opacity-0 group-hover/subsidebar:opacity-100 hidden group-hover/subsidebar:block' : 'opacity-100']"
          >
            Multi-Industry provisioner ready to launch client workspaces.
          </p>
          <Link href="/crm-selling-panel" class="w-full py-2 bg-slate-900 hover:bg-slate-800 text-white text-[11px] font-extrabold rounded-xl flex items-center justify-center gap-1.5 transition-all shadow-xs">
            <RocketLaunchIcon class="w-3.5 h-3.5 text-red-500" />
            <span 
              class="truncate transition-opacity duration-75"
              :class="[isSubSidebarCollapsed ? 'opacity-0 group-hover/subsidebar:opacity-100 hidden group-hover/subsidebar:inline' : 'opacity-100']"
            >
              Launch Instance
            </span>
          </Link>
        </div>
      </aside>

      <!-- MAIN CONTENT WINDOW -->
      <main class="flex-1 p-8 space-y-8 overflow-y-auto">
        <!-- TOP STATS CARDS (Compact & Sleek Layout) -->
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
                <span>+14.2% from last month</span>
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
                <span>+12% MRR growth rate</span>
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
                <span>+2.1% conversion boost</span>
              </div>
            </div>
          </div>
        </div>

        <!-- VISUAL DEALS PIPELINE SECTION (Clean Light Theme) -->
        <div class="space-y-4">
          <div class="flex items-center justify-between">
            <div>
              <h2 class="text-xl font-extrabold text-slate-900">Visual Deals Pipeline</h2>
              <p class="text-xs text-slate-500 font-medium">Track and move lead opportunities across deal stages.</p>
            </div>

            <button 
              @click="isAddLeadModalOpen = true"
              class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-extrabold text-xs rounded-xl shadow-xs flex items-center gap-1.5 transition-all cursor-pointer"
            >
              <PlusIcon class="w-4 h-4 stroke-[3]" />
              <span>Add Deal Lead</span>
            </button>
          </div>

          <!-- 4 Stage Columns -->
          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div 
              v-for="stage in pipelineStages" 
              :key="stage"
              class="p-4 rounded-3xl bg-white border border-slate-200 shadow-xs space-y-3"
            >
              <div class="flex items-center justify-between pb-2 border-b border-slate-200">
                <span class="text-xs font-extrabold uppercase tracking-wider text-slate-700">{{ stage }}</span>
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
                  </div>
                </div>

                <div v-if="getStageLeads(stage).length === 0" class="py-8 text-center text-xs text-slate-400 font-medium">
                  No deals in {{ stage }}
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- RECENT CRM SALES DATA TABLE SECTION (Light Theme) -->
        <div class="relative bg-white border border-slate-200 rounded-3xl p-6 shadow-xs space-y-5">
          <Loader v-if="isPageLoading" overlay text="Refreshing sales data..." />
          <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
              <h2 class="text-xl font-extrabold text-slate-900">Recent CRM Sales & Subscriptions</h2>
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
            <!-- Subscription Tier Filter Buttons -->
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

            <!-- Payment Status Filter Buttons -->
            <div class="flex items-center gap-1.5 bg-slate-100 p-1 rounded-2xl border border-slate-200">
              <span class="text-[10px] font-extrabold text-slate-500 uppercase tracking-wider px-2">Status:</span>
              <button
                v-for="st in ['all', 'Paid', 'Pending', 'Refunded']"
                :key="st"
                @click="setStatusFilter(st)"
                :class="[
                  'px-3 py-1.5 rounded-xl text-xs font-bold transition-all cursor-pointer capitalize',
                  selectedStatusFilter === st ? 'bg-emerald-600 text-white shadow-xs' : 'text-slate-600 hover:text-slate-900'
                ]"
              >
                {{ st }}
              </button>
            </div>
          </div>

          <!-- MAIN DATA TABLE -->
          <div class="overflow-x-auto rounded-2xl border border-slate-200">
            <table class="w-full text-left text-xs font-medium text-slate-700">
              <thead class="bg-slate-50 text-slate-500 font-extrabold uppercase tracking-wider border-b border-slate-200">
                <tr>
                  <th class="px-3.5 py-3">Customer Name</th>
                  <th class="px-3.5 py-3">CRM Plan</th>
                  <th class="px-3.5 py-3">Amount</th>
                  <th class="px-3.5 py-3">Purchase Date</th>
                  <th class="px-3.5 py-3">Payment Status</th>
                  <th class="px-3.5 py-3 text-right">Actions</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100 bg-white">
                <tr v-for="tx in transactions" :key="tx.id" class="hover:bg-slate-50/80 transition-colors">
                  <td class="px-3.5 py-3">
                    <div class="flex items-center gap-2.5">
                      <div class="w-8 h-8 rounded-xl bg-indigo-50 border border-indigo-200/80 text-indigo-600 font-black flex items-center justify-center text-xs shadow-2xs shrink-0">
                        {{ tx.customer_name ? tx.customer_name.charAt(0).toUpperCase() : 'C' }}
                      </div>
                      <div class="min-w-0">
                        <div class="font-extrabold text-slate-900 text-xs truncate max-w-[160px]">{{ tx.customer_name }}</div>
                        <div class="text-[10px] text-slate-400 font-mono truncate max-w-[160px]">{{ tx.customer_email }}</div>
                      </div>
                    </div>
                  </td>

                  <td class="px-3.5 py-3 whitespace-nowrap">
                    <span :class="[
                      'px-2.5 py-0.5 rounded-full text-[10px] font-extrabold uppercase tracking-wider border whitespace-nowrap inline-flex items-center gap-1',
                      tx.plan_tier === 'Enterprise' ? 'bg-purple-50 text-purple-700 border-purple-200' :
                      tx.plan_tier === 'Pro' || tx.plan_tier === 'Growth' ? 'bg-indigo-50 text-indigo-700 border-indigo-200' : 'bg-slate-100 text-slate-700 border-slate-200'
                    ]">
                      {{ tx.plan_tier }} Plan
                    </span>
                  </td>

                  <td class="px-3.5 py-3 font-black text-slate-900 text-xs whitespace-nowrap">
                    ₹{{ Number(tx.amount || 0).toLocaleString('en-IN') }}
                  </td>

                  <td class="px-3.5 py-3 text-slate-500 font-medium text-[11px] whitespace-nowrap">
                    {{ new Date(tx.purchase_date).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) }}
                  </td>

                  <td class="px-3.5 py-3 whitespace-nowrap">
                    <span :class="[
                      'px-2.5 py-0.5 rounded-full text-[9px] font-extrabold uppercase tracking-wider border whitespace-nowrap inline-flex items-center gap-1',
                      tx.payment_status === 'Paid' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' :
                      tx.payment_status === 'Pending' ? 'bg-amber-50 text-amber-700 border-amber-200' : 'bg-rose-50 text-rose-700 border-rose-200'
                    ]">
                      {{ tx.payment_status }}
                    </span>
                  </td>

                  <td class="px-3.5 py-3 text-right whitespace-nowrap">
                    <div class="flex items-center justify-end gap-1.5">
                      <button class="px-2.5 py-1 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-[10px] rounded-lg transition-all border border-slate-200 cursor-pointer">
                        Invoice
                      </button>
                      <Link href="/crm-selling-panel" class="px-2.5 py-1 bg-indigo-600 hover:bg-indigo-700 text-white font-extrabold text-[10px] rounded-lg transition-all flex items-center gap-1 shadow-2xs cursor-pointer">
                        <RocketLaunchIcon class="w-3 h-3 text-white" />
                        <span>Provision</span>
                      </Link>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>

            <div v-if="transactions.length === 0" class="text-center py-12 text-slate-400 font-bold text-xs">
              No recent CRM sales match your filters.
            </div>
          </div>
        </div>
      </main>
    </div>

    <!-- ADD LEAD MODAL (Light Theme) -->
    <div v-if="isAddLeadModalOpen" class="fixed inset-0 z-50 overflow-y-auto font-sans">
      <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs" @click="isAddLeadModalOpen = false"></div>

      <div class="flex min-h-full items-center justify-center p-4">
        <div class="relative transform overflow-hidden rounded-3xl bg-white text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-md border border-slate-200 p-6 space-y-4">
          <div class="flex items-center justify-between pb-3 border-b border-slate-200">
            <h3 class="text-base font-extrabold text-slate-900">Add Deal Lead to Pipeline</h3>
            <button @click="isAddLeadModalOpen = false" class="text-slate-400 hover:text-slate-600">
              <XMarkIcon class="w-5 h-5" />
            </button>
          </div>

          <form @submit.prevent="submitLead" class="space-y-4">
            <div class="space-y-1">
              <label class="block text-xs font-extrabold uppercase text-slate-500">Customer Name *</label>
              <input v-model="newLeadForm.customer_name" type="text" required class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2 text-xs text-slate-900 focus:outline-none focus:border-indigo-500 focus:bg-white" />
            </div>

            <div class="space-y-1">
              <label class="block text-xs font-extrabold uppercase text-slate-500">Company Name *</label>
              <input v-model="newLeadForm.company_name" type="text" required class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2 text-xs text-slate-900 focus:outline-none focus:border-indigo-500 focus:bg-white" />
            </div>

            <div class="space-y-1">
              <label class="block text-xs font-extrabold uppercase text-slate-500">Email Address *</label>
              <input v-model="newLeadForm.email" type="email" required class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2 text-xs text-slate-900 focus:outline-none focus:border-indigo-500 focus:bg-white" />
            </div>

            <div class="grid grid-cols-2 gap-3">
              <div class="space-y-1">
                <label class="block text-xs font-extrabold uppercase text-slate-500">Industry</label>
                <input v-model="newLeadForm.industry" type="text" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2 text-xs text-slate-900 focus:outline-none focus:border-indigo-500 focus:bg-white" />
              </div>

              <div class="space-y-1">
                <label class="block text-xs font-extrabold uppercase text-slate-500">Est. MRR (₹)</label>
                <input v-model="newLeadForm.estimated_mrr" type="number" step="0.01" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2 text-xs text-slate-900 focus:outline-none focus:border-indigo-500 focus:bg-white" />
              </div>
            </div>

            <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-200">
              <button type="button" @click="isAddLeadModalOpen = false" class="px-4 py-2 bg-slate-100 text-xs font-bold text-slate-700 rounded-xl border border-slate-200">Cancel</button>
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
