<script setup>
import { ref, computed } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import { 
  CircleStackIcon, 
  XMarkIcon, 
  CloudArrowUpIcon, 
  DocumentArrowUpIcon, 
  CheckCircleIcon,
  ExclamationCircleIcon,
  TableCellsIcon,
  ArrowTopRightOnSquareIcon,
  SparklesIcon,
  ShieldCheckIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
  isOpen: {
    type: Boolean,
    default: false,
  },
  tenantSubdomain: {
    type: String,
    default: '',
  }
});

const emit = defineEmits(['close']);

// Tab: 'sql' (Database Backup) vs 'spreadsheet' (Excel / CSV)
const uploadType = ref('sql');
const isDragging = ref(false);
const selectedFile = ref(null);
const fileInputRef = ref(null);
const uploadSuccess = ref(false);
const successMessage = ref('');
const errorMessage = ref('');

// SQL Upload Form
const sqlForm = useForm({
  file: null,
});

// Spreadsheet Upload Form
const excelForm = useForm({
  file: null,
  entity_type: 'crm_records',
});

const targetEntities = [
  { id: 'contacts', name: '👥 Leads & Clients', desc: 'Name, email, phone, status, notes' },
  { id: 'properties', name: '🏠 Properties & Listings', desc: 'Title, type, price, address, status' },
  { id: 'crm_records', name: '📊 Dynamic CRM Records', desc: 'Matches custom columns and dynamic fields' },
];

const handleFileSelect = (file) => {
  if (!file) return;
  selectedFile.value = file;
  errorMessage.value = '';

  const ext = file.name.split('.').pop().toLowerCase();
  if (['sql', 'dump'].includes(ext)) {
    uploadType.value = 'sql';
    sqlForm.file = file;
  } else if (['xlsx', 'xls', 'csv', 'tsv', 'json'].includes(ext)) {
    uploadType.value = 'spreadsheet';
    excelForm.file = file;
  } else {
    // Default to SQL form
    sqlForm.file = file;
  }
};

const onFileInputChange = (e) => {
  const file = e.target.files[0];
  if (file) handleFileSelect(file);
};

const onDrop = (e) => {
  isDragging.value = false;
  const file = e.dataTransfer.files[0];
  if (file) handleFileSelect(file);
};

const formatFileSize = (bytes) => {
  if (!bytes) return '0 B';
  const k = 1024;
  const sizes = ['B', 'KB', 'MB', 'GB'];
  const i = Math.floor(Math.log(bytes) / Math.log(k));
  return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
};

const isProcessing = computed(() => sqlForm.processing || excelForm.processing);

const submitUpload = () => {
  errorMessage.value = '';
  uploadSuccess.value = false;

  const fileToUpload = selectedFile.value || (uploadType.value === 'sql' ? sqlForm.file : excelForm.file);
  if (!fileToUpload) {
    errorMessage.value = uploadType.value === 'sql' 
      ? 'Please choose a .sql database backup file to upload.'
      : 'Please select an Excel or CSV file to import.';
    return;
  }

  const uploadForm = uploadType.value === 'sql' ? sqlForm : excelForm;
  uploadForm.file = fileToUpload;

  uploadForm.post('/tenant/database/upload', {
    preserveScroll: false,
    onSuccess: () => {
      uploadSuccess.value = true;
      successMessage.value = 'Database table created and records loaded into CRM successfully!';
      setTimeout(() => {
        closeModal();
      }, 1500);
    },
    onError: (errors) => {
      errorMessage.value = errors.file || errors.message || 'Failed to upload database file.';
    }
  });
};

const closeModal = () => {
  selectedFile.value = null;
  sqlForm.reset();
  excelForm.reset();
  uploadSuccess.value = false;
  errorMessage.value = '';
  emit('close');
};
</script>

<template>
  <Teleport to="body">
    <div 
      v-if="isOpen" 
      class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-md transition-all duration-300"
    >
      <div 
        class="relative w-full max-w-xl bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-3xl p-6 sm:p-8 shadow-2xl text-slate-900 dark:text-white space-y-6 overflow-hidden"
      >
        <!-- Close Button -->
        <button 
          @click="closeModal" 
          type="button"
          class="absolute top-5 right-5 p-2 rounded-xl text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 transition cursor-pointer"
        >
          <XMarkIcon class="w-5 h-5" />
        </button>

        <!-- Header -->
        <div class="flex items-center gap-3.5 pr-8">
          <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-emerald-500 to-teal-600 text-white flex items-center justify-center shadow-lg shadow-emerald-500/20 shrink-0">
            <CircleStackIcon class="w-6 h-6" />
          </div>
          <div>
            <h2 class="text-xl font-bold tracking-tight text-slate-900 dark:text-white flex items-center gap-2">
              <span>Upload Database & Records</span>
              <span class="px-2 py-0.5 text-[10px] font-bold bg-emerald-100 dark:bg-emerald-500/20 text-emerald-700 dark:text-emerald-400 rounded-full border border-emerald-300 dark:border-emerald-500/30">
                User Panel
              </span>
            </h2>
            <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
              Import a .SQL database dump or bulk upload client spreadsheets into your CRM.
            </p>
          </div>
        </div>

        <!-- Format Selector Tabs -->
        <div class="grid grid-cols-2 gap-2 p-1.5 bg-slate-100 dark:bg-slate-800/70 rounded-2xl border border-slate-200 dark:border-slate-700/60">
          <button
            type="button"
            @click="uploadType = 'sql'"
            :class="[
              'py-2.5 px-3 rounded-xl text-xs font-bold transition flex items-center justify-center gap-2 cursor-pointer',
              uploadType === 'sql' 
                ? 'bg-white dark:bg-slate-900 text-emerald-600 dark:text-emerald-400 shadow-sm' 
                : 'text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-white'
            ]"
          >
            <CircleStackIcon class="w-4 h-4" />
            <span>.SQL Database Dump</span>
          </button>

          <button
            type="button"
            @click="uploadType = 'spreadsheet'"
            :class="[
              'py-2.5 px-3 rounded-xl text-xs font-bold transition flex items-center justify-center gap-2 cursor-pointer',
              uploadType === 'spreadsheet' 
                ? 'bg-white dark:bg-slate-900 text-emerald-600 dark:text-emerald-400 shadow-sm' 
                : 'text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-white'
            ]"
          >
            <TableCellsIcon class="w-4 h-4" />
            <span>Excel / CSV / JSON</span>
          </button>
        </div>

        <!-- Feedback Alert Messages -->
        <div v-if="uploadSuccess" class="p-3.5 bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 rounded-2xl text-emerald-800 dark:text-emerald-300 text-xs flex items-center gap-2.5">
          <CheckCircleIcon class="w-5 h-5 shrink-0 text-emerald-600 dark:text-emerald-400" />
          <span class="font-medium">{{ successMessage }}</span>
        </div>

        <div v-if="errorMessage" class="p-3.5 bg-red-50 dark:bg-red-950/40 border border-red-200 dark:border-red-800 rounded-2xl text-red-800 dark:text-red-300 text-xs flex items-center gap-2.5">
          <ExclamationCircleIcon class="w-5 h-5 shrink-0 text-red-600 dark:text-red-400" />
          <span class="font-medium">{{ errorMessage }}</span>
        </div>

        <!-- Target Entity (if Spreadsheet Mode) -->
        <div v-if="uploadType === 'spreadsheet'" class="space-y-2">
          <label class="text-xs font-semibold text-slate-700 dark:text-slate-300 block">Select Destination Table</label>
          <div class="grid grid-cols-1 sm:grid-cols-3 gap-2">
            <label
              v-for="entity in targetEntities"
              :key="entity.id"
              :class="[
                'p-2.5 rounded-xl border text-left cursor-pointer transition-all',
                excelForm.entity_type === entity.id
                  ? 'border-emerald-500 bg-emerald-50/50 dark:bg-emerald-950/30 ring-1 ring-emerald-500/50 text-emerald-900 dark:text-emerald-300'
                  : 'border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-900 text-slate-600 dark:text-slate-400 hover:border-slate-300'
              ]"
            >
              <input type="radio" v-model="excelForm.entity_type" :value="entity.id" class="sr-only" />
              <div class="text-xs font-bold">{{ entity.name }}</div>
              <div class="text-[10px] text-slate-400 mt-0.5 line-clamp-1">{{ entity.desc }}</div>
            </label>
          </div>
        </div>

        <!-- Drag and Drop Zone -->
        <div
          @dragover.prevent="isDragging = true"
          @dragleave.prevent="isDragging = false"
          @drop.prevent="onDrop"
          :class="[
            'border-2 border-dashed rounded-2xl p-6 sm:p-8 text-center transition-all flex flex-col items-center justify-center gap-2.5 relative',
            isDragging ? 'border-emerald-500 bg-emerald-50/40 dark:bg-emerald-950/20 scale-[1.01]' : 'border-slate-300 dark:border-slate-700 bg-slate-50/60 dark:bg-slate-800/40 hover:bg-slate-50 dark:hover:bg-slate-800/70'
          ]"
        >
          <input 
            type="file" 
            ref="fileInputRef" 
            @change="onFileInputChange" 
            :accept="uploadType === 'sql' ? '.sql,.dump,.txt' : '.xlsx,.xls,.csv,.tsv,.json'" 
            class="sr-only" 
          />

          <div class="w-14 h-14 rounded-2xl bg-emerald-100 dark:bg-emerald-500/20 text-emerald-600 dark:text-emerald-400 flex items-center justify-center text-2xl shadow-sm">
            <span v-if="uploadType === 'sql'">🗄️</span>
            <span v-else>📊</span>
          </div>

          <div class="space-y-1">
            <div class="text-sm font-bold text-slate-800 dark:text-slate-200">
              <span v-if="selectedFile">{{ selectedFile.name }}</span>
              <span v-else>Drag & drop your {{ uploadType === 'sql' ? '.sql database backup' : 'spreadsheet' }} here</span>
            </div>
            <p class="text-xs text-slate-500 dark:text-slate-400">
              <span v-if="selectedFile">{{ formatFileSize(selectedFile.size) }} • Ready to process</span>
              <span v-else>Or click browse to select a file from your computer (Up to 50MB)</span>
            </p>
          </div>

          <button 
            type="button" 
            @click="fileInputRef?.click()" 
            class="mt-1 px-4 py-2 bg-white dark:bg-slate-700 hover:bg-slate-100 dark:hover:bg-slate-600 border border-slate-300 dark:border-slate-600 text-slate-700 dark:text-slate-200 text-xs font-semibold rounded-xl shadow-xs transition cursor-pointer"
          >
            {{ selectedFile ? 'Choose Another File' : 'Browse Files' }}
          </button>
        </div>

        <!-- Footer Buttons & Direct Hub Link -->
        <div class="flex flex-col sm:flex-row items-center justify-between gap-3 pt-3 border-t border-slate-100 dark:border-slate-800">
          <a 
            href="/data-import" 
            class="text-xs font-semibold text-emerald-600 dark:text-emerald-400 hover:underline flex items-center gap-1.5"
          >
            <span>Open Advanced Data Import Hub</span>
            <ArrowTopRightOnSquareIcon class="w-3.5 h-3.5" />
          </a>

          <div class="flex items-center gap-2 w-full sm:w-auto">
            <button
              type="button"
              @click="closeModal"
              class="flex-1 sm:flex-initial px-4 py-2.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 text-xs font-bold rounded-xl transition cursor-pointer"
            >
              Cancel
            </button>
            <button
              type="button"
              @click="submitUpload"
              :disabled="isProcessing || !selectedFile"
              class="flex-1 sm:flex-initial px-6 py-2.5 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 text-white text-xs font-extrabold rounded-xl shadow-md transition flex items-center justify-center gap-2 disabled:opacity-40 cursor-pointer"
            >
              <CloudArrowUpIcon class="w-4 h-4" />
              <span>{{ isProcessing ? 'Importing...' : 'Upload & Restore Now' }}</span>
            </button>
          </div>
        </div>
      </div>
    </div>
  </Teleport>
</template>
