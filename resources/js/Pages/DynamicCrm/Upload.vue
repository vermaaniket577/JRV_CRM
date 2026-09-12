<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import Navbar from '@/Components/Navbar.vue';
import {
  ArrowUpTrayIcon, DocumentTextIcon, CheckCircleIcon,
  XCircleIcon, ArrowPathIcon, CircleStackIcon,
  TableCellsIcon, LinkIcon, ClipboardDocumentListIcon,
  ExclamationTriangleIcon, SparklesIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
  tenant: Object,
  existingDatabases: Array,
});

const file = ref(null);
const isDragging = ref(false);
const isUploading = ref(false);
const uploadProgress = ref(0);
const uploadResult = ref(null);
const uploadError = ref(null);
const currentStep = ref(0);

const steps = [
  { label: 'Uploading file', icon: ArrowUpTrayIcon },
  { label: 'Parsing SQL schema', icon: DocumentTextIcon },
  { label: 'Detecting tables & relationships', icon: LinkIcon },
  { label: 'Importing data', icon: CircleStackIcon },
  { label: 'Generating CRM', icon: SparklesIcon },
  { label: 'Complete!', icon: CheckCircleIcon },
];

const onDragOver = (e) => { e.preventDefault(); isDragging.value = true; };
const onDragLeave = () => { isDragging.value = false; };
const onDrop = (e) => { e.preventDefault(); isDragging.value = false; if (e.dataTransfer.files[0]) selectFile(e.dataTransfer.files[0]); };
const onFileSelect = (e) => { if (e.target.files[0]) selectFile(e.target.files[0]); };

const selectFile = (f) => {
  const ext = f.name.split('.').pop().toLowerCase();
  if (!['sql', 'txt', 'dump'].includes(ext)) {
    uploadError.value = 'Invalid file type. Please upload a .sql, .txt, or .dump file.';
    return;
  }
  if (f.size > 100 * 1024 * 1024) {
    uploadError.value = 'File too large. Maximum size is 100MB.';
    return;
  }
  file.value = f;
  uploadError.value = null;
};

const fileSize = computed(() => {
  if (!file.value) return '';
  const bytes = file.value.size;
  if (bytes < 1024) return bytes + ' B';
  if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
  return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
});

const uploadFile = async () => {
  if (!file.value) return;
  isUploading.value = true;
  uploadError.value = null;
  uploadResult.value = null;
  currentStep.value = 0;

  const formData = new FormData();
  formData.append('file', file.value);

  // Animate steps
  const stepInterval = setInterval(() => {
    if (currentStep.value < 4) currentStep.value++;
  }, 1200);

  try {
    const res = await fetch('/dynamic-crm/database/upload', {
      method: 'POST',
      body: formData,
      headers: {
        'Accept': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
      },
    });

    clearInterval(stepInterval);
    const data = await res.json();

    if (data.success) {
      currentStep.value = 5;
      uploadResult.value = data;
      setTimeout(() => {
        if (data.redirect_url) {
          window.location.href = data.redirect_url;
        } else {
          router.visit('/dynamic-crm/dashboard');
        }
      }, 2500);
    } else {
      uploadError.value = data.error || 'Upload failed.';
      currentStep.value = 0;
    }
  } catch (e) {
    clearInterval(stepInterval);
    uploadError.value = 'Connection error. Please try again.';
    currentStep.value = 0;
  }

  isUploading.value = false;
};

const removeFile = () => { file.value = null; uploadResult.value = null; uploadError.value = null; currentStep.value = 0; };
</script>

<template>
  <Head title="Upload Database" />
  <div class="min-h-screen bg-[#0f0f23] flex font-sans text-slate-100">
    <Navbar />

    <main class="flex-1 min-w-0 overflow-y-auto">
      <div class="upload-page">
        <div class="upload-container">
      <!-- Header -->
      <div class="upload-header">
        <Link href="/dynamic-crm/dashboard" class="back-link">← Back to Dashboard</Link>
        <h1>Upload SQL Database</h1>
        <p>Upload a .sql database dump file and we'll automatically generate a complete CRM interface</p>
      </div>

      <!-- Upload Zone -->
      <div
        v-if="!isUploading && !uploadResult"
        class="drop-zone"
        :class="{ 'drop-zone-active': isDragging, 'drop-zone-has-file': file }"
        @dragover="onDragOver"
        @dragleave="onDragLeave"
        @drop="onDrop"
      >
        <div v-if="!file" class="drop-zone-content">
          <div class="drop-icon-wrap">
            <ArrowUpTrayIcon class="w-12 h-12" />
          </div>
          <h3>Drag & Drop your SQL file here</h3>
          <p>or click to browse</p>
          <p class="file-types">Supported: .sql, .txt, .dump — Max 100MB</p>
          <input type="file" accept=".sql,.txt,.dump" @change="onFileSelect" class="file-input" />
        </div>

        <div v-else class="file-preview">
          <DocumentTextIcon class="w-10 h-10 text-indigo-400" />
          <div class="file-info">
            <h4>{{ file.name }}</h4>
            <p>{{ fileSize }}</p>
          </div>
          <button @click="removeFile" class="btn-remove">✕</button>
        </div>
      </div>

      <!-- Error -->
      <div v-if="uploadError" class="error-banner">
        <ExclamationTriangleIcon class="w-5 h-5" />
        <span>{{ uploadError }}</span>
      </div>

      <!-- Upload Button -->
      <div v-if="file && !isUploading && !uploadResult" class="upload-actions">
        <button @click="uploadFile" class="btn-upload">
          <CircleStackIcon class="w-5 h-5" />
          Deploy Database to CRM
        </button>
      </div>

      <!-- Progress -->
      <div v-if="isUploading || uploadResult" class="progress-panel">
        <h2>{{ uploadResult ? '✅ Import Complete!' : 'Importing Database...' }}</h2>
        <div class="steps-list">
          <div
            v-for="(step, idx) in steps"
            :key="idx"
            class="step-item"
            :class="{ 'step-active': idx === currentStep, 'step-done': idx < currentStep, 'step-pending': idx > currentStep }"
          >
            <div class="step-icon">
              <CheckCircleIcon v-if="idx < currentStep" class="w-5 h-5 text-emerald-400" />
              <ArrowPathIcon v-else-if="idx === currentStep && !uploadResult" class="w-5 h-5 text-indigo-400 animate-spin" />
              <component v-else-if="idx === currentStep && uploadResult" :is="CheckCircleIcon" class="w-5 h-5 text-emerald-400" />
              <component v-else :is="step.icon" class="w-5 h-5 text-gray-600" />
            </div>
            <span>{{ step.label }}</span>
          </div>
        </div>

        <!-- Result Summary -->
        <div v-if="uploadResult?.details" class="result-summary">
          <div class="result-item"><span>✔ SQL file uploaded</span></div>
          <div class="result-item"><span>✔ Schema detected</span></div>
          <div class="result-item"><span>✔ {{ uploadResult.details.tables_count }} tables detected</span></div>
          <div class="result-item"><span>✔ {{ uploadResult.details.relationships_count }} relationships detected</span></div>
          <div class="result-item"><span>✔ {{ uploadResult.details.total_records?.toLocaleString() }} records detected</span></div>
          <div class="result-item highlight"><span>✔ CRM generated successfully</span></div>
          <p class="redirect-msg">Redirecting to your CRM dashboard...</p>
        </div>
      </div>

      <!-- Existing Databases -->
      <div v-if="existingDatabases && existingDatabases.length > 0" class="existing-section">
        <h2>Previously Uploaded Databases</h2>
        <div class="existing-list">
          <div v-for="db in existingDatabases" :key="db.id" class="existing-item">
            <CircleStackIcon class="w-8 h-8 text-indigo-400" />
            <div class="existing-info">
              <h4>{{ db.name }}</h4>
              <p>{{ db.tables_count }} tables · {{ db.total_records?.toLocaleString() }} records · {{ db.imported_at }}</p>
            </div>
            <span class="status-badge" :class="db.status === 'active' ? 'status-active' : 'status-other'">{{ db.status }}</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</main>
</div>
</template>

<style scoped>
.upload-page {
  min-height: 100vh;
  background: linear-gradient(135deg, #0f0f23 0%, #1a1a3e 50%, #0f0f23 100%);
  padding: 32px 24px 40px;
  color: #e2e8f0;
}
.upload-container { max-width: 720px; margin: 0 auto; }
.upload-header { text-align: center; margin-bottom: 32px; }
.back-link { color: #8b5cf6; text-decoration: none; font-size: 0.9rem; display: inline-block; margin-bottom: 16px; }
.upload-header h1 { font-size: 2rem; font-weight: 800; color: #fff; margin: 0 0 8px; }
.upload-header p { color: #94a3b8; font-size: 0.95rem; margin: 0; }

.drop-zone {
  position: relative; padding: 48px; border: 2px dashed rgba(99, 102, 241, 0.3);
  border-radius: 20px; text-align: center; cursor: pointer;
  background: rgba(30, 30, 60, 0.4); transition: all 0.3s; margin-bottom: 24px;
}
.drop-zone:hover, .drop-zone-active { border-color: #6366f1; background: rgba(99, 102, 241, 0.08); }
.drop-zone-has-file { border-color: #34d399; border-style: solid; }
.drop-zone-content h3 { color: #fff; font-size: 1.2rem; margin: 16px 0 8px; }
.drop-zone-content p { color: #94a3b8; margin: 0 0 4px; }
.file-types { font-size: 0.8rem; color: #64748b; }
.drop-icon-wrap { color: #6366f1; }
.file-input { position: absolute; inset: 0; opacity: 0; cursor: pointer; }

.file-preview {
  display: flex; align-items: center; gap: 16px; justify-content: center;
}
.file-info h4 { color: #fff; margin: 0; font-size: 1rem; }
.file-info p { color: #64748b; margin: 0; font-size: 0.85rem; }
.btn-remove { background: rgba(239, 68, 68, 0.2); border: none; color: #f87171; width: 32px; height: 32px; border-radius: 8px; cursor: pointer; font-size: 1rem; }

.error-banner {
  display: flex; align-items: center; gap: 10px; padding: 14px 20px;
  background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.3);
  border-radius: 12px; color: #fca5a5; margin-bottom: 24px;
}

.upload-actions { text-align: center; margin-bottom: 24px; }
.btn-upload {
  display: inline-flex; align-items: center; gap: 10px; padding: 14px 32px;
  background: linear-gradient(135deg, #6366f1, #8b5cf6); color: white;
  border: none; border-radius: 14px; font-size: 1rem; font-weight: 700;
  cursor: pointer; transition: all 0.3s;
  box-shadow: 0 4px 20px rgba(99, 102, 241, 0.3);
}
.btn-upload:hover { transform: translateY(-2px); box-shadow: 0 8px 30px rgba(99, 102, 241, 0.4); }

.progress-panel {
  padding: 32px; background: rgba(30, 30, 60, 0.6);
  border: 1px solid rgba(99, 102, 241, 0.1); border-radius: 20px; margin-bottom: 32px;
}
.progress-panel h2 { color: #fff; font-size: 1.3rem; margin: 0 0 24px; text-align: center; }
.steps-list { display: flex; flex-direction: column; gap: 16px; margin-bottom: 24px; }
.step-item { display: flex; align-items: center; gap: 12px; font-size: 0.95rem; }
.step-done { color: #34d399; }
.step-active { color: #c4b5fd; font-weight: 600; }
.step-pending { color: #475569; }
.step-icon { width: 28px; display: flex; justify-content: center; }

.result-summary { padding: 20px; background: rgba(16, 185, 129, 0.08); border: 1px solid rgba(16, 185, 129, 0.2); border-radius: 14px; }
.result-item { padding: 6px 0; font-size: 0.9rem; color: #34d399; }
.result-item.highlight { font-weight: 700; font-size: 1rem; }
.redirect-msg { text-align: center; color: #8b5cf6; margin: 16px 0 0; font-weight: 600; animation: pulse 1.5s infinite; }
@keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.5; } }

.existing-section { margin-top: 40px; }
.existing-section h2 { font-size: 1.1rem; color: #fff; margin: 0 0 16px; }
.existing-list { display: flex; flex-direction: column; gap: 12px; }
.existing-item {
  display: flex; align-items: center; gap: 16px; padding: 16px 20px;
  background: rgba(30, 30, 60, 0.5); border: 1px solid rgba(99, 102, 241, 0.1);
  border-radius: 14px;
}
.existing-info h4 { color: #fff; margin: 0; font-size: 0.95rem; }
.existing-info p { color: #64748b; margin: 0; font-size: 0.8rem; }
.status-badge { padding: 4px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: 600; }
.status-active { background: rgba(16, 185, 129, 0.15); color: #34d399; }
.status-other { background: rgba(99, 102, 241, 0.15); color: #c4b5fd; }
</style>
