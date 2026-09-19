<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import {
  TableCellsIcon,
  CircleStackIcon,
  MagnifyingGlassIcon,
  ChevronLeftIcon,
  ArrowTopRightOnSquareIcon,
  ServerStackIcon,
  SparklesIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
  tables: {
    type: Array,
    default: () => []
  },
  filters: {
    type: Object,
    default: () => ({ search: '' })
  }
});

const searchQuery = ref(props.filters.search || '');

const filteredTables = computed(() => {
  if (!searchQuery.value) return props.tables;
  const q = searchQuery.value.toLowerCase();
  return props.tables.filter(t => t.table_name.toLowerCase().includes(q));
});

const handleSearch = () => {
  router.get('/admin/database/tables', { search: searchQuery.value }, { preserveState: true });
};
</script>

<template>
  <Head title="Database Tables Directory — Master Admin" />

  <div class="min-h-screen bg-slate-950 text-slate-100 flex flex-col selection:bg-red-500 selection:text-white">
    <!-- Top Header -->
    <header class="border-b border-slate-800/80 bg-slate-900/60 backdrop-blur-xl sticky top-0 z-40">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3.5 flex items-center justify-between">
        <div class="flex items-center gap-3">
          <Link href="/admin" class="w-9 h-9 rounded-xl bg-red-500/10 border border-red-500/30 text-red-500 flex items-center justify-center hover:bg-red-500/20 transition-all">
            <ChevronLeftIcon class="w-5 h-5 stroke-[2.5]" />
          </Link>
          <div>
            <div class="flex items-center gap-2">
              <span class="px-2 py-0.5 text-[10px] font-black uppercase tracking-wider bg-red-600/20 text-red-400 border border-red-500/30 rounded-md">Catalog</span>
              <h1 class="text-base font-black text-white tracking-tight">Database Tables Directory</h1>
            </div>
            <p class="text-xs text-slate-400 font-medium">Browse, search, and dynamically manage all CRM and imported tables</p>
          </div>
        </div>

        <div class="flex items-center gap-2">
          <Link
            href="/admin/database/import"
            class="px-3.5 py-1.5 bg-red-600 hover:bg-red-500 text-white rounded-xl text-xs font-black transition-all flex items-center gap-1.5 shadow-lg shadow-red-600/20"
          >
            <SparklesIcon class="w-4 h-4" />
            <span>Import Database</span>
          </Link>
        </div>
      </div>
    </header>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 flex-1 w-full space-y-6">
      <!-- Search & Filters -->
      <div class="flex flex-wrap items-center justify-between gap-4">
        <div class="relative w-full max-w-sm">
          <MagnifyingGlassIcon class="w-4 h-4 text-slate-500 absolute left-3.5 top-1/2 -translate-y-1/2" />
          <input
            type="text"
            v-model="searchQuery"
            @input="handleSearch"
            placeholder="Search table name..."
            class="w-full pl-9 pr-4 py-2 bg-slate-900/80 border border-slate-800 rounded-xl text-xs text-white placeholder:text-slate-500 focus:outline-none focus:border-red-500 transition-colors"
          />
        </div>

        <div class="text-xs text-slate-400 font-medium">
          Showing <span class="font-bold text-white">{{ filteredTables.length }}</span> tables in database
        </div>
      </div>

      <!-- Tables Grid -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <div
          v-for="table in filteredTables"
          :key="table.table_name"
          class="p-5 rounded-2xl bg-slate-900/70 border border-slate-800/80 hover:border-slate-700 transition-all flex flex-col justify-between group space-y-4"
        >
          <div class="space-y-2">
            <div class="flex items-start justify-between">
              <div class="flex items-center gap-2">
                <TableCellsIcon class="w-5 h-5 text-sky-400" />
                <h3 class="font-black text-sm text-white group-hover:text-red-400 transition-colors">
                  {{ table.table_name }}
                </h3>
              </div>
              <span
                class="px-2 py-0.5 text-[9px] font-black uppercase rounded-md border"
                :class="table.is_crm_core ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/30' : 'bg-purple-500/10 text-purple-400 border-purple-500/30'"
              >
                {{ table.is_crm_core ? 'Core CRM' : 'Dynamic Table' }}
              </span>
            </div>

            <div class="grid grid-cols-2 gap-2 text-xs text-slate-400 pt-1">
              <div>Records: <span class="font-bold text-white">{{ table.record_count.toLocaleString() }}</span></div>
              <div>Size: <span class="font-bold text-white">{{ table.size_formatted }}</span></div>
            </div>
          </div>

          <div class="pt-3 border-t border-slate-800/80 flex items-center justify-between">
            <span class="text-[11px] text-slate-500 font-mono">{{ table.update_time ? 'Updated: ' + table.update_time : '' }}</span>
            <Link
              :href="`/admin/database/tables/${table.table_name}`"
              class="px-3 py-1.5 bg-slate-800 hover:bg-slate-700 text-slate-200 font-bold text-xs rounded-xl flex items-center gap-1.5 transition-all"
            >
              <span>View Records</span>
              <ArrowTopRightOnSquareIcon class="w-3.5 h-3.5" />
            </Link>
          </div>
        </div>
      </div>
    </main>
  </div>
</template>
