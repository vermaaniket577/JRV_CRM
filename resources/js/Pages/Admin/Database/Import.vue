<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import {
  ArrowUpTrayIcon,
  CircleStackIcon,
  TableCellsIcon,
  ArrowPathIcon,
  CheckCircleIcon,
  ExclamationTriangleIcon,
  ShieldCheckIcon,
  DocumentArrowDownIcon,
  ClockIcon,
  EyeIcon,
  ChevronRightIcon,
  ChevronLeftIcon,
  XMarkIcon,
  ArrowRightIcon,
  SparklesIcon,
  CommandLineIcon,
  ServerStackIcon,
  FolderArrowDownIcon,
  BoltIcon,
  QuestionMarkCircleIcon,
  CheckBadgeIcon,
  ArrowUturnLeftIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
  recentImports: {
    type: Array,
    default: () => []
  },
  summaryStats: {
    type: Object,
    default: () => ({})
  },
  activeImport: {
    type: Object,
    default: null
  },
  maxUploadSizeMb: {
    type: Number,
    default: 50
  }
});

// Wizard State (Steps 1 to 10)
const currentStep = ref(1);
const activeTab = ref('wizard'); // 'wizard' or 'history'

// Step 1: Upload State
const isDragging = ref(false);
const selectedFile = ref(null);
const uploadProgress = ref(0);
const isUploading = ref(false);
const currentImportId = ref(props.activeImport?.id || null);

// Step 2 & 3 & 4: Analysis State
const isAnalyzing = ref(false);
const analysisData = ref(null);
const detectedTables = ref([]);
const relationships = ref([]);
const warnings = ref([]);

// Step 5: Field Mapping State
const fieldMappings = ref([]);
const targetCrmTables = ref([
  { key: 'contacts', label: 'Contacts & Customers' },
  { key: 'crm_sales_leads', label: 'Sales Leads' },
  { key: 'properties', label: 'Properties & Real Estate' },
  { key: 'companies', label: 'Companies' }
]);

// Step 6: Import Mode & Duplicate Rules
const importMode = ref('upsert'); // 'insert_only', 'update_existing', 'upsert', 'skip_duplicates'
const duplicateRules = ref({
  customers: ['email', 'phone'],
  leads: ['phone', 'email'],
  properties: ['owner_phone', 'title']
});

// Step 7: Preview
const previewData = ref(null);
const approvedColumns = ref([]);

// Step 8: Backup
const isCreatingBackup = ref(false);
const backupCreated = ref(null);
const skipBackup = ref(false);

// Step 9: Import Progress
const isImporting = ref(false);
const importProgress = ref({
  percent: 0,
  processed: 0,
  total: 0,
  inserted: 0,
  updated: 0,
  skipped: 0,
  failed: 0,
  status: 'PENDING'
});
let pollInterval = null;

// Step 10: Completion
const importSummary = ref(null);
const errorMessage = ref('');

// Step definitions for top stepper
const steps = [
  { number: 1, title: 'Upload SQL' },
  { number: 2, title: 'Analyze' },
  { number: 3, title: 'Review Tables' },
  { number: 4, title: 'Columns' },
  { number: 5, title: 'Field Mapping' },
  { number: 6, title: 'Import Mode' },
  { number: 7, title: 'Preview' },
  { number: 8, title: 'Backup' },
  { number: 9, title: 'Importing' },
  { number: 10, title: 'Complete' },
];

// If initialized with an activeImport
onMounted(() => {
  if (props.activeImport) {
    currentImportId.value = props.activeImport.id;
    if (props.activeImport.status === 'READY') {
      loadImportDetails(props.activeImport);
      currentStep.value = 3;
    } else if (props.activeImport.status === 'COMPLETED' || props.activeImport.status === 'PARTIAL') {
      currentStep.value = 10;
      importSummary.value = {
        tables_created: props.activeImport.tables_detected,
        records_inserted: props.activeImport.records_inserted,
        records_updated: props.activeImport.records_updated,
        records_skipped: props.activeImport.records_skipped,
        records_failed: props.activeImport.records_failed,
      };
    }
  }
});

onUnmounted(() => {
  if (pollInterval) clearInterval(pollInterval);
});

// File Drop Handlers
const handleFileDrop = (e) => {
  isDragging.value = false;
  const files = e.dataTransfer.files;
  if (files.length > 0) {
    validateAndSelectFile(files[0]);
  }
};

const handleFileSelect = (e) => {
  const files = e.target.files;
  if (files.length > 0) {
    validateAndSelectFile(files[0]);
  }
};

const validateAndSelectFile = (file) => {
  if (!file.name.toLowerCase().endsWith('.sql') && !file.name.toLowerCase().endsWith('.txt')) {
    alert('Please select a valid MySQL .sql dump file.');
    return;
  }
  selectedFile.value = file;
};

// Step 1 -> 2: Upload SQL File
const uploadSqlFile = async () => {
  if (!selectedFile.value) return;

  isUploading.value = true;
  errorMessage.value = '';

  const formData = new FormData();
  formData.append('sql_file', selectedFile.value);

  try {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    const response = await fetch('/admin/database/import/upload', {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': csrfToken || '',
        'Accept': 'application/json'
      },
      body: formData
    });

    const res = await response.json();
    if (res.success) {
      currentImportId.value = res.import_id;
      currentStep.value = 2;
      startAnalysis();
    } else {
      errorMessage.value = res.message || 'File upload failed.';
    }
  } catch (err) {
    errorMessage.value = 'Failed to upload SQL file: ' + err.message;
  } finally {
    isUploading.value = false;
  }
};

// Step 2: Analyze Database
const startAnalysis = async () => {
  if (!currentImportId.value) return;

  isAnalyzing.value = true;
  errorMessage.value = '';

  try {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    const response = await fetch(`/admin/database/import/${currentImportId.value}/analyze`, {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': csrfToken || '',
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      }
    });

    const res = await response.json();
    if (res.success) {
      loadImportDetails(res.import, res.analysis);
      relationships.value = res.relationships || [];
      currentStep.value = 3; // Go to Review Tables
    } else {
      errorMessage.value = res.message || 'Analysis failed.';
    }
  } catch (err) {
    errorMessage.value = 'Failed to analyze SQL: ' + err.message;
  } finally {
    isAnalyzing.value = false;
  }
};

const loadImportDetails = (importModel, analysis = null) => {
  detectedTables.value = importModel.tables || [];
  fieldMappings.value = importModel.mappings || [];
  analysisData.value = analysis || importModel.schema_changes_summary || {};

  // Default approvals
  approvedColumns.value = [];
  detectedTables.value.forEach(tbl => {
    tbl.columns?.forEach(col => {
      if (col.requires_approval && col.is_approved) {
        approvedColumns.value.push(col.id);
      }
    });
  });
};

// Step 4: Toggle Column Approval
const toggleColumnApproval = (colId) => {
  const idx = approvedColumns.value.indexOf(colId);
  if (idx === -1) {
    approvedColumns.value.push(colId);
  } else {
    approvedColumns.value.splice(idx, 1);
  }
};

// Step 5: Save Custom Field Mappings
const saveMappings = async () => {
  try {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    await fetch(`/admin/database/mapping/${currentImportId.value}`, {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': csrfToken || '',
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      },
      body: JSON.stringify({
        mappings: fieldMappings.value.map(m => ({
          id: m.id,
          target_table: m.target_table,
          target_column: m.target_column,
          is_confirmed: true,
          transformation_rule: m.transformation_rule
        }))
      })
    });
  } catch (e) {
    console.error('Failed to save mappings', e);
  }
};

// Step 7: Load Preview
const loadPreview = async () => {
  if (!currentImportId.value) return;

  try {
    const res = await fetch(`/admin/database/import/${currentImportId.value}/preview`);
    const data = await res.json();
    if (data.success) {
      previewData.value = data;
    }
  } catch (e) {
    console.error('Preview error', e);
  }
};

// Navigation between steps
const goToNextStep = async () => {
  if (currentStep.value === 5) {
    await saveMappings();
  }
  if (currentStep.value === 6) {
    await loadPreview();
  }
  if (currentStep.value < 10) {
    currentStep.value++;
  }
};

const goToPrevStep = () => {
  if (currentStep.value > 1 && currentStep.value !== 9) {
    currentStep.value--;
  }
};

// Step 8 -> 9: Execute Import
const executeImport = async () => {
  currentStep.value = 9;
  isImporting.value = true;
  errorMessage.value = '';

  // Start polling progress
  pollProgress();
  pollInterval = setInterval(pollProgress, 1000);

  try {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    const response = await fetch(`/admin/database/import/${currentImportId.value}/execute`, {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': csrfToken || '',
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      },
      body: JSON.stringify({
        import_mode: importMode.value,
        approved_columns: approvedColumns.value,
        create_backup: !skipBackup.value
      })
    });

    const res = await response.json();
    if (pollInterval) clearInterval(pollInterval);

    if (res.success) {
      importSummary.value = res.summary;
      currentStep.value = 10; // Complete
    } else {
      errorMessage.value = res.message || 'Import execution failed.';
      currentStep.value = 7; // Go back to preview
    }
  } catch (err) {
    if (pollInterval) clearInterval(pollInterval);
    errorMessage.value = 'Execution error: ' + err.message;
    currentStep.value = 7;
  } finally {
    isImporting.value = false;
  }
};

// Poller for Step 9
const pollProgress = async () => {
  if (!currentImportId.value) return;
  try {
    const res = await fetch(`/admin/database/import/${currentImportId.value}/status`);
    const data = await res.json();
    importProgress.value = {
      percent: data.percent || 0,
      processed: data.records_processed || 0,
      total: data.records_detected || 0,
      inserted: data.records_inserted || 0,
      updated: data.records_updated || 0,
      skipped: data.records_skipped || 0,
      failed: data.records_failed || 0,
      status: data.status || 'IMPORTING'
    };
  } catch (e) {
    console.error('Status poll error', e);
  }
};

// Format bytes
const formatBytes = (bytes) => {
  if (!bytes) return '0 B';
  const k = 1024;
  const sizes = ['B', 'KB', 'MB', 'GB'];
  const i = Math.floor(Math.log(bytes) / Math.log(k));
  return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
};
</script>

<template>
  <Head title="SQL Database Import & Auto-Mapping — Master Admin" />

  <div class="min-h-screen bg-slate-950 text-slate-100 flex flex-col selection:bg-red-500 selection:text-white">
    <!-- Top Admin Header -->
    <header class="border-b border-slate-800/80 bg-slate-900/60 backdrop-blur-xl sticky top-0 z-40">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3.5 flex items-center justify-between">
        <div class="flex items-center gap-3">
          <Link href="/admin" class="w-9 h-9 rounded-xl bg-red-500/10 border border-red-500/30 text-red-500 flex items-center justify-center hover:bg-red-500/20 transition-all">
            <ChevronLeftIcon class="w-5 h-5 stroke-[2.5]" />
          </Link>
          <div>
            <div class="flex items-center gap-2">
              <span class="px-2 py-0.5 text-[10px] font-black uppercase tracking-wider bg-red-600/20 text-red-400 border border-red-500/30 rounded-md">Enterprise Module</span>
              <h1 class="text-base font-black text-white tracking-tight">SQL Database Import & Auto-Mapping</h1>
            </div>
            <p class="text-xs text-slate-400 font-medium">Safe parsing, live schema comparison, semantic CRM auto-mapping & zero-loss migration</p>
          </div>
        </div>

        <!-- Quick Tabs & Actions -->
        <div class="flex items-center gap-2">
          <button
            @click="activeTab = 'wizard'"
            :class="activeTab === 'wizard' ? 'bg-red-600 text-white shadow-lg shadow-red-600/20' : 'bg-slate-800 text-slate-300 hover:bg-slate-700'"
            class="px-3.5 py-1.5 rounded-xl text-xs font-black transition-all cursor-pointer flex items-center gap-1.5"
          >
            <SparklesIcon class="w-4 h-4" />
            <span>Import Wizard</span>
          </button>
          <Link
            href="/admin/database/tables"
            class="px-3.5 py-1.5 bg-slate-800 hover:bg-slate-700 text-slate-300 border border-slate-700 rounded-xl text-xs font-black transition-all flex items-center gap-1.5"
          >
            <TableCellsIcon class="w-4 h-4 text-sky-400" />
            <span>Database Tables</span>
          </Link>
          <Link
            href="/admin/database/import/history"
            class="px-3.5 py-1.5 bg-slate-800 hover:bg-slate-700 text-slate-300 border border-slate-700 rounded-xl text-xs font-black transition-all flex items-center gap-1.5"
          >
            <ClockIcon class="w-4 h-4 text-emerald-400" />
            <span>History</span>
          </Link>
        </div>
      </div>
    </header>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 flex-1 w-full space-y-6">
      <!-- Top Metric Overview Cards -->
      <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-6 gap-3">
        <div class="p-3.5 rounded-2xl bg-slate-900/60 border border-slate-800/80 backdrop-blur-md">
          <div class="text-[10px] font-black uppercase text-slate-400">Total Imports</div>
          <div class="text-xl font-black text-white mt-1">{{ summaryStats.total_imports || 0 }}</div>
        </div>
        <div class="p-3.5 rounded-2xl bg-slate-900/60 border border-slate-800/80 backdrop-blur-md">
          <div class="text-[10px] font-black uppercase text-emerald-400">Records Imported</div>
          <div class="text-xl font-black text-emerald-400 mt-1">{{ (summaryStats.records_imported || 0).toLocaleString() }}</div>
        </div>
        <div class="p-3.5 rounded-2xl bg-slate-900/60 border border-slate-800/80 backdrop-blur-md">
          <div class="text-[10px] font-black uppercase text-sky-400">New Records</div>
          <div class="text-xl font-black text-sky-400 mt-1">{{ (summaryStats.records_inserted || 0).toLocaleString() }}</div>
        </div>
        <div class="p-3.5 rounded-2xl bg-slate-900/60 border border-slate-800/80 backdrop-blur-md">
          <div class="text-[10px] font-black uppercase text-amber-400">Updated Records</div>
          <div class="text-xl font-black text-amber-400 mt-1">{{ (summaryStats.records_updated || 0).toLocaleString() }}</div>
        </div>
        <div class="p-3.5 rounded-2xl bg-slate-900/60 border border-slate-800/80 backdrop-blur-md">
          <div class="text-[10px] font-black uppercase text-purple-400">Tables Detected</div>
          <div class="text-xl font-black text-purple-400 mt-1">{{ summaryStats.tables_imported || 0 }}</div>
        </div>
        <div class="p-3.5 rounded-2xl bg-slate-900/60 border border-slate-800/80 backdrop-blur-md">
          <div class="text-[10px] font-black uppercase text-rose-400">Import Errors</div>
          <div class="text-xl font-black text-rose-400 mt-1">{{ summaryStats.records_failed || 0 }}</div>
        </div>
      </div>

      <!-- Error Alert Message Banner -->
      <div v-if="errorMessage" class="p-4 rounded-2xl bg-rose-950/60 border border-rose-600/50 text-rose-200 flex items-start gap-3">
        <ExclamationTriangleIcon class="w-5 h-5 text-rose-400 shrink-0 mt-0.5" />
        <div class="flex-1 text-xs">
          <span class="font-bold">Error Encountered:</span> {{ errorMessage }}
        </div>
        <button @click="errorMessage = ''" class="text-rose-400 hover:text-white">
          <XMarkIcon class="w-4 h-4" />
        </button>
      </div>

      <!-- 10-Step Interactive Wizard Stepper -->
      <div class="p-4 rounded-2xl bg-slate-900/70 border border-slate-800/90 shadow-xl overflow-x-auto">
        <div class="flex items-center min-w-[760px] justify-between relative">
          <!-- Stepper line -->
          <div class="absolute left-4 right-4 top-4 -translate-y-1/2 h-0.5 bg-slate-800 -z-0"></div>
          
          <div
            v-for="st in steps"
            :key="st.number"
            class="relative z-10 flex flex-col items-center gap-1.5 cursor-pointer group"
            @click="currentStep > st.number && (currentStep = st.number)"
          >
            <div
              class="w-8 h-8 rounded-xl font-black text-xs flex items-center justify-center transition-all"
              :class="[
                currentStep === st.number
                  ? 'bg-red-600 text-white shadow-lg shadow-red-600/40 ring-4 ring-red-500/20'
                  : currentStep > st.number
                  ? 'bg-emerald-500 text-slate-950 ring-2 ring-emerald-500/30'
                  : 'bg-slate-800 text-slate-500 border border-slate-700'
              ]"
            >
              <CheckCircleIcon v-if="currentStep > st.number" class="w-5 h-5 stroke-[3]" />
              <span v-else>{{ st.number }}</span>
            </div>
            <span
              class="text-[11px] font-bold whitespace-nowrap transition-colors"
              :class="currentStep === st.number ? 'text-white' : currentStep > st.number ? 'text-slate-300' : 'text-slate-500'"
            >
              {{ st.title }}
            </span>
          </div>
        </div>
      </div>

      <!-- ========================================================================= -->
      <!-- STEP 1: Upload SQL -->
      <!-- ========================================================================= -->
      <div v-if="currentStep === 1" class="space-y-6">
        <div class="p-8 rounded-3xl bg-slate-900/60 border border-slate-800/80 shadow-2xl backdrop-blur-md text-center space-y-6">
          <div class="max-w-md mx-auto space-y-2">
            <h2 class="text-xl font-black text-white">Upload MySQL Database Dump (.sql)</h2>
            <p class="text-xs text-slate-400">
              Upload an SQL dump containing CREATE TABLE and INSERT INTO statements. The file will be parsed securely without blind execution.
            </p>
          </div>

          <!-- Drag and Drop Box -->
          <div
            @dragover.prevent="isDragging = true"
            @dragleave.prevent="isDragging = false"
            @drop.prevent="handleFileDrop"
            :class="isDragging ? 'border-red-500 bg-red-500/5' : 'border-slate-700 hover:border-slate-600 bg-slate-950/40'"
            class="border-2 border-dashed rounded-3xl p-10 transition-all flex flex-col items-center justify-center gap-3 cursor-pointer group"
          >
            <div class="w-16 h-16 rounded-2xl bg-red-500/10 border border-red-500/20 flex items-center justify-center text-red-500 group-hover:scale-105 transition-transform">
              <ArrowUpTrayIcon class="w-8 h-8" />
            </div>

            <div class="space-y-1 text-center">
              <div class="text-sm font-bold text-white">
                <label for="sql-file-input" class="text-red-400 hover:text-red-300 underline cursor-pointer">Click to browse</label>
                or drag and drop your file here
              </div>
              <p class="text-xs text-slate-400">Supported format: MySQL SQL Dump (.sql) — Up to {{ maxUploadSizeMb }}MB</p>
            </div>

            <input
              id="sql-file-input"
              type="file"
              accept=".sql,.txt"
              class="hidden"
              @change="handleFileSelect"
            />

            <!-- Selected File Badge -->
            <div v-if="selectedFile" class="mt-4 px-4 py-2 rounded-xl bg-slate-800/90 border border-slate-700 flex items-center gap-2 text-xs font-bold text-slate-200">
              <CircleStackIcon class="w-4 h-4 text-red-400" />
              <span>{{ selectedFile.name }} ({{ formatBytes(selectedFile.size) }})</span>
              <button @click.stop="selectedFile = null" class="text-slate-400 hover:text-white ml-2">
                <XMarkIcon class="w-4 h-4" />
              </button>
            </div>
          </div>

          <!-- Upload Action Button -->
          <div class="flex items-center justify-center gap-3 pt-2">
            <button
              @click="uploadSqlFile"
              :disabled="!selectedFile || isUploading"
              class="px-8 py-3 bg-red-600 hover:bg-red-500 disabled:opacity-50 disabled:cursor-not-allowed text-white font-black text-xs uppercase tracking-wider rounded-xl shadow-xl shadow-red-600/30 flex items-center gap-2 transition-all cursor-pointer"
            >
              <ArrowPathIcon v-if="isUploading" class="w-4 h-4 animate-spin" />
              <SparklesIcon v-else class="w-4 h-4" />
              <span>{{ isUploading ? 'Uploading SQL File...' : 'Analyze SQL File' }}</span>
            </button>
          </div>
        </div>

        <!-- Security Guarantee Notice -->
        <div class="p-4 rounded-2xl bg-slate-900/40 border border-slate-800/80 flex items-start gap-3">
          <ShieldCheckIcon class="w-5 h-5 text-emerald-400 shrink-0 mt-0.5" />
          <div class="text-xs text-slate-400 space-y-1">
            <span class="font-bold text-slate-200">Zero-Loss & Security Protection:</span>
            Your uploaded SQL is analyzed through an AST schema parser. Destructive statements (DROP DATABASE, TRUNCATE, system user creation) are strictly filtered and blocked. Existing CRM data is never deleted.
          </div>
        </div>
      </div>

      <!-- ========================================================================= -->
      <!-- STEP 2: Analyze Database -->
      <!-- ========================================================================= -->
      <div v-if="currentStep === 2" class="p-12 rounded-3xl bg-slate-900/60 border border-slate-800 text-center space-y-6">
        <div class="w-16 h-16 rounded-2xl bg-red-600/20 border border-red-500/30 text-red-500 flex items-center justify-center mx-auto animate-pulse">
          <ArrowPathIcon class="w-8 h-8 animate-spin" />
        </div>
        <div class="space-y-2">
          <h2 class="text-xl font-black text-white">Analyzing SQL Dump Structure...</h2>
          <p class="text-xs text-slate-400 max-w-md mx-auto">
            Extracting table definitions, data types, indexes, and primary keys. Comparing schema with existing CRM database.
          </p>
        </div>
      </div>

      <!-- ========================================================================= -->
      <!-- STEP 3: Review Tables -->
      <!-- ========================================================================= -->
      <div v-if="currentStep === 3" class="space-y-6">
        <div class="flex items-center justify-between">
          <div>
            <h2 class="text-lg font-black text-white">Detected Tables ({{ detectedTables.length }})</h2>
            <p class="text-xs text-slate-400">Review tables found in the uploaded SQL and their target synchronization action.</p>
          </div>
          <div class="flex items-center gap-2">
            <button @click="goToPrevStep" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 font-bold text-xs rounded-xl">
              Back
            </button>
            <button @click="goToNextStep" class="px-5 py-2 bg-red-600 hover:bg-red-500 text-white font-black text-xs rounded-xl flex items-center gap-1.5 shadow-lg shadow-red-600/20">
              <span>Next: Review Columns</span>
              <ChevronRightIcon class="w-4 h-4 stroke-[3]" />
            </button>
          </div>
        </div>

        <!-- Tables Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
          <div
            v-for="table in detectedTables"
            :key="table.id || table.table_name"
            class="p-5 rounded-2xl bg-slate-900/70 border border-slate-800/80 hover:border-slate-700 transition-all space-y-3"
          >
            <div class="flex items-start justify-between">
              <div class="flex items-center gap-2">
                <CircleStackIcon class="w-5 h-5 text-red-500" />
                <span class="font-black text-sm text-white">{{ table.table_name }}</span>
              </div>
              <span
                class="px-2.5 py-0.5 text-[10px] font-black uppercase rounded-lg border"
                :class="[
                  table.status === 'NEW' ? 'bg-sky-500/10 text-sky-400 border-sky-500/30' :
                  table.status === 'MODIFIED' ? 'bg-amber-500/10 text-amber-400 border-amber-500/30' :
                  table.status === 'EXISTS' ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/30' :
                  'bg-slate-800 text-slate-400 border-slate-700'
                ]"
              >
                {{ table.status }}
              </span>
            </div>

            <div class="grid grid-cols-2 gap-2 text-xs text-slate-400">
              <div>Columns: <span class="font-bold text-slate-200">{{ table.columns?.length || table.columns_count || 0 }}</span></div>
              <div>Est. Records: <span class="font-bold text-slate-200">{{ (table.records_count || 0).toLocaleString() }}</span></div>
            </div>

            <div class="pt-2 border-t border-slate-800 flex items-center justify-between text-xs">
              <span class="text-slate-500">Target Action:</span>
              <span class="font-black uppercase text-slate-300">
                {{ table.status === 'NEW' ? 'Create Table' : 'Add Missing Columns' }}
              </span>
            </div>
          </div>
        </div>
      </div>

      <!-- ========================================================================= -->
      <!-- STEP 4: Review Columns -->
      <!-- ========================================================================= -->
      <div v-if="currentStep === 4" class="space-y-6">
        <div class="flex items-center justify-between">
          <div>
            <h2 class="text-lg font-black text-white">Column Comparisons & Actions</h2>
            <p class="text-xs text-slate-400">Verify detected columns. Potentially destructive changes require explicit administrator approval.</p>
          </div>
          <div class="flex items-center gap-2">
            <button @click="goToPrevStep" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 font-bold text-xs rounded-xl">
              Back
            </button>
            <button @click="goToNextStep" class="px-5 py-2 bg-red-600 hover:bg-red-500 text-white font-black text-xs rounded-xl flex items-center gap-1.5 shadow-lg shadow-red-600/20">
              <span>Next: Field Mapping</span>
              <ChevronRightIcon class="w-4 h-4 stroke-[3]" />
            </button>
          </div>
        </div>

        <div class="space-y-4">
          <div
            v-for="table in detectedTables"
            :key="'col_tbl_' + (table.id || table.table_name)"
            class="rounded-2xl bg-slate-900/60 border border-slate-800/80 overflow-hidden"
          >
            <div class="px-5 py-3.5 bg-slate-900/90 border-b border-slate-800 flex items-center justify-between">
              <div class="flex items-center gap-2">
                <span class="font-black text-sm text-white">{{ table.table_name }}</span>
                <span class="text-xs text-slate-400">({{ table.columns?.length || 0 }} columns)</span>
              </div>
              <span class="text-xs text-slate-400 font-mono">{{ table.status }}</span>
            </div>

            <div class="overflow-x-auto">
              <table class="w-full text-left text-xs text-slate-300">
                <thead class="bg-slate-950/60 text-slate-400 text-[10px] font-black uppercase tracking-wider border-b border-slate-800">
                  <tr>
                    <th class="px-5 py-2.5">Column Name</th>
                    <th class="px-5 py-2.5">Uploaded Type</th>
                    <th class="px-5 py-2.5">Existing Type</th>
                    <th class="px-5 py-2.5">Status</th>
                    <th class="px-5 py-2.5">Action</th>
                    <th class="px-5 py-2.5 text-right">Approval</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/50">
                  <tr v-for="col in table.columns" :key="col.id || col.column_name" class="hover:bg-slate-800/30">
                    <td class="px-5 py-3 font-bold text-white flex items-center gap-1.5">
                      <span>{{ col.column_name }}</span>
                      <span v-if="col.is_primary" class="px-1.5 py-0.5 text-[9px] bg-amber-500/20 text-amber-400 rounded">PK</span>
                    </td>
                    <td class="px-5 py-3 font-mono text-slate-300">{{ col.data_type }}</td>
                    <td class="px-5 py-3 font-mono text-slate-500">{{ col.existing_data_type || '—' }}</td>
                    <td class="px-5 py-3">
                      <span
                        class="px-2 py-0.5 text-[10px] font-bold rounded"
                        :class="col.status === 'NEW' ? 'bg-sky-500/20 text-sky-400' : col.status === 'MODIFIED' ? 'bg-amber-500/20 text-amber-400' : 'bg-slate-800 text-slate-400'"
                      >
                        {{ col.status }}
                      </span>
                    </td>
                    <td class="px-5 py-3 font-bold uppercase text-[10px]" :class="col.action === 'add' ? 'text-emerald-400' : col.action === 'modify' ? 'text-amber-400' : 'text-slate-400'">
                      {{ col.action }}
                    </td>
                    <td class="px-5 py-3 text-right">
                      <div v-if="col.requires_approval">
                        <label class="inline-flex items-center gap-1.5 text-xs text-amber-400 cursor-pointer">
                          <input
                            type="checkbox"
                            :checked="approvedColumns.includes(col.id)"
                            @change="toggleColumnApproval(col.id)"
                            class="rounded border-slate-700 bg-slate-900 text-red-600 focus:ring-red-500"
                          />
                          <span class="font-bold">Approve</span>
                        </label>
                      </div>
                      <span v-else class="text-slate-500 text-[10px]">Auto-Safe</span>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>

      <!-- ========================================================================= -->
      <!-- STEP 5: Map CRM Fields -->
      <!-- ========================================================================= -->
      <div v-if="currentStep === 5" class="space-y-6">
        <div class="flex items-center justify-between">
          <div>
            <h2 class="text-lg font-black text-white">Intelligent CRM Field Auto-Mapping</h2>
            <p class="text-xs text-slate-400">The engine automatically suggested target fields based on terminology and semantics. You can override any mapping below.</p>
          </div>
          <div class="flex items-center gap-2">
            <button @click="goToPrevStep" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 font-bold text-xs rounded-xl">
              Back
            </button>
            <button @click="goToNextStep" class="px-5 py-2 bg-red-600 hover:bg-red-500 text-white font-black text-xs rounded-xl flex items-center gap-1.5 shadow-lg shadow-red-600/20">
              <span>Next: Import Mode</span>
              <ChevronRightIcon class="w-4 h-4 stroke-[3]" />
            </button>
          </div>
        </div>

        <div class="p-6 rounded-2xl bg-slate-900/60 border border-slate-800/80 space-y-4">
          <div v-for="mapping in fieldMappings" :key="mapping.id" class="p-4 rounded-xl bg-slate-950/60 border border-slate-800 flex flex-wrap items-center justify-between gap-4">
            <div class="flex items-center gap-3">
              <div class="space-y-0.5">
                <span class="text-[10px] uppercase font-black text-slate-500">{{ mapping.source_table }}</span>
                <div class="font-mono text-sm font-bold text-white">{{ mapping.source_column }}</div>
              </div>
              <ArrowRightIcon class="w-4 h-4 text-slate-600" />
              <div class="space-y-0.5">
                <span class="text-[10px] uppercase font-black text-red-400">{{ mapping.target_table }}</span>
                <input
                  type="text"
                  v-model="mapping.target_column"
                  class="px-2.5 py-1 text-xs font-mono font-bold bg-slate-900 border border-slate-700 rounded-lg text-white focus:outline-none focus:border-red-500"
                />
              </div>
            </div>

            <div class="flex items-center gap-3">
              <!-- Confidence Tag -->
              <span
                class="px-2.5 py-0.5 text-[10px] font-black uppercase rounded-md border"
                :class="[
                  mapping.confidence === 'high' ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/30' :
                  mapping.confidence === 'medium' ? 'bg-amber-500/10 text-amber-400 border-amber-500/30' :
                  'bg-rose-500/10 text-rose-400 border-rose-500/30'
                ]"
              >
                {{ mapping.confidence }} Confidence ({{ Math.round((mapping.confidence_score || 1) * 100) }}%)
              </span>

              <!-- Transformation Rule -->
              <span v-if="mapping.transformation_rule" class="text-[10px] font-mono text-slate-400 px-2 py-0.5 bg-slate-800 rounded">
                {{ mapping.transformation_rule }}
              </span>
            </div>
          </div>
        </div>
      </div>

      <!-- ========================================================================= -->
      <!-- STEP 6: Select Import Mode -->
      <!-- ========================================================================= -->
      <div v-if="currentStep === 6" class="space-y-6">
        <div class="flex items-center justify-between">
          <div>
            <h2 class="text-lg font-black text-white">Select Import Mode & Conflict Resolution</h2>
            <p class="text-xs text-slate-400">Choose how matching records should be handled when importing into CRM tables.</p>
          </div>
          <div class="flex items-center gap-2">
            <button @click="goToPrevStep" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 font-bold text-xs rounded-xl">
              Back
            </button>
            <button @click="goToNextStep" class="px-5 py-2 bg-red-600 hover:bg-red-500 text-white font-black text-xs rounded-xl flex items-center gap-1.5 shadow-lg shadow-red-600/20">
              <span>Next: Preview Changes</span>
              <ChevronRightIcon class="w-4 h-4 stroke-[3]" />
            </button>
          </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
          <!-- Upsert -->
          <div
            @click="importMode = 'upsert'"
            :class="importMode === 'upsert' ? 'border-red-500 bg-red-500/10 ring-2 ring-red-500/30' : 'border-slate-800 bg-slate-900/60 hover:border-slate-700'"
            class="p-5 rounded-2xl border cursor-pointer transition-all space-y-2"
          >
            <div class="flex items-center justify-between">
              <BoltIcon class="w-6 h-6 text-red-500" />
              <span class="text-[10px] font-black uppercase text-red-400">Recommended</span>
            </div>
            <h3 class="font-black text-sm text-white">Upsert (Smart Sync)</h3>
            <p class="text-xs text-slate-400">Insert new records and safely update matching existing records.</p>
          </div>

          <!-- Insert Only -->
          <div
            @click="importMode = 'insert_only'"
            :class="importMode === 'insert_only' ? 'border-red-500 bg-red-500/10 ring-2 ring-red-500/30' : 'border-slate-800 bg-slate-900/60 hover:border-slate-700'"
            class="p-5 rounded-2xl border cursor-pointer transition-all space-y-2"
          >
            <div class="flex items-center justify-between">
              <CheckBadgeIcon class="w-6 h-6 text-emerald-500" />
            </div>
            <h3 class="font-black text-sm text-white">Insert Only</h3>
            <p class="text-xs text-slate-400">Only add new records. Do not touch or modify existing CRM records.</p>
          </div>

          <!-- Update Existing -->
          <div
            @click="importMode = 'update_existing'"
            :class="importMode === 'update_existing' ? 'border-red-500 bg-red-500/10 ring-2 ring-red-500/30' : 'border-slate-800 bg-slate-900/60 hover:border-slate-700'"
            class="p-5 rounded-2xl border cursor-pointer transition-all space-y-2"
          >
            <div class="flex items-center justify-between">
              <ArrowPathIcon class="w-6 h-6 text-sky-500" />
            </div>
            <h3 class="font-black text-sm text-white">Update Existing</h3>
            <p class="text-xs text-slate-400">Only refresh matching records. Do not insert any new rows.</p>
          </div>

          <!-- Skip Duplicates -->
          <div
            @click="importMode = 'skip_duplicates'"
            :class="importMode === 'skip_duplicates' ? 'border-red-500 bg-red-500/10 ring-2 ring-red-500/30' : 'border-slate-800 bg-slate-900/60 hover:border-slate-700'"
            class="p-5 rounded-2xl border cursor-pointer transition-all space-y-2"
          >
            <div class="flex items-center justify-between">
              <ShieldCheckIcon class="w-6 h-6 text-purple-500" />
            </div>
            <h3 class="font-black text-sm text-white">Skip Duplicates</h3>
            <p class="text-xs text-slate-400">Skip any records that already match existing records in CRM.</p>
          </div>
        </div>

        <!-- Normalization Rules Info -->
        <div class="p-5 rounded-2xl bg-slate-900/40 border border-slate-800 text-xs text-slate-300 space-y-2">
          <span class="font-bold text-white">Automated Data Normalization:</span>
          <p class="text-slate-400">
            Phones (+91 98765 43210 & 9876543210) will be standardized. Emails are trimmed and lowercased. Dates are converted into uniform Y-m-d H:i:s timestamps.
          </p>
        </div>
      </div>

      <!-- ========================================================================= -->
      <!-- STEP 7: Preview Changes -->
      <!-- ========================================================================= -->
      <div v-if="currentStep === 7" class="space-y-6">
        <div class="flex items-center justify-between">
          <div>
            <h2 class="text-lg font-black text-white">Pre-Execution Impact Summary</h2>
            <p class="text-xs text-slate-400">Review the exact changes that will be applied to your live CRM database.</p>
          </div>
          <div class="flex items-center gap-2">
            <button @click="goToPrevStep" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 font-bold text-xs rounded-xl">
              Back
            </button>
            <button @click="goToNextStep" class="px-5 py-2 bg-red-600 hover:bg-red-500 text-white font-black text-xs rounded-xl flex items-center gap-1.5 shadow-lg shadow-red-600/20">
              <span>Next: Database Backup</span>
              <ChevronRightIcon class="w-4 h-4 stroke-[3]" />
            </button>
          </div>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
          <div class="p-5 rounded-2xl bg-slate-900/80 border border-slate-800">
            <div class="text-xs font-bold text-slate-400">New Tables to Create</div>
            <div class="text-2xl font-black text-sky-400 mt-1">
              {{ detectedTables.filter(t => t.status === 'NEW').length }}
            </div>
          </div>
          <div class="p-5 rounded-2xl bg-slate-900/80 border border-slate-800">
            <div class="text-xs font-bold text-slate-400">Existing Tables to Update</div>
            <div class="text-2xl font-black text-amber-400 mt-1">
              {{ detectedTables.filter(t => t.status !== 'NEW').length }}
            </div>
          </div>
          <div class="p-5 rounded-2xl bg-slate-900/80 border border-slate-800">
            <div class="text-xs font-bold text-slate-400">Columns to Add</div>
            <div class="text-2xl font-black text-emerald-400 mt-1">
              {{ detectedTables.reduce((acc, t) => acc + (t.columns?.filter(c => c.action === 'add').length || 0), 0) }}
            </div>
          </div>
          <div class="p-5 rounded-2xl bg-slate-900/80 border border-slate-800">
            <div class="text-xs font-bold text-slate-400">Records to Process</div>
            <div class="text-2xl font-black text-white mt-1">
              {{ detectedTables.reduce((acc, t) => acc + (t.records_count || 0), 0).toLocaleString() }}
            </div>
          </div>
        </div>
      </div>

      <!-- ========================================================================= -->
      <!-- STEP 8: Backup Database -->
      <!-- ========================================================================= -->
      <div v-if="currentStep === 8" class="p-8 rounded-3xl bg-slate-900/70 border border-slate-800 text-center space-y-6">
        <div class="w-16 h-16 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 flex items-center justify-center mx-auto">
          <FolderArrowDownIcon class="w-8 h-8" />
        </div>

        <div class="max-w-md mx-auto space-y-2">
          <h2 class="text-xl font-black text-white">Automated Database Safety Snapshot</h2>
          <p class="text-xs text-slate-400">
            Before applying migrations and inserting data, a full backup snapshot of your current database will be generated in <code class="text-slate-200">database_backups/</code> for instant rollback if needed.
          </p>
        </div>

        <div class="flex items-center justify-center gap-4 pt-4">
          <button @click="goToPrevStep" class="px-5 py-2.5 bg-slate-800 hover:bg-slate-700 text-slate-300 font-bold text-xs rounded-xl">
            Back
          </button>
          <button
            @click="executeImport"
            class="px-8 py-3 bg-red-600 hover:bg-red-500 text-white font-black text-xs uppercase tracking-wider rounded-xl shadow-xl shadow-red-600/30 flex items-center gap-2 cursor-pointer"
          >
            <ShieldCheckIcon class="w-5 h-5" />
            <span>Create Backup & Import Database</span>
          </button>
        </div>
      </div>

      <!-- ========================================================================= -->
      <!-- STEP 9: Import in Progress -->
      <!-- ========================================================================= -->
      <div v-if="currentStep === 9" class="p-10 rounded-3xl bg-slate-900/70 border border-slate-800 text-center space-y-6">
        <div class="space-y-2">
          <h2 class="text-xl font-black text-white">Importing Database Records...</h2>
          <p class="text-xs text-slate-400">Streaming and inserting data in transactional batches.</p>
        </div>

        <!-- Animated Progress Bar -->
        <div class="max-w-xl mx-auto space-y-2">
          <div class="flex items-center justify-between text-xs font-bold text-slate-300">
            <span>Progress: {{ importProgress.percent }}%</span>
            <span>{{ importProgress.processed.toLocaleString() }} / {{ importProgress.total.toLocaleString() }} records</span>
          </div>
          <div class="w-full h-4 rounded-full bg-slate-800 overflow-hidden p-0.5 border border-slate-700">
            <div
              class="h-full rounded-full bg-gradient-to-r from-red-600 to-amber-500 transition-all duration-300"
              :style="{ width: importProgress.percent + '%' }"
            ></div>
          </div>
        </div>

        <div class="grid grid-cols-4 gap-3 max-w-lg mx-auto text-xs font-bold pt-4">
          <div class="p-3 rounded-xl bg-slate-950/60 border border-slate-800">
            <div class="text-slate-500 text-[10px]">Inserted</div>
            <div class="text-emerald-400 text-sm mt-0.5">{{ importProgress.inserted.toLocaleString() }}</div>
          </div>
          <div class="p-3 rounded-xl bg-slate-950/60 border border-slate-800">
            <div class="text-slate-500 text-[10px]">Updated</div>
            <div class="text-sky-400 text-sm mt-0.5">{{ importProgress.updated.toLocaleString() }}</div>
          </div>
          <div class="p-3 rounded-xl bg-slate-950/60 border border-slate-800">
            <div class="text-slate-500 text-[10px]">Skipped</div>
            <div class="text-slate-400 text-sm mt-0.5">{{ importProgress.skipped.toLocaleString() }}</div>
          </div>
          <div class="p-3 rounded-xl bg-slate-950/60 border border-slate-800">
            <div class="text-slate-500 text-[10px]">Errors</div>
            <div class="text-rose-400 text-sm mt-0.5">{{ importProgress.failed.toLocaleString() }}</div>
          </div>
        </div>
      </div>

      <!-- ========================================================================= -->
      <!-- STEP 10: Import Complete -->
      <!-- ========================================================================= -->
      <div v-if="currentStep === 10" class="p-8 rounded-3xl bg-slate-900/70 border border-slate-800 space-y-6 text-center">
        <div class="w-16 h-16 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 flex items-center justify-center mx-auto">
          <CheckCircleIcon class="w-10 h-10" />
        </div>

        <div class="space-y-1">
          <h2 class="text-2xl font-black text-white">Database Imported Successfully!</h2>
          <p class="text-xs text-slate-400">All requested schema synchronizations and data insertions have finished.</p>
        </div>

        <!-- Summary Grid -->
        <div class="grid grid-cols-2 sm:grid-cols-5 gap-3 max-w-3xl mx-auto pt-2">
          <div class="p-4 rounded-xl bg-slate-950/60 border border-slate-800">
            <div class="text-[10px] uppercase font-bold text-slate-400">Tables Created</div>
            <div class="text-lg font-black text-sky-400 mt-1">{{ importSummary?.tables_created || 0 }}</div>
          </div>
          <div class="p-4 rounded-xl bg-slate-950/60 border border-slate-800">
            <div class="text-[10px] uppercase font-bold text-slate-400">Tables Updated</div>
            <div class="text-lg font-black text-amber-400 mt-1">{{ importSummary?.tables_updated || 0 }}</div>
          </div>
          <div class="p-4 rounded-xl bg-slate-950/60 border border-slate-800">
            <div class="text-[10px] uppercase font-bold text-slate-400">Records Inserted</div>
            <div class="text-lg font-black text-emerald-400 mt-1">{{ (importSummary?.records_inserted || 0).toLocaleString() }}</div>
          </div>
          <div class="p-4 rounded-xl bg-slate-950/60 border border-slate-800">
            <div class="text-[10px] uppercase font-bold text-slate-400">Records Updated</div>
            <div class="text-lg font-black text-sky-400 mt-1">{{ (importSummary?.records_updated || 0).toLocaleString() }}</div>
          </div>
          <div class="p-4 rounded-xl bg-slate-950/60 border border-slate-800">
            <div class="text-[10px] uppercase font-bold text-slate-400">Errors</div>
            <div class="text-lg font-black text-rose-400 mt-1">{{ importSummary?.records_failed || 0 }}</div>
          </div>
        </div>

        <!-- Final Action Buttons -->
        <div class="flex flex-wrap items-center justify-center gap-3 pt-6">
          <Link
            href="/admin/database/tables"
            class="px-5 py-2.5 bg-red-600 hover:bg-red-500 text-white font-black text-xs rounded-xl shadow-lg shadow-red-600/20 flex items-center gap-1.5"
          >
            <TableCellsIcon class="w-4 h-4" />
            <span>View Database Tables</span>
          </Link>
          <Link
            :href="`/admin/database/import/${currentImportId}`"
            class="px-5 py-2.5 bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 font-bold text-xs rounded-xl flex items-center gap-1.5"
          >
            <EyeIcon class="w-4 h-4 text-sky-400" />
            <span>View Import Report</span>
          </Link>
          <a
            :href="`/admin/database/import/${currentImportId}/errors/export`"
            class="px-5 py-2.5 bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 font-bold text-xs rounded-xl flex items-center gap-1.5"
          >
            <DocumentArrowDownIcon class="w-4 h-4 text-emerald-400" />
            <span>Download Error Report</span>
          </a>
          <Link
            href="/admin"
            class="px-5 py-2.5 bg-slate-900 hover:bg-slate-800 text-slate-400 font-bold text-xs rounded-xl"
          >
            Back to Dashboard
          </Link>
        </div>
      </div>
    </main>
  </div>
</template>
