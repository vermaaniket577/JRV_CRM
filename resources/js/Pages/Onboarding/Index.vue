<script setup>
import { ref, computed } from 'vue';
import { useForm, Head } from '@inertiajs/vue3';
import { 
  SparklesIcon, 
  ArrowRightIcon, 
  ArrowLeftIcon, 
  CheckIcon,
  UserGroupIcon,
  RocketLaunchIcon,
  MagnifyingGlassIcon,
  CircleStackIcon,
  PlusIcon,
  TrashIcon,
  TableCellsIcon,
  CheckCircleIcon,
  CpuChipIcon,
  LockClosedIcon,
  EyeIcon,
  ShieldCheckIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
  industries: Array,
  defaultColumns: Array,
  tenant: Object,
});

const currentStep = ref(1);
const selectedIndustry = ref(null);
const businessTypes = ref([]);
const loadingTypes = ref(false);
const searchQuery = ref('');

// Database & Column state
const availableColumns = ref(props.defaultColumns || []);
const loadingColumns = ref(false);
const newColumnModalOpen = ref(false);

const newCustomCol = ref({
  label: '',
  type: 'text',
  optionsStr: '',
  is_required: false,
});

const form = useForm({
  industry_id: null,
  business_type_id: null,
  employee_range: '6–20',
  crm_goals: ['Lead Management', 'Sales', 'Automation'],
  selected_columns: [],
});

const filteredIndustries = computed(() => {
  if (!searchQuery.value) return props.industries || [];
  const q = searchQuery.value.toLowerCase().trim();
  return (props.industries || []).filter(ind => 
    ind.name.toLowerCase().includes(q) || 
    (ind.description && ind.description.toLowerCase().includes(q))
  );
});

const generatedDbName = computed(() => {
  const tSlug = props.tenant?.slug || 'crm';
  const tId = props.tenant?.id || '1';
  const indSlug = selectedIndustry.value?.slug || 'general';
  return `crm_tenant_${tId}_${indSlug.replace(/[^a-zA-Z0-9]/g, '')}`;
});

const selectedColumnsCount = computed(() => {
  return availableColumns.value.filter(c => c.is_visible !== false).length;
});

const steps = [
  { number: 1, title: 'CRM Option' },
  { number: 2, title: 'Business Niche' },
  { number: 3, title: 'Database & Columns' },
  { number: 4, title: 'Team Scale' },
  { number: 5, title: 'CRM Objectives' },
];

const teamSizes = [
  { id: '1–5', label: '1–5 Members', desc: 'Solo entrepreneurs & micro teams', icon: '🚀' },
  { id: '6–20', label: '6–20 Members', desc: 'Growing small businesses & agencies', icon: '⚡' },
  { id: '21–50', label: '21–50 Members', desc: 'Mid-sized companies & scaling orgs', icon: '🏢' },
  { id: '51–200', label: '51–200 Members', desc: 'Expanding enterprises & operations', icon: '🌐' },
  { id: '201–500', label: '201–500 Members', desc: 'Multi-branch organizations', icon: '🏛️' },
  { id: '500+', label: '500+ Members', desc: 'Large corporate enterprises', icon: '👑' },
];

const crmGoalsList = [
  { id: 'Lead Management', label: 'Lead Capture & Pipeline', desc: 'Track inquiries & conversion funnel', icon: '🎯' },
  { id: 'Sales', label: 'Sales & Deal Flow', desc: 'Revenue forecasting & won deals', icon: '💰' },
  { id: 'Customer Support', label: 'Customer Helpdesk', desc: 'Ticketing & client satisfaction', icon: '🎧' },
  { id: 'Marketing', label: 'Campaign Automations', desc: 'Targeted email & SMS broadcasts', icon: '📢' },
  { id: 'Communication', label: 'WhatsApp & Email Comms', desc: 'Instant 2-way message sync', icon: '💬' },
  { id: 'Appointment Management', label: 'Smart Scheduling', desc: 'Calendar bookings & reminders', icon: '📅' },
  { id: 'Payments', label: 'Invoicing & Payments', desc: 'Online billing & GST compliance', icon: '💳' },
  { id: 'Reports', label: 'Analytics & Insights', desc: 'Real-time revenue metrics', icon: '📊' },
  { id: 'Team Management', label: 'Employee Tracking', desc: 'Tasks, attendance & targets', icon: '👥' },
  { id: 'Automation', label: 'Custom Auto-Workflows', desc: 'Trigger auto-updates on stage shift', icon: '⚡' },
];

const selectIndustry = async (ind) => {
  selectedIndustry.value = ind;
  form.industry_id = ind.id;
  form.business_type_id = null;
  loadingTypes.value = true;
  loadingColumns.value = true;

  try {
    const [typesRes, colsRes] = await Promise.all([
      fetch(`/onboarding/business-types?industry_id=${ind.id}`),
      fetch(`/onboarding/industry-columns?industry_id=${ind.id}`)
    ]);

    if (typesRes.ok) {
      businessTypes.value = await typesRes.json();
    }
    if (colsRes.ok) {
      availableColumns.value = await colsRes.json();
    }
  } catch (e) {
    businessTypes.value = [];
  } finally {
    loadingTypes.value = false;
    loadingColumns.value = false;
  }

  currentStep.value = 2;
};

const toggleColumnVisibility = (col) => {
  col.is_visible = !col.is_visible;
};

const addCustomColumn = () => {
  if (!newCustomCol.value.label.trim()) return;

  const key = newCustomCol.value.label.toLowerCase().replace(/[^a-z0-9]/g, '_').substring(0, 30);
  const options = newCustomCol.value.optionsStr ? newCustomCol.value.optionsStr.split(',').map(s => s.trim()).filter(Boolean) : null;

  availableColumns.value.push({
    key: `custom_${key}_${Date.now().toString().slice(-4)}`,
    label: newCustomCol.value.label.trim(),
    type: newCustomCol.value.type,
    options: options,
    is_required: newCustomCol.value.is_required,
    is_default: false,
    is_visible: true,
    category: 'custom',
  });

  newCustomCol.value = {
    label: '',
    type: 'text',
    optionsStr: '',
    is_required: false,
  };
  newColumnModalOpen.value = false;
};

const removeCustomColumn = (index) => {
  availableColumns.value.splice(index, 1);
};

const toggleGoal = (goalId) => {
  const index = form.crm_goals.indexOf(goalId);
  if (index === -1) {
    form.crm_goals.push(goalId);
  } else {
    form.crm_goals.splice(index, 1);
  }
};

const completeOnboarding = () => {
  // Sync selected columns into form payload
  form.selected_columns = availableColumns.value.filter(c => c.is_visible !== false);
  form.post('/onboarding/complete');
};
</script>

<template>
  <Head title="Configure Your CRM & Database - JRV SaaS" />

  <div class="min-h-screen bg-slate-50 text-slate-900 font-sans flex flex-col justify-between relative overflow-hidden">
    
    <!-- Ambient Soft Glows -->
    <div class="absolute -top-32 left-1/2 -translate-x-1/2 w-[600px] h-[400px] bg-red-500/5 rounded-full blur-3xl pointer-events-none"></div>
    <div class="absolute bottom-0 right-0 w-[400px] h-[300px] bg-red-500/5 rounded-full blur-3xl pointer-events-none"></div>

    <!-- Header Navigation & Stepper -->
    <header class="max-w-6xl mx-auto w-full px-4 sm:px-6 pt-5 pb-2 z-20">
      <div class="bg-white border border-slate-200 rounded-2xl px-5 py-3.5 shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-4">
        
        <!-- Brand Logo -->
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-xl bg-red-600 flex items-center justify-center text-white font-bold text-lg shadow-sm shadow-red-600/20">
            ⚡
          </div>
          <div>
            <div class="flex items-center gap-2">
              <span class="font-bold text-sm text-slate-900">{{ tenant?.name || 'JRV Multi-Sector CRM' }}</span>
              <span class="px-2 py-0.5 text-xs font-medium bg-red-50 text-red-700 rounded-full border border-red-100">
                Setup
              </span>
            </div>
            <p class="text-xs text-slate-500 font-normal">Database Provisioning & Columns</p>
          </div>
        </div>

        <!-- Step Indicator Pills -->
        <div class="flex items-center gap-1.5 sm:gap-2">
          <div 
            v-for="step in steps" 
            :key="step.number"
            class="flex items-center gap-1.5"
          >
            <div 
              @click="step.number < currentStep ? currentStep = step.number : null"
              :class="[
                'flex items-center gap-2 px-3 py-1.5 rounded-xl text-xs transition-all',
                step.number === currentStep 
                  ? 'bg-red-50 border border-red-200 text-red-700 font-semibold' 
                  : step.number < currentStep 
                    ? 'bg-emerald-50 border border-emerald-200 text-emerald-700 font-medium cursor-pointer hover:bg-emerald-100/60' 
                    : 'bg-slate-100 border border-slate-200 text-slate-400 font-normal'
              ]"
            >
              <div 
                :class="[
                  'w-4 h-4 rounded-full flex items-center justify-center text-[10px] font-bold',
                  step.number === currentStep 
                    ? 'bg-red-600 text-white' 
                    : step.number < currentStep 
                      ? 'bg-emerald-600 text-white' 
                      : 'bg-slate-200 text-slate-500'
                ]"
              >
                <CheckIcon v-if="step.number < currentStep" class="w-3 h-3 stroke-[3]" />
                <span v-else>{{ step.number }}</span>
              </div>
              <span class="hidden md:inline">{{ step.title }}</span>
            </div>
            <span v-if="step.number < 5" class="text-slate-300 hidden lg:inline">›</span>
          </div>
        </div>

      </div>
    </header>

    <!-- Main Step Body -->
    <main class="max-w-6xl mx-auto w-full px-4 sm:px-6 py-6 z-10 flex-1 flex flex-col justify-center">
      
      <!-- STEP 1: Select CRM Industry Sector -->
      <div v-if="currentStep === 1" class="space-y-6 max-w-5xl mx-auto w-full">
        
        <!-- Step 1 Heading -->
        <div class="text-center space-y-2 max-w-2xl mx-auto">
          <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-red-50 border border-red-100 text-xs font-semibold text-red-700">
            <SparklesIcon class="w-4 h-4 text-red-600" />
            <span>Step 1 of 5 • Choose Your CRM Option</span>
          </div>
          <h1 class="text-2xl sm:text-3xl font-bold text-slate-900 tracking-tight">
            Which CRM engine does your business need?
          </h1>
          <p class="text-slate-500 text-sm font-normal">
            Select your CRM type. We'll automatically provision a dedicated MySQL database schema and customizable columns.
          </p>

          <!-- Search Filter Bar -->
          <div class="pt-2 max-w-md mx-auto">
            <div class="relative">
              <MagnifyingGlassIcon class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2" />
              <input 
                v-model="searchQuery" 
                type="text" 
                placeholder="Search Real Estate, Education, Healthcare, Finance..." 
                class="w-full bg-white border border-slate-300 focus:border-red-500 focus:ring-2 focus:ring-red-500/20 rounded-xl pl-10 pr-24 py-2.5 text-sm font-normal text-slate-900 placeholder:text-slate-400 transition shadow-2xs outline-none"
              />
              <span class="absolute right-3 top-1/2 -translate-y-1/2 px-2 py-0.5 bg-slate-100 text-xs font-medium text-slate-500 rounded-md">
                {{ filteredIndustries.length }} options
              </span>
            </div>
          </div>
        </div>

        <!-- Industry Cards Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 max-h-[480px] overflow-y-auto pr-2 custom-scrollbar">
          <div
            v-for="ind in filteredIndustries"
            :key="ind.id"
            @click="selectIndustry(ind)"
            :class="[
              'p-5 rounded-2xl border transition-all duration-200 group flex flex-col justify-between min-h-[155px] cursor-pointer bg-white shadow-2xs hover:shadow-md hover:-translate-y-0.5',
              form.industry_id === ind.id 
                ? 'border-red-500 bg-red-50/40 ring-1 ring-red-500' 
                : 'border-slate-200 hover:border-red-300 hover:bg-slate-50/50'
            ]"
          >
            <div class="flex items-center justify-between">
              <div class="w-10 h-10 rounded-xl bg-slate-50 border border-slate-100 text-2xl flex items-center justify-center group-hover:bg-red-50 group-hover:border-red-100 transition-colors">
                {{ ind.icon }}
              </div>
              <span :class="[
                'text-xs font-medium px-2.5 py-0.5 rounded-full transition-colors',
                form.industry_id === ind.id 
                  ? 'bg-red-100 text-red-700 font-semibold' 
                  : 'bg-slate-100 text-slate-500 group-hover:bg-red-50 group-hover:text-red-600'
              ]">
                CRM
              </span>
            </div>

            <div class="space-y-1 pt-3">
              <div class="flex items-center justify-between">
                <h3 :class="[
                  'text-sm sm:text-base font-semibold transition-colors',
                  form.industry_id === ind.id ? 'text-red-600' : 'text-slate-900 group-hover:text-red-600'
                ]">
                  {{ ind.name }}
                </h3>
                <ArrowRightIcon :class="[
                  'w-4 h-4 transition-all duration-200',
                  form.industry_id === ind.id 
                    ? 'text-red-600 translate-x-0 opacity-100' 
                    : 'text-slate-300 -translate-x-1 opacity-0 group-hover:opacity-100 group-hover:translate-x-0 group-hover:text-red-600'
                ]" />
              </div>
              <p class="text-xs text-slate-500 line-clamp-2 font-normal leading-relaxed">
                {{ ind.description }}
              </p>
            </div>
          </div>
        </div>

      </div>

      <!-- STEP 2: Select Specific Business Sub-Type -->
      <div v-if="currentStep === 2" class="space-y-6 max-w-4xl mx-auto w-full">
        <div class="text-center space-y-2 max-w-2xl mx-auto">
          <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-red-50 border border-red-100 text-xs font-semibold text-red-700">
            <span>{{ selectedIndustry?.icon }} {{ selectedIndustry?.name }}</span>
          </div>
          <h2 class="text-2xl sm:text-3xl font-bold text-slate-900 tracking-tight">What is your specific business niche?</h2>
          <p class="text-slate-500 text-sm font-normal">Fine-tune your CRM workflow templates and default pipeline stages.</p>
        </div>

        <div v-if="loadingTypes" class="py-16 text-center text-slate-500 font-medium flex items-center justify-center gap-3">
          <div class="w-5 h-5 border-2 border-red-600 border-t-transparent rounded-full animate-spin"></div>
          Loading business workflows...
        </div>

        <div v-else class="grid grid-cols-1 sm:grid-cols-2 gap-3.5 max-h-[440px] overflow-y-auto pr-1 custom-scrollbar">
          <div
            v-for="bt in businessTypes"
            :key="bt.id"
            @click="form.business_type_id = bt.id"
            :class="[
              'p-4 sm:p-5 rounded-2xl border transition-all duration-200 cursor-pointer flex items-center justify-between gap-4 bg-white shadow-2xs hover:shadow-sm',
              form.business_type_id === bt.id 
                ? 'bg-red-50/40 border-red-500 ring-1 ring-red-500' 
                : 'border-slate-200 hover:border-slate-300 hover:bg-slate-50/60'
            ]"
          >
            <div class="space-y-0.5">
              <h4 class="font-semibold text-sm sm:text-base text-slate-900">{{ bt.name }}</h4>
              <p v-if="bt.description" class="text-xs text-slate-500 font-normal leading-relaxed">{{ bt.description }}</p>
            </div>
            <div :class="[
              'w-5 h-5 rounded-full border flex items-center justify-center shrink-0 transition-all', 
              form.business_type_id === bt.id 
                ? 'bg-red-600 border-red-600 text-white' 
                : 'border-slate-300 bg-white text-transparent'
            ]">
              <CheckIcon class="w-3 h-3 stroke-[3]" />
            </div>
          </div>
        </div>

        <!-- Navigation Action Buttons -->
        <div class="flex items-center justify-between pt-6 border-t border-slate-200">
          <button 
            @click="currentStep = 1" 
            type="button" 
            class="px-5 py-2.5 bg-white hover:bg-slate-50 border border-slate-300 text-slate-700 text-sm font-semibold rounded-xl flex items-center gap-2 transition cursor-pointer shadow-2xs"
          >
            <ArrowLeftIcon class="w-4 h-4 stroke-[2.5]" />
            <span>Back</span>
          </button>
          <button 
            @click="currentStep = 3" 
            type="button" 
            class="px-6 py-2.5 bg-red-600 hover:bg-red-700 text-white text-sm font-semibold rounded-xl flex items-center gap-2 shadow-md shadow-red-600/20 transition cursor-pointer"
          >
            <span>Next: Database & Columns</span>
            <ArrowRightIcon class="w-4 h-4 stroke-[2.5]" />
          </button>
        </div>
      </div>

      <!-- STEP 3: CRM Database Creation & Column Customizer -->
      <div v-if="currentStep === 3" class="space-y-6 max-w-5xl mx-auto w-full">
        
        <!-- Step Header -->
        <div class="text-center space-y-2 max-w-2xl mx-auto">
          <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-red-50 border border-red-100 text-xs font-semibold text-red-700">
            <CircleStackIcon class="w-4 h-4 text-red-600" />
            <span>Step 3 of 5 • Database Provisioning & Schema Columns</span>
          </div>
          <h2 class="text-2xl sm:text-3xl font-bold text-slate-900 tracking-tight">Select & Customize Your Database Columns</h2>
          <p class="text-slate-500 text-sm font-normal">
            Choose which columns to include in your dedicated database table. You can check/uncheck columns or add your own fields.
          </p>
        </div>

        <!-- Database Engine Banner -->
        <div class="bg-slate-900 rounded-2xl p-5 text-white shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-4 border border-slate-800">
          <div class="flex items-center gap-3.5">
            <div class="w-11 h-11 rounded-xl bg-red-500/20 border border-red-500/30 flex items-center justify-center text-red-400 font-mono text-xl shrink-0">
              🗄️
            </div>
            <div>
              <div class="flex items-center gap-2">
                <span class="text-xs font-semibold uppercase tracking-wider text-red-400 font-mono">Dedicated MySQL Database</span>
                <span class="px-2 py-0.5 rounded-full bg-emerald-500/20 text-emerald-300 text-[11px] font-medium">
                  Auto-Provision
                </span>
              </div>
              <p class="text-sm font-semibold text-white font-mono tracking-tight mt-0.5">{{ generatedDbName }}</p>
              <p class="text-xs text-slate-400 font-normal">Primary Table: <span class="text-slate-200 font-mono">crm_leads</span> • {{ selectedColumnsCount }} active columns selected</p>
            </div>
          </div>

          <button
            type="button"
            @click="newColumnModalOpen = true"
            class="px-4 py-2.5 bg-red-600 hover:bg-red-700 text-white text-sm font-semibold rounded-xl shadow-md shadow-red-600/20 flex items-center gap-2 transition cursor-pointer self-start sm:self-auto shrink-0"
          >
            <PlusIcon class="w-4 h-4 stroke-[2.5]" />
            <span>+ Add Custom Column</span>
          </button>
        </div>

        <!-- Columns Selection Table / Grid -->
        <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-2xs space-y-4">
          <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <div class="flex items-center gap-2.5">
              <TableCellsIcon class="w-5 h-5 text-red-600" />
              <h3 class="font-semibold text-sm text-slate-900">CRM Database Table Columns</h3>
              <span class="px-2.5 py-0.5 bg-slate-100 text-slate-700 text-xs font-medium rounded-lg">{{ selectedColumnsCount }} Enabled</span>
            </div>
            <span class="text-xs text-slate-400 font-normal hidden sm:inline">Click checkboxes to include/exclude columns</span>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-3 max-h-[360px] overflow-y-auto pr-2 custom-scrollbar">
            <div
              v-for="(col, idx) in availableColumns"
              :key="col.key || idx"
              @click="toggleColumnVisibility(col)"
              :class="[
                'p-3.5 rounded-xl border transition-all duration-200 flex items-center justify-between gap-3 cursor-pointer group',
                col.is_visible !== false 
                  ? 'bg-red-50/30 border-red-200 shadow-2xs' 
                  : 'bg-slate-50/60 border-slate-200 opacity-60 hover:opacity-100 hover:border-slate-300'
              ]"
            >
              <div class="flex items-center gap-3 min-w-0">
                <!-- Toggle Checkbox -->
                <div :class="[
                  'w-4 h-4 rounded-md border flex items-center justify-center shrink-0 transition-all',
                  col.is_visible !== false 
                    ? 'bg-red-600 border-red-600 text-white' 
                    : 'border-slate-300 bg-white text-transparent'
                ]">
                  <CheckIcon class="w-3 h-3 stroke-[3]" />
                </div>

                <div class="min-w-0">
                  <div class="flex items-center gap-2">
                    <span class="font-semibold text-sm text-slate-900 truncate">{{ col.label }}</span>
                    <span v-if="col.is_required" class="text-xs text-red-500 font-medium">*Required</span>
                  </div>
                  <div class="flex items-center gap-2 mt-0.5">
                    <span class="text-[11px] font-mono font-medium text-slate-500 uppercase px-1.5 py-0.5 bg-slate-100 rounded border border-slate-200">
                      {{ col.type }}
                    </span>
                    <span class="text-xs font-mono text-slate-400 truncate">`{{ col.key }}`</span>
                  </div>
                </div>
              </div>

              <div class="flex items-center gap-2 shrink-0">
                <span 
                  v-if="col.category === 'industry'" 
                  class="px-2 py-0.5 rounded-full text-xs font-medium bg-amber-50 text-amber-700 border border-amber-200"
                >
                  Sector
                </span>
                <span 
                  v-else-if="col.category === 'custom'" 
                  class="px-2 py-0.5 rounded-full text-xs font-medium bg-purple-50 text-purple-700 border border-purple-200"
                >
                  Custom
                </span>
                <button
                  v-if="col.category === 'custom'"
                  @click.stop="removeCustomColumn(idx)"
                  class="p-1 text-slate-400 hover:text-red-600 rounded-lg hover:bg-red-50 transition"
                  title="Remove column"
                >
                  <TrashIcon class="w-4 h-4" />
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Add Custom Column Modal -->
        <div v-if="newColumnModalOpen" class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4 z-50">
          <div class="bg-white rounded-2xl border border-slate-200 p-6 max-w-md w-full shadow-xl space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
              <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-lg bg-red-50 text-red-600 flex items-center justify-center font-bold text-base">
                  +
                </div>
                <h3 class="font-bold text-base text-slate-900">Add New Database Column</h3>
              </div>
              <button @click="newColumnModalOpen = false" class="text-slate-400 hover:text-slate-600 text-lg font-bold">✕</button>
            </div>

            <div class="space-y-4 text-sm">
              <div class="space-y-1.5">
                <label class="block font-medium text-slate-700 text-xs">Column label name</label>
                <input 
                  v-model="newCustomCol.label"
                  type="text" 
                  placeholder="e.g. Budget Range, License Number, Locality..." 
                  class="w-full bg-white border border-slate-300 rounded-xl px-3.5 py-2 text-sm text-slate-900 placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-red-500/20 focus:border-red-500"
                />
              </div>

              <div class="space-y-1.5">
                <label class="block font-medium text-slate-700 text-xs">Data / SQL type</label>
                <select 
                  v-model="newCustomCol.type"
                  class="w-full bg-white border border-slate-300 rounded-xl px-3.5 py-2 text-sm text-slate-900 focus:outline-none focus:ring-2 focus:ring-red-500/20 focus:border-red-500"
                >
                  <option value="text">Text (VARCHAR 255)</option>
                  <option value="number">Number (Integer)</option>
                  <option value="currency">Currency / Value (DECIMAL 15,2)</option>
                  <option value="date">Date (YYYY-MM-DD)</option>
                  <option value="dropdown">Dropdown Selection</option>
                  <option value="email">Email Address</option>
                  <option value="textarea">Long Text / Notes (TEXT)</option>
                  <option value="boolean">Yes / No (Boolean)</option>
                </select>
              </div>

              <div v-if="newCustomCol.type === 'dropdown'" class="space-y-1.5">
                <label class="block font-medium text-slate-700 text-xs">Dropdown options (Comma separated)</label>
                <input 
                  v-model="newCustomCol.optionsStr"
                  type="text" 
                  placeholder="e.g. Bronze, Silver, Gold, Platinum" 
                  class="w-full bg-white border border-slate-300 rounded-xl px-3.5 py-2 text-sm text-slate-900 placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-red-500/20 focus:border-red-500"
                />
              </div>

              <label class="flex items-center gap-2 cursor-pointer pt-1">
                <input type="checkbox" v-model="newCustomCol.is_required" class="w-4 h-4 text-red-600 rounded" />
                <span class="font-normal text-slate-700 text-sm">Make this field required</span>
              </label>
            </div>

            <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
              <button 
                type="button"
                @click="newColumnModalOpen = false"
                class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-medium rounded-xl text-sm"
              >
                Cancel
              </button>
              <button 
                type="button"
                @click="addCustomColumn"
                class="px-5 py-2 bg-red-600 hover:bg-red-700 text-white font-semibold rounded-xl shadow-md shadow-red-600/20 text-sm"
              >
                Add Column
              </button>
            </div>
          </div>
        </div>

        <!-- Navigation Action Buttons -->
        <div class="flex items-center justify-between pt-6 border-t border-slate-200">
          <button 
            @click="currentStep = 2" 
            type="button" 
            class="px-5 py-2.5 bg-white hover:bg-slate-50 border border-slate-300 text-slate-700 text-sm font-semibold rounded-xl flex items-center gap-2 transition cursor-pointer shadow-2xs"
          >
            <ArrowLeftIcon class="w-4 h-4 stroke-[2.5]" />
            <span>Back</span>
          </button>
          <button 
            @click="currentStep = 4" 
            type="button" 
            class="px-6 py-2.5 bg-red-600 hover:bg-red-700 text-white text-sm font-semibold rounded-xl flex items-center gap-2 shadow-md shadow-red-600/20 transition cursor-pointer"
          >
            <span>Next: Organization Scale</span>
            <ArrowRightIcon class="w-4 h-4 stroke-[2.5]" />
          </button>
        </div>
      </div>

      <!-- STEP 4: Select Organization Scale -->
      <div v-if="currentStep === 4" class="space-y-6 max-w-4xl mx-auto w-full">
        <div class="text-center space-y-2 max-w-2xl mx-auto">
          <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-red-50 border border-red-100 text-xs font-semibold text-red-700">
            <UserGroupIcon class="w-4 h-4 text-red-600" />
            <span>Step 4 of 5 • Scale & Capacity</span>
          </div>
          <h2 class="text-2xl sm:text-3xl font-bold text-slate-900 tracking-tight">How many team members will use your CRM?</h2>
          <p class="text-slate-500 text-sm font-normal">We'll pre-allocate user quota, multi-tenant RBAC permissions, and storage volume.</p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
          <div
            v-for="ts in teamSizes"
            :key="ts.id"
            @click="form.employee_range = ts.id"
            :class="[
              'p-5 rounded-2xl border transition-all duration-200 cursor-pointer flex flex-col justify-between space-y-3 bg-white shadow-2xs hover:shadow-sm',
              form.employee_range === ts.id 
                ? 'bg-red-50/40 border-red-500 ring-1 ring-red-500' 
                : 'border-slate-200 hover:border-slate-300 hover:bg-slate-50/60'
            ]"
          >
            <div class="flex items-center justify-between">
              <span class="text-2xl">{{ ts.icon }}</span>
              <div :class="[
                'w-5 h-5 rounded-full border flex items-center justify-center transition-all', 
                form.employee_range === ts.id 
                  ? 'bg-red-600 border-red-600 text-white' 
                  : 'border-slate-300 bg-white text-transparent'
              ]">
                <CheckIcon class="w-3 h-3 stroke-[3]" />
              </div>
            </div>
            <div>
              <h4 class="font-semibold text-sm sm:text-base text-slate-900">{{ ts.label }}</h4>
              <p class="text-xs text-slate-500 mt-0.5 font-normal leading-relaxed">{{ ts.desc }}</p>
            </div>
          </div>
        </div>

        <!-- Navigation Action Buttons -->
        <div class="flex items-center justify-between pt-6 border-t border-slate-200">
          <button 
            @click="currentStep = 3" 
            type="button" 
            class="px-5 py-2.5 bg-white hover:bg-slate-50 border border-slate-300 text-slate-700 text-sm font-semibold rounded-xl flex items-center gap-2 transition cursor-pointer shadow-2xs"
          >
            <ArrowLeftIcon class="w-4 h-4 stroke-[2.5]" />
            <span>Back to Columns</span>
          </button>
          <button 
            @click="currentStep = 5" 
            type="button" 
            class="px-6 py-2.5 bg-red-600 hover:bg-red-700 text-white text-sm font-semibold rounded-xl flex items-center gap-2 shadow-md shadow-red-600/20 transition cursor-pointer"
          >
            <span>Next: CRM Objectives</span>
            <ArrowRightIcon class="w-4 h-4 stroke-[2.5]" />
          </button>
        </div>
      </div>

      <!-- STEP 5: Select Primary Objectives & Provision Database -->
      <div v-if="currentStep === 5" class="space-y-6 max-w-4xl mx-auto w-full">
        <div class="text-center space-y-2 max-w-2xl mx-auto">
          <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-red-50 border border-red-100 text-xs font-semibold text-red-700">
            <SparklesIcon class="w-4 h-4 text-red-600" />
            <span>Step 5 of 5 • Finalize & Provision</span>
          </div>
          <h2 class="text-2xl sm:text-3xl font-bold text-slate-900 tracking-tight">Ready to create your CRM Database</h2>
          <p class="text-slate-500 text-sm font-normal">Confirm your target objectives. We'll provision your MySQL database table and launch your workspace.</p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
          <div
            v-for="goal in crmGoalsList"
            :key="goal.id"
            @click="toggleGoal(goal.id)"
            :class="[
              'p-4 rounded-2xl border transition-all duration-200 cursor-pointer flex items-center justify-between gap-3 bg-white shadow-2xs hover:shadow-sm',
              form.crm_goals.includes(goal.id) 
                ? 'bg-red-50/40 border-red-500 ring-1 ring-red-500' 
                : 'border-slate-200 hover:border-slate-300 hover:bg-slate-50/60'
            ]"
          >
            <div class="flex items-center gap-3">
              <div class="w-9 h-9 rounded-xl bg-slate-50 border border-slate-100 flex items-center justify-center text-lg shrink-0">
                {{ goal.icon }}
              </div>
              <div>
                <h4 class="font-semibold text-sm text-slate-900">{{ goal.label }}</h4>
                <p class="text-xs text-slate-500 font-normal">{{ goal.desc }}</p>
              </div>
            </div>
            <div :class="[
              'w-5 h-5 rounded-full border flex items-center justify-center shrink-0 transition-all', 
              form.crm_goals.includes(goal.id) 
                ? 'bg-red-600 border-red-600 text-white' 
                : 'border-slate-300 bg-white text-transparent'
            ]">
              <CheckIcon class="w-3 h-3 stroke-[3]" />
            </div>
          </div>
        </div>

        <!-- Launch Button -->
        <div class="flex items-center justify-between pt-6 border-t border-slate-200">
          <button 
            @click="currentStep = 4" 
            type="button" 
            class="px-5 py-2.5 bg-white hover:bg-slate-50 border border-slate-300 text-slate-700 text-sm font-semibold rounded-xl flex items-center gap-2 transition cursor-pointer shadow-2xs"
          >
            <ArrowLeftIcon class="w-4 h-4 stroke-[2.5]" />
            <span>Back</span>
          </button>

          <button 
            @click="completeOnboarding"
            :disabled="form.processing"
            class="px-7 py-3 bg-red-600 hover:bg-red-700 text-white text-sm font-semibold rounded-xl shadow-lg shadow-red-600/25 flex items-center gap-2.5 transition cursor-pointer disabled:opacity-50"
          >
            <RocketLaunchIcon class="w-4 h-4 stroke-[2.5]" />
            <span>Create Database & Launch CRM</span>
          </button>
        </div>
      </div>

    </main>

    <!-- Footer Status -->
    <footer class="max-w-6xl mx-auto w-full px-4 sm:px-6 py-4 text-center text-xs text-slate-400 font-normal z-10">
      JRV CRM Multi-Sector Dynamic Engine • Enterprise Multi-Tenant Architecture
    </footer>

  </div>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
  width: 5px;
}
.custom-scrollbar::-webkit-scrollbar-track {
  background: rgba(241, 245, 249, 0.6);
  border-radius: 8px;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
  background: rgba(203, 213, 225, 0.8);
  border-radius: 8px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
  background: rgba(220, 38, 38, 0.7);
}
</style>
