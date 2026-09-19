<script setup>
import { ref, computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import {
  ChevronLeftIcon,
  ServerStackIcon,
  CheckCircleIcon,
  ArrowRightIcon,
  ArrowPathIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
  databaseImport: {
    type: Object,
    default: () => ({})
  },
  import: {
    type: Object,
    default: null
  },
  mappings: {
    type: Array,
    default: () => []
  },
  tables: {
    type: Array,
    default: () => []
  },
  standardTables: {
    type: Object,
    default: () => ({})
  }
});

const currentSession = computed(() => props.databaseImport?.id ? props.databaseImport : (props.import || {}));
const localMappings = ref(JSON.parse(JSON.stringify(props.mappings)));
const isSaving = ref(false);
const saveSuccess = ref(false);

const saveMappings = async () => {
  if (!currentSession.value?.id) return;
  isSaving.value = true;
  saveSuccess.value = false;

  const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
  try {
    const res = await fetch(`/admin/database/mapping/${currentSession.value.id}`, {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': csrfToken || '',
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      },
      body: JSON.stringify({
        mappings: localMappings.value.map(m => ({
          id: m.id,
          target_table: m.target_table,
          target_column: m.target_column,
          is_confirmed: true,
          transformation_rule: m.transformation_rule
        }))
      })
    });

    const data = await res.json();
    if (data.success) {
      saveSuccess.value = true;
      setTimeout(() => {
        saveSuccess.value = false;
      }, 3000);
    } else {
      alert('Save failed: ' + data.message);
    }
  } catch (e) {
    alert('Save error: ' + e.message);
  } finally {
    isSaving.value = false;
  }
};
</script>

<template>
  <Head :title="`Field Mappings — Import #${currentSession.id || ''}`" />

  <div class="min-h-screen bg-slate-950 text-slate-100 flex flex-col selection:bg-red-500 selection:text-white">
    <!-- Header -->
    <header class="border-b border-slate-800/80 bg-slate-900/60 backdrop-blur-xl sticky top-0 z-40">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3.5 flex items-center justify-between">
        <div class="flex items-center gap-3">
          <Link :href="`/admin/database/import?import_id=${currentSession.id}`" class="w-9 h-9 rounded-xl bg-red-500/10 border border-red-500/30 text-red-500 flex items-center justify-center hover:bg-red-500/20 transition-all">
            <ChevronLeftIcon class="w-5 h-5 stroke-[2.5]" />
          </Link>
          <div>
            <div class="flex items-center gap-2">
              <span class="px-2 py-0.5 text-[10px] font-black uppercase tracking-wider bg-red-600/20 text-red-400 border border-red-500/30 rounded-md">Mapping Config</span>
              <h1 class="text-base font-black text-white tracking-tight">CRM Field Auto-Mapping</h1>
            </div>
            <p class="text-xs text-slate-400 font-medium">Configure and confirm column associations for Import #{{ currentSession.id }}</p>
          </div>
        </div>

        <div class="flex items-center gap-2">
          <button
            @click="saveMappings"
            :disabled="isSaving"
            class="px-4 py-2 bg-red-600 hover:bg-red-500 text-white font-black text-xs rounded-xl shadow-lg shadow-red-600/20 flex items-center gap-1.5"
          >
            <ArrowPathIcon v-if="isSaving" class="w-4 h-4 animate-spin" />
            <CheckCircleIcon v-else class="w-4 h-4" />
            <span>{{ isSaving ? 'Saving...' : 'Save Mappings' }}</span>
          </button>
        </div>
      </div>
    </header>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 flex-1 w-full space-y-6">
      <div v-if="saveSuccess" class="p-4 rounded-2xl bg-emerald-950/60 border border-emerald-600/50 text-emerald-200 text-xs font-bold flex items-center gap-2">
        <CheckCircleIcon class="w-4 h-4 text-emerald-400" />
        <span>Field mappings saved successfully!</span>
      </div>

      <div class="p-6 rounded-2xl bg-slate-900/60 border border-slate-800/80 space-y-3">
        <div v-for="mapping in localMappings" :key="mapping.id" class="p-4 rounded-xl bg-slate-950/60 border border-slate-800 flex flex-wrap items-center justify-between gap-4">
          <div class="flex items-center gap-4">
            <div class="space-y-0.5">
              <div class="text-[10px] uppercase font-black text-slate-500">{{ mapping.source_table }}</div>
              <div class="font-mono text-sm font-bold text-white">{{ mapping.source_column }}</div>
            </div>

            <ArrowRightIcon class="w-4 h-4 text-slate-600" />

            <div class="flex items-center gap-2">
              <input
                type="text"
                v-model="mapping.target_table"
                placeholder="Target table"
                class="px-2.5 py-1 text-xs font-mono font-bold bg-slate-900 border border-slate-700 rounded-lg text-white"
              />
              <span class="text-slate-600">.</span>
              <input
                type="text"
                v-model="mapping.target_column"
                placeholder="Target column"
                class="px-2.5 py-1 text-xs font-mono font-bold bg-slate-900 border border-slate-700 rounded-lg text-white"
              />
            </div>
          </div>

          <div class="flex items-center gap-3">
            <span
              class="px-2.5 py-0.5 text-[10px] font-black uppercase rounded-md border"
              :class="mapping.confidence === 'high' ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/30' : 'bg-amber-500/10 text-amber-400 border-amber-500/30'"
            >
              {{ mapping.confidence }}
            </span>
          </div>
        </div>
      </div>
    </main>
  </div>
</template>
