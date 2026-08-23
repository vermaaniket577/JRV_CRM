<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { 
  XMarkIcon, 
  ArrowUpTrayIcon, 
  DocumentArrowDownIcon, 
  DocumentTextIcon, 
  CheckCircleIcon,
  ExclamationCircleIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
  isOpen: {
    type: Boolean,
    default: false,
  },
  title: {
    type: String,
    default: 'Import Data Records (CSV)',
  },
  importUrl: {
    type: String,
    required: true,
  },
  sampleUrl: {
    type: String,
    required: true,
  },
  description: {
    type: String,
    default: 'Upload a standard CSV or Excel file to bulk import records into your CRM.',
  }
});

const emit = defineEmits(['close', 'success']);

const fileInput = ref(null);
const selectedFile = ref(null);
const previewRows = ref([]);
const fileError = ref('');

const form = useForm({
  file: null,
});

const onFileChange = (e) => {
  const file = e.target.files[0];
  handleFile(file);
};

const handleFile = (file) => {
  if (!file) return;
  fileError.value = '';

  if (!file.name.endsWith('.csv') && !file.name.endsWith('.txt')) {
    fileError.value = 'Please select a valid CSV (.csv) file.';
    return;
  }

  selectedFile.value = file;
  form.file = file;

  // Read first few rows for instant visual preview
  const reader = new FileReader();
  reader.onload = (event) => {
    const text = event.target.result;
    const lines = text.split('\n').filter(l => l.trim().length > 0).slice(0, 4);
    previewRows.value = lines.map(line => line.split(',').map(c => c.replace(/^["']|["']$/g, '').trim()));
  };
  reader.readAsText(file);
};

const submit = () => {
  if (!form.file) {
    fileError.value = 'Please upload a CSV file before submitting.';
    return;
  }

  form.post(props.importUrl, {
    preserveScroll: true,
    onSuccess: () => {
      selectedFile.value = null;
      previewRows.value = [];
      form.reset();
      emit('success');
      emit('close');
    },
    onError: (err) => {
      fileError.value = Object.values(err)[0] || 'Import failed. Please check your CSV format.';
    }
  });
};
</script>

<template>
  <div v-if="isOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs overflow-y-auto">
    <div class="bg-white border border-slate-200 rounded-3xl max-w-lg w-full shadow-2xl flex flex-col max-h-[90vh] overflow-y-auto my-auto">
      
      <!-- Modal Header -->
      <div class="p-6 border-b border-slate-100 flex items-center justify-between sticky top-0 bg-white z-10">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-2xl bg-red-50 text-red-600 flex items-center justify-center font-black">
            <ArrowUpTrayIcon class="w-5 h-5 stroke-[2.5]" />
          </div>
          <div>
            <h3 class="text-base font-black text-slate-900">{{ title }}</h3>
            <p class="text-xs text-slate-500 font-medium">{{ description }}</p>
          </div>
        </div>

        <button @click="emit('close')" class="text-slate-400 hover:text-slate-600 p-1 rounded-lg">
          <XMarkIcon class="w-5 h-5 stroke-[2.5]" />
        </button>
      </div>

      <!-- Modal Body -->
      <div class="p-6 space-y-5">
        
        <!-- Step 1: Download Sample Template -->
        <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200 flex items-center justify-between gap-3">
          <div class="space-y-0.5">
            <h4 class="text-xs font-black text-slate-900">Need the correct column format?</h4>
            <p class="text-[11px] text-slate-500 font-medium">Download our pre-filled CSV sample template to avoid formatting errors.</p>
          </div>

          <a 
            :href="sampleUrl" 
            download
            class="px-3.5 py-2 bg-white hover:bg-slate-100 text-slate-700 border border-slate-300 text-xs font-bold rounded-xl flex items-center gap-1.5 shadow-2xs shrink-0 transition"
          >
            <DocumentArrowDownIcon class="w-4 h-4 text-slate-500" />
            <span>Sample CSV</span>
          </a>
        </div>

        <!-- Step 2: Drag and Drop Upload Area -->
        <div 
          @click="$refs.fileInput.click()"
          class="border-2 border-dashed border-slate-300 hover:border-red-500 bg-slate-50/50 hover:bg-red-50/20 rounded-3xl p-8 text-center cursor-pointer transition space-y-3 group"
        >
          <input 
            ref="fileInput" 
            type="file" 
            accept=".csv,text/csv,text/plain" 
            class="hidden" 
            @change="onFileChange" 
          />

          <div class="w-14 h-14 mx-auto rounded-full bg-white border border-slate-200 text-slate-400 group-hover:text-red-600 group-hover:border-red-300 flex items-center justify-center transition shadow-xs">
            <DocumentTextIcon class="w-7 h-7" />
          </div>

          <div class="space-y-1">
            <p class="text-xs font-black text-slate-800 group-hover:text-red-700">
              {{ selectedFile ? selectedFile.name : 'Click to Browse or Drag & Drop CSV File' }}
            </p>
            <p class="text-[11px] text-slate-400 font-medium">
              Supports .CSV or .TXT files up to 10MB
            </p>
          </div>
        </div>

        <!-- File Error Notice -->
        <div v-if="fileError" class="p-3 rounded-xl bg-red-50 border border-red-200 text-red-700 text-xs font-semibold flex items-center gap-2">
          <ExclamationCircleIcon class="w-4 h-4 shrink-0" />
          <span>{{ fileError }}</span>
        </div>

        <!-- Preview Rows Table -->
        <div v-if="previewRows.length > 0" class="space-y-2">
          <div class="flex items-center justify-between text-xs font-bold text-slate-700">
            <span>Detected Columns & Data Preview:</span>
            <span class="text-[10px] text-emerald-600 font-black flex items-center gap-1">
              <CheckCircleIcon class="w-3.5 h-3.5" />
              <span>Ready for Import</span>
            </span>
          </div>

          <div class="bg-slate-900 text-slate-200 text-[11px] p-3 rounded-2xl overflow-x-auto max-h-36">
            <table class="w-full text-left font-mono">
              <tbody>
                <tr v-for="(row, idx) in previewRows" :key="idx" :class="idx === 0 ? 'text-emerald-300 font-bold border-b border-slate-700' : 'text-slate-300 border-b border-slate-800/50'">
                  <td v-for="(cell, cIdx) in row.slice(0, 5)" :key="cIdx" class="py-1 px-2 whitespace-nowrap">
                    {{ cell }}
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

      </div>

      <!-- Modal Footer Actions -->
      <div class="p-6 border-t border-slate-100 flex items-center justify-end gap-3 bg-slate-50/50 rounded-b-3xl">
        <button 
          @click="emit('close')" 
          type="button" 
          class="px-4 py-2.5 bg-white hover:bg-slate-100 text-slate-700 font-bold text-xs rounded-xl border border-slate-200 transition"
        >
          Cancel
        </button>

        <button 
          @click="submit"
          :disabled="!selectedFile || form.processing"
          class="px-6 py-2.5 bg-red-600 hover:bg-red-700 disabled:opacity-50 text-white font-black text-xs rounded-xl shadow-md shadow-red-600/20 flex items-center gap-2 transition cursor-pointer"
        >
          <div v-if="form.processing" class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></div>
          <ArrowUpTrayIcon v-else class="w-4 h-4 stroke-[3]" />
          <span>{{ form.processing ? 'Importing Data...' : 'Import Records Now' }}</span>
        </button>
      </div>

    </div>
  </div>
</template>
