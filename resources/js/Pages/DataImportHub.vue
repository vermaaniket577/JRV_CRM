<script setup>
import { ref, computed, watch } from 'vue';
import { Head, Link, useForm, router, usePage } from '@inertiajs/vue3';
import Navbar from '@/Components/Navbar.vue';
import GlobalSearchModal from '@/Components/GlobalSearchModal.vue';
import { 
  ArrowUpTrayIcon,
  CircleStackIcon,
  DocumentArrowDownIcon,
  TableCellsIcon,
  CheckCircleIcon,
  ExclamationCircleIcon,
  ClockIcon,
  SparklesIcon,
  ArrowPathIcon,
  ShieldCheckIcon,
  DocumentTextIcon,
  UserGroupIcon,
  BuildingOffice2Icon,
  HeartIcon,
  CloudArrowUpIcon,
  CommandLineIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
  logs: {
    type: Array,
    default: () => [],
  },
  stats: {
    type: Object,
    default: () => ({}),
  },
  industryConfig: {
    type: Object,
    default: () => ({
      slug: 'insurance',
      name: 'Insurance',
      icon: '🛡️',
      color: 'teal',
      banner_title: 'Insurance CRM Data Import Hub',
      banner_desc: 'Import policyholders, claims, insurance brokers, underwriting files, and coverage records from Excel spreadsheets and database dumps.',
      stats_labels: {
        candidates: { title: 'Underwriters & Adjusters', desc: 'Claims adjusters & brokers in pipeline' },
        vacancies: { title: 'Insurance Vacancies', desc: 'Active insurance & agency roles' },
        contacts: { title: 'Policyholders & Leads', desc: 'Active insured member database' },
      },
      entities: [
        { id: 'policyholders', name: '🛡️ Policyholders & Insured Members', desc: 'Policy #, insured name, coverage type, premium, expiry date' },
        { id: 'claims', name: '📋 Claims & Underwriting Applications', desc: 'Claim ID, policy ref, incident date, claim amount, stage' },
        { id: 'insurance_vacancies', name: '💼 Insurance Careers & Openings', desc: 'Job title, underwriting dept, branch, salary' },
        { id: 'agents', name: '👥 Insurance Agents & Brokers', desc: 'Agent code, license #, branch, commission rate' },
        { id: 'crm_records', name: '📊 Custom Dynamic Policy Records', desc: 'Dynamic JSON key-value pairs matching custom columns' },
      ],
    }),
  },
});

const page = usePage();
const isSearchOpen = ref(false);
const activeTab = ref('excel'); // 'excel' | 'database' | 'logs'

// Dynamic industry metadata
const currentIndustry = computed(() => {
  if (props.industryConfig && props.industryConfig.entities && props.industryConfig.entities.length > 0) {
    return props.industryConfig;
  }
  const tenantInd = page.props.tenant_industry;
  const indName = tenantInd?.name || 'Insurance';
  const indIcon = tenantInd?.icon || '🛡️';
  return {
    name: indName,
    icon: indIcon,
    banner_title: `${indName} CRM Data Import Hub`,
    banner_desc: `Import ${indName} records, client profiles, policy files, and job openings from spreadsheets and databases.`,
    stats_labels: {
      candidates: { title: `${indName} Applicants`, desc: 'Candidates & staff in pipeline' },
      vacancies: { title: `${indName} Vacancies`, desc: 'Active openings & roles' },
      contacts: { title: `${indName} Client Database`, desc: 'Active contacts & accounts' },
    },
    entities: [
      { id: 'contacts', name: `👥 ${indName} Client Profiles & Leads`, desc: 'Name, email, phone, company, status, notes' },
      { id: 'vacancies', name: `💼 Careers & Openings`, desc: 'Job title, department, location, salary' },
      { id: 'candidates', name: `📋 Job Candidates & Resumes`, desc: 'Applicant name, email, phone, stage, experience' },
      { id: 'crm_records', name: `📊 Custom Dynamic CRM Records`, desc: 'Dynamic JSON key-value pairs' },
    ],
  };
});

const availableEntities = computed(() => currentIndustry.value.entities || []);

// Excel / File Import Form
const excelForm = useForm({
  file: null,
  entity_type: availableEntities.value[0]?.id || 'policyholders',
});

// Sync default entity type if industry changes
watch(availableEntities, (newEntities) => {
  if (newEntities && newEntities.length > 0) {
    const exists = newEntities.some(e => e.id === excelForm.entity_type);
    if (!exists) {
      excelForm.entity_type = newEntities[0].id;
    }
  }
}, { immediate: true });

const selectedFileName = ref('');
const isDragging = ref(false);

const onFileChange = (e) => {
  const file = e.target.files[0];
  if (file) {
    excelForm.file = file;
    selectedFileName.value = file.name;
  }
};

const handleDrop = (e) => {
  isDragging.value = false;
  const file = e.dataTransfer.files[0];
  if (file) {
    excelForm.file = file;
    selectedFileName.value = file.name;
  }
};

const submitExcelImport = () => {
  if (!excelForm.file) return;
  excelForm.post('/data-import/excel', {
    preserveScroll: true,
    onSuccess: () => {
      excelForm.reset('file');
      selectedFileName.value = '';
    }
  });
};

// Database Import Form
const dbForm = useForm({
  driver: 'mysql',
  host: '127.0.0.1',
  port: 3306,
  database: '',
  username: 'root',
  password: '',
  source_table: '',
  target_entity: availableEntities.value[0]?.id || 'policyholders',
  dump_file: null,
});

const isTestingDb = ref(false);
const dbTestResult = ref(null);
const availableTables = ref([]);
const isLiveServerExpanded = ref(false);

const testDbConnection = async () => {
  isTestingDb.value = true;
  dbTestResult.value = null;
  try {
    const response = await fetch('/data-import/database-test', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
        'Accept': 'application/json'
      },
      body: JSON.stringify({
        host: dbForm.host,
        port: dbForm.port,
        database: dbForm.database,
        username: dbForm.username,
        password: dbForm.password,
      })
    });
    
    const data = await response.json();
    dbTestResult.value = data;
    if (data.success && Array.isArray(data.tables)) {
      availableTables.value = data.tables;
      if (data.tables.length > 0 && !dbForm.source_table) {
        dbForm.source_table = data.tables[0];
      }
    }
  } catch (err) {
    dbTestResult.value = {
      success: false,
      message: 'Failed to communicate with the server: ' + err.message
    };
  } finally {
    isTestingDb.value = false;
  }
};

const submitDbImport = () => {
  dbForm.post('/data-import/database-sync', {
    preserveScroll: true,
  });
};

// 1-Click Sector Dynamic Demo Seeder
const isSeeding = ref(false);
const triggerDemoSeed = () => {
  isSeeding.value = true;
  router.post('/data-import/demo-seed', {}, {
    preserveScroll: true,
    onFinish: () => { isSeeding.value = false; }
  });
};
</script>

<template>
  <Head :title="`${currentIndustry.name} Data Import Hub - JRV CRM`" />

  <div class="min-h-screen bg-slate-50 flex font-sans text-slate-900">
    <!-- Navbar (Fixed Left Vertical Sidebar) -->
    <Navbar @open-search="isSearchOpen = true" />

    <!-- Main Content Area -->
    <div class="flex-1 flex flex-col min-w-0">
      <!-- Top Bar -->
      <header class="bg-white/95 backdrop-blur-md border-b border-slate-200 px-6 py-3.5 flex items-center justify-between gap-3 sticky top-0 z-30 shadow-xs">
        <div class="flex items-center gap-3">
          <div class="flex items-center gap-2 px-3 py-1 bg-emerald-50 border border-emerald-200/80 rounded-full">
            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
            <span class="text-[11px] font-bold text-emerald-800 tracking-wide uppercase">{{ currentIndustry.name }} Data Import Hub</span>
          </div>
          <Link href="/staff-recruitment" class="hidden sm:inline-flex px-3.5 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs rounded-xl shadow-xs items-center gap-1.5 transition-all">
            <span class="text-xs">{{ currentIndustry.icon }}</span>
            <span>{{ currentIndustry.name }} Staff & Agency Portal</span>
          </Link>
        </div>

        <div class="flex items-center gap-3">
          <div class="flex items-center gap-2 bg-slate-100 py-1 px-3 rounded-full border border-slate-200 text-xs font-semibold text-slate-600">
            <ShieldCheckIcon class="w-4 h-4 text-emerald-600" />
            <span>Multi-Format Sanitized Importer</span>
          </div>
          <div class="w-9 h-9 bg-gradient-to-tr from-emerald-600 to-teal-500 rounded-full flex items-center justify-center text-white font-black text-sm shadow-md ring-2 ring-emerald-100">
            {{ currentIndustry.icon }}
          </div>
        </div>
      </header>

      <div class="p-6 md:p-8 space-y-7 flex-1 overflow-y-auto max-w-7xl mx-auto w-full">
        <!-- Hero Header (Sector Dynamic) -->
        <div class="bg-gradient-to-r from-slate-950 via-slate-900 to-slate-950 border border-slate-800 rounded-3xl p-6 md:p-8 text-white shadow-xl relative overflow-hidden">
          <div class="absolute -right-10 -bottom-10 w-72 h-72 bg-teal-500/15 rounded-full blur-3xl pointer-events-none"></div>
          <div class="absolute -left-10 -top-10 w-64 h-64 bg-emerald-500/15 rounded-full blur-2xl pointer-events-none"></div>

          <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div class="space-y-2 max-w-2xl">
              <div class="inline-flex items-center gap-2 px-2.5 py-0.5 rounded-md bg-teal-500/20 border border-teal-400/30 text-teal-300 text-[11px] font-bold tracking-wider uppercase">
                <TableCellsIcon class="w-3.5 h-3.5 text-teal-400" />
                <span>{{ currentIndustry.name }} Sector Engine</span>
              </div>
              <h1 class="text-2xl md:text-3xl font-black tracking-tight text-white drop-shadow-xs flex items-center gap-2.5">
                <span>{{ currentIndustry.icon }}</span>
                <span>{{ currentIndustry.banner_title }}</span>
              </h1>
              <p class="text-xs md:text-sm text-slate-200 font-normal leading-relaxed">
                {{ currentIndustry.banner_desc }}
              </p>
            </div>

            <!-- Fast Demo Action -->
            <div class="flex items-center gap-3">
              <button 
                @click="triggerDemoSeed" 
                :disabled="isSeeding"
                class="px-5 py-2.5 bg-gradient-to-r from-emerald-500 to-teal-500 hover:from-emerald-600 hover:to-teal-600 text-white font-bold text-xs rounded-2xl shadow-lg shadow-emerald-900/40 flex items-center gap-2 transition-all transform hover:-translate-y-0.5 disabled:opacity-50 cursor-pointer"
              >
                <SparklesIcon class="w-4 h-4" />
                <span>{{ isSeeding ? 'Importing Demo...' : `1-Click ${currentIndustry.name} Demo Import` }}</span>
              </button>
            </div>
          </div>
        </div>

        <!-- Metric Stat Counters (Sector Dynamic) -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
          <div class="p-5 bg-white rounded-2xl border border-slate-200 shadow-xs space-y-2">
            <div class="flex items-center justify-between">
              <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Total Imports Run</span>
              <ArrowUpTrayIcon class="w-4 h-4 text-emerald-600" />
            </div>
            <div class="text-3xl font-black text-slate-900">{{ stats?.total_imports || 0 }}</div>
            <p class="text-[11px] text-slate-500">Completed batches logged</p>
          </div>

          <div class="p-5 bg-white rounded-2xl border border-slate-200 shadow-xs space-y-2">
            <div class="flex items-center justify-between">
              <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">{{ currentIndustry.stats_labels?.candidates?.title || 'Candidates & Staff' }}</span>
              <span class="text-sm">{{ currentIndustry.icon }}</span>
            </div>
            <div class="text-3xl font-black text-teal-600">{{ stats?.total_candidates || 0 }}</div>
            <p class="text-[11px] text-slate-500">{{ currentIndustry.stats_labels?.candidates?.desc || 'Records in pipeline' }}</p>
          </div>

          <div class="p-5 bg-white rounded-2xl border border-slate-200 shadow-xs space-y-2">
            <div class="flex items-center justify-between">
              <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">{{ currentIndustry.stats_labels?.vacancies?.title || 'Open Vacancies' }}</span>
              <BuildingOffice2Icon class="w-4 h-4 text-indigo-600" />
            </div>
            <div class="text-3xl font-black text-indigo-600">{{ stats?.total_vacancies || 0 }}</div>
            <p class="text-[11px] text-slate-500">{{ currentIndustry.stats_labels?.vacancies?.desc || 'Active opportunities' }}</p>
          </div>

          <div class="p-5 bg-white rounded-2xl border border-slate-200 shadow-xs space-y-2">
            <div class="flex items-center justify-between">
              <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">{{ currentIndustry.stats_labels?.contacts?.title || 'Client Database' }}</span>
              <UserGroupIcon class="w-4 h-4 text-cyan-600" />
            </div>
            <div class="text-3xl font-black text-cyan-600">{{ stats?.total_contacts || 0 }}</div>
            <p class="text-[11px] text-slate-500">{{ currentIndustry.stats_labels?.contacts?.desc || 'Active database accounts' }}</p>
          </div>
        </div>

        <!-- Tab Navigation Bar -->
        <div class="flex items-center gap-2 border-b border-slate-200 pb-2">
          <button 
            @click="activeTab = 'excel'" 
            :class="[
              'px-5 py-2.5 text-xs font-black rounded-xl flex items-center gap-2 transition-all cursor-pointer',
              activeTab === 'excel' ? 'bg-emerald-600 text-white shadow-md' : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-200'
            ]"
          >
            <TableCellsIcon class="w-4 h-4" />
            <span>1. Import from Excel / CSV</span>
          </button>

          <button 
            @click="activeTab = 'database'" 
            :class="[
              'px-5 py-2.5 text-xs font-black rounded-xl flex items-center gap-2 transition-all cursor-pointer',
              activeTab === 'database' ? 'bg-emerald-600 text-white shadow-md' : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-200'
            ]"
          >
            <CircleStackIcon class="w-4 h-4" />
            <span>2. Import from External Database (SQL)</span>
          </button>

          <button 
            @click="activeTab = 'logs'" 
            :class="[
              'px-5 py-2.5 text-xs font-black rounded-xl flex items-center gap-2 transition-all cursor-pointer',
              activeTab === 'logs' ? 'bg-emerald-600 text-white shadow-md' : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-200'
            ]"
          >
            <ClockIcon class="w-4 h-4" />
            <span>3. Import Audit History</span>
          </button>
        </div>

        <!-- TAB 1: UPLOAD DATA FROM EXCEL SHEET (Sector Dynamic) -->
        <div v-if="activeTab === 'excel'" class="bg-white border border-slate-200 rounded-3xl p-6 md:p-8 shadow-xs space-y-6 max-w-4xl mx-auto">
          <div class="pb-4 border-b border-slate-100 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
              <h2 class="text-lg font-black text-slate-900 flex items-center gap-2">
                <TableCellsIcon class="w-5 h-5 text-emerald-600" />
                <span>Upload {{ currentIndustry.name }} Data from Excel Sheet</span>
              </h2>
              <p class="text-xs text-slate-500 mt-1">
                Upload your Excel spreadsheet (.xlsx, .xls) or CSV file to import {{ currentIndustry.name.toLowerCase() }} records directly into your CRM.
              </p>
            </div>

            <!-- Quick Template Download Button -->
            <a 
              :href="`/data-import/sample/${excelForm.entity_type}`" 
              class="px-3.5 py-2 rounded-xl border border-emerald-200 bg-emerald-50/70 hover:bg-emerald-100/80 text-emerald-800 text-xs font-bold transition flex items-center gap-2 cursor-pointer shrink-0"
              title="Download pre-formatted Excel template"
            >
              <DocumentArrowDownIcon class="w-4 h-4 text-emerald-600" />
              <span>Download Sample Template</span>
            </a>
          </div>

          <form @submit.prevent="submitExcelImport" class="space-y-6">
            <!-- Dynamic Sector Target Destination Module Selection -->
            <div>
              <label class="text-xs font-bold text-slate-700 block mb-2">Select Target CRM Destination</label>
              <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                <label 
                  v-for="entity in availableEntities"
                  :key="entity.id"
                  :class="[
                    'flex items-center gap-3 p-3.5 rounded-2xl border cursor-pointer transition-all',
                    excelForm.entity_type === entity.id 
                      ? 'border-emerald-600 bg-emerald-50/60 ring-2 ring-emerald-500/20 shadow-2xs' 
                      : 'border-slate-200 hover:border-slate-300 bg-white'
                  ]"
                >
                  <input type="radio" v-model="excelForm.entity_type" :value="entity.id" class="sr-only" />
                  <span class="text-2xl shrink-0">{{ entity.icon || currentIndustry.icon }}</span>
                  <div class="min-w-0">
                    <div class="text-xs font-extrabold text-slate-900 leading-tight">{{ entity.name.replace(/^[\p{Emoji}\s]+/u, '') }}</div>
                    <div class="text-[10px] text-slate-500 line-clamp-1 mt-0.5">{{ entity.desc }}</div>
                  </div>
                </label>
              </div>
            </div>

            <!-- Drag & Drop Upload Zone -->
            <div 
              @dragover.prevent="isDragging = true"
              @dragleave.prevent="isDragging = false"
              @drop.prevent="handleDrop"
              :class="[
                'border-2 border-dashed rounded-3xl p-8 md:p-12 text-center transition-all flex flex-col items-center justify-center gap-3 relative',
                isDragging ? 'border-emerald-600 bg-emerald-50/40 scale-[1.005]' : 'border-slate-300 bg-slate-50/40 hover:bg-slate-50/70'
              ]"
            >
              <input 
                type="file" 
                id="excelFileInput"
                @change="onFileChange" 
                accept=".xlsx,.xls,.csv,.tsv,.json" 
                class="sr-only" 
              />

              <div class="w-16 h-16 rounded-2xl bg-emerald-100/70 text-emerald-700 flex items-center justify-center text-3xl shadow-xs">
                📊
              </div>

              <div class="space-y-1">
                <h3 class="text-sm font-extrabold text-slate-800">
                  {{ selectedFileName ? selectedFileName : 'Drag & Drop your spreadsheet here' }}
                </h3>
                <p class="text-xs text-slate-500">
                  {{ selectedFileName ? 'File selected and ready to import.' : 'Supports Excel (.xlsx, .xls), CSV, TSV, and JSON formats up to 20MB' }}
                </p>
              </div>

              <label 
                for="excelFileInput" 
                class="mt-2 px-4 py-2 bg-white border border-slate-300 hover:border-slate-400 text-slate-700 text-xs font-bold rounded-xl shadow-2xs transition cursor-pointer"
              >
                {{ selectedFileName ? 'Change Selected File' : 'Browse Files on Computer' }}
              </label>
            </div>

            <!-- Submit Button -->
            <div class="flex items-center justify-end gap-3 pt-2 border-t border-slate-100">
              <button 
                type="submit" 
                :disabled="excelForm.processing || !excelForm.file"
                class="px-7 py-3 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 text-white font-extrabold text-xs rounded-2xl shadow-md transition-all flex items-center gap-2 disabled:opacity-40 cursor-pointer"
              >
                <CloudArrowUpIcon class="w-4 h-4" />
                <span>{{ excelForm.processing ? 'Importing File...' : 'Start Universal Import' }}</span>
              </button>
            </div>
          </form>
        </div>

        <!-- TAB 2: IMPORT FROM EXTERNAL DATABASE (SQL) -->
        <div v-if="activeTab === 'database'" class="bg-white border border-slate-200 rounded-3xl p-6 md:p-8 shadow-xs space-y-6 max-w-4xl mx-auto">
          <div class="pb-4 border-b border-slate-100 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
              <h2 class="text-lg font-black text-slate-900 flex items-center gap-2">
                <CircleStackIcon class="w-5 h-5 text-emerald-600" />
                <span>Import {{ currentIndustry.name }} Data from Database File / SQL Dump</span>
              </h2>
              <p class="text-xs text-slate-500 mt-1">
                Upload a MySQL dump file (.sql), SQLite database (.sqlite, .db), or JSON file to sync records into your CRM.
              </p>
            </div>
          </div>

          <!-- Section A: Direct Database File Upload -->
          <form @submit.prevent="submitExcelImport" class="space-y-6">
            <div>
              <label class="text-xs font-bold text-slate-700 block mb-2">Target CRM Entity Destination</label>
              <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                <label 
                  v-for="entity in availableEntities"
                  :key="entity.id"
                  :class="[
                    'flex items-center gap-3 p-3.5 rounded-2xl border cursor-pointer transition-all',
                    excelForm.entity_type === entity.id 
                      ? 'border-emerald-600 bg-emerald-50/60 ring-2 ring-emerald-500/20' 
                      : 'border-slate-200 hover:border-slate-300 bg-white'
                  ]"
                >
                  <input type="radio" v-model="excelForm.entity_type" :value="entity.id" class="sr-only" />
                  <span class="text-2xl shrink-0">{{ entity.icon || currentIndustry.icon }}</span>
                  <div class="min-w-0">
                    <div class="text-xs font-extrabold text-slate-900 leading-tight">{{ entity.name.replace(/^[\p{Emoji}\s]+/u, '') }}</div>
                    <div class="text-[10px] text-slate-500 line-clamp-1 mt-0.5">{{ entity.desc }}</div>
                  </div>
                </label>
              </div>
            </div>

            <!-- Drag & Drop Zone for SQL / SQLite / Dump -->
            <div 
              @dragover.prevent="isDragging = true"
              @dragleave.prevent="isDragging = false"
              @drop.prevent="handleDrop"
              :class="[
                'border-2 border-dashed rounded-3xl p-8 md:p-12 text-center transition-all flex flex-col items-center justify-center gap-3 relative',
                isDragging ? 'border-emerald-600 bg-emerald-50/40 scale-[1.005]' : 'border-slate-300 bg-slate-50/40 hover:bg-slate-50/70'
              ]"
            >
              <input 
                type="file" 
                id="sqlFileInput"
                @change="onFileChange" 
                accept=".sql,.dump,.sqlite,.sqlite3,.db,.json" 
                class="sr-only" 
              />

              <div class="w-16 h-16 rounded-2xl bg-teal-100/70 text-teal-700 flex items-center justify-center text-3xl shadow-xs">
                🗄️
              </div>

              <div class="space-y-1">
                <h3 class="text-sm font-extrabold text-slate-800">
                  {{ selectedFileName ? selectedFileName : 'Drag & Drop your SQL Dump or Database file here' }}
                </h3>
                <p class="text-xs text-slate-500">
                  {{ selectedFileName ? 'Database file selected and ready for extraction.' : 'Supports MySQL Dumps (.sql), SQLite (.sqlite, .db), and JSON Database Dumps up to 20MB' }}
                </p>
              </div>

              <label 
                for="sqlFileInput" 
                class="mt-2 px-4 py-2 bg-white border border-slate-300 hover:border-slate-400 text-slate-700 text-xs font-bold rounded-xl shadow-2xs transition cursor-pointer"
              >
                {{ selectedFileName ? 'Change Selected File' : 'Browse Database File' }}
              </label>
            </div>

            <div class="flex items-center justify-end gap-3 pt-2 border-t border-slate-100">
              <button 
                type="submit" 
                :disabled="excelForm.processing || !excelForm.file"
                class="px-7 py-3 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 text-white font-extrabold text-xs rounded-2xl shadow-md transition-all flex items-center gap-2 disabled:opacity-40 cursor-pointer"
              >
                <CircleStackIcon class="w-4 h-4" />
                <span>{{ excelForm.processing ? 'Importing Database File...' : 'Import Database File Records' }}</span>
              </button>
            </div>
          </form>

          <!-- Section B: Optional Live Remote Server Sync (Collapsible) -->
          <div class="pt-4 border-t border-slate-200">
            <button 
              type="button" 
              @click="isLiveServerExpanded = !isLiveServerExpanded" 
              class="w-full py-2.5 px-4 bg-slate-50 hover:bg-slate-100 border border-slate-200 rounded-2xl flex items-center justify-between text-xs font-bold text-slate-700 transition cursor-pointer"
            >
              <div class="flex items-center gap-2">
                <CommandLineIcon class="w-4 h-4 text-slate-500" />
                <span>Or connect to a live remote MySQL Server (Optional)</span>
              </div>
              <span class="text-xs text-slate-400">{{ isLiveServerExpanded ? '▲ Hide' : '▼ Expand' }}</span>
            </button>

            <div v-if="isLiveServerExpanded" class="mt-4 p-5 rounded-2xl bg-slate-50 border border-slate-200 space-y-4">
              <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3">
                <div>
                  <label class="text-[11px] font-bold text-slate-600 block mb-1">Host</label>
                  <input type="text" v-model="dbForm.host" class="w-full bg-white border border-slate-300 rounded-xl px-3 py-2 text-xs" />
                </div>
                <div>
                  <label class="text-[11px] font-bold text-slate-600 block mb-1">Port</label>
                  <input type="number" v-model="dbForm.port" class="w-full bg-white border border-slate-300 rounded-xl px-3 py-2 text-xs" />
                </div>
                <div>
                  <label class="text-[11px] font-bold text-slate-600 block mb-1">Database Name</label>
                  <input type="text" v-model="dbForm.database" placeholder="e.g. jrv_insurance" class="w-full bg-white border border-slate-300 rounded-xl px-3 py-2 text-xs" />
                </div>
                <div>
                  <label class="text-[11px] font-bold text-slate-600 block mb-1">Username</label>
                  <input type="text" v-model="dbForm.username" class="w-full bg-white border border-slate-300 rounded-xl px-3 py-2 text-xs" />
                </div>
              </div>

              <div class="flex items-center justify-between pt-2">
                <button 
                  type="button" 
                  @click="testDbConnection" 
                  :disabled="isTestingDb || !dbForm.database"
                  class="px-4 py-2 bg-slate-800 hover:bg-slate-900 text-white text-xs font-bold rounded-xl transition cursor-pointer disabled:opacity-50"
                >
                  {{ isTestingDb ? 'Testing Connection...' : 'Test Connection' }}
                </button>

                <span v-if="dbTestResult" :class="['text-xs font-bold', dbTestResult.success ? 'text-emerald-600' : 'text-rose-600']">
                  {{ dbTestResult.message }}
                </span>
              </div>
            </div>
          </div>
        </div>

        <!-- TAB 3: AUDIT HISTORY -->
        <div v-if="activeTab === 'logs'" class="bg-white border border-slate-200 rounded-3xl p-6 md:p-8 shadow-xs space-y-4">
          <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <h2 class="text-base font-black text-slate-900 flex items-center gap-2">
              <ClockIcon class="w-5 h-5 text-emerald-600" />
              <span>Import Audit Trail & Logs</span>
            </h2>
            <span class="text-xs text-slate-500">Last 20 operations</span>
          </div>

          <div v-if="!logs || logs.length === 0" class="p-8 text-center text-xs text-slate-400">
            No imports run yet. Upload a file above to begin.
          </div>

          <div v-else class="overflow-x-auto">
            <table class="w-full text-left text-xs">
              <thead>
                <tr class="border-b border-slate-200 text-slate-400 font-bold uppercase text-[10px]">
                  <th class="pb-3">Source & File</th>
                  <th class="pb-3">Target Entity</th>
                  <th class="pb-3">Rows Imported</th>
                  <th class="pb-3">Status</th>
                  <th class="pb-3 text-right">Timestamp</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100 text-slate-700">
                <tr v-for="log in logs" :key="log.id" class="hover:bg-slate-50/60 transition">
                  <td class="py-3 font-semibold text-slate-900">
                    <div>{{ log.file_name || log.import_source }}</div>
                    <div class="text-[10px] font-normal text-slate-400">{{ log.import_source }}</div>
                  </td>
                  <td class="py-3 font-mono text-[11px] text-teal-700">{{ log.entity_type }}</td>
                  <td class="py-3 font-bold text-emerald-600">{{ log.imported_rows }} / {{ log.total_rows }}</td>
                  <td class="py-3">
                    <span :class="['px-2 py-0.5 rounded-full text-[10px] font-extrabold', log.status === 'Completed' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800']">
                      {{ log.status }}
                    </span>
                  </td>
                  <td class="py-3 text-right text-slate-400 font-mono text-[10px]">{{ new Date(log.created_at).toLocaleString() }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
