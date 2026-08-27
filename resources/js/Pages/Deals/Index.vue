<script setup>
import { ref, computed } from 'vue';
import { Head, router, Link, usePage } from '@inertiajs/vue3';
import Navbar from '@/Components/Navbar.vue';
import GlobalSearchModal from '@/Components/GlobalSearchModal.vue';
import CreateDealModal from '@/Components/CreateDealModal.vue';
import DealCard from '@/Components/Kanban/DealCard.vue';
import { 
  PlusIcon, 
  FunnelIcon,
  SparklesIcon,
  ArrowPathIcon,
  ChevronUpDownIcon,
  CheckBadgeIcon,
  CurrencyDollarIcon,
  TagIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
  currentPipeline: {
    type: Object,
    required: true,
  },
  pipelines: {
    type: Array,
    default: () => [],
  },
  industryConfig: {
    type: Object,
    default: () => ({}),
  },
});

const page = usePage();
const tenantIndustry = computed(() => page.props.tenant_industry || props.industryConfig || { name: 'CRM', icon: '💼', color: 'indigo' });

// Safe unwrapper for pipeline data
const pipeline = computed(() => {
  if (props.currentPipeline?.data) {
    return props.currentPipeline.data;
  }
  return props.currentPipeline || { stages: [] };
});

const stages = computed(() => pipeline.value.stages || []);

const totalPipelineValue = computed(() => {
  return stages.value.reduce((acc, stage) => acc + (stage.stage_value || 0), 0);
});

const totalDealsCount = computed(() => {
  return stages.value.reduce((acc, stage) => acc + (stage.deals_count || 0), 0);
});

const isSearchOpen = ref(false);
const isCreateDealOpen = ref(false);
const isUpdating = ref(false);

const switchPipeline = (pipelineId) => {
  router.get('/offers', { pipeline_id: pipelineId }, {
    preserveState: true,
    preserveScroll: true,
  });
};

const onDragStart = (event, deal, fromStageId) => {
  event.dataTransfer.setData('application/json', JSON.stringify({
    dealId: deal.id,
    fromStageId: fromStageId
  }));
};

const onDrop = (event, targetStageId) => {
  event.preventDefault();
  const data = JSON.parse(event.dataTransfer.getData('application/json'));
  
  if (data.fromStageId === targetStageId) return;

  isUpdating.value = true;

  router.patch(`/deals/${data.dealId}/stage`, {
    stage_id: targetStageId,
  }, {
    preserveScroll: true,
    preserveState: false,
    onFinish: () => {
      isUpdating.value = false;
    },
  });
};

const formatCurrency = (val) => {
  if (!val) return '₹0';
  return new Intl.NumberFormat('en-IN', { style: 'currency', currency: 'INR', maximumFractionDigits: 0 }).format(val);
};
</script>

<template>
  <Head :title="`${pipeline.name || 'Deals'} Pipeline - JRV CRM`" />

  <div class="flex h-screen bg-slate-50 overflow-hidden font-sans text-slate-900">
    <!-- Navbar Sidebar -->
    <Navbar @open-search="isSearchOpen = true" />

    <!-- Main Workspace -->
    <div class="flex-1 flex flex-col min-w-0 overflow-hidden">
      <!-- Top Action Bar -->
      <header class="h-16 bg-white border-b border-slate-200 px-6 flex items-center justify-between shrink-0 shadow-2xs">
        <div class="flex items-center gap-3">
          <div class="w-9 h-9 bg-slate-900 rounded-xl flex items-center justify-center text-white font-black text-sm shadow-md">
            {{ tenantIndustry.icon || '💼' }}
          </div>
          <div>
            <h1 class="text-sm font-black text-slate-900 leading-tight">
              {{ pipeline.name || 'Opportunities & Deals Pipeline' }}
            </h1>
            <p class="text-[11px] text-slate-500 font-medium">Interactive Multi-Stage Kanban Pipeline</p>
          </div>
        </div>

        <div class="flex items-center gap-3">
          <!-- Pipeline Switcher Dropdown (if multiple) -->
          <div v-if="pipelines.length > 1" class="relative hidden sm:block">
            <select
              :value="pipeline.id"
              @change="switchPipeline($event.target.value)"
              class="bg-slate-100 border border-slate-200 text-xs font-bold rounded-xl px-3 py-1.5 pr-7 text-slate-700 focus:outline-none focus:ring-2 focus:ring-slate-900/20 cursor-pointer"
            >
              <option v-for="p in pipelines" :key="p.id" :value="p.id">
                {{ p.name }}
              </option>
            </select>
          </div>

          <button 
            @click="isCreateDealOpen = true"
            class="px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white rounded-xl text-xs font-black shadow-md transition flex items-center gap-1.5 cursor-pointer"
          >
            <PlusIcon class="w-4 h-4 stroke-[3]" />
            <span>+ Add Opportunity</span>
          </button>
        </div>
      </header>

      <!-- Scrollable Kanban Body -->
      <main class="flex-1 p-6 md:p-8 space-y-6 overflow-y-auto min-w-0 max-w-7xl mx-auto w-full flex flex-col">
        <!-- Hero Header Card -->
        <div class="bg-gradient-to-r from-slate-950 via-slate-900 to-slate-950 border border-slate-800 rounded-3xl p-6 md:p-7 text-white shadow-xl flex flex-col md:flex-row md:items-center justify-between gap-6">
          <div class="space-y-1.5 max-w-2xl">
            <div class="inline-flex items-center gap-2 px-2.5 py-0.5 rounded-md bg-white/10 text-slate-200 text-[10px] font-bold uppercase tracking-wider">
              <span>{{ tenantIndustry.icon }}</span>
              <span>{{ tenantIndustry.name }} Opportunity Pipeline</span>
            </div>
            <h2 class="text-xl md:text-2xl font-black tracking-tight text-white">
              {{ pipeline.name || 'Sales & Claims Conversion Board' }}
            </h2>
            <p class="text-xs text-slate-300">
              Drag and drop opportunities, proposals, and contracts across customized stages with automated progress tracking.
            </p>
          </div>

          <!-- Total Pipeline Valuation Counter -->
          <div class="bg-white/10 backdrop-blur-md border border-white/15 rounded-2xl p-4 min-w-[200px] text-right">
            <div class="text-[10px] font-bold text-slate-400 uppercase">Pipeline Total Value</div>
            <div class="text-2xl font-black text-emerald-400">{{ formatCurrency(totalPipelineValue) }}</div>
            <div class="text-[11px] text-slate-300 font-medium">{{ totalDealsCount }} active opportunities</div>
          </div>
        </div>

        <!-- Drag-and-Drop Kanban Board Grid -->
        <div class="flex-1 overflow-x-auto pb-4">
          <div class="flex gap-4 min-w-max">
            <div 
              v-for="stage in stages" 
              :key="stage.id"
              class="w-80 bg-slate-100/90 border border-slate-200/90 rounded-3xl p-4 flex flex-col space-y-3 shadow-xs"
              @dragover.prevent
              @drop="onDrop($event, stage.id)"
            >
              <!-- Stage Header -->
              <div class="flex items-center justify-between border-b border-slate-200/80 pb-2.5 px-1">
                <div class="space-y-0.5">
                  <h3 class="text-xs font-black text-slate-900 tracking-tight flex items-center gap-1.5">
                    <span class="w-2 h-2 rounded-full" :class="stage.stage_type === 'won' ? 'bg-emerald-500' : (stage.stage_type === 'lost' ? 'bg-rose-500' : 'bg-indigo-500')"></span>
                    <span>{{ stage.name }}</span>
                  </h3>
                  <div class="text-[11px] font-extrabold text-emerald-700">{{ stage.formatted_stage_value }}</div>
                </div>
                <span class="px-2 py-0.5 bg-white text-slate-700 text-[11px] font-black rounded-full border border-slate-200 shadow-2xs">
                  {{ stage.deals_count }}
                </span>
              </div>

              <!-- Cards Stack -->
              <div class="flex-1 space-y-2.5 min-h-[350px]">
                <div 
                  v-for="deal in stage.deals" 
                  :key="deal.id"
                  draggable="true"
                  @dragstart="onDragStart($event, deal, stage.id)"
                  class="cursor-grab active:cursor-grabbing transition-transform hover:-translate-y-0.5"
                >
                  <DealCard :deal="deal" />
                </div>

                <!-- Empty Stage Placeholder -->
                <div v-if="stage.deals.length === 0" class="h-36 border-2 border-dashed border-slate-300/80 rounded-2xl flex flex-col items-center justify-center text-xs text-slate-400 font-semibold bg-white/40 gap-1">
                  <span>Drop opportunities here</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </main>
    </div>

    <!-- Modals -->
    <GlobalSearchModal :is-open="isSearchOpen" @close="isSearchOpen = false" />
    <CreateDealModal :is-open="isCreateDealOpen" :stages="stages" @close="isCreateDealOpen = false" />
  </div>
</template>
