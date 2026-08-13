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
  MagnifyingGlassIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
  industries: Array,
});

const currentStep = ref(1);
const selectedIndustry = ref(null);
const businessTypes = ref([]);
const loadingTypes = ref(false);
const searchQuery = ref('');

const form = useForm({
  industry_id: null,
  business_type_id: null,
  employee_range: '6–20',
  crm_goals: [],
});

const filteredIndustries = computed(() => {
  if (!searchQuery.value) return props.industries || [];
  const q = searchQuery.value.toLowerCase();
  return (props.industries || []).filter(ind => 
    ind.name.toLowerCase().includes(q) || 
    (ind.description && ind.description.toLowerCase().includes(q))
  );
});

const teamSizes = [
  { id: '1–5', label: '1–5 employees', desc: 'Solo entrepreneurs & micro teams' },
  { id: '6–20', label: '6–20 employees', desc: 'Growing small businesses' },
  { id: '21–50', label: '21–50 employees', desc: 'Mid-sized companies' },
  { id: '51–200', label: '51–200 employees', desc: 'Expanding enterprises' },
  { id: '201–500', label: '201–500 employees', desc: 'Large organizations' },
  { id: '500+', label: '500+ employees', desc: 'Global enterprises' },
];

const crmGoalsList = [
  { id: 'Lead Management', label: 'Lead Management', icon: '🎯' },
  { id: 'Sales', label: 'Sales & Deals', icon: '💰' },
  { id: 'Customer Support', label: 'Customer Support', icon: '🎧' },
  { id: 'Marketing', label: 'Marketing Campaigns', icon: '📢' },
  { id: 'Communication', label: 'WhatsApp & Email Comms', icon: '💬' },
  { id: 'Appointment Management', label: 'Appointments & Scheduling', icon: '📅' },
  { id: 'Payments', label: 'Invoicing & Payments', icon: '💳' },
  { id: 'Reports', label: 'Analytics & Reports', icon: '📊' },
  { id: 'Team Management', label: 'Team Performance', icon: '👥' },
  { id: 'Automation', label: 'Workflow Automation', icon: '⚡' },
];

const selectIndustry = async (ind) => {
  selectedIndustry.value = ind;
  form.industry_id = ind.id;
  form.business_type_id = null;
  loadingTypes.value = true;

  try {
    const res = await fetch(`/onboarding/business-types?industry_id=${ind.id}`);
    if (res.ok) {
      businessTypes.value = await res.json();
    }
  } catch (e) {
    businessTypes.value = [];
  } finally {
    loadingTypes.value = false;
  }

  currentStep.value = 2;
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
  form.post('/onboarding/complete');
};
</script>

<template>
  <Head title="Configure Your CRM - Onboarding" />

  <div class="min-h-screen bg-slate-100 text-slate-900 font-sans flex flex-col justify-between p-4 sm:p-8 relative overflow-hidden">
    <!-- Background Soft Red Ambient Glows -->
    <div class="absolute top-1/4 left-1/3 -translate-x-1/2 w-[600px] h-[600px] bg-red-500/10 rounded-full blur-3xl pointer-events-none"></div>
    <div class="absolute bottom-1/4 right-1/4 w-[500px] h-[500px] bg-red-400/10 rounded-full blur-3xl pointer-events-none"></div>

    <!-- Header & Step Indicator -->
    <header class="max-w-6xl mx-auto w-full pt-2 flex items-center justify-between z-10">
      <div class="flex items-center gap-2.5">
        <div class="w-11 h-11 rounded-2xl bg-red-600 flex items-center justify-center text-white font-black text-xl shadow-md shadow-red-600/30">
          ⚡
        </div>
        <div>
          <span class="font-black text-xl tracking-tight text-slate-900">Multi-Sector CRM</span>
          <p class="text-[10px] font-extrabold text-slate-500 uppercase tracking-widest">Dynamic Engine Configurator</p>
        </div>
      </div>

      <!-- Step Counter -->
      <div class="flex items-center gap-2">
        <div 
          v-for="step in 4" 
          :key="step"
          :class="[
            'h-2.5 rounded-full transition-all duration-300',
            step === currentStep ? 'w-10 bg-red-600 shadow-md shadow-red-600/40' : step < currentStep ? 'w-4 bg-emerald-500' : 'w-4 bg-slate-300'
          ]"
        ></div>
        <span class="text-xs font-extrabold text-slate-700 ml-2">Step {{ currentStep }} of 4</span>
      </div>
    </header>

    <!-- Main Content Step Containers -->
    <main class="max-w-6xl mx-auto w-full my-auto py-6 z-10">
      
      <!-- STEP 1: Select Industry Sector -->
      <div v-if="currentStep === 1" class="space-y-6">
        <div class="text-center max-w-3xl mx-auto space-y-2">
          <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-red-50 border border-red-200 text-xs font-extrabold text-red-700 shadow-2xs">
            <SparklesIcon class="w-4 h-4 text-red-600" />
            <span>Step 1 of 4 • Choose Sector Engine</span>
          </div>
          <h2 class="text-3xl sm:text-4xl font-black text-slate-900 tracking-tight">What type of business do you operate?</h2>
          <p class="text-slate-500 text-xs sm:text-sm font-medium leading-relaxed">
            Select your industry. Our engine will dynamically configure your pipelines, custom fields, automation workflows, and analytics dashboard.
          </p>

          <!-- Search Bar -->
          <div class="max-w-md mx-auto pt-2">
            <div class="relative">
              <MagnifyingGlassIcon class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2" />
              <input 
                v-model="searchQuery" 
                type="text" 
                placeholder="Search Real Estate, Healthcare, Education, Law..." 
                class="w-full bg-white border border-slate-300 rounded-2xl pl-10 pr-4 py-2 text-xs font-bold text-slate-900 focus:outline-none focus:border-red-500 shadow-2xs"
              />
            </div>
          </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 max-h-[540px] overflow-y-auto pr-2 custom-scrollbar">
          <div
            v-for="ind in filteredIndustries"
            :key="ind.id"
            @click="selectIndustry(ind)"
            :class="[
              'p-5 rounded-2xl border transition-all duration-200 group flex flex-col justify-between space-y-3 cursor-pointer shadow-2xs hover:shadow-lg hover:-translate-y-0.5',
              form.industry_id === ind.id 
                ? 'border-red-500 bg-gradient-to-br from-red-50/60 via-white to-white ring-2 ring-red-500 shadow-md shadow-red-500/10' 
                : 'bg-white border-slate-200 hover:border-red-400 hover:bg-red-50/20'
            ]"
          >
            <div class="flex items-center justify-between">
              <div class="w-11 h-11 rounded-2xl bg-slate-100/80 border border-slate-200 text-2xl flex items-center justify-center group-hover:scale-110 group-hover:bg-red-50 group-hover:border-red-200 transition-all shadow-2xs">
                {{ ind.icon }}
              </div>
              <span class="text-[9px] font-black uppercase tracking-wider px-2.5 py-0.5 rounded-full bg-slate-100 text-slate-500 border border-slate-200/60 group-hover:bg-red-100 group-hover:text-red-700 group-hover:border-red-200 transition-colors">
                SECTOR
              </span>
            </div>
            <div>
              <h3 class="font-extrabold text-base text-slate-900 group-hover:text-red-600 transition-colors">{{ ind.name }}</h3>
              <p class="text-xs text-slate-500 line-clamp-2 mt-1 font-medium leading-relaxed">{{ ind.description }}</p>
            </div>
          </div>
        </div>
      </div>

      <!-- STEP 2: Select Specific Business Sub-Type -->
      <div v-if="currentStep === 2" class="space-y-6 max-w-3xl mx-auto">
        <div class="text-center space-y-2">
          <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-red-50 border border-red-200 text-xs font-extrabold text-red-700 shadow-2xs">
            <span>{{ selectedIndustry?.icon }} {{ selectedIndustry?.name }}</span>
          </div>
          <h2 class="text-3xl font-black text-slate-900 tracking-tight">What is your specific business type?</h2>
          <p class="text-slate-500 text-sm font-medium">Fine-tune your CRM for exact business operational workflows.</p>
        </div>

        <div v-if="loadingTypes" class="py-12 text-center text-slate-500 font-bold">
          Loading business sub-types...
        </div>

        <div v-else class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div
            v-for="bt in businessTypes"
            :key="bt.id"
            @click="form.business_type_id = bt.id"
            :class="[
              'p-4 rounded-2xl border bg-white hover:bg-slate-50 border-slate-200 cursor-pointer transition-all flex items-center justify-between shadow-xs',
              form.business_type_id === bt.id ? 'border-red-500 bg-red-50/60 ring-2 ring-red-500' : ''
            ]"
          >
            <div>
              <h4 class="font-bold text-sm text-slate-900">{{ bt.name }}</h4>
              <p v-if="bt.description" class="text-xs text-slate-500 mt-0.5 font-medium">{{ bt.description }}</p>
            </div>
            <div :class="['w-6 h-6 rounded-full border flex items-center justify-center', form.business_type_id === bt.id ? 'bg-red-600 border-red-500' : 'border-slate-300']">
              <CheckIcon v-if="form.business_type_id === bt.id" class="w-4 h-4 text-white stroke-[3]" />
            </div>
          </div>
        </div>

        <!-- Navigation Buttons -->
        <div class="flex items-center justify-between pt-6 border-t border-slate-200">
          <button 
            @click="currentStep = 1" 
            type="button" 
            class="px-5 py-2.5 bg-white hover:bg-slate-50 border border-slate-300 text-slate-700 text-xs font-bold rounded-xl flex items-center gap-2 cursor-pointer"
          >
            <ArrowLeftIcon class="w-4 h-4 stroke-[2.5]" />
            <span>Back to Sectors</span>
          </button>
          <button 
            @click="currentStep = 3" 
            type="button" 
            class="px-6 py-2.5 bg-red-600 hover:bg-red-700 text-white text-xs font-extrabold rounded-xl flex items-center gap-2 shadow-md shadow-red-600/30 cursor-pointer"
          >
            <span>Next: Team Size</span>
            <ArrowRightIcon class="w-4 h-4 stroke-[3]" />
          </button>
        </div>
      </div>

      <!-- STEP 3: Select Team Size -->
      <div v-if="currentStep === 3" class="space-y-6 max-w-3xl mx-auto">
        <div class="text-center space-y-2">
          <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-red-50 border border-red-200 text-xs font-extrabold text-red-700 shadow-2xs">
            <UserGroupIcon class="w-4 h-4 text-red-600" />
            <span>Step 3: Organization Scale</span>
          </div>
          <h2 class="text-3xl font-black text-slate-900 tracking-tight">How many employees/users do you have?</h2>
          <p class="text-slate-500 text-sm font-medium">We'll optimize workspace capacity and default team permissions.</p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div
            v-for="ts in teamSizes"
            :key="ts.id"
            @click="form.employee_range = ts.id"
            :class="[
              'p-5 rounded-2xl border bg-white hover:bg-slate-50 border-slate-200 cursor-pointer transition-all flex items-center justify-between shadow-xs',
              form.employee_range === ts.id ? 'border-red-500 bg-red-50/60 ring-2 ring-red-500' : ''
            ]"
          >
            <div>
              <h4 class="font-extrabold text-base text-slate-900">{{ ts.label }}</h4>
              <p class="text-xs text-slate-500 mt-1 font-medium">{{ ts.desc }}</p>
            </div>
            <div :class="['w-6 h-6 rounded-full border flex items-center justify-center', form.employee_range === ts.id ? 'bg-red-600 border-red-500' : 'border-slate-300']">
              <CheckIcon v-if="form.employee_range === ts.id" class="w-4 h-4 text-white stroke-[3]" />
            </div>
          </div>
        </div>

        <!-- Navigation Buttons -->
        <div class="flex items-center justify-between pt-6 border-t border-slate-200">
          <button 
            @click="currentStep = 2" 
            type="button" 
            class="px-5 py-2.5 bg-white hover:bg-slate-50 border border-slate-300 text-slate-700 text-xs font-bold rounded-xl flex items-center gap-2 cursor-pointer"
          >
            <ArrowLeftIcon class="w-4 h-4 stroke-[2.5]" />
            <span>Back</span>
          </button>
          <button 
            @click="currentStep = 4" 
            type="button" 
            class="px-6 py-2.5 bg-red-600 hover:bg-red-700 text-white text-xs font-extrabold rounded-xl flex items-center gap-2 shadow-md shadow-red-600/30 cursor-pointer"
          >
            <span>Next: Primary Goals</span>
            <ArrowRightIcon class="w-4 h-4 stroke-[3]" />
          </button>
        </div>
      </div>

      <!-- STEP 4: Primary CRM Goals (Multi-select) -->
      <div v-if="currentStep === 4" class="space-y-6 max-w-3xl mx-auto">
        <div class="text-center space-y-2">
          <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-red-50 border border-red-200 text-xs font-extrabold text-red-700 shadow-2xs">
            <SparklesIcon class="w-4 h-4 text-red-600" />
            <span>Step 4: Objectives</span>
          </div>
          <h2 class="text-3xl font-black text-slate-900 tracking-tight">What are your primary CRM goals?</h2>
          <p class="text-slate-500 text-sm font-medium">Select all that apply. We'll highlight relevant quick actions on your dashboard.</p>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
          <div
            v-for="goal in crmGoalsList"
            :key="goal.id"
            @click="toggleGoal(goal.id)"
            :class="[
              'p-4 rounded-2xl border bg-white hover:bg-slate-50 border-slate-200 cursor-pointer transition-all flex items-center justify-between gap-2 shadow-xs',
              form.crm_goals.includes(goal.id) ? 'border-red-500 bg-red-50/60 ring-2 ring-red-500' : ''
            ]"
          >
            <div class="flex items-center gap-2.5">
              <span class="text-xl">{{ goal.icon }}</span>
              <span class="font-extrabold text-xs text-slate-900">{{ goal.label }}</span>
            </div>
            <div :class="['w-5 h-5 rounded border flex items-center justify-center shrink-0', form.crm_goals.includes(goal.id) ? 'bg-red-600 border-red-500' : 'border-slate-300']">
              <CheckIcon v-if="form.crm_goals.includes(goal.id)" class="w-3.5 h-3.5 text-white stroke-[3]" />
            </div>
          </div>
        </div>

        <!-- Submit Button -->
        <div class="flex items-center justify-between pt-6 border-t border-slate-200">
          <button 
            @click="currentStep = 3" 
            type="button" 
            class="px-5 py-2.5 bg-white hover:bg-slate-50 border border-slate-300 text-slate-700 text-xs font-bold rounded-xl flex items-center gap-2 cursor-pointer"
          >
            <ArrowLeftIcon class="w-4 h-4 stroke-[2.5]" />
            <span>Back</span>
          </button>

          <button 
            @click="completeOnboarding"
            :disabled="form.processing"
            class="px-8 py-3.5 bg-red-600 hover:bg-red-700 text-white text-sm font-black rounded-2xl shadow-xl shadow-red-600/30 flex items-center gap-3 transition-all cursor-pointer disabled:opacity-50"
          >
            <RocketLaunchIcon class="w-5 h-5 stroke-[2.5]" />
            <span>Generate & Launch My CRM</span>
          </button>
        </div>
      </div>

    </main>

    <!-- Footer Status -->
    <footer class="max-w-6xl mx-auto w-full pb-2 text-center text-xs text-slate-500 font-medium z-10">
      Multi-Sector Dynamic Engine • Automatic Module & Pipeline Provisioning
    </footer>
  </div>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
  width: 6px;
}
.custom-scrollbar::-webkit-scrollbar-track {
  background: rgba(226, 232, 240, 0.6);
  border-radius: 8px;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
  background: rgba(203, 213, 225, 0.8);
  border-radius: 8px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
  background: rgba(239, 68, 68, 0.8);
}
</style>
