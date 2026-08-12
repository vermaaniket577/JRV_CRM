<script setup>
import { ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import Navbar from '@/Components/Navbar.vue';
import GlobalSearchModal from '@/Components/GlobalSearchModal.vue';
import CreateDealModal from '@/Components/CreateDealModal.vue';
import DealCard from '@/Components/Kanban/DealCard.vue';
import { PlusIcon, FunnelIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
  currentPipeline: {
    type: Object,
    required: true,
  },
  pipelines: {
    type: Array,
    default: () => [],
  },
});

const isSearchOpen = ref(false);
const isCreateDealOpen = ref(false);
const isUpdating = ref(false);

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
</script>

<template>
  <Head title="Deals Pipeline - CRM" />

  <div class="min-h-screen bg-slate-100 flex font-sans text-slate-900">
    <Navbar @open-search="isSearchOpen = true" />

    <main class="flex-1 p-8 space-y-6 overflow-y-auto min-w-0 max-w-7xl mx-auto w-full flex flex-col">
      <!-- Header -->
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-slate-200 pb-4">
        <div>
          <h1 class="text-2xl font-extrabold text-slate-900 flex items-center gap-3">
            <span>Deals Pipeline</span>
            <span class="text-xs px-2.5 py-1 bg-indigo-50 text-indigo-700 border border-indigo-200 rounded-full font-bold">
              {{ currentPipeline.name }}
            </span>
          </h1>
          <p class="text-xs text-slate-500 mt-1">Drag and drop opportunities across customized pipeline stages.</p>
        </div>

        <div class="flex items-center gap-3">
          <button 
            @click="isSearchOpen = true"
            class="px-3.5 py-1.5 bg-white border border-slate-300 hover:bg-slate-100 rounded-lg text-xs font-semibold text-slate-700 shadow-xs flex items-center gap-2"
          >
            <FunnelIcon class="w-4 h-4 text-slate-500" />
            <span>Filter</span>
          </button>
          <button 
            @click="isCreateDealOpen = true"
            class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-semibold shadow-md shadow-indigo-600/20 flex items-center gap-2"
          >
            <PlusIcon class="w-4 h-4" />
            <span>Add Deal</span>
          </button>
        </div>
      </div>

      <!-- Drag-and-Drop Kanban Board Grid -->
      <div class="flex-1 overflow-x-auto pb-4">
        <div class="flex gap-6 min-w-max">
          <div 
            v-for="stage in currentPipeline.stages" 
            :key="stage.id"
            class="w-80 bg-slate-100/80 border border-slate-200 rounded-2xl p-4 flex flex-col space-y-4 shadow-xs"
            @dragover.prevent
            @drop="onDrop($event, stage.id)"
          >
            <!-- Stage Header -->
            <div class="flex items-center justify-between border-b border-slate-200 pb-3">
              <div class="space-y-0.5">
                <h3 class="text-sm font-bold text-slate-900">{{ stage.name }}</h3>
                <div class="text-[11px] font-bold text-emerald-700">{{ stage.formatted_stage_value }}</div>
              </div>
              <span class="px-2 py-0.5 bg-white text-slate-700 text-xs font-bold rounded-full border border-slate-200 shadow-xs">
                {{ stage.deals_count }}
              </span>
            </div>

            <!-- Cards Stack -->
            <div class="flex-1 space-y-3 min-h-[300px]">
              <div 
                v-for="deal in stage.deals" 
                :key="deal.id"
                draggable="true"
                @dragstart="onDragStart($event, deal, stage.id)"
              >
                <DealCard :deal="deal" />
              </div>

              <div v-if="stage.deals.length === 0" class="h-32 border-2 border-dashed border-slate-300 rounded-xl flex items-center justify-center text-xs text-slate-500 font-semibold bg-white/50">
                Drop deals here
              </div>
            </div>
          </div>
        </div>
      </div>
    </main>

    <!-- Modals -->
    <GlobalSearchModal :is-open="isSearchOpen" @close="isSearchOpen = false" />
    <CreateDealModal :is-open="isCreateDealOpen" :stages="currentPipeline.stages" @close="isCreateDealOpen = false" />
  </div>
</template>
