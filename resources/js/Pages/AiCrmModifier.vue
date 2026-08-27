<script setup>
import { ref, computed } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import Navbar from '@/Components/Navbar.vue';
import {
  SparklesIcon,
  CpuChipIcon,
  CheckCircleIcon,
  ArrowPathIcon,
  ShieldCheckIcon,
  LockClosedIcon,
  RocketLaunchIcon,
  TableCellsIcon,
  AdjustmentsHorizontalIcon,
  DocumentCheckIcon,
  Squares2X2Icon,
  BoltIcon,
  CheckBadgeIcon,
  FireIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
  isPaidUser: {
    type: Boolean,
    default: false,
  },
  currentConfig: {
    type: Object,
    default: () => ({}),
  },
  presets: {
    type: Array,
    default: () => [],
  },
  plans: {
    type: Array,
    default: () => [],
  },
  currentTier: {
    type: String,
    default: 'Free Trial',
  },
});

const promptInput = ref('');
const selectedPreset = ref(props.presets?.[0]?.id || 'cardiology_clinic');
const isGenerating = ref(false);
const showUpgradeModal = ref(false);
const activePreviewTab = ref('pipeline'); // 'pipeline', 'fields', 'nav', 'automations'

// Active generated configuration
const activeConfig = ref(props.presets?.[0] || {
  id: 'cardiology_clinic',
  name: 'Cardiology & Cath-Lab Center',
  icon: '❤️',
  color: 'rose',
  specialty: 'Cardiology & Cardiovascular Care',
  description: 'ECG triage, Angiography scheduling, Holter monitoring, and Cardiac ICU bed allocation.',
  prompt: 'Transform my CRM into a full-scale Cardiology and Cardiovascular Surgery Center with triage stages, risk factor scoring, and post-op rehabilitation tracking.',
  pipeline_stages: [
    { name: 'Emergency / Outpatient Triage', color: 'rose', sla_hours: 2 },
    { name: 'ECG & Echo Diagnostics', color: 'amber', sla_hours: 12 },
    { name: 'Cardiologist Consultation', color: 'indigo', sla_hours: 24 },
    { name: 'Cath-Lab / Angiography', color: 'purple', sla_hours: 48 },
    { name: 'Cardiac ICU / Post-Op Care', color: 'teal', sla_hours: 72 },
    { name: 'Discharge & Cardiac Rehab', color: 'emerald', sla_hours: 168 },
  ],
  custom_fields: [
    { key: 'blood_pressure', label: 'Blood Pressure (mmHg)', type: 'text', default: '120/80' },
    { key: 'cardiac_risk_score', label: 'Framingham Risk Score', type: 'select', options: ['Low (<10%)', 'Moderate (10-20%)', 'High (>20%)'] },
    { key: 'ecg_interpretation', label: 'ECG Finding Summary', type: 'text', default: 'Normal Sinus Rhythm' },
    { key: 'lead_cardiologist', label: 'Assigned Cardiologist', type: 'text', default: 'Dr. Mehta, MD' },
  ],
  nav_items: [
    { key: 'dashboard', label: 'Cardiac Dashboard', route: '/', icon: 'ChartBarIcon' },
    { key: 'patients', label: 'Cardiac Patients', route: '/contacts', icon: 'UserGroupIcon' },
    { key: 'cath_pipeline', label: 'Cath-Lab Pipeline', route: '/deals', icon: 'Square3Stack3DIcon' },
    { key: 'staff_recruit', 'label': 'Medical Staff', route: '/staff-recruitment', icon: 'BriefcaseIcon' },
  ],
  automations: [
    'Auto-notify On-Duty Cardiologist if Blood Pressure systolic > 180 mmHg',
    'Send automated pre-cath lab fasting instructions via WhatsApp 12h before procedure',
    'Schedule 30-day Post-Angioplasty Follow-up consultation automatically',
  ],
});

// Select a preset template
const selectPreset = (preset) => {
  selectedPreset.value = preset.id;
  promptInput.value = preset.prompt;
  activeConfig.value = JSON.parse(JSON.stringify(preset));
};

// Generate from prompt
const generateAiCrm = async () => {
  if (!promptInput.value && !selectedPreset.value) return;

  isGenerating.value = true;
  try {
    const response = await fetch('/ai-crm-modifier/generate', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
        'Accept': 'application/json',
      },
      body: JSON.stringify({
        prompt: promptInput.value,
        preset_id: selectedPreset.value,
      }),
    });

    const data = await response.json();
    if (data.success && data.config) {
      activeConfig.value = data.config;
    }
  } catch (err) {
    console.error('AI Generation Error:', err);
  } finally {
    isGenerating.value = false;
  }
};

// Apply Form
const applyForm = useForm({
  config: {},
});

const handleApplyToWorkspace = () => {
  if (!props.isPaidUser) {
    showUpgradeModal.value = true;
    return;
  }

  applyForm.config = activeConfig.value;
  applyForm.post('/ai-crm-modifier/apply', {
    preserveScroll: true,
  });
};

// Quick 1-click Paid Plan activation for testing/demo
const activatePaidPlan = (tier = 'growth') => {
  router.post('/ai-crm-modifier/activate-paid', { tier }, {
    preserveScroll: true,
    onSuccess: () => {
      showUpgradeModal.value = false;
    }
  });
};
</script>

<template>
  <Head title="AI CRM Studio - Multi-Specialty Modifier" />

  <div class="flex h-screen bg-slate-50 overflow-hidden font-sans">
    <!-- Sidebar Navigation -->
    <Navbar />

    <!-- Main Content Area -->
    <div class="flex-1 flex flex-col min-w-0 overflow-hidden">
      <!-- Top Bar Header -->
      <header class="h-16 bg-white border-b border-slate-200 px-6 flex items-center justify-between shrink-0 shadow-2xs">
        <div class="flex items-center gap-3">
          <div class="w-9 h-9 bg-gradient-to-tr from-purple-600 to-indigo-600 rounded-xl flex items-center justify-center text-white font-black text-sm shadow-md">
            ✨
          </div>
          <div>
            <h1 class="text-sm font-black text-slate-900 leading-tight">AI CRM Studio</h1>
            <p class="text-[11px] text-slate-500 font-medium">Multi-Specialty Healthcare & Workflow Generator</p>
          </div>
        </div>

        <div class="flex items-center gap-3">
          <!-- Paid User Status Badge -->
          <div 
            v-if="isPaidUser"
            class="flex items-center gap-2 bg-emerald-50 border border-emerald-200 text-emerald-800 px-3 py-1.5 rounded-full text-xs font-black"
          >
            <CheckBadgeIcon class="w-4 h-4 text-emerald-600 shrink-0" />
            <span>{{ currentTier.toUpperCase() }} (Paid Active)</span>
          </div>

          <div 
            v-else
            class="flex items-center gap-2 bg-purple-50 border border-purple-200 text-purple-800 px-3 py-1.5 rounded-full text-xs font-black cursor-pointer hover:bg-purple-100 transition"
            @click="showUpgradeModal = true"
          >
            <LockClosedIcon class="w-3.5 h-3.5 text-purple-600 shrink-0" />
            <span>Free Tier • Upgrade to Apply AI</span>
          </div>
        </div>
      </header>

      <!-- Scrollable Workspace Body -->
      <div class="p-6 md:p-8 space-y-7 flex-1 overflow-y-auto max-w-7xl mx-auto w-full">
        <!-- Hero Header -->
        <div class="bg-gradient-to-r from-slate-950 via-purple-950 to-slate-900 border border-purple-800/40 rounded-3xl p-6 md:p-8 text-white shadow-xl relative overflow-hidden">
          <div class="absolute -right-10 -bottom-10 w-72 h-72 bg-purple-500/20 rounded-full blur-3xl pointer-events-none"></div>
          <div class="absolute -left-10 -top-10 w-64 h-64 bg-indigo-500/20 rounded-full blur-2xl pointer-events-none"></div>

          <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div class="space-y-2 max-w-2xl">
              <div class="inline-flex items-center gap-2 px-2.5 py-0.5 rounded-md bg-purple-500/20 border border-purple-400/30 text-purple-300 text-[11px] font-bold tracking-wider uppercase">
                <SparklesIcon class="w-3.5 h-3.5 text-purple-400" />
                <span>AI Workspace Synthesis Engine</span>
              </div>
              <h1 class="text-2xl md:text-3xl font-black tracking-tight text-white drop-shadow-xs">
                Transform & Modify Your CRM with AI
              </h1>
              <p class="text-xs md:text-sm text-slate-300 font-normal leading-relaxed">
                Describe your desired hospital department or clinical practice in plain English. AI will automatically engineer custom pipelines, patient schema columns, medical alerts, and tailored navigation.
              </p>
            </div>

            <!-- Header Action Button -->
            <div class="flex items-center gap-3">
              <button 
                v-if="!isPaidUser"
                @click="showUpgradeModal = true"
                class="px-5 py-2.5 bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 text-white font-bold text-xs rounded-2xl shadow-lg shadow-purple-900/40 flex items-center gap-2 transition-all cursor-pointer"
              >
                <FireIcon class="w-4 h-4 text-amber-300" />
                <span>Unlock Paid Plan Access</span>
              </button>
            </div>
          </div>
        </div>

        <!-- Paid Feature Notice Alert (for Free users) -->
        <div v-if="!isPaidUser" class="p-4 bg-amber-50 border border-amber-200 rounded-2xl flex items-center justify-between gap-4 text-xs font-medium text-amber-900 shadow-xs">
          <div class="flex items-center gap-2.5">
            <LockClosedIcon class="w-5 h-5 text-amber-600 shrink-0" />
            <div>
              <span class="font-extrabold">Paid Subscriber Exclusive:</span>
              <span> You are previewing AI CRM Studio in Sandbox Mode. Upgrade to Growth or Enterprise Plan to deploy generated pipelines & custom fields directly to your live CRM database.</span>
            </div>
          </div>
          <button 
            @click="showUpgradeModal = true"
            class="px-3.5 py-1.5 bg-amber-600 hover:bg-amber-700 text-white text-xs font-black rounded-xl transition shrink-0 cursor-pointer shadow-xs"
          >
            Upgrade Plan
          </button>
        </div>

        <!-- SECTION 1: AI PROMPT INPUT & PRESETS -->
        <div class="bg-white border border-slate-200 rounded-3xl p-6 md:p-7 shadow-xs space-y-6">
          <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <h2 class="text-sm font-black text-slate-900 flex items-center gap-2">
              <CpuChipIcon class="w-4 h-4 text-purple-600" />
              <span>Prompt AI or Pick a Medical Specialty Preset</span>
            </h2>
            <span class="text-[11px] text-slate-500">Natural Language Schema Modifier</span>
          </div>

          <!-- 1-Click Specialty Preset Chips -->
          <div>
            <label class="text-xs font-bold text-slate-700 block mb-2">1-Click Healthcare Presets:</label>
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3">
              <button
                v-for="preset in presets"
                :key="preset.id"
                type="button"
                @click="selectPreset(preset)"
                :class="[
                  'p-3 rounded-2xl border text-left transition-all cursor-pointer flex flex-col justify-between',
                  selectedPreset === preset.id
                    ? 'border-purple-600 bg-purple-50/70 ring-2 ring-purple-500/20 shadow-xs'
                    : 'border-slate-200 hover:border-purple-300 bg-slate-50/50'
                ]"
              >
                <div class="flex items-center justify-between mb-1.5">
                  <span class="text-xl">{{ preset.icon }}</span>
                  <span v-if="selectedPreset === preset.id" class="w-2 h-2 rounded-full bg-purple-600"></span>
                </div>
                <div class="text-xs font-extrabold text-slate-900 leading-tight">{{ preset.name }}</div>
                <div class="text-[10px] text-slate-500 mt-1 line-clamp-1">{{ preset.specialty }}</div>
              </button>
            </div>
          </div>

          <!-- Natural Language Custom Prompt Box -->
          <div class="space-y-3">
            <label class="text-xs font-bold text-slate-700 block">Custom AI Prompt Instructions:</label>
            <div class="relative">
              <textarea
                v-model="promptInput"
                rows="3"
                class="w-full bg-slate-50 border border-slate-300 rounded-2xl p-4 text-xs font-medium text-slate-900 focus:bg-white focus:border-purple-600 focus:ring-2 focus:ring-purple-500/20 focus:outline-none transition"
                placeholder="e.g. Adapt our CRM for an Oncology Daycare Center with chemotherapy cycle tracking, biopsy staging, and absolute neutrophil count fields..."
              ></textarea>
            </div>

            <div class="flex items-center justify-between pt-1">
              <p class="text-[11px] text-slate-500">
                💡 Tip: Mention specific medical stages, lab fields, SLA deadlines, or clinical roles.
              </p>

              <button
                type="button"
                @click="generateAiCrm"
                :disabled="isGenerating"
                class="px-6 py-2.5 bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-700 hover:to-indigo-700 text-white font-black text-xs rounded-xl shadow-md transition-all flex items-center gap-2 cursor-pointer disabled:opacity-50"
              >
                <ArrowPathIcon :class="['w-4 h-4', isGenerating ? 'animate-spin' : '']" />
                <span>{{ isGenerating ? 'Synthesizing with AI...' : '✨ Generate CRM Modifications' }}</span>
              </button>
            </div>
          </div>
        </div>

        <!-- SECTION 2: AI GENERATED BLUEPRINT PREVIEW & LIVE DEPLOYMENT -->
        <div class="bg-white border border-slate-200 rounded-3xl p-6 md:p-8 shadow-xs space-y-6">
          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-100 pb-4">
            <div>
              <div class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-md bg-emerald-100 text-emerald-800 text-[10px] font-black uppercase mb-1">
                <CheckCircleIcon class="w-3 h-3 text-emerald-600" />
                <span>Generated Schema Ready</span>
              </div>
              <h2 class="text-lg font-black text-slate-900 flex items-center gap-2">
                <span>{{ activeConfig.icon }}</span>
                <span>{{ activeConfig.name }}</span>
              </h2>
              <p class="text-xs text-slate-500">{{ activeConfig.description }}</p>
            </div>

            <!-- Apply / Deploy Button -->
            <div>
              <button
                type="button"
                @click="handleApplyToWorkspace"
                :disabled="applyForm.processing"
                class="px-6 py-3 bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 text-white font-black text-xs rounded-2xl shadow-lg shadow-emerald-900/20 transition-all flex items-center gap-2 cursor-pointer"
              >
                <RocketLaunchIcon class="w-4 h-4 text-white" />
                <span>{{ applyForm.processing ? 'Deploying to CRM...' : (isPaidUser ? '🚀 Apply to Live CRM Workspace' : '🔒 Upgrade to Deploy to Live CRM') }}</span>
              </button>
            </div>
          </div>

          <!-- Preview Tabs -->
          <div class="flex items-center gap-2 border-b border-slate-100 pb-2">
            <button
              type="button"
              @click="activePreviewTab = 'pipeline'"
              :class="[
                'px-4 py-2 rounded-xl text-xs font-bold transition-all cursor-pointer flex items-center gap-1.5',
                activePreviewTab === 'pipeline' ? 'bg-slate-900 text-white shadow-xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'
              ]"
            >
              <Squares2X2Icon class="w-3.5 h-3.5" />
              <span>1. Pipeline Stages ({{ activeConfig.pipeline_stages?.length || 0 }})</span>
            </button>

            <button
              type="button"
              @click="activePreviewTab = 'fields'"
              :class="[
                'px-4 py-2 rounded-xl text-xs font-bold transition-all cursor-pointer flex items-center gap-1.5',
                activePreviewTab === 'fields' ? 'bg-slate-900 text-white shadow-xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'
              ]"
            >
              <TableCellsIcon class="w-3.5 h-3.5" />
              <span>2. Custom Columns ({{ activeConfig.custom_fields?.length || 0 }})</span>
            </button>

            <button
              type="button"
              @click="activePreviewTab = 'automations'"
              :class="[
                'px-4 py-2 rounded-xl text-xs font-bold transition-all cursor-pointer flex items-center gap-1.5',
                activePreviewTab === 'automations' ? 'bg-slate-900 text-white shadow-xs' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'
              ]"
            >
              <BoltIcon class="w-3.5 h-3.5" />
              <span>3. AI Clinical Automations ({{ activeConfig.automations?.length || 0 }})</span>
            </button>
          </div>

          <!-- TAB 1: PIPELINE STAGES PREVIEW -->
          <div v-if="activePreviewTab === 'pipeline'" class="space-y-4">
            <p class="text-xs text-slate-500">
              AI has configured this multi-step clinical progression pathway with target SLA turnaround hours:
            </p>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
              <div
                v-for="(stage, idx) in activeConfig.pipeline_stages"
                :key="idx"
                class="p-4 rounded-2xl bg-slate-50 border border-slate-200 space-y-2"
              >
                <div class="flex items-center justify-between">
                  <span class="text-[11px] font-bold text-slate-400">STAGE 0{{ idx + 1 }}</span>
                  <span class="text-[10px] font-extrabold px-2 py-0.5 rounded-full bg-slate-200 text-slate-700">
                    SLA: {{ stage.sla_hours }}h
                  </span>
                </div>
                <div class="text-xs font-black text-slate-900">{{ stage.name }}</div>
              </div>
            </div>
          </div>

          <!-- TAB 2: CUSTOM COLUMNS & SCHEMA PREVIEW -->
          <div v-else-if="activePreviewTab === 'fields'" class="space-y-4">
            <p class="text-xs text-slate-500">
              AI will generate and link these custom metadata fields to patient and clinical contact records:
            </p>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
              <div
                v-for="(field, idx) in activeConfig.custom_fields"
                :key="idx"
                class="p-4 rounded-2xl bg-slate-50 border border-slate-200 flex items-center justify-between"
              >
                <div>
                  <div class="text-xs font-black text-slate-900">{{ field.label }}</div>
                  <div class="text-[10px] font-mono text-slate-400">key: {{ field.key }} • type: {{ field.type }}</div>
                </div>
                <span class="text-xs font-bold px-2 py-1 bg-purple-100 text-purple-800 rounded-lg">
                  {{ field.options ? `${field.options.length} Options` : (field.default || 'Text Field') }}
                </span>
              </div>
            </div>
          </div>

          <!-- TAB 3: AUTOMATIONS & ALERTS -->
          <div v-else-if="activePreviewTab === 'automations'" class="space-y-4">
            <p class="text-xs text-slate-500">
              Active automated clinical workflows attached to this workspace configuration:
            </p>

            <div class="space-y-2.5">
              <div
                v-for="(auto, idx) in activeConfig.automations"
                :key="idx"
                class="p-3.5 rounded-2xl bg-emerald-50/70 border border-emerald-200 text-emerald-900 text-xs font-bold flex items-center gap-3"
              >
                <span class="text-base">⚡</span>
                <span>{{ auto }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- UPGRADE MODAL FOR FREE USERS -->
  <div v-if="showUpgradeModal" class="fixed inset-0 z-50 overflow-y-auto">
    <div @click="showUpgradeModal = false" class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity"></div>

    <div class="flex min-h-full items-center justify-center p-4">
      <div class="relative w-full max-w-xl bg-white rounded-3xl p-8 shadow-2xl border border-slate-200 space-y-6">
        <!-- Header -->
        <div class="text-center space-y-2">
          <div class="w-14 h-14 bg-gradient-to-tr from-purple-600 to-indigo-600 rounded-3xl mx-auto flex items-center justify-center text-2xl text-white shadow-xl shadow-purple-900/20">
            👑
          </div>
          <h3 class="text-xl font-black text-slate-900 tracking-tight">Unlock AI CRM Studio</h3>
          <p class="text-xs text-slate-500 max-w-md mx-auto">
            AI CRM Studio is exclusively available on Paid Plans (Starter, Growth Pro, Enterprise). Activate your plan to deploy infinite custom clinical schemas.
          </p>
        </div>

        <!-- Plan Features Grid -->
        <div class="grid grid-cols-2 gap-3">
          <div class="p-4 rounded-2xl bg-purple-50/70 border border-purple-200 space-y-1 text-center">
            <div class="text-xs font-black text-purple-900">Growth Pro Plan</div>
            <div class="text-lg font-black text-purple-700">₹14,999<span class="text-[10px] font-normal">/mo</span></div>
            <p class="text-[10px] text-purple-800">Unlimited AI Pipelines + 25 Users</p>
            <button 
              @click="activatePaidPlan('growth')"
              class="w-full mt-2 py-1.5 bg-purple-600 hover:bg-purple-700 text-white font-black text-xs rounded-xl transition cursor-pointer"
            >
              Activate Growth
            </button>
          </div>

          <div class="p-4 rounded-2xl bg-indigo-50/70 border border-indigo-200 space-y-1 text-center">
            <div class="text-xs font-black text-indigo-900">Enterprise Suite</div>
            <div class="text-lg font-black text-indigo-700">₹39,999<span class="text-[10px] font-normal">/mo</span></div>
            <p class="text-[10px] text-indigo-800">Custom Multi-Hospital AI Engine</p>
            <button 
              @click="activatePaidPlan('enterprise')"
              class="w-full mt-2 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white font-black text-xs rounded-xl transition cursor-pointer"
            >
              Activate Enterprise
            </button>
          </div>
        </div>

        <div class="flex justify-end gap-2 pt-2 border-t border-slate-100">
          <button
            type="button"
            @click="showUpgradeModal = false"
            class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-xl transition cursor-pointer"
          >
            Close Preview
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
