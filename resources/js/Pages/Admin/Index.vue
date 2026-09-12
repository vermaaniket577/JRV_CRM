<script setup>
import { ref, computed, onMounted } from 'vue';
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
  CurrencyDollarIcon, 
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
  PhoneIcon,
  EnvelopeIcon,
  ShieldCheckIcon,
  CheckBadgeIcon,
  ArrowLeftIcon,
  EllipsisVerticalIcon,
  ServerStackIcon
} from '@heroicons/vue/24/outline';

const goBack = () => {
  if (window.history.length > 1) {
    window.history.back();
  } else {
    router.visit('/');
  }
};

const props = defineProps({
  stats: Object,
  leads: Array,
  transactions: Array,
  industries: Array,
  plans: Array,
  filters: Object,
});

const isSearchOpen = ref(false);
const isAddLeadModalOpen = ref(false);
const isManagePlansModalOpen = ref(false);
const editingPlan = ref(null);
const selectedStatusTab = ref(props.filters.status || 'all');
const selectedIndustryFilter = ref(props.filters.industry || 'all');
const searchQuery = ref(props.filters.search || '');
const provisioningLeadId = ref(null);

const planForm = useForm({
  name: '',
  price_monthly: 0,
  price_annual: 0,
  storage_limit_gb: 25,
  max_users: 5,
});

const editPlan = (plan) => {
  editingPlan.value = plan;
  planForm.name = plan.name;
  planForm.price_monthly = plan.price_monthly;
  planForm.price_annual = plan.price_annual || Math.round(plan.price_monthly * 0.8);
  planForm.storage_limit_gb = plan.storage_limit_gb;
  planForm.max_users = plan.max_users;
  isManagePlansModalOpen.value = true;
};

const savePlan = () => {
  if (!editingPlan.value) return;
  planForm.put(`/admin/plans/${editingPlan.value.id}`, {
    onSuccess: () => {
      isManagePlansModalOpen.value = false;
      editingPlan.value = null;
    },
  });
};

onMounted(() => {
  const urlParams = new URLSearchParams(window.location.search);
  if (urlParams.get('manage') === 'plans') {
    isManagePlansModalOpen.value = true;
  }
});

const newLeadForm = useForm({
  customer_name: '',
  company_name: '',
  email: '',
  phone: '',
  industry: 'CS (Company Secretary)',
  deal_stage: 'New',
  estimated_mrr: 149,
});

const applyFilters = () => {
  router.get('/admin', {
    status: selectedStatusTab.value,
    industry: selectedIndustryFilter.value,
    search: searchQuery.value,
  }, { preserveState: true, replace: true });
};

const submitNewLead = () => {
  newLeadForm.post('/admin/leads', {
    onSuccess: () => {
      isAddLeadModalOpen.value = false;
      newLeadForm.reset();
    },
  });
};

const submitAndProvisionLead = () => {
  newLeadForm.deal_stage = 'Won';
  newLeadForm.post('/admin/leads', {
    onSuccess: () => {
      isAddLeadModalOpen.value = false;
      newLeadForm.reset();
    },
  });
};

const updateLeadStage = (leadId, newStage) => {
  router.patch(`/admin/leads/${leadId}/status`, {
    deal_stage: newStage,
  }, { preserveScroll: true });
};

const provisionCrmFromLead = (leadId) => {
  provisioningLeadId.value = leadId;
  router.post(`/admin/leads/${leadId}/provision`, {}, {
    onFinish: () => {
      provisioningLeadId.value = null;
    },
  });
};

const getStageBadgeClass = (stage) => {
  switch (stage) {
    case 'Won': return 'bg-emerald-50 text-emerald-700 border-emerald-200';
    case 'Proposal': return 'bg-purple-50 text-purple-700 border-purple-200';
    case 'Qualified': return 'bg-sky-50 text-sky-700 border-sky-200';
    case 'New': return 'bg-amber-50 text-amber-700 border-amber-200';
    case 'Lost': return 'bg-rose-50 text-rose-700 border-rose-200';
    default: return 'bg-slate-50 text-slate-700 border-slate-200';
  }
};
</script>

<template>
  <Head title="Admin Product Sales & Lead Control Center" />

  <div class="min-h-screen bg-slate-100 flex font-sans text-slate-900">
    <!-- Navbar (Fixed Left Vertical Sidebar) -->
    <Navbar @open-search="isSearchOpen = true" />

    <!-- RIGHT MAIN CONTENT WINDOW -->
    <main class="flex-1 p-8 space-y-8 overflow-y-auto max-w-7xl mx-auto w-full">
      <!-- Top Title & Action Header -->
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-slate-200 pb-6">
        <div class="space-y-1">
          <div class="flex items-center gap-2">
            <button 
              @click="goBack"
              class="px-3 py-1 bg-white hover:bg-slate-100 border border-slate-200 text-slate-700 font-extrabold text-xs rounded-xl shadow-2xs flex items-center gap-1.5 cursor-pointer transition-all whitespace-nowrap"
            >
              <ArrowLeftIcon class="w-3.5 h-3.5 text-slate-500" />
              <span>Back</span>
            </button>
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-red-50 border border-red-200 text-xs font-bold text-red-700">
              <SparklesIcon class="w-4 h-4 text-red-600" />
              <span>Admin SaaS Product Sales Engine</span>
            </div>
          </div>
          <h1 class="text-3xl font-black text-slate-900 tracking-tight">Master Admin Product Sales & Lead Control Center</h1>
          <p class="text-slate-500 text-sm font-medium">Under Master Admin Control — Manage incoming client product requests, track subscriptions, and provision CRMs on demand.</p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
          <button 
            @click="isSearchOpen = true"
            class="px-3.5 py-2 bg-slate-900 hover:bg-slate-800 text-white font-extrabold text-xs rounded-xl shadow-2xs flex items-center gap-1.5 transition-all cursor-pointer whitespace-nowrap"
          >
            <MagnifyingGlassIcon class="w-4 h-4 text-red-500 stroke-[2.5]" />
            <span>Search System</span>
          </button>

          <Link 
            href="/crm-selling-panel"
            class="px-3.5 py-2 bg-white border border-slate-200 hover:bg-slate-50 text-slate-700 font-extrabold text-xs rounded-xl shadow-2xs flex items-center gap-1.5 transition-all cursor-pointer whitespace-nowrap"
          >
            <RocketLaunchIcon class="w-4 h-4 text-red-600" />
            <span>Provisioning Panel</span>
          </Link>

          <Link 
            href="/admin/load-balancer"
            class="px-3.5 py-2 bg-purple-50 hover:bg-purple-100 border border-purple-200 text-purple-900 font-extrabold text-xs rounded-xl shadow-2xs flex items-center gap-1.5 transition-all cursor-pointer whitespace-nowrap"
          >
            <ServerStackIcon class="w-4 h-4 text-purple-600" />
            <span>Load Balancer</span>
          </Link>

          <button 
            @click="isManagePlansModalOpen = true"
            class="px-3.5 py-2 bg-amber-50 hover:bg-amber-100 border border-amber-200 text-amber-900 font-extrabold text-xs rounded-xl shadow-2xs flex items-center gap-1.5 transition-all cursor-pointer whitespace-nowrap"
          >
            <CreditCardIcon class="w-4 h-4 text-amber-600" />
            <span>Manage Plans</span>
          </button>

          <button 
            @click="isAddLeadModalOpen = true"
            class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-extrabold text-xs rounded-xl shadow-xs shadow-red-600/20 flex items-center gap-1.5 transition-all cursor-pointer whitespace-nowrap"
          >
            <PlusIcon class="w-4 h-4 stroke-[3]" />
            <span>Log Product Lead</span>
          </button>

          <Link 
            href="/admin/logout"
            method="post"
            as="button"
            class="px-3.5 py-2 bg-red-50 hover:bg-red-100 text-red-700 border border-red-200 font-extrabold text-xs rounded-xl shadow-2xs flex items-center gap-1.5 transition-all cursor-pointer whitespace-nowrap"
          >
            <span>Logout</span>
          </Link>
        </div>
      </div>

      <!-- Executive Product Sales Metrics Row -->
      <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4">
        <!-- Total Leads -->
        <div class="p-4 sm:p-5 rounded-2xl bg-white border border-slate-200 shadow-2xs space-y-1.5 hover:shadow-xs transition-all">
          <div class="flex items-center justify-between">
            <span class="text-[10px] font-black uppercase tracking-wider text-slate-400">Total Leads</span>
            <div class="w-7 h-7 rounded-lg bg-red-50 text-red-600 flex items-center justify-center">
              <UserGroupIcon class="w-4 h-4" />
            </div>
          </div>
          <div class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">{{ stats.total_leads }}</div>
          <div class="text-[11px] font-bold text-slate-500 truncate">Inbound Requests</div>
        </div>

        <!-- Converted Workspaces -->
        <div class="p-4 sm:p-5 rounded-2xl bg-white border border-slate-200 shadow-2xs space-y-1.5 hover:shadow-xs transition-all">
          <div class="flex items-center justify-between">
            <span class="text-[10px] font-black uppercase tracking-wider text-slate-400">Converted</span>
            <div class="w-7 h-7 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center">
              <CheckBadgeIcon class="w-4 h-4" />
            </div>
          </div>
          <div class="text-2xl sm:text-3xl font-black text-emerald-600 tracking-tight">{{ stats.converted_leads }}</div>
          <div class="text-[11px] font-bold text-emerald-700 truncate">Active Workspaces</div>
        </div>

        <!-- Total Pipeline MRR -->
        <div class="p-4 sm:p-5 rounded-2xl bg-white border border-slate-200 shadow-2xs space-y-1.5 hover:shadow-xs transition-all">
          <div class="flex items-center justify-between">
            <span class="text-[10px] font-black uppercase tracking-wider text-slate-400">Pipeline MRR</span>
            <div class="w-7 h-7 rounded-lg bg-purple-50 text-purple-600 flex items-center justify-center">
              <CurrencyDollarIcon class="w-4 h-4" />
            </div>
          </div>
          <div class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight whitespace-nowrap">{{ stats.total_pipeline_mrr }}</div>
          <div class="text-[11px] font-bold text-purple-700 truncate">Est. Monthly Value</div>
        </div>

        <!-- Conversion Rate -->
        <div class="p-4 sm:p-5 rounded-2xl bg-white border border-slate-200 shadow-2xs space-y-1.5 hover:shadow-xs transition-all">
          <div class="flex items-center justify-between">
            <span class="text-[10px] font-black uppercase tracking-wider text-slate-400">Conversion</span>
            <div class="w-7 h-7 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center">
              <ArrowTrendingUpIcon class="w-4 h-4" />
            </div>
          </div>
          <div class="text-2xl sm:text-3xl font-black text-amber-600 tracking-tight">{{ stats.conversion_rate }}</div>
          <div class="text-[11px] font-bold text-amber-700 truncate">Lead-to-Client Rate</div>
        </div>

        <!-- Active CRM Instances -->
        <div class="p-4 sm:p-5 rounded-2xl bg-white border border-slate-200 shadow-2xs space-y-1.5 hover:shadow-xs transition-all">
          <div class="flex items-center justify-between">
            <span class="text-[10px] font-black uppercase tracking-wider text-slate-400">Live CRMs</span>
            <div class="w-7 h-7 rounded-lg bg-sky-50 text-sky-600 flex items-center justify-center">
              <BuildingOfficeIcon class="w-4 h-4" />
            </div>
          </div>
          <div class="text-2xl sm:text-3xl font-black text-sky-600 tracking-tight">{{ stats.total_active_crms }}</div>
          <div class="text-[11px] font-bold text-sky-700 truncate">SaaS Engines</div>
        </div>
      </div>

      <!-- Master Admin Subscription Plans Management Card Section -->
      <div class="bg-white border border-slate-200 rounded-3xl p-6 shadow-2xs space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 pb-4">
          <div class="space-y-0.5">
            <div class="flex items-center gap-2">
              <CreditCardIcon class="w-5 h-5 text-red-600" />
              <h2 class="text-lg font-extrabold text-slate-900">Manage Master Subscription Plans</h2>
            </div>
            <p class="text-xs text-slate-500 font-medium">Modify monthly charges (INR ₹), storage quotas (GB), and user seat limits for all client tenants in real time.</p>
          </div>
          <button 
            @click="isManagePlansModalOpen = true"
            class="px-3.5 py-2 bg-red-50 hover:bg-red-100 text-red-700 border border-red-200 font-extrabold text-xs rounded-xl flex items-center gap-1.5 shadow-2xs cursor-pointer transition-all w-fit"
          >
            <SparklesIcon class="w-4 h-4 text-red-600" />
            <span>Open Full Plan Manager</span>
          </button>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div 
            v-for="p in plans" 
            :key="p.id"
            class="p-5 rounded-2xl bg-white border border-slate-200 flex flex-col justify-between space-y-4 hover:border-red-300 hover:shadow-xs transition-all"
          >
            <div class="space-y-2">
              <div class="flex items-center justify-between">
                <span class="text-sm font-black text-slate-900 tracking-tight">{{ p.name }}</span>
                <span class="px-2.5 py-0.5 bg-emerald-50 text-emerald-700 border border-emerald-200 text-[10px] font-black uppercase rounded-full">
                  Active
                </span>
              </div>

              <div class="text-2xl font-black text-red-600 tracking-tight">
                ₹{{ Number(p.price_monthly).toLocaleString('en-IN') }}<span class="text-xs font-extrabold text-slate-400">/mo</span>
              </div>

              <div class="space-y-1.5 text-xs text-slate-600 font-medium pt-2.5 border-t border-slate-200/70">
                <div class="flex items-center justify-between">
                  <span class="text-slate-400 font-bold">☁️ Storage Quota:</span>
                  <strong class="text-slate-900 font-extrabold">{{ p.storage_limit_gb >= 999 ? 'Unlimited' : p.storage_limit_gb + ' GB' }}</strong>
                </div>
                <div class="flex items-center justify-between">
                  <span class="text-slate-400 font-bold">👥 User Seats Limit:</span>
                  <strong class="text-slate-900 font-extrabold">{{ p.max_users >= 999 ? 'Unlimited Users' : p.max_users + ' Seats' }}</strong>
                </div>
              </div>
            </div>

            <button 
              @click="editPlan(p)"
              class="w-full py-2 bg-red-600 hover:bg-red-700 text-white font-extrabold text-xs rounded-xl transition-all flex items-center justify-center gap-1.5 cursor-pointer shadow-xs shadow-red-600/20"
            >
              <span>Edit Plan Price & Quota</span>
            </button>
          </div>
        </div>
      </div>

      <!-- Filter & Search Toolbar -->
      <div class="bg-white border border-slate-200 rounded-3xl p-5 shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-4">
        <!-- Status Tabs -->
        <div class="flex items-center gap-1.5 overflow-x-auto no-scrollbar py-1">
          <button 
            v-for="st in ['all', 'New', 'Qualified', 'Proposal', 'Won', 'Lost']"
            :key="st"
            @click="selectedStatusTab = st; applyFilters()"
            :class="[
              'px-3.5 py-2 rounded-xl text-xs font-extrabold transition-all shrink-0 cursor-pointer',
              selectedStatusTab === st 
                ? 'bg-red-600 text-white shadow-xs' 
                : 'bg-slate-100 text-slate-600 hover:bg-slate-200'
            ]"
          >
            {{ st === 'all' ? 'All Product Leads' : st }}
          </button>
        </div>

        <!-- Search & Industry Dropdown -->
        <div class="flex items-center gap-3">
          <div class="relative">
            <MagnifyingGlassIcon class="w-4 h-4 text-slate-400 absolute left-3 top-3" />
            <input 
              v-model="searchQuery"
              @keyup.enter="applyFilters"
              type="text"
              placeholder="Search product leads..."
              class="bg-slate-50 border border-slate-200 rounded-xl pl-9 pr-4 py-2 text-xs font-medium text-slate-900 focus:outline-none focus:border-red-500 w-48 sm:w-64"
            />
          </div>

          <select 
            v-model="selectedIndustryFilter"
            @change="applyFilters"
            class="bg-slate-50 border border-slate-200 rounded-xl px-3 py-2 text-xs font-bold text-slate-700 focus:outline-none focus:border-red-500"
          >
            <option value="all">All Sectors</option>
            <option value="CS">CS (Company Secretary)</option>
            <option value="Lawyer">Lawyer / Advocate Firm</option>
            <option value="Education">Education & Training</option>
            <option value="Real Estate">Real Estate</option>
            <option value="Healthcare">Healthcare</option>
            <option value="Financial">Banking & Financial</option>
          </select>
        </div>
      </div>

      <!-- MAIN PRODUCT LEADS TABLE -->
      <div class="bg-white border border-slate-200 rounded-3xl shadow-xs overflow-hidden">
        <div class="p-6 border-b border-slate-200 flex items-center justify-between">
          <div class="space-y-0.5">
            <h2 class="text-lg font-extrabold text-slate-900">Incoming Product Leads & Client Requests</h2>
            <p class="text-xs text-slate-500 font-medium">Track product inquiries from selling channels and provision client CRMs with one click.</p>
          </div>
          <span class="text-xs font-extrabold text-red-600 bg-red-50 px-3 py-1 rounded-full border border-red-100">
            {{ leads.length }} Leads Listed
          </span>
        </div>

        <div class="overflow-x-auto">
          <table class="w-full text-left border-collapse">
            <thead>
              <tr class="border-b border-slate-200 bg-slate-50 text-[11px] font-black uppercase text-slate-400 tracking-wider">
                <th class="py-4 px-6">Client / Firm Name</th>
                <th class="py-4 px-6">Product / Sector Requested</th>
                <th class="py-4 px-6">Contact Info</th>
                <th class="py-4 px-6">Est. MRR Value</th>
                <th class="py-4 px-6">Lead Status Stage</th>
                <th class="py-4 px-6 text-right">Instant Action</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-xs">
              <tr v-for="lead in leads" :key="lead.id" class="hover:bg-slate-50/80 transition-colors">
                <!-- Client & Firm -->
                <td class="py-4 px-6 font-bold text-slate-900">
                  <div class="font-extrabold text-sm text-slate-900">{{ lead.customer_name }}</div>
                  <div class="text-slate-500 text-[11px] font-medium">{{ lead.company_name }}</div>
                </td>

                <!-- Industry Sector -->
                <td class="py-4 px-6">
                  <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl bg-slate-100 border border-slate-200 font-bold text-slate-800">
                    <span>{{ lead.industry }}</span>
                  </span>
                </td>

                <!-- Contact -->
                <td class="py-4 px-6 space-y-0.5">
                  <div class="flex items-center gap-1 text-slate-700 font-medium">
                    <EnvelopeIcon class="w-3.5 h-3.5 text-slate-400" />
                    <span>{{ lead.email }}</span>
                  </div>
                  <div v-if="lead.phone" class="flex items-center gap-1 text-slate-500 text-[11px]">
                    <PhoneIcon class="w-3.5 h-3.5 text-slate-400" />
                    <span>{{ lead.phone }}</span>
                  </div>
                </td>

                <!-- Est MRR -->
                <td class="py-4 px-6 font-black text-slate-900">
                  ₹{{ parseFloat(lead.estimated_mrr || 0).toLocaleString('en-IN') }} / mo
                </td>

                <!-- Lead Stage Status Dropdown -->
                <td class="py-4 px-6">
                  <select 
                    :value="lead.deal_stage"
                    @change="updateLeadStage(lead.id, $event.target.value)"
                    :class="[
                      'px-3 py-1.5 rounded-xl border font-bold text-xs cursor-pointer focus:outline-none transition-all',
                      getStageBadgeClass(lead.deal_stage)
                    ]"
                  >
                    <option value="New">New Lead</option>
                    <option value="Qualified">Qualified</option>
                    <option value="Proposal">Proposal Sent</option>
                    <option value="Won">Won (Converted)</option>
                    <option value="Lost">Lost</option>
                  </select>
                </td>

                <!-- Provision Action Button -->
                <td class="py-4 px-6 text-right">
                  <button 
                    v-if="lead.deal_stage !== 'Won'"
                    @click="provisionCrmFromLead(lead.id)"
                    :disabled="provisioningLeadId === lead.id"
                    class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-extrabold text-xs rounded-xl shadow-xs inline-flex items-center gap-1.5 transition-all cursor-pointer disabled:opacity-50"
                  >
                    <Loader v-if="provisioningLeadId === lead.id" size="sm" color="white" text="Provisioning..." />
                    <template v-else>
                      <RocketLaunchIcon class="w-4 h-4 stroke-[2.5]" />
                      <span>⚡ Provision CRM</span>
                    </template>
                  </button>

                  <span v-else class="inline-flex items-center gap-1 text-xs font-extrabold text-emerald-600 bg-emerald-50 px-3 py-1 rounded-xl border border-emerald-200">
                    <CheckCircleIcon class="w-4 h-4" />
                    <span>CRM Active</span>
                  </span>
                </td>
              </tr>

              <tr v-if="leads.length === 0">
                <td colspan="6" class="text-center py-12 text-slate-500 font-medium">
                  No product sales leads found matching your search filters.
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </main>

    <!-- MODAL: LOG NEW PRODUCT SALES LEAD -->
    <div v-if="isAddLeadModalOpen" class="fixed inset-0 z-50 overflow-y-auto font-sans">
      <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity" @click="isAddLeadModalOpen = false"></div>

      <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
        <div class="relative transform overflow-hidden rounded-3xl bg-white text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-lg border border-slate-200">
          <div class="bg-red-600 px-6 py-5 flex items-center justify-between text-white">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 rounded-2xl bg-white/20 backdrop-blur-xs flex items-center justify-center text-white font-bold">
                <UserGroupIcon class="w-6 h-6 stroke-[2.5]" />
              </div>
              <div>
                <h3 class="text-lg font-extrabold tracking-tight">Log Incoming Product Lead</h3>
                <p class="text-xs text-white/80 font-medium">Add direct lead inquiry from product selling channels</p>
              </div>
            </div>
            <button @click="isAddLeadModalOpen = false" class="rounded-xl p-1.5 text-white/80 hover:bg-white/20 transition-colors">
              <XMarkIcon class="w-5 h-5 stroke-[2.5]" />
            </button>
          </div>

          <form @submit.prevent="submitNewLead" class="p-6 space-y-4">
            <div class="grid grid-cols-2 gap-4">
              <div class="space-y-1">
                <label class="block text-xs font-extrabold uppercase text-slate-500 tracking-wider">Client Name *</label>
                <input 
                  v-model="newLeadForm.customer_name" 
                  type="text" 
                  required 
                  placeholder="e.g. Rajesh Sharma"
                  class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-xs font-medium text-slate-900 focus:outline-none focus:border-red-500"
                />
              </div>

              <div class="space-y-1">
                <label class="block text-xs font-extrabold uppercase text-slate-500 tracking-wider">Firm / Company *</label>
                <input 
                  v-model="newLeadForm.company_name" 
                  type="text" 
                  required 
                  placeholder="e.g. Apex CS Firm"
                  class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-xs font-medium text-slate-900 focus:outline-none focus:border-red-500"
                />
              </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
              <div class="space-y-1">
                <label class="block text-xs font-extrabold uppercase text-slate-500 tracking-wider">Email Address *</label>
                <input 
                  v-model="newLeadForm.email" 
                  type="email" 
                  required 
                  placeholder="client@firm.com"
                  class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-xs font-medium text-slate-900 focus:outline-none focus:border-red-500"
                />
              </div>

              <div class="space-y-1">
                <label class="block text-xs font-extrabold uppercase text-slate-500 tracking-wider">Phone / WhatsApp</label>
                <input 
                  v-model="newLeadForm.phone" 
                  type="text" 
                  placeholder="+91 98765 43210"
                  class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-xs font-medium text-slate-900 focus:outline-none focus:border-red-500"
                />
              </div>
            </div>

            <div class="space-y-1">
              <label class="block text-xs font-extrabold uppercase text-slate-500 tracking-wider">Requested Product Sector *</label>
              <select 
                v-model="newLeadForm.industry"
                class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-xs font-bold text-slate-900 focus:outline-none focus:border-red-500"
              >
                <option value="CS (Company Secretary)">CS (Company Secretary)</option>
                <option value="Lawyer / Advocate Firm">Lawyer / Advocate Firm</option>
                <option value="Education & Training">Education & Training</option>
                <option value="Healthcare">Healthcare</option>
                <option value="Real Estate">Real Estate</option>
                <option value="Banking & Financial Services">Banking & Financial Services</option>
                <option value="IT & Software">IT & Software</option>
              </select>
            </div>

            <div class="grid grid-cols-2 gap-4">
              <div class="space-y-1">
                <label class="block text-xs font-extrabold uppercase text-slate-500 tracking-wider">Initial Deal Stage</label>
                <select 
                  v-model="newLeadForm.deal_stage"
                  class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-xs font-bold text-slate-900 focus:outline-none focus:border-red-500"
                >
                  <option value="New">New Lead</option>
                  <option value="Qualified">Qualified</option>
                  <option value="Proposal">Proposal Sent</option>
                  <option value="Won">Won (Convert)</option>
                </select>
              </div>

              <div class="space-y-1">
                <label class="block text-xs font-extrabold uppercase text-slate-500 tracking-wider">Est. Monthly Value ($)</label>
                <input 
                  v-model="newLeadForm.estimated_mrr" 
                  type="number" 
                  required 
                  class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-xs font-bold text-slate-900 focus:outline-none focus:border-red-500"
                />
              </div>
            </div>

            <div class="flex flex-wrap items-center justify-between gap-3 pt-4 border-t border-slate-200">
              <button type="button" @click="isAddLeadModalOpen = false" class="px-5 py-2.5 bg-slate-100 text-slate-700 font-bold text-xs rounded-xl border border-slate-200 cursor-pointer">Cancel</button>
              <div class="flex items-center gap-2">
                <button type="submit" :disabled="newLeadForm.processing" class="px-5 py-2.5 bg-slate-800 hover:bg-slate-900 text-white font-extrabold text-xs rounded-xl shadow-xs flex items-center gap-2 cursor-pointer">
                  <Loader v-if="newLeadForm.processing" size="sm" color="white" text="Saving..." />
                  <span v-else>Log Lead Inquiry</span>
                </button>
                <button 
                  type="button" 
                  @click="submitAndProvisionLead"
                  :disabled="newLeadForm.processing" 
                  class="px-6 py-2.5 bg-gradient-to-r from-red-600 to-red-700 hover:from-red-500 hover:to-red-600 text-white font-extrabold text-xs rounded-xl shadow-md shadow-red-600/20 flex items-center gap-2 cursor-pointer"
                >
                  <RocketLaunchIcon class="w-4 h-4 text-white" />
                  <span>⚡ Assign & Provision CRM Now</span>
                </button>
              </div>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- Manage Subscription Plans Modal -->
    <div v-if="isManagePlansModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-3 bg-slate-900/60 backdrop-blur-xs">
      <div class="bg-white rounded-2xl max-w-2xl w-full border border-slate-200 shadow-2xl overflow-hidden space-y-0 my-4">
        <!-- Header Banner -->
        <div class="bg-gradient-to-r from-red-600 via-red-700 to-slate-900 p-3.5 sm:p-4 text-white relative">
          <button 
            @click="isManagePlansModalOpen = false" 
            class="absolute top-3 right-3 text-white/80 hover:text-white p-1 rounded-full hover:bg-white/10 transition-colors cursor-pointer"
          >
            <XMarkIcon class="w-4 h-4" />
          </button>

          <div class="flex items-center gap-1.5 px-2 py-0.5 rounded-full bg-white/20 text-white text-[9px] font-extrabold w-fit mb-1 backdrop-blur-xs">
            <CreditCardIcon class="w-3.5 h-3.5 text-amber-300" />
            <span>Master Admin Control</span>
          </div>

          <h3 class="text-base sm:text-lg font-black tracking-tight text-white">
            Manage Subscription Tiers & Pricing
          </h3>
          <p class="text-[11px] text-red-100 mt-0.5 max-w-lg font-medium leading-normal">
            Modify monthly charges (INR ₹), storage quotas (GB), and user seat limits for all client tenants in real time.
          </p>
        </div>

        <div class="p-3.5 sm:p-4 space-y-4 max-h-[70vh] overflow-y-auto">
          <!-- Live Subscription Plans Cards -->
          <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <div 
              v-for="p in plans" 
              :key="p.id" 
              :class="[
                'p-3 rounded-xl border transition-all flex flex-col justify-between space-y-3',
                editingPlan?.id === p.id 
                  ? 'bg-red-50/50 border-red-500 ring-2 ring-red-500/20 shadow-xs' 
                  : 'bg-slate-50/80 border-slate-200 hover:border-slate-300 shadow-2xs'
              ]"
            >
              <div class="space-y-1.5">
                <div class="flex items-center justify-between">
                  <span class="text-[11px] font-black text-slate-900 uppercase tracking-wider">{{ p.name }}</span>
                  <span class="px-2 py-0.2 bg-emerald-50 text-emerald-700 border border-emerald-200 text-[8px] font-black uppercase rounded-full">
                    Active
                  </span>
                </div>

                <div class="text-xl font-black text-red-600 tracking-tight">
                  ₹{{ Number(p.price_monthly).toLocaleString('en-IN') }}
                  <span class="text-[10px] font-extrabold text-slate-500">/mo</span>
                </div>
                <div class="text-[10px] font-bold text-slate-500">
                  Annual: <strong class="text-slate-900">₹{{ Number(p.price_annual || Math.round(p.price_monthly * 0.8)).toLocaleString('en-IN') }}/mo</strong>
                </div>

                <div class="space-y-1 text-[10px] text-slate-600 font-medium pt-1.5 border-t border-slate-200">
                  <div class="flex items-center justify-between">
                    <span class="text-slate-400 font-bold">Storage:</span>
                    <strong class="text-slate-900 font-extrabold">{{ p.storage_limit_gb >= 999 ? 'Unlimited' : p.storage_limit_gb + ' GB' }}</strong>
                  </div>
                  <div class="flex items-center justify-between">
                    <span class="text-slate-400 font-bold">Seats:</span>
                    <strong class="text-slate-900 font-extrabold">{{ p.max_users >= 999 ? 'Unlimited' : p.max_users + ' Seats' }}</strong>
                  </div>
                </div>
              </div>

              <button 
                @click="editPlan(p)"
                :class="[
                  'w-full py-1.5 rounded-lg text-[10px] font-black transition-all cursor-pointer flex items-center justify-center gap-1 shadow-2xs',
                  editingPlan?.id === p.id 
                    ? 'bg-red-600 text-white shadow-2xs' 
                    : 'bg-white hover:bg-slate-100 border border-slate-200 text-slate-900'
                ]"
              >
                <span>Edit Plan</span>
              </button>
            </div>
          </div>

          <!-- Edit Selected Plan Form -->
          <div v-if="editingPlan" class="p-3.5 rounded-xl bg-red-50/70 border border-red-200 space-y-3 shadow-2xs">
            <div class="flex items-center justify-between border-b border-red-200/80 pb-2">
              <h4 class="text-[11px] font-black uppercase text-red-700 tracking-wider flex items-center gap-1">
                <SparklesIcon class="w-3.5 h-3.5 text-red-600" />
                <span>Editing {{ editingPlan.name }}</span>
              </h4>
              <button @click="editingPlan = null" class="text-[10px] font-bold text-slate-500 hover:text-slate-900 cursor-pointer">
                Cancel
              </button>
            </div>

            <form @submit.prevent="savePlan" class="space-y-3">
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div class="space-y-0.5">
                  <label class="block text-[10px] font-extrabold text-slate-700">Plan Display Name</label>
                  <input v-model="planForm.name" type="text" required class="w-full bg-white border border-slate-300 rounded-lg px-2.5 py-1.5 text-xs font-bold text-slate-900 focus:outline-none focus:border-red-500 shadow-2xs" />
                </div>
                <div class="space-y-0.5">
                  <label class="block text-[10px] font-extrabold text-slate-700">Monthly Rate (₹/mo)</label>
                  <input v-model="planForm.price_monthly" type="number" required min="0" class="w-full bg-white border border-slate-300 rounded-lg px-2.5 py-1.5 text-xs font-bold text-slate-900 focus:outline-none focus:border-red-500 shadow-2xs" />
                </div>
                <div class="space-y-0.5">
                  <label class="block text-[10px] font-extrabold text-slate-700">Annual Rate (₹/mo)</label>
                  <input v-model="planForm.price_annual" type="number" required min="0" class="w-full bg-white border border-slate-300 rounded-lg px-2.5 py-1.5 text-xs font-bold text-slate-900 focus:outline-none focus:border-red-500 shadow-2xs" />
                </div>
                <div class="space-y-0.5">
                  <label class="block text-[10px] font-extrabold text-slate-700">Storage Quota (GB)</label>
                  <input v-model="planForm.storage_limit_gb" type="number" required min="1" class="w-full bg-white border border-slate-300 rounded-lg px-2.5 py-1.5 text-xs font-bold text-slate-900 focus:outline-none focus:border-red-500 shadow-2xs" />
                </div>
                <div class="space-y-0.5 sm:col-span-2">
                  <label class="block text-[10px] font-extrabold text-slate-700">User Seats Limit</label>
                  <input v-model="planForm.max_users" type="number" required min="1" class="w-full bg-white border border-slate-300 rounded-lg px-2.5 py-1.5 text-xs font-bold text-slate-900 focus:outline-none focus:border-red-500 shadow-2xs" />
                </div>
              </div>

              <div class="flex items-center justify-end gap-2 pt-1">
                <button type="button" @click="editingPlan = null" class="px-3 py-1.5 bg-white hover:bg-slate-100 border border-slate-200 text-slate-700 font-bold text-[11px] rounded-lg cursor-pointer">
                  Cancel
                </button>
                <button type="submit" :disabled="planForm.processing" class="px-4 py-1.5 bg-red-600 hover:bg-red-700 text-white font-extrabold text-xs rounded-lg shadow-xs shadow-red-600/20 flex items-center gap-1 cursor-pointer">
                  <Loader v-if="planForm.processing" size="sm" color="white" text="Saving..." />
                  <span v-else>Save Changes</span>
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>

    <!-- Modals -->
    <GlobalSearchModal :is-open="isSearchOpen" @close="isSearchOpen = false" />
  </div>
</template>

<style scoped>
.no-scrollbar::-webkit-scrollbar {
  display: none;
}
</style>
