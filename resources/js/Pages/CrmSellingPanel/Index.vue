<script setup>
import { ref, computed } from 'vue';
import { useForm, Head, Link, router } from '@inertiajs/vue3';
import Navbar from '@/Components/Navbar.vue';
import GlobalSearchModal from '@/Components/GlobalSearchModal.vue';
import Loader from '@/Components/Loader.vue';
import { 
  SparklesIcon, 
  BuildingOfficeIcon, 
  UserGroupIcon, 
  ArrowRightIcon,
  CheckIcon,
  XMarkIcon,
  PlusIcon,
  RocketLaunchIcon,
  ArrowPathIcon,
  ChartBarIcon,
  ShieldCheckIcon,
  CurrencyDollarIcon,
  Cog6ToothIcon,
  ArrowLeftIcon,
  KeyIcon
} from '@heroicons/vue/24/outline';

const goBack = () => {
  if (window.history.length > 1) {
    window.history.back();
  } else {
    router.visit('/admin');
  }
};

const props = defineProps({
  tenants: Array,
  industries: Array,
  plans: Array,
  metrics: Object,
});

const isSearchOpen = ref(false);
const isProvisionModalOpen = ref(false);
const isReassignModalOpen = ref(false);
const selectedTenant = ref(null);
const businessSubTypes = ref([]);

// Form for provisioning brand new CRM instance for a client
const provisionForm = useForm({
  organization_name: '',
  industry_id: null,
  business_type_id: null,
  admin_name: '',
  admin_email: '',
  admin_password: 'password123',
  plan_name: 'Growth Plan',
  employee_range: '6-20',
});

// Form for re-assigning sector to existing CRM instance
const reassignForm = useForm({
  industry_id: null,
});

const onIndustrySelect = (indId) => {
  provisionForm.industry_id = indId;
  provisionForm.business_type_id = null;

  const ind = props.industries.find(i => i.id === indId);
  businessSubTypes.value = ind ? ind.business_types || [] : [];
};

const generatePassword = () => {
  const chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%';
  let pass = '';
  for (let i = 0; i < 10; i++) {
    pass += chars.charAt(Math.floor(Math.random() * chars.length));
  }
  provisionForm.admin_password = pass;
};

const openProvisionModal = () => {
  provisionForm.reset();
  if (props.industries.length > 0) {
    onIndustrySelect(props.industries[0].id);
  }
  isProvisionModalOpen.value = true;
};

const submitProvision = () => {
  provisionForm.post('/crm-selling-panel/provision', {
    onSuccess: () => {
      isProvisionModalOpen.value = false;
      provisionForm.reset();
    },
  });
};

const openReassignModal = (tenant) => {
  selectedTenant.value = tenant;
  reassignForm.industry_id = tenant.industry ? tenant.industry.id : (props.industries[0]?.id || null);
  isReassignModalOpen.value = true;
};

const submitReassign = () => {
  if (!selectedTenant.value) return;
  reassignForm.post(`/crm-selling-panel/tenants/${selectedTenant.value.id}/assign-industry`, {
    onSuccess: () => {
      isReassignModalOpen.value = false;
      selectedTenant.value = null;
    },
  });
};

const launchWorkspace = (tenantId) => {
  router.post(`/crm-selling-panel/tenants/${tenantId}/launch`);
};
</script>

<template>
  <Head title="CRM Selling & Provisioning Control Panel" />

  <div class="min-h-screen bg-slate-100 flex font-sans text-slate-900">
    <!-- Navbar (Fixed Left Vertical Sidebar) -->
    <Navbar @open-search="isSearchOpen = true" />

    <!-- RIGHT MAIN CONTENT AREA -->
    <main class="p-8 space-y-8 flex-1 overflow-y-auto w-full">
        <!-- Title & Hero Header -->
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
                <span>Multi-Sector CRM Selling & Provisioning Panel</span>
              </div>
            </div>
            <h1 class="text-3xl font-black text-slate-900 tracking-tight">CRM Client Control Center</h1>
          <p class="text-slate-500 text-sm font-medium">Provision, configure, and assign sector-specific CRMs to your business clients on demand.</p>
        </div>

        <div class="flex items-center gap-2">
          <button 
            @click="openProvisionModal"
            class="px-5 py-3 bg-red-600 hover:bg-red-700 text-white font-extrabold text-xs rounded-xl shadow-xs shadow-red-600/20 flex items-center gap-2 transition-all cursor-pointer shrink-0"
          >
            <PlusIcon class="w-4 h-4 stroke-[3]" />
            <span>+ Provision New CRM for Client</span>
          </button>
        </div>
      </div>

      <!-- Executive Metric Stats Row -->
      <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4">
        <div class="p-5 rounded-2xl bg-white border border-slate-200 shadow-xs space-y-1 hover:shadow-md transition-all">
          <div class="text-2xl font-black text-slate-900">{{ metrics.total_crms }}</div>
          <div class="text-xs font-extrabold text-slate-500 uppercase tracking-wider">Total Sold CRMs</div>
        </div>

        <div class="p-5 rounded-2xl bg-white border border-slate-200 shadow-xs space-y-1 hover:shadow-md transition-all">
          <div class="text-2xl font-black text-emerald-600">{{ metrics.active_crms }}</div>
          <div class="text-xs font-extrabold text-slate-500 uppercase tracking-wider">Active Instances</div>
        </div>

        <div class="p-5 rounded-2xl bg-white border border-slate-200 shadow-xs space-y-1 hover:shadow-md transition-all">
          <div class="text-2xl font-black text-amber-600">{{ metrics.trial_crms }}</div>
          <div class="text-xs font-extrabold text-slate-500 uppercase tracking-wider">In Trialing / Setup</div>
        </div>

        <div class="p-5 rounded-2xl bg-white border border-slate-200 shadow-xs space-y-1 hover:shadow-md transition-all">
          <div class="text-2xl font-black text-indigo-600">{{ metrics.total_users }}</div>
          <div class="text-xs font-extrabold text-slate-500 uppercase tracking-wider">Total Active Users</div>
        </div>

        <div class="p-5 rounded-2xl bg-white border border-slate-200 shadow-xs space-y-1 hover:shadow-md transition-all">
          <div class="text-2xl font-black text-sky-600">{{ metrics.monthly_revenue }}</div>
          <div class="text-xs font-extrabold text-slate-500 uppercase tracking-wider">Est. Monthly MRR</div>
        </div>

        <div class="p-5 rounded-2xl bg-white border border-slate-200 shadow-xs space-y-1 hover:shadow-md transition-all">
          <div class="text-2xl font-black text-purple-600">🎓 Education</div>
          <div class="text-xs font-extrabold text-slate-500 uppercase tracking-wider">Top Sector Sold</div>
        </div>
      </div>

      <!-- Quick Industry Sector Presets Bar -->
      <div class="bg-white border border-slate-200 rounded-3xl p-5 shadow-xs space-y-3">
        <div class="flex items-center justify-between">
          <h3 class="text-xs font-extrabold uppercase tracking-wider text-slate-500 flex items-center gap-2">
            <span>AVAILABLE SECTOR ENGINES ({{ industries?.length || 0 }})</span>
          </h3>
          <span class="text-xs font-bold text-indigo-600">Instant One-Click Provisioning</span>
        </div>

        <div class="flex items-center gap-2 overflow-x-auto pb-2 scrollbar-none">
          <div 
            v-for="ind in industries" 
            :key="ind.id"
            @click="provisionForm.industry_id = ind.id; openProvisionModal();"
            class="px-3.5 py-2 rounded-xl bg-slate-50 border border-slate-200 hover:border-indigo-400 text-xs font-bold text-slate-700 hover:text-indigo-600 flex items-center gap-2 whitespace-nowrap cursor-pointer transition-all hover:scale-105 shadow-xs"
          >
            <span>{{ ind.icon }}</span>
            <span>{{ ind.name }}</span>
          </div>
        </div>
      </div>

      <!-- Provisioned CRM SaaS Instances Table -->
      <div class="bg-white border border-slate-200 rounded-3xl p-6 shadow-xs space-y-4">
        <div class="flex items-center justify-between">
          <div>
            <h2 class="text-lg font-extrabold text-slate-900">Client CRM Accounts & Workspaces</h2>
            <p class="text-xs text-slate-500 font-medium">Manage client CRM configurations, switch sector engines, and launch workspaces.</p>
          </div>

          <div class="text-xs font-extrabold text-slate-500">
            Showing {{ tenants?.length || 0 }} Client CRM(s)
          </div>
        </div>

        <div v-if="tenants && tenants.length > 0" class="overflow-x-auto rounded-2xl border border-slate-200">
          <table class="w-full text-left text-xs font-medium text-slate-700">
            <thead class="bg-slate-50 text-slate-500 font-extrabold uppercase tracking-wider border-b border-slate-200">
              <tr>
                <th class="px-5 py-3.5">Client Organization</th>
                <th class="px-5 py-3.5">Assigned Industry Sector</th>
                <th class="px-5 py-3.5">Sub-Type</th>
                <th class="px-5 py-3.5">Plan & Revenue</th>
                <th class="px-5 py-3.5">Users</th>
                <th class="px-5 py-3.5">Status</th>
                <th class="px-5 py-3.5">Created Date</th>
                <th class="px-5 py-3.5 text-right">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 bg-white">
              <tr v-for="t in tenants" :key="t.id" class="hover:bg-slate-50/80 transition-colors">
                <!-- Org Name -->
                <td class="px-5 py-4 flex items-center gap-3">
                  <div class="w-9 h-9 rounded-xl bg-red-50 border border-red-200 text-red-600 font-extrabold flex items-center justify-center text-sm shadow-2xs shrink-0">
                    {{ t.industry?.icon || '🏢' }}
                  </div>
                  <div class="truncate">
                    <div class="font-extrabold text-slate-900 text-sm truncate">{{ t.name }}</div>
                    <div class="text-[11px] text-slate-400 font-mono truncate">{{ t.slug }}.jrvcrm.com</div>
                  </div>
                </td>

                <!-- Assigned Industry -->
                <td class="px-5 py-4">
                  <span v-if="t.industry" class="whitespace-nowrap px-3 py-1 rounded-full text-xs font-extrabold bg-red-50 border border-red-200 text-red-700 inline-flex items-center gap-1.5 shadow-2xs">
                    <span>{{ t.industry.icon }}</span>
                    <span>{{ t.industry.name }}</span>
                  </span>
                  <span v-else class="whitespace-nowrap px-3 py-1 rounded-full text-xs font-bold bg-slate-100 text-slate-500 border border-slate-200">
                    Unassigned
                  </span>
                </td>

                <!-- Sub-type -->
                <td class="px-5 py-4 font-semibold text-slate-600 whitespace-nowrap">
                  {{ t.business_type || 'General' }}
                </td>

                <!-- Plan -->
                <td class="px-5 py-4">
                  <span class="whitespace-nowrap inline-flex items-center px-3 py-1 rounded-xl text-xs font-extrabold uppercase bg-emerald-50 border border-emerald-200 text-emerald-700">
                    {{ t.plan || 'Growth Plan' }}
                  </span>
                </td>

                <!-- Users Count -->
                <td class="px-5 py-4 font-extrabold text-slate-900 whitespace-nowrap">
                  {{ t.users_count || 1 }} User Seats
                </td>

                <!-- Status -->
                <td class="px-5 py-4">
                  <span :class="['whitespace-nowrap inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-extrabold uppercase tracking-wider border', t.status === 'active' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-amber-50 text-amber-700 border-amber-200']">
                    {{ t.status }}
                  </span>
                </td>

                <!-- Created Date -->
                <td class="px-5 py-4 text-slate-500 font-medium whitespace-nowrap">
                  {{ t.created_at }}
                </td>

                <!-- Quick Actions -->
                <td class="px-5 py-4 text-right">
                  <div class="flex items-center justify-end gap-2">
                    <button 
                      @click="launchWorkspace(t.id)"
                      title="Launch CRM Workspace"
                      class="whitespace-nowrap px-3.5 py-1.5 bg-red-600 hover:bg-red-700 text-white font-extrabold text-xs rounded-xl shadow-2xs transition-all flex items-center gap-1.5 cursor-pointer"
                    >
                      <RocketLaunchIcon class="w-3.5 h-3.5" />
                      <span>Launch</span>
                    </button>

                    <button 
                      @click="openReassignModal(t)"
                      title="Re-assign Industry Sector Engine"
                      class="whitespace-nowrap px-3.5 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs rounded-xl border border-slate-200 transition-all flex items-center gap-1.5 cursor-pointer"
                    >
                      <ArrowPathIcon class="w-3.5 h-3.5 text-amber-600" />
                      <span>Switch Sector</span>
                    </button>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div v-else class="text-center py-16 space-y-3">
          <div class="w-12 h-12 rounded-2xl bg-slate-100 border border-slate-200 text-slate-400 mx-auto flex items-center justify-center">
            <BuildingOfficeIcon class="w-6 h-6" />
          </div>
          <p class="text-base font-bold text-slate-600">No Client CRMs Provisioned Yet</p>
          <button @click="openProvisionModal" class="px-5 py-2.5 bg-red-600 hover:bg-red-700 text-white font-extrabold text-xs rounded-xl shadow-xs cursor-pointer">
            Provision First Client CRM
          </button>
        </div>
      </div>
    </main>

    <!-- MODAL 1: PROVISION NEW CRM FOR CLIENT -->
    <div v-if="isProvisionModalOpen" class="fixed inset-0 z-50 overflow-y-auto font-sans">
      <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity" @click="isProvisionModalOpen = false"></div>

      <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
        <div class="relative transform overflow-hidden rounded-3xl bg-white text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-2xl border border-slate-200">
          <!-- Header -->
          <div class="bg-red-600 px-6 py-5 flex items-center justify-between text-white">
            <div class="flex items-center gap-3">
              <div class="w-10 h-10 rounded-2xl bg-white/20 backdrop-blur-xs flex items-center justify-center text-white font-bold">
                <RocketLaunchIcon class="w-6 h-6 stroke-[2.5]" />
              </div>
              <div>
                <h3 class="text-lg font-extrabold tracking-tight">Provision Client CRM Instance</h3>
                <p class="text-xs text-white/80 font-medium">Instantly configure modules, pipelines, and fields for client requirement</p>
              </div>
            </div>
            <button @click="isProvisionModalOpen = false" class="rounded-xl p-1.5 text-white/80 hover:bg-white/20 transition-colors">
              <XMarkIcon class="w-5 h-5 stroke-[2.5]" />
            </button>
          </div>

          <!-- Form Body -->
          <form @submit.prevent="submitProvision" class="p-6 space-y-5">
            <!-- Organization Name -->
            <div class="space-y-1">
              <label class="block text-xs font-extrabold uppercase text-slate-500 tracking-wider">Client Organization Name *</label>
              <input 
                v-model="provisionForm.organization_name"
                type="text" 
                required 
                placeholder="e.g. St. Xavier International School or Apex Health Clinic"
                class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-xs text-slate-900 placeholder-slate-400 font-medium focus:outline-none focus:border-red-500 focus:bg-white"
              />
              <p v-if="provisionForm.errors.organization_name" class="text-xs font-bold text-rose-500 mt-1">{{ provisionForm.errors.organization_name }}</p>
            </div>

            <!-- Industry Sector Selection -->
            <div class="space-y-1.5">
              <label class="block text-xs font-extrabold uppercase text-slate-500 tracking-wider">Select Required Industry Sector *</label>
              <div class="grid grid-cols-2 sm:grid-cols-3 gap-2.5 max-h-48 overflow-y-auto p-1.5 border border-slate-200 rounded-2xl bg-slate-50">
                <div 
                  v-for="ind in industries" 
                  :key="ind.id"
                  @click="onIndustrySelect(ind.id)"
                  :class="[
                    'p-2.5 rounded-xl border text-xs font-bold flex items-center gap-2 cursor-pointer transition-all',
                    provisionForm.industry_id === ind.id ? 'border-red-500 bg-red-50 text-red-900 shadow-xs' : 'border-slate-200 bg-white text-slate-600 hover:text-slate-900 hover:bg-slate-100'
                  ]"
                >
                  <span class="text-lg">{{ ind.icon }}</span>
                  <span class="truncate">{{ ind.name }}</span>
                </div>
              </div>
              <p v-if="provisionForm.errors.industry_id" class="text-xs font-bold text-rose-500 mt-1">{{ provisionForm.errors.industry_id }}</p>
            </div>

            <!-- Sub-Type Selection -->
            <div v-if="businessSubTypes && businessSubTypes.length > 0" class="space-y-1">
              <label class="block text-xs font-extrabold uppercase text-slate-500 tracking-wider">Business Sub-Type</label>
              <select 
                v-model="provisionForm.business_type_id"
                class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-xs text-slate-900 font-medium focus:outline-none focus:border-red-500 focus:bg-white"
              >
                <option :value="null">General / Default</option>
                <option v-for="bt in businessSubTypes" :key="bt.id" :value="bt.id">{{ bt.name }}</option>
              </select>
            </div>

            <!-- Subscription Plan & Scale Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div class="space-y-1">
                <label class="block text-xs font-extrabold uppercase text-slate-500 tracking-wider">SaaS Subscription Plan</label>
                <select 
                  v-model="provisionForm.plan_name"
                  class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-xs text-slate-900 font-medium focus:outline-none focus:border-indigo-500 focus:bg-white"
                >
                  <option value="Starter Plan">Starter Plan ($49/mo - 5 Users)</option>
                  <option value="Growth Plan">Growth Plan ($149/mo - 25 Users)</option>
                  <option value="Enterprise Plan">Enterprise Plan ($399/mo - 100 Users)</option>
                </select>
              </div>

              <div class="space-y-1">
                <label class="block text-xs font-extrabold uppercase text-slate-500 tracking-wider">Expected User Capacity</label>
                <select 
                  v-model="provisionForm.employee_range"
                  class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-xs text-slate-900 font-medium focus:outline-none focus:border-indigo-500 focus:bg-white"
                >
                  <option value="1-5">1-5 Employees</option>
                  <option value="6-20">6-20 Employees</option>
                  <option value="21-50">21-50 Employees</option>
                  <option value="51-200">51-200 Employees</option>
                  <option value="200+">200+ Enterprise Employees</option>
                </select>
              </div>
            </div>

            <!-- Admin Credentials Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div class="space-y-1">
                <label class="block text-xs font-extrabold uppercase text-slate-500 tracking-wider">Client Admin Name *</label>
                <input 
                  v-model="provisionForm.admin_name"
                  type="text" 
                  required 
                  placeholder="e.g. Principal Sarah Jenkins"
                  class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-xs text-slate-900 placeholder-slate-400 font-medium focus:outline-none focus:border-indigo-500 focus:bg-white"
                />
                <p v-if="provisionForm.errors.admin_name" class="text-xs font-bold text-rose-500 mt-1">{{ provisionForm.errors.admin_name }}</p>
              </div>

              <div class="space-y-1">
                <label class="block text-xs font-extrabold uppercase text-slate-500 tracking-wider">Client Admin Email *</label>
                <input 
                  v-model="provisionForm.admin_email"
                  type="email" 
                  required 
                  placeholder="admin@stxavierschool.com"
                  class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-xs text-slate-900 placeholder-slate-400 font-medium focus:outline-none focus:border-indigo-500 focus:bg-white"
                />
                <p v-if="provisionForm.errors.admin_email" class="text-xs font-bold text-rose-500 mt-1">{{ provisionForm.errors.admin_email }}</p>
              </div>
            </div>

            <!-- Admin Password -->
            <div class="space-y-1">
              <div class="flex items-center justify-between">
                <label class="block text-xs font-extrabold uppercase text-slate-500 tracking-wider">Initial Admin Password *</label>
                <button type="button" @click="generatePassword" class="text-xs font-bold text-red-600 hover:text-red-700 flex items-center gap-1">
                  <KeyIcon class="w-3.5 h-3.5" />
                  <span>Generate Password</span>
                </button>
              </div>
              <input 
                v-model="provisionForm.admin_password"
                type="text" 
                required 
                class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-xs text-slate-900 font-mono font-bold focus:outline-none focus:border-indigo-500 focus:bg-white"
              />
            </div>

            <!-- Action Buttons -->
            <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-200">
              <button 
                type="button" 
                @click="isProvisionModalOpen = false" 
                class="px-5 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs rounded-xl border border-slate-200"
              >
                Cancel
              </button>
              <button 
                type="submit" 
                :disabled="provisionForm.processing"
                class="px-6 py-2.5 bg-red-600 hover:bg-red-700 text-white font-extrabold text-xs rounded-xl shadow-xs flex items-center gap-2 disabled:opacity-50 cursor-pointer"
              >
                <Loader v-if="provisionForm.processing" size="sm" color="white" text="Provisioning..." />
                <template v-else>
                  <RocketLaunchIcon class="w-4 h-4 stroke-[2.5]" />
                  <span>Provision CRM Engine</span>
                </template>
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- MODAL 2: REASSIGN INDUSTRY SECTOR ENGINE -->
    <div v-if="isReassignModalOpen" class="fixed inset-0 z-50 overflow-y-auto font-sans">
      <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity" @click="isReassignModalOpen = false"></div>

      <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
        <div class="relative transform overflow-hidden rounded-3xl bg-white text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-xl border border-slate-200">
          <div class="bg-white px-6 py-4 flex items-center justify-between text-slate-900 border-b border-slate-200">
            <div class="flex items-center gap-2.5">
              <ArrowPathIcon class="w-5 h-5 text-red-600" />
              <h3 class="text-base font-extrabold text-slate-900">Re-assign CRM Industry Engine</h3>
            </div>
            <button @click="isReassignModalOpen = false" class="text-slate-400 hover:text-slate-600">
              <XMarkIcon class="w-5 h-5" />
            </button>
          </div>

          <form @submit.prevent="submitReassign" class="p-6 space-y-4">
            <p class="text-xs text-slate-600">
              Re-assigning industry sector for <strong class="text-slate-900">{{ selectedTenant?.name }}</strong> will instantly reconfigure their sidebar modules, pipeline stages, and dashboard metrics.
            </p>

            <div class="space-y-1.5">
              <label class="block text-xs font-extrabold uppercase text-slate-500 tracking-wider">Select New Industry Engine</label>
              <div class="grid grid-cols-2 gap-2 max-h-56 overflow-y-auto p-1.5 border border-slate-200 rounded-xl bg-slate-50">
                <div 
                  v-for="ind in industries" 
                  :key="ind.id"
                  @click="reassignForm.industry_id = ind.id"
                  :class="[
                    'p-2.5 rounded-xl border text-xs font-bold flex items-center gap-2 cursor-pointer transition-all',
                    reassignForm.industry_id === ind.id ? 'border-red-500 bg-red-50 text-red-900 shadow-xs' : 'border-slate-200 bg-white text-slate-600 hover:text-slate-900'
                  ]"
                >
                  <span>{{ ind.icon }}</span>
                  <span class="truncate">{{ ind.name }}</span>
                </div>
              </div>
            </div>

            <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-200">
              <button type="button" @click="isReassignModalOpen = false" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-xl border border-slate-200">Cancel</button>
              <button type="submit" :disabled="reassignForm.processing" class="px-6 py-2.5 bg-red-600 hover:bg-red-700 text-white font-extrabold text-xs rounded-xl shadow-xs flex items-center gap-1.5 cursor-pointer">
                <Loader v-if="reassignForm.processing" size="sm" color="white" text="Reconfiguring..." />
                <template v-else>
                  <ArrowPathIcon class="w-4 h-4" />
                  <span>Reconfig CRM Engine</span>
                </template>
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
