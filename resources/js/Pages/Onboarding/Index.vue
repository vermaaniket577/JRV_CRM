<script setup>
import { ref, computed, onMounted } from 'vue';
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
  ShieldCheckIcon,
  AdjustmentsHorizontalIcon,
  BuildingOfficeIcon,
  ServerIcon,
  ArrowUpTrayIcon,
  DocumentArrowUpIcon
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

// Database Upload & Auto-Config State
const selectedDbFile = ref(null);
const isUploadingDb = ref(false);
const uploadDbStep = ref(1);
const uploadError = ref('');
const uploadSuccessMessage = ref('');
const isDraggingFile = ref(false);
const fileInputRef = ref(null);

const deployStepsList = [
  'Reading and parsing SQL database file schema...',
  'Deploying tables directly to dedicated MySQL database...',
  'Registering dynamic custom fields and schema attributes...',
  'Auto-detecting industry sector & calibrating CRM pipelines...',
  'Loading data records and launching your CRM workspace...'
];

const handleDbFileSelect = (e) => {
  const files = e.target.files;
  if (files && files.length > 0) {
    selectedDbFile.value = files[0];
    uploadError.value = '';
  }
};

const handleDbFileDrop = (e) => {
  isDraggingFile.value = false;
  const files = e.dataTransfer.files;
  if (files && files.length > 0) {
    selectedDbFile.value = files[0];
    uploadError.value = '';
  }
};

const removeSelectedDbFile = () => {
  selectedDbFile.value = null;
  uploadError.value = '';
  if (fileInputRef.value) {
    fileInputRef.value.value = '';
  }
};

const formatFileSize = (bytes) => {
  if (!bytes) return '0 B';
  const k = 1024;
  const sizes = ['B', 'KB', 'MB', 'GB'];
  const i = Math.floor(Math.log(bytes) / Math.log(k));
  return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
};

const deployUploadedDatabase = async () => {
  if (!selectedDbFile.value) {
    uploadError.value = 'Please select a .sql or spreadsheet file first.';
    return;
  }

  isUploadingDb.value = true;
  uploadDbStep.value = 1;
  uploadError.value = '';

  const stepInterval = setInterval(() => {
    if (uploadDbStep.value < 4) {
      uploadDbStep.value++;
    }
  }, 900);

  const formData = new FormData();
  formData.append('file', selectedDbFile.value);
  if (selectedIndustry.value?.id) {
    formData.append('industry_id', selectedIndustry.value.id);
  }

  try {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    const res = await fetch('/onboarding/upload-database', {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        ...(csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {})
      },
      body: formData
    });

    clearInterval(stepInterval);
    const data = await res.json();

    if (!res.ok || !data.success) {
      throw new Error(data.error || data.message || 'Database deployment failed.');
    }

    uploadDbStep.value = 5;
    uploadSuccessMessage.value = data.message || 'Database successfully deployed!';

    setTimeout(() => {
      if (data.redirect_url) {
        window.location.href = data.redirect_url;
      } else {
        window.location.href = '/tenant/crm-records?onboarding_success=1';
      }
    }, 1000);
  } catch (err) {
    clearInterval(stepInterval);
    isUploadingDb.value = false;
    uploadError.value = err.message || 'Failed to deploy database. Please check your file and try again.';
  }
};

const skipDatabaseStep = () => {
  currentStep.value = 3;
};

// Database & Column state
const availableColumns = ref(props.defaultColumns || []);
const loadingColumns = ref(false);
const newColumnModalOpen = ref(false);

const isLocalEnv = ref(true);
const portSuffix = ref(':8000');

onMounted(() => {
  if (typeof window !== 'undefined') {
    isLocalEnv.value = window.location.hostname === 'localhost' || 
                       window.location.hostname === '127.0.0.1' || 
                       window.location.hostname.endsWith('.localhost');
    portSuffix.value = window.location.port ? `:${window.location.port}` : '';
  }
});

const tenantSubdomainDisplay = computed(() => {
  const sub = props.tenant?.subdomain || 'unlockrentals';
  if (isLocalEnv.value) {
    return `http://${sub}.localhost${portSuffix.value || ':8000'}`;
  }
  return `https://${sub}.jrvcrm.com`;
});

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
  { number: 1, title: 'Industry', short: 'Industry' },
  { number: 2, title: 'Database Setup', short: 'Database' },
  { number: 3, title: 'Specialization', short: 'Niche' },
  { number: 4, title: 'Fields', short: 'Fields' },
  { number: 5, title: 'Team Size', short: 'Team' },
  { number: 6, title: 'Goals & Launch', short: 'Launch' },
];

const progressPercent = computed(() => {
  return ((currentStep.value - 1) / (steps.length - 1)) * 100;
});

const recommendedIndustryId = computed(() => {
  const orgName = (props.tenant?.name || '').toLowerCase();
  if (/admiss|school|college|edu|academ|univ|class|coaching|tutor|student/.test(orgName)) {
    const found = (props.industries || []).find(i => i.slug === 'education' || i.name.toLowerCase().includes('education'));
    if (found) return found.id;
  }
  if (/realt|estate|prop|rent|broker|build|home|housing/.test(orgName)) {
    const found = (props.industries || []).find(i => i.slug === 'real-estate' || i.name.toLowerCase().includes('real estate'));
    if (found) return found.id;
  }
  if (/health|clinic|medic|hosp|care|dental|doctor|pharma/.test(orgName)) {
    const found = (props.industries || []).find(i => i.slug === 'healthcare' || i.name.toLowerCase().includes('healthcare'));
    if (found) return found.id;
  }
  if (/matrimon|shadi|vivah|risht|counsel|wedding/.test(orgName)) {
    const found = (props.industries || []).find(i => i.slug === 'matrimonial' || i.name.toLowerCase().includes('matrimonial'));
    if (found) return found.id;
  }
  if (/finance|tax|wealth|invest|loan|ca|audit|bank/.test(orgName)) {
    const found = (props.industries || []).find(i => i.slug === 'financial-services' || i.name.toLowerCase().includes('finance'));
    if (found) return found.id;
  }
  if (/tech|soft|code|dev|app|cloud|digital|agency/.test(orgName)) {
    const found = (props.industries || []).find(i => i.slug === 'technology' || i.name.toLowerCase().includes('technology'));
    if (found) return found.id;
  }
  return null;
});

const formatFieldType = (type) => {
  const map = {
    text: 'Short Text',
    string: 'Short Text',
    textarea: 'Long Text / Notes',
    number: 'Number (Integer)',
    decimal: 'Currency (DECIMAL)',
    currency: 'Currency (DECIMAL)',
    email: 'Email Address',
    phone: 'Phone / WhatsApp',
    date: 'Date (YYYY-MM-DD)',
    dropdown: 'Select Dropdown',
    boolean: 'Boolean (Yes/No)',
  };
  return map[type] || 'Text';
};

const isProvisioning = ref(false);
const provisioningStep = ref(1);

const teamSizes = [
  { id: '1–5', label: '1–5 people', desc: 'Solo founders, partners & boutique teams', icon: '🚀' },
  { id: '6–20', label: '6–20 people', desc: 'Growing small businesses & boutique agencies', icon: '⚡' },
  { id: '21–50', label: '21–50 people', desc: 'Mid-sized companies with multiple departments', icon: '🏢' },
  { id: '51–200', label: '51–200 people', desc: 'Expanding organizations with regional operations', icon: '🌐' },
  { id: '201–500', label: '201–500 people', desc: 'Multi-branch companies & large operations', icon: '🏛️' },
  { id: '500+', label: '500+ people', desc: 'Enterprise organizations & high-volume teams', icon: '👑' },
];

const crmGoalsList = [
  { id: 'Lead Management', label: 'Lead Tracking & Pipelines', desc: 'Capture and qualify incoming leads through stage-based funnels', icon: '🎯' },
  { id: 'Sales', label: 'Deals & Revenue Tracking', desc: 'Manage deals, track probabilities, and monitor closed revenue', icon: '💰' },
  { id: 'Customer Support', label: 'Client Support & Tickets', desc: 'Log customer requests, track resolutions, and manage inquiries', icon: '🎧' },
  { id: 'Marketing', label: 'Automated Campaigns', desc: 'Schedule follow-up emails, notifications, and broadcast messages', icon: '📢' },
  { id: 'Communication', label: 'Email & WhatsApp Messaging', desc: 'Centralize team communication and client conversation history', icon: '💬' },
  { id: 'Appointment Management', label: 'Calendar & Scheduling', desc: 'Book appointments, manage consultations, and send automated reminders', icon: '📅' },
  { id: 'Payments', label: 'Invoicing & Payments', desc: 'Generate quotes, send invoices, and record customer payments', icon: '💳' },
  { id: 'Reports', label: 'Analytics & KPI Reports', desc: 'Monitor team performance, conversion rates, and revenue trends', icon: '📊' },
  { id: 'Team Management', label: 'Roles & Permissions', desc: 'Assign staff roles, delegate tasks, and control access permissions', icon: '👥' },
  { id: 'Automation', label: 'Automated Workflows', desc: 'Trigger automatic actions and notifications when records change', icon: '⚡' },
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
  form.selected_columns = availableColumns.value.filter(c => c.is_visible !== false);
  isProvisioning.value = true;
  provisioningStep.value = 1;

  const interval = setInterval(() => {
    if (provisioningStep.value < 4) {
      provisioningStep.value++;
    }
  }, 600);

  form.post('/onboarding/complete', {
    onFinish: () => {
      clearInterval(interval);
    }
  });
};
</script>

<template>
  <Head title="Configure Workspace - JRV CRM" />

  <div class="min-h-screen bg-slate-50 text-slate-900 font-sans flex flex-col justify-between relative selection:bg-red-600 selection:text-white antialiased">
    
    <!-- Subtle Ambient Glows -->
    <div class="absolute -top-32 left-1/2 -translate-x-1/2 w-[700px] h-[350px] bg-red-500/5 rounded-full blur-3xl pointer-events-none"></div>
    <div class="absolute bottom-0 right-0 w-[500px] h-[350px] bg-indigo-500/5 rounded-full blur-3xl pointer-events-none"></div>

    <!-- Header Navigation & Stepper -->
    <header class="max-w-6xl mx-auto w-full px-4 sm:px-6 pt-5 pb-3 z-20">
      <div class="bg-white/90 backdrop-blur-md border border-slate-200/90 rounded-2xl px-5 py-3.5 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
        
        <!-- Brand & Workspace Meta -->
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-red-500 to-red-600 flex items-center justify-center text-white font-bold text-lg shadow-sm shadow-red-600/30 shrink-0">
            ⚡
          </div>
          <div>
            <div class="flex items-center gap-2">
              <span class="font-bold text-sm text-slate-900 tracking-tight">{{ tenant?.name || 'Workspace Setup' }}</span>
              <span class="px-2 py-0.5 text-xs font-semibold uppercase tracking-wider bg-red-50 text-red-700 rounded-md border border-red-200 font-mono">
                Setup
              </span>
            </div>
            <p class="text-xs text-slate-500 font-mono mt-0.5">{{ tenantSubdomainDisplay }}</p>
          </div>
        </div>

        <!-- Sleek Segmented Step Navigation -->
        <div class="flex items-center gap-1 sm:gap-2">
          <div 
            v-for="step in steps" 
            :key="step.number"
            class="flex items-center gap-1.5"
          >
            <button 
              type="button"
              @click="step.number < currentStep ? currentStep = step.number : null"
              :disabled="step.number > currentStep"
              :class="[
                'flex items-center gap-2 px-3 py-1.5 rounded-xl text-xs transition-all font-medium',
                step.number === currentStep 
                  ? 'bg-red-600 text-white shadow-sm shadow-red-600/20 font-bold' 
                  : step.number < currentStep 
                    ? 'bg-emerald-50 border border-emerald-200 text-emerald-700 hover:bg-emerald-100 cursor-pointer' 
                    : 'bg-slate-100 border border-slate-200 text-slate-400 cursor-not-allowed'
              ]"
            >
              <div 
                :class="[
                  'w-5 h-5 rounded-full flex items-center justify-center text-xs font-semibold shrink-0',
                  step.number === currentStep 
                    ? 'bg-white text-red-600' 
                    : step.number < currentStep 
                      ? 'bg-emerald-600 text-white' 
                      : 'bg-slate-200 text-slate-500'
                ]"
              >
                <CheckIcon v-if="step.number < currentStep" class="w-3 h-3 stroke-[3]" />
                <span v-else>{{ step.number }}</span>
              </div>
              <span class="hidden md:inline">{{ step.title }}</span>
              <span class="inline md:hidden">{{ step.short }}</span>
            </button>
            <span v-if="step.number < steps.length" class="text-slate-300 text-xs hidden sm:inline">›</span>
          </div>
        </div>

      </div>

      <!-- Linear Subtle Progress Bar -->
      <div class="w-full bg-slate-200 h-1.5 rounded-full mt-2.5 overflow-hidden border border-slate-200/80">
        <div 
          class="bg-gradient-to-r from-red-600 to-rose-500 h-full transition-all duration-500 rounded-full"
          :style="{ width: `${progressPercent}%` }"
        ></div>
      </div>
    </header>

    <!-- Main Step Body Container -->
    <main class="max-w-6xl mx-auto w-full px-4 sm:px-6 py-6 z-10 flex-1 flex flex-col justify-center">
      
      <!-- STEP 1: Select CRM Industry Engine -->
      <div v-if="currentStep === 1" class="space-y-6 max-w-5xl mx-auto w-full">
        
        <!-- Step 1 Heading -->
        <div class="text-center space-y-2 max-w-2xl mx-auto">
          <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-red-50 border border-red-200 text-xs font-semibold text-red-700">
            <SparklesIcon class="w-4 h-4 text-red-600" />
            <span>Step 1 of 6 • Industry Selection</span>
          </div>
          <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">
            Choose Your CRM Industry
          </h1>
          <p class="text-slate-600 text-xs sm:text-sm font-normal max-w-xl mx-auto leading-relaxed">
            Select the industry that best matches your organization. We will automatically configure your default pipelines, client records, and workflows.
          </p>

          <!-- Search Filter Bar & Quick Direct Upload -->
          <div class="pt-3 max-w-md mx-auto space-y-2.5">
            <div class="relative">
              <MagnifyingGlassIcon class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none" />
              <input 
                v-model="searchQuery" 
                type="text" 
                placeholder="Search industries (e.g. Real Estate, Education, Healthcare)..." 
                class="w-full bg-white border border-slate-300 focus:border-red-500 focus:ring-2 focus:ring-red-500/20 rounded-xl pl-10 pr-24 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 transition shadow-xs outline-none"
              />
              <span class="absolute right-2.5 top-1/2 -translate-y-1/2 px-2 py-0.5 bg-slate-100 text-xs font-mono font-medium text-slate-600 rounded-md border border-slate-200">
                {{ filteredIndustries.length }} industries
              </span>
            </div>

            <!-- Quick Direct Upload Action -->
            <div class="flex items-center justify-center">
              <button 
                type="button" 
                @click="currentStep = 2" 
                class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-red-50 hover:bg-red-100 border border-red-200 text-xs text-red-700 hover:text-red-800 transition font-semibold cursor-pointer shadow-2xs group"
              >
                <CircleStackIcon class="w-3.5 h-3.5 text-red-600 group-hover:scale-110 transition" />
                <span>Already have a database (.sql / .csv)? <span class="underline decoration-red-400">Upload Database Directly →</span></span>
              </button>
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
              'p-5 rounded-2xl border transition-all duration-200 group flex flex-col justify-between min-h-[160px] cursor-pointer shadow-xs relative overflow-hidden bg-white',
              form.industry_id === ind.id 
                ? 'border-red-500 bg-red-50/40 ring-2 ring-red-500/20 shadow-sm' 
                : 'border-slate-200 hover:border-slate-300 hover:shadow-md hover:-translate-y-0.5'
            ]"
          >
            <div class="flex items-center justify-between">
              <div class="w-10 h-10 rounded-xl bg-slate-50 border border-slate-200 text-xl flex items-center justify-center group-hover:border-red-400/50 transition-colors">
                {{ ind.icon }}
              </div>
              
              <span v-if="recommendedIndustryId === ind.id" class="text-xs font-semibold px-2.5 py-0.5 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200">
                ★ Recommended
              </span>
              <span v-else :class="[
                'text-xs font-mono uppercase px-2 py-0.5 rounded-md transition-colors',
                form.industry_id === ind.id 
                  ? 'bg-red-100 text-red-700 font-semibold' 
                  : 'bg-slate-100 text-slate-600 group-hover:bg-slate-200'
              ]">
                Standard
              </span>
            </div>

            <div class="space-y-1.5 pt-4">
              <div class="flex items-center justify-between">
                <h3 :class="[
                  'text-base font-bold transition-colors tracking-tight',
                  form.industry_id === ind.id ? 'text-red-600' : 'text-slate-900 group-hover:text-red-600'
                ]">
                  {{ ind.name }}
                </h3>
                <ArrowRightIcon :class="[
                  'w-4 h-4 transition-all duration-200',
                  form.industry_id === ind.id 
                    ? 'text-red-600 translate-x-0 opacity-100' 
                    : 'text-slate-400 -translate-x-1 opacity-0 group-hover:opacity-100 group-hover:translate-x-0 group-hover:text-red-600'
                ]" />
              </div>
              <p class="text-xs text-slate-600 line-clamp-2 leading-relaxed font-normal">
                {{ ind.description }}
              </p>
            </div>
          </div>
        </div>

      </div>

      <!-- STEP 2: Database Setup (Upload Database or Skip) -->
      <div v-if="currentStep === 2" class="space-y-6 max-w-4xl mx-auto w-full">
        <!-- Step 2 Heading -->
        <div class="text-center space-y-2 max-w-2xl mx-auto">
          <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-red-50 border border-red-200 text-xs font-semibold text-red-700">
            <CircleStackIcon class="w-4 h-4 text-red-600" />
            <span>Step 2 of 6 • Database Setup</span>
          </div>
          <h2 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">
            Upload Database or Skip to Configure
          </h2>
          <p class="text-slate-600 text-xs sm:text-sm font-normal max-w-xl mx-auto leading-relaxed">
            Have an existing SQL database backup or spreadsheet? Upload it now to automatically build your CRM tables, custom fields, and records — or skip to configure manually.
          </p>
        </div>

        <!-- Two Path Choice Cards -->
        <div class="grid grid-cols-1 md:grid-cols-12 gap-5 items-stretch">
          
          <!-- Path 1: Upload Existing Database (7 cols on md) -->
          <div class="md:col-span-7 bg-white border border-red-200/80 rounded-2xl p-5 sm:p-6 shadow-sm flex flex-col justify-between relative overflow-hidden ring-1 ring-red-500/10">
            <!-- Subtle accent top line -->
            <div class="absolute top-0 left-0 right-0 h-1 bg-gradient-to-r from-red-500 via-rose-500 to-red-600"></div>

            <div class="space-y-4">
              <div class="flex items-center justify-between">
                <div class="flex items-center gap-2.5">
                  <div class="w-9 h-9 rounded-xl bg-red-50 border border-red-200 flex items-center justify-center text-red-600">
                    <DocumentArrowUpIcon class="w-5 h-5 stroke-[2]" />
                  </div>
                  <div>
                    <h3 class="font-bold text-sm text-slate-900">Upload Database Backup</h3>
                    <p class="text-xs text-slate-500">Supports .sql, .dump, .csv, .xlsx, .json</p>
                  </div>
                </div>
                <span class="px-2.5 py-0.5 rounded-full bg-emerald-50 text-emerald-700 text-xs font-semibold border border-emerald-200">
                  ⚡ Auto-Configure CRM
                </span>
              </div>

              <!-- Drag & Drop Zone -->
              <div
                @dragover.prevent="isDraggingFile = true"
                @dragleave.prevent="isDraggingFile = false"
                @drop.prevent="handleDbFileDrop"
                @click="fileInputRef?.click()"
                :class="[
                  'border-2 border-dashed rounded-xl p-6 text-center cursor-pointer transition-all duration-200',
                  isDraggingFile 
                    ? 'border-red-500 bg-red-50/60 scale-[1.01]' 
                    : selectedDbFile 
                      ? 'border-emerald-400 bg-emerald-50/30' 
                      : 'border-slate-300 hover:border-red-400 hover:bg-slate-50/60'
                ]"
              >
                <input
                  ref="fileInputRef"
                  type="file"
                  accept=".sql,.dump,.csv,.xlsx,.xls,.json,.tsv"
                  class="hidden"
                  @change="handleDbFileSelect"
                />

                <!-- If File Selected -->
                <div v-if="selectedDbFile" class="space-y-2">
                  <div class="w-12 h-12 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center mx-auto text-xl">
                    📁
                  </div>
                  <div class="min-w-0">
                    <p class="text-sm font-bold text-slate-900 truncate">{{ selectedDbFile.name }}</p>
                    <p class="text-xs font-mono text-slate-500">{{ formatFileSize(selectedDbFile.size) }}</p>
                  </div>
                  <div class="pt-1 flex items-center justify-center gap-2">
                    <button
                      type="button"
                      @click.stop="removeSelectedDbFile"
                      class="text-xs text-red-600 hover:text-red-700 font-medium px-2.5 py-1 rounded-lg bg-red-50 hover:bg-red-100 transition"
                    >
                      Remove / Change File
                    </button>
                  </div>
                </div>

                <!-- If No File Selected -->
                <div v-else class="space-y-2 py-2">
                  <div class="w-12 h-12 rounded-xl bg-red-50 text-red-600 flex items-center justify-center mx-auto">
                    <ArrowUpTrayIcon class="w-6 h-6 stroke-[2]" />
                  </div>
                  <div>
                    <p class="text-sm font-semibold text-slate-800">
                      <span class="text-red-600 font-bold hover:underline">Click to browse</span> or drag and drop
                    </p>
                    <p class="text-xs text-slate-500 mt-0.5">
                      Upload an exported MySQL dump (.sql) or customer spreadsheet
                    </p>
                  </div>
                </div>
              </div>

              <!-- Feature Bullets / Highlights -->
              <div class="grid grid-cols-2 gap-2 text-xs text-slate-600 bg-slate-50/80 p-3 rounded-xl border border-slate-200/80">
                <div class="flex items-center gap-1.5">
                  <CheckCircleIcon class="w-4 h-4 text-emerald-600 shrink-0" />
                  <span>Dedicated Database</span>
                </div>
                <div class="flex items-center gap-1.5">
                  <CheckCircleIcon class="w-4 h-4 text-emerald-600 shrink-0" />
                  <span>Auto-detects Industry</span>
                </div>
                <div class="flex items-center gap-1.5">
                  <CheckCircleIcon class="w-4 h-4 text-emerald-600 shrink-0" />
                  <span>Registers Custom Fields</span>
                </div>
                <div class="flex items-center gap-1.5">
                  <CheckCircleIcon class="w-4 h-4 text-emerald-600 shrink-0" />
                  <span>Displays Data Instantly</span>
                </div>
              </div>

              <!-- Error Alert -->
              <div v-if="uploadError" class="p-3 rounded-xl bg-red-50 border border-red-200 text-xs text-red-700 flex items-start gap-2">
                <span class="text-base leading-none">⚠️</span>
                <span class="font-medium">{{ uploadError }}</span>
              </div>
            </div>

            <!-- Deploy Button -->
            <div class="pt-4 mt-2 border-t border-slate-100 flex items-center justify-between gap-3">
              <span class="text-xs text-slate-500 truncate">
                {{ selectedDbFile ? 'File ready to deploy' : 'Select a .sql or spreadsheet file' }}
              </span>
              <button
                type="button"
                @click="deployUploadedDatabase"
                :disabled="!selectedDbFile || isUploadingDb"
                :class="[
                  'px-5 py-2.5 rounded-xl text-xs font-bold transition flex items-center gap-2 shadow-sm shrink-0',
                  selectedDbFile && !isUploadingDb
                    ? 'bg-red-600 hover:bg-red-700 text-white shadow-red-600/20 cursor-pointer'
                    : 'bg-slate-200 text-slate-400 cursor-not-allowed'
                ]"
              >
                <RocketLaunchIcon class="w-4 h-4 stroke-[2]" />
                <span>Deploy Database & Launch CRM</span>
              </button>
            </div>
          </div>

          <!-- Path 2: Skip & Configure Manually (5 cols on md) -->
          <div class="md:col-span-5 bg-white border border-slate-200 rounded-2xl p-5 sm:p-6 shadow-xs flex flex-col justify-between hover:border-slate-300 transition">
            <div class="space-y-3.5">
              <div class="flex items-center gap-2.5">
                <div class="w-9 h-9 rounded-xl bg-slate-100 border border-slate-200 flex items-center justify-center text-slate-700 text-lg">
                  ⚡
                </div>
                <div>
                  <h3 class="font-bold text-sm text-slate-900">Start Clean / Manual</h3>
                  <p class="text-xs text-slate-500">No database backup? Skip ahead</p>
                </div>
              </div>

              <p class="text-xs text-slate-600 leading-relaxed">
                You don't need a database to get started. We will guide you through choosing your business model, customer fields, team size, and goals. You can always import or upload databases later.
              </p>

              <div class="space-y-2 pt-1">
                <div class="flex items-center gap-2 text-xs text-slate-500">
                  <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span>
                  <span>Step-by-step niche customization</span>
                </div>
                <div class="flex items-center gap-2 text-xs text-slate-500">
                  <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span>
                  <span>Pick and edit default CRM fields</span>
                </div>
                <div class="flex items-center gap-2 text-xs text-slate-500">
                  <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span>
                  <span>Calibrate user roles & permissions</span>
                </div>
              </div>
            </div>

            <div class="pt-4 mt-4 border-t border-slate-100">
              <button
                type="button"
                @click="skipDatabaseStep"
                class="w-full py-2.5 px-4 bg-slate-100 hover:bg-slate-200 text-slate-800 text-xs font-semibold rounded-xl flex items-center justify-center gap-2 transition cursor-pointer"
              >
                <span>Skip & Setup Manually</span>
                <ArrowRightIcon class="w-3.5 h-3.5 stroke-[2.5]" />
              </button>
            </div>
          </div>

        </div>

        <!-- Navigation Action Buttons -->
        <div class="flex items-center justify-between pt-6 border-t border-slate-200">
          <button 
            @click="currentStep = 1" 
            type="button" 
            class="px-5 py-2.5 bg-white hover:bg-slate-100 border border-slate-300 text-slate-700 text-xs font-semibold rounded-xl flex items-center gap-2 transition cursor-pointer shadow-xs"
          >
            <ArrowLeftIcon class="w-3.5 h-3.5 stroke-[2.5]" />
            <span>Back to Industry</span>
          </button>
          
          <button 
            @click="skipDatabaseStep" 
            type="button" 
            class="px-5 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold rounded-xl flex items-center gap-2 transition cursor-pointer"
          >
            <span>Skip this step</span>
            <ArrowRightIcon class="w-3.5 h-3.5 stroke-[2.5]" />
          </button>
        </div>
      </div>

      <!-- STEP 3: Select Specific Business Sub-Type / Niche -->
      <div v-if="currentStep === 3" class="space-y-6 max-w-4xl mx-auto w-full">
        <div class="text-center space-y-2 max-w-2xl mx-auto">
          <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-red-50 border border-red-200 text-xs font-semibold text-red-700">
            <span>{{ selectedIndustry?.icon }} {{ selectedIndustry?.name }}</span>
          </div>
          <h2 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">Select Your Specialization</h2>
          <p class="text-slate-600 text-xs sm:text-sm font-normal">Choose your specific business model to customize pipeline stages and default terminology.</p>
        </div>

        <div v-if="loadingTypes" class="py-16 text-center text-slate-500 font-medium flex items-center justify-center gap-3">
          <div class="w-5 h-5 border-2 border-red-500 border-t-transparent rounded-full animate-spin"></div>
          <span>Loading specializations...</span>
        </div>

        <div v-else class="grid grid-cols-1 sm:grid-cols-2 gap-3.5 max-h-[440px] overflow-y-auto pr-1 custom-scrollbar">
          <div
            v-for="bt in businessTypes"
            :key="bt.id"
            @click="form.business_type_id = bt.id"
            :class="[
              'p-4 sm:p-5 rounded-2xl border transition-all duration-200 cursor-pointer flex items-center justify-between gap-4 shadow-xs bg-white',
              form.business_type_id === bt.id 
                ? 'bg-red-50/50 border-red-500 ring-2 ring-red-500/20 shadow-sm' 
                : 'border-slate-200 hover:border-slate-300 hover:bg-slate-50/60'
            ]"
          >
            <div class="space-y-0.5">
              <h4 class="font-bold text-sm text-slate-900">{{ bt.name }}</h4>
              <p v-if="bt.description" class="text-xs text-slate-600 leading-relaxed">{{ bt.description }}</p>
            </div>
            <div :class="[
              'w-5 h-5 rounded-full border flex items-center justify-center shrink-0 transition-all', 
              form.business_type_id === bt.id 
                ? 'bg-red-600 border-red-600 text-white' 
                : 'border-slate-300 bg-slate-100 text-transparent'
            ]">
              <CheckIcon class="w-3 h-3 stroke-[3]" />
            </div>
          </div>
        </div>

        <!-- Navigation Action Buttons -->
        <div class="flex items-center justify-between pt-6 border-t border-slate-200">
          <button 
            @click="currentStep = 2" 
            type="button" 
            class="px-5 py-2.5 bg-white hover:bg-slate-100 border border-slate-300 text-slate-700 text-sm font-medium rounded-xl flex items-center gap-2 transition cursor-pointer shadow-xs"
          >
            <ArrowLeftIcon class="w-4 h-4 stroke-[2]" />
            <span>Back</span>
          </button>
          <button 
            @click="currentStep = 4" 
            type="button" 
            class="px-6 py-2.5 bg-red-600 hover:bg-red-700 text-white text-sm font-semibold rounded-xl flex items-center gap-2 shadow-md shadow-red-600/20 transition cursor-pointer"
          >
            <span>Continue to Fields</span>
            <ArrowRightIcon class="w-4 h-4 stroke-[2]" />
          </button>
        </div>
      </div>

      <!-- STEP 4: Client Schema & Columns Inspector -->
      <div v-if="currentStep === 4" class="space-y-6 max-w-5xl mx-auto w-full">
        
        <!-- Step Header -->
        <div class="text-center space-y-2 max-w-2xl mx-auto">
          <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-red-50 border border-red-200 text-xs font-semibold text-red-700">
            <CircleStackIcon class="w-4 h-4 text-red-600" />
            <span>Step 4 of 6 • Customer Fields</span>
          </div>
          <h2 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">Configure Customer Record Fields</h2>
          <p class="text-slate-600 text-xs sm:text-sm font-normal leading-relaxed">
            Choose the default attributes to include on your client and lead profiles. You can also define custom fields anytime.
          </p>
        </div>

        <!-- Schema Inspector Meta Card -->
        <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-xs flex flex-col sm:flex-row sm:items-center justify-between gap-4">
          <div class="flex items-center gap-3.5">
            <div class="w-11 h-11 rounded-xl bg-red-50 border border-red-200 text-red-600 flex items-center justify-center text-xl shrink-0">
              📋
            </div>
            <div>
              <div class="flex items-center gap-2">
                <span class="text-xs font-mono uppercase tracking-wider text-red-600 font-semibold">Workspace Database</span>
                <span class="px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 text-xs font-mono border border-emerald-200 font-semibold">
                  {{ selectedColumnsCount }} Active Fields
                </span>
              </div>
              <p class="text-base font-bold text-slate-900 tracking-tight mt-0.5">{{ tenant?.name || 'Workspace' }} Client Profile</p>
              <p class="text-xs text-slate-500 font-mono">Workspace URL: <span class="text-red-600 font-semibold">{{ tenantSubdomainDisplay }}</span></p>
            </div>
          </div>

          <button
            type="button"
            @click="newColumnModalOpen = true"
            class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-semibold rounded-xl shadow-md shadow-red-600/20 flex items-center gap-1.5 transition cursor-pointer self-start sm:self-auto shrink-0"
          >
            <PlusIcon class="w-4 h-4 stroke-[2.5]" />
            <span>+ Add Custom Field</span>
          </button>
        </div>

        <!-- Columns Selection Table / Grid -->
        <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-xs space-y-4">
          <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <div class="flex items-center gap-2.5">
              <TableCellsIcon class="w-4 h-4 text-red-600" />
              <h3 class="font-bold text-sm text-slate-900">Lead & Client Record Fields</h3>
              <span class="px-2 py-0.5 bg-slate-100 text-slate-700 text-xs font-mono rounded border border-slate-200 font-semibold">{{ selectedColumnsCount }} enabled</span>
            </div>
            <span class="text-xs text-slate-500 hidden sm:inline">Select checkboxes to enable or disable fields on entry forms</span>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-3 max-h-[360px] overflow-y-auto pr-2 custom-scrollbar">
            <div
              v-for="(col, idx) in availableColumns"
              :key="col.key || idx"
              @click="toggleColumnVisibility(col)"
              :class="[
                'p-3.5 rounded-xl border transition-all duration-200 flex items-center justify-between gap-3 cursor-pointer group',
                col.is_visible !== false 
                  ? 'bg-slate-50/70 border-slate-200 hover:border-red-400 hover:bg-white' 
                  : 'bg-slate-100/50 border-slate-200 opacity-60 hover:opacity-90'
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
                    <span v-if="col.is_required" class="text-xs text-red-600 font-mono font-medium">*Required</span>
                  </div>
                  <div class="flex items-center gap-2 mt-0.5">
                    <span class="text-xs font-mono font-medium text-slate-700 px-1.5 py-0.5 bg-white rounded border border-slate-200">
                      {{ formatFieldType(col.type) }}
                    </span>
                    <span class="text-xs font-mono text-slate-400 truncate">`{{ col.key }}`</span>
                  </div>
                </div>
              </div>

              <div class="flex items-center gap-2 shrink-0">
                <span 
                  v-if="col.category === 'industry'" 
                  class="px-2 py-0.5 rounded-md text-xs font-mono bg-amber-50 text-amber-700 border border-amber-200 font-medium"
                >
                  Default
                </span>
                <span 
                  v-else-if="col.category === 'custom'" 
                  class="px-2 py-0.5 rounded-md text-xs font-mono bg-indigo-50 text-indigo-700 border border-indigo-200 font-medium"
                >
                  Custom
                </span>
                <button
                  v-if="col.category === 'custom'"
                  @click.stop="removeCustomColumn(idx)"
                  class="p-1 text-slate-400 hover:text-red-600 rounded-lg hover:bg-slate-100 transition"
                  title="Remove field"
                >
                  <TrashIcon class="w-3.5 h-3.5" />
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Add Custom Column Modal -->
        <div v-if="newColumnModalOpen" class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4 z-50">
          <div class="bg-white rounded-2xl border border-slate-200 p-6 max-w-md w-full shadow-2xl space-y-4 text-slate-900">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
              <div class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-lg bg-red-50 text-red-600 flex items-center justify-center font-bold text-base border border-red-200">
                  +
                </div>
                <h3 class="font-bold text-sm text-slate-900">Add Custom Field</h3>
              </div>
              <button @click="newColumnModalOpen = false" class="text-slate-400 hover:text-slate-700 text-lg font-bold">✕</button>
            </div>

            <div class="space-y-4 text-sm">
              <div class="space-y-1.5">
                <label class="block font-medium text-slate-700 text-sm">Field Label *</label>
                <input 
                  v-model="newCustomCol.label"
                  type="text" 
                  placeholder="e.g. Budget Range, License Number, Preferred Location..." 
                  class="w-full bg-white border border-slate-300 rounded-xl px-3.5 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:outline-none focus:border-red-500"
                />
              </div>

              <div class="space-y-1.5">
                <label class="block font-medium text-slate-700 text-sm">Field Type *</label>
                <select 
                  v-model="newCustomCol.type"
                  class="w-full bg-white border border-slate-300 rounded-xl px-3.5 py-2.5 text-sm text-slate-900 focus:outline-none focus:border-red-500"
                >
                  <option value="text">Short Text</option>
                  <option value="number">Number (Integer)</option>
                  <option value="currency">Currency Amount</option>
                  <option value="date">Date</option>
                  <option value="dropdown">Select Dropdown</option>
                  <option value="email">Email Address</option>
                  <option value="textarea">Long Text / Notes</option>
                  <option value="boolean">Yes / No</option>
                </select>
              </div>

              <div v-if="newCustomCol.type === 'dropdown'" class="space-y-1.5">
                <label class="block font-medium text-slate-700 text-sm">Dropdown Options (comma-separated)</label>
                <input 
                  v-model="newCustomCol.optionsStr"
                  type="text" 
                  placeholder="e.g. Bronze, Silver, Gold, Platinum" 
                  class="w-full bg-white border border-slate-300 rounded-xl px-3.5 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:outline-none focus:border-red-500"
                />
              </div>

              <label class="flex items-center gap-2 cursor-pointer pt-1">
                <input type="checkbox" v-model="newCustomCol.is_required" class="w-4 h-4 text-red-600 bg-white border-slate-300 rounded" />
                <span class="font-normal text-slate-700 text-sm">Require this field on record entry</span>
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
                Save Field
              </button>
            </div>
          </div>
        </div>

        <!-- Navigation Action Buttons -->
        <div class="flex items-center justify-between pt-6 border-t border-slate-200">
          <button 
            @click="currentStep = 3" 
            type="button" 
            class="px-5 py-2.5 bg-white hover:bg-slate-100 border border-slate-300 text-slate-700 text-xs font-semibold rounded-xl flex items-center gap-2 transition cursor-pointer shadow-xs"
          >
            <ArrowLeftIcon class="w-3.5 h-3.5 stroke-[2.5]" />
            <span>Back</span>
          </button>
          <button 
            @click="currentStep = 5" 
            type="button" 
            class="px-6 py-2.5 bg-red-600 hover:bg-red-700 text-white text-xs font-bold rounded-xl flex items-center gap-2 shadow-md shadow-red-600/20 transition cursor-pointer"
          >
            <span>Continue to Team Size</span>
            <ArrowRightIcon class="w-3.5 h-3.5 stroke-[2.5]" />
          </button>
        </div>
      </div>

      <!-- STEP 5: Select Organization Capacity -->
      <div v-if="currentStep === 5" class="space-y-6 max-w-4xl mx-auto w-full">
        <div class="text-center space-y-2 max-w-2xl mx-auto">
          <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-red-50 border border-red-200 text-xs font-semibold text-red-700">
            <UserGroupIcon class="w-4 h-4 text-red-600" />
            <span>Step 5 of 6 • Team Size</span>
          </div>
          <h2 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">How Large Is Your Team?</h2>
          <p class="text-slate-600 text-xs sm:text-sm font-normal">Select your team size to help us calibrate initial user quotas, permissions, and workspace defaults.</p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
          <div
            v-for="ts in teamSizes"
            :key="ts.id"
            @click="form.employee_range = ts.id"
            :class="[
              'p-5 rounded-2xl border transition-all duration-200 cursor-pointer flex flex-col justify-between space-y-3 shadow-xs bg-white',
              form.employee_range === ts.id 
                ? 'bg-red-50/50 border-red-500 ring-2 ring-red-500/20 shadow-sm' 
                : 'border-slate-200 hover:border-slate-300 hover:bg-slate-50/60'
            ]"
          >
            <div class="flex items-center justify-between">
              <span class="text-2xl">{{ ts.icon }}</span>
              <div :class="[
                'w-5 h-5 rounded-full border flex items-center justify-center transition-all', 
                form.employee_range === ts.id 
                  ? 'bg-red-600 border-red-600 text-white' 
                  : 'border-slate-300 bg-slate-100 text-transparent'
              ]">
                <CheckIcon class="w-3 h-3 stroke-[3]" />
              </div>
            </div>
            <div>
              <h4 class="font-bold text-sm text-slate-900">{{ ts.label }}</h4>
              <p class="text-xs text-slate-600 mt-1 leading-relaxed">{{ ts.desc }}</p>
            </div>
          </div>
        </div>

        <!-- Navigation Action Buttons -->
        <div class="flex items-center justify-between pt-6 border-t border-slate-200">
          <button 
            @click="currentStep = 4" 
            type="button" 
            class="px-5 py-2.5 bg-white hover:bg-slate-100 border border-slate-300 text-slate-700 text-xs font-semibold rounded-xl flex items-center gap-2 transition cursor-pointer shadow-xs"
          >
            <ArrowLeftIcon class="w-3.5 h-3.5 stroke-[2.5]" />
            <span>Back</span>
          </button>
          <button 
            @click="currentStep = 6" 
            type="button" 
            class="px-6 py-2.5 bg-red-600 hover:bg-red-700 text-white text-xs font-bold rounded-xl flex items-center gap-2 shadow-md shadow-red-600/20 transition cursor-pointer"
          >
            <span>Continue to Goals</span>
            <ArrowRightIcon class="w-3.5 h-3.5 stroke-[2.5]" />
          </button>
        </div>
      </div>

      <!-- STEP 6: Select Primary Objectives & Provision Database -->
      <div v-if="currentStep === 6" class="space-y-6 max-w-4xl mx-auto w-full">
        <div class="text-center space-y-2 max-w-2xl mx-auto">
          <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-red-50 border border-red-200 text-xs font-semibold text-red-700">
            <SparklesIcon class="w-4 h-4 text-red-600" />
            <span>Step 6 of 6 • Workspace Goals</span>
          </div>
          <h2 class="text-2xl sm:text-3xl font-extrabold text-slate-900 tracking-tight">What Are Your Primary Objectives?</h2>
          <p class="text-slate-600 text-xs sm:text-sm font-normal">Select the modules and tools you plan to use most. We'll configure your dashboard and primary shortcuts accordingly.</p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
          <div
            v-for="goal in crmGoalsList"
            :key="goal.id"
            @click="toggleGoal(goal.id)"
            :class="[
              'p-4 rounded-2xl border transition-all duration-200 cursor-pointer flex items-center justify-between gap-3 shadow-xs bg-white',
              form.crm_goals.includes(goal.id) 
                ? 'bg-red-50/50 border-red-500 ring-2 ring-red-500/20 shadow-sm' 
                : 'border-slate-200 hover:border-slate-300 hover:bg-slate-50/60'
            ]"
          >
            <div class="flex items-center gap-3">
              <div class="w-9 h-9 rounded-xl bg-slate-50 border border-slate-200 flex items-center justify-center text-lg shrink-0">
                {{ goal.icon }}
              </div>
              <div>
                <h4 class="font-bold text-sm text-slate-900">{{ goal.label }}</h4>
                <p class="text-xs text-slate-600 mt-0.5 leading-relaxed">{{ goal.desc }}</p>
              </div>
            </div>
            <div :class="[
              'w-5 h-5 rounded-full border flex items-center justify-center shrink-0 transition-all', 
              form.crm_goals.includes(goal.id) 
                ? 'bg-red-600 border-red-600 text-white' 
                : 'border-slate-300 bg-slate-100 text-transparent'
            ]">
              <CheckIcon class="w-3 h-3 stroke-[3]" />
            </div>
          </div>
        </div>

        <!-- Launch Button & Back -->
        <div class="flex items-center justify-between pt-6 border-t border-slate-200">
          <button 
            @click="currentStep = 5" 
            type="button" 
            class="px-5 py-2.5 bg-white hover:bg-slate-100 border border-slate-300 text-slate-700 text-sm font-medium rounded-xl flex items-center gap-2 transition cursor-pointer shadow-xs"
          >
            <ArrowLeftIcon class="w-4 h-4 stroke-[2]" />
            <span>Back</span>
          </button>

          <button 
            @click="completeOnboarding"
            :disabled="form.processing"
            class="px-7 py-3 bg-red-600 hover:bg-red-700 text-white text-sm font-semibold rounded-xl shadow-lg shadow-red-600/30 flex items-center gap-2.5 transition cursor-pointer disabled:opacity-50"
          >
            <RocketLaunchIcon class="w-4 h-4 stroke-[2.5]" />
            <span>Complete Setup & Launch CRM</span>
          </button>
        </div>
      </div>

    </main>

    <!-- Footer Status -->
    <footer class="max-w-6xl mx-auto w-full px-4 sm:px-6 py-4 text-center text-xs text-slate-500 font-mono z-10">
      JRV CRM • Enterprise Multi-Tenant Architecture
    </footer>

    <!-- Provisioning Progress Terminal/Modal -->
    <div v-if="isProvisioning" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-md">
      <div class="bg-white border border-slate-200 text-slate-900 rounded-3xl max-w-md w-full p-8 shadow-2xl space-y-6 text-center">
        <div class="w-16 h-16 rounded-2xl bg-red-50 border border-red-200 text-red-600 flex items-center justify-center mx-auto text-2xl font-bold">
          <RocketLaunchIcon class="w-8 h-8 animate-pulse text-red-600" />
        </div>

        <div class="space-y-1.5">
          <h3 class="text-xl font-bold text-slate-900 tracking-tight">Setting Up Your CRM Workspace</h3>
          <p class="text-xs text-slate-500">Preparing dedicated environment for <span class="text-red-600 font-semibold">{{ tenant?.name || 'Your Company' }}</span>...</p>
        </div>

        <!-- 4 Step Provisioning Checklist -->
        <div class="space-y-3 text-left text-xs bg-slate-50 p-4 rounded-2xl border border-slate-200">
          <div class="flex items-center gap-2.5" :class="provisioningStep >= 1 ? 'text-emerald-700 font-medium' : 'text-slate-400'">
            <CheckCircleIcon v-if="provisioningStep >= 1" class="w-4 h-4 text-emerald-600 shrink-0" />
            <div v-else class="w-4 h-4 rounded-full border border-slate-300 shrink-0"></div>
            <span>Configuring company subdomain: <strong class="font-mono text-slate-800">{{ tenantSubdomainDisplay }}</strong></span>
          </div>

          <div class="flex items-center gap-2.5" :class="provisioningStep >= 2 ? 'text-emerald-700 font-medium' : 'text-slate-400'">
            <CheckCircleIcon v-if="provisioningStep >= 2" class="w-4 h-4 text-emerald-600 shrink-0" />
            <div v-else class="w-4 h-4 rounded-full border border-slate-300 shrink-0"></div>
            <span>Creating dedicated database and security schema</span>
          </div>

          <div class="flex items-center gap-2.5" :class="provisioningStep >= 3 ? 'text-emerald-700 font-medium' : 'text-slate-400'">
            <CheckCircleIcon v-if="provisioningStep >= 3" class="w-4 h-4 text-emerald-600 shrink-0" />
            <div v-else class="w-4 h-4 rounded-full border border-slate-300 shrink-0"></div>
            <span>Configuring pipeline stages and customer fields</span>
          </div>

          <div class="flex items-center gap-2.5" :class="provisioningStep >= 4 ? 'text-emerald-700 font-medium' : 'text-slate-400'">
            <CheckCircleIcon v-if="provisioningStep >= 4" class="w-4 h-4 text-emerald-600 shrink-0" />
            <div v-else class="w-4 h-4 rounded-full border border-slate-300 shrink-0"></div>
            <span>Finalizing workspace and launching your dashboard...</span>
          </div>
        </div>

        <div class="flex items-center justify-center gap-2 text-xs text-slate-500">
          <div class="w-3.5 h-3.5 border-2 border-red-500 border-t-transparent rounded-full animate-spin"></div>
          <span>This usually takes just a few seconds...</span>
        </div>
      </div>
    </div>

    <!-- Dedicated Database Deployment Progress Modal -->
    <div v-if="isUploadingDb" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-md">
      <div class="bg-white border border-slate-200 text-slate-900 rounded-3xl max-w-md w-full p-8 shadow-2xl space-y-6 text-center">
        <div class="w-16 h-16 rounded-2xl bg-red-50 border border-red-200 text-red-600 flex items-center justify-center mx-auto text-2xl font-bold">
          <CircleStackIcon class="w-8 h-8 animate-pulse text-red-600" />
        </div>

        <div class="space-y-1.5">
          <h3 class="text-xl font-bold text-slate-900 tracking-tight">Deploying Database Into Your CRM</h3>
          <p class="text-xs text-slate-500">
            Reading schema from <span class="font-mono text-slate-700 font-semibold">{{ selectedDbFile?.name }}</span> and configuring workspace...
          </p>
        </div>

        <!-- 5 Step Live Deployment Checklist -->
        <div class="space-y-3 text-left text-xs bg-slate-50 p-4 rounded-2xl border border-slate-200">
          <div
            v-for="(stepText, sIdx) in deployStepsList"
            :key="sIdx"
            class="flex items-center gap-2.5"
            :class="uploadDbStep > sIdx ? 'text-emerald-700 font-medium' : 'text-slate-400'"
          >
            <CheckCircleIcon v-if="uploadDbStep > sIdx" class="w-4 h-4 text-emerald-600 shrink-0" />
            <div v-else-if="uploadDbStep === sIdx + 1" class="w-4 h-4 border-2 border-red-500 border-t-transparent rounded-full animate-spin shrink-0"></div>
            <div v-else class="w-4 h-4 rounded-full border border-slate-300 shrink-0"></div>
            <span>{{ stepText }}</span>
          </div>
        </div>

        <div v-if="uploadSuccessMessage" class="p-3 bg-emerald-50 border border-emerald-200 rounded-xl text-xs font-semibold text-emerald-700">
          {{ uploadSuccessMessage }}
        </div>
        <div v-else class="flex items-center justify-center gap-2 text-xs text-slate-500">
          <div class="w-3.5 h-3.5 border-2 border-red-500 border-t-transparent rounded-full animate-spin"></div>
          <span>Configuring CRM and displaying your database...</span>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
  width: 5px;
}
.custom-scrollbar::-webkit-scrollbar-track {
  background: rgba(241, 245, 249, 0.8);
  border-radius: 8px;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
  background: rgba(203, 213, 225, 0.8);
  border-radius: 8px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
  background: rgba(148, 163, 184, 0.9);
}
</style>
