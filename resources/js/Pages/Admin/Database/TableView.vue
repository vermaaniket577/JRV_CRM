<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import {
  TableCellsIcon,
  ChevronLeftIcon,
  MagnifyingGlassIcon,
  ArrowDownTrayIcon,
  PlusIcon,
  PencilSquareIcon,
  TrashIcon,
  EyeIcon,
  XMarkIcon,
  AdjustmentsHorizontalIcon,
  ArrowUpIcon,
  ArrowDownIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
  tableName: {
    type: String,
    required: true
  },
  primaryKey: {
    type: String,
    default: 'id'
  },
  columns: {
    type: Array,
    default: () => []
  },
  records: {
    type: Object,
    default: () => ({ data: [], links: [] })
  },
  filters: {
    type: Object,
    default: () => ({})
  },
  totalRecords: {
    type: Number,
    default: 0
  }
});

const searchQuery = ref(props.filters.search || '');
const dateFrom = ref(props.filters.date_from || '');
const dateTo = ref(props.filters.date_to || '');
const currentSort = ref(props.filters.sort || props.primaryKey || 'id');
const currentDirection = ref(props.filters.direction || 'desc');

// Column Visibility Drawer
const isColumnDrawerOpen = ref(false);
const visibleColumns = ref(props.columns.map(c => c.name));

// Record Modal State (View / Create / Edit)
const isRecordModalOpen = ref(false);
const modalMode = ref('view'); // 'view', 'create', 'edit'
const activeRecord = ref({});
const isSaving = ref(false);
const modalError = ref('');

// Filter actions
const applyFilters = () => {
  router.get(`/admin/database/tables/${props.tableName}`, {
    search: searchQuery.value,
    date_from: dateFrom.value,
    date_to: dateTo.value,
    sort: currentSort.value,
    direction: currentDirection.value,
  }, { preserveState: true });
};

const handleSort = (colName) => {
  if (currentSort.value === colName) {
    currentDirection.value = currentDirection.value === 'asc' ? 'desc' : 'asc';
  } else {
    currentSort.value = colName;
    currentDirection.value = 'asc';
  }
  applyFilters();
};

const toggleColumnVisibility = (colName) => {
  const idx = visibleColumns.value.indexOf(colName);
  if (idx === -1) {
    visibleColumns.value.push(colName);
  } else {
    if (visibleColumns.value.length > 1) {
      visibleColumns.value.splice(idx, 1);
    }
  }
};

// Record Actions
const openCreateModal = () => {
  modalMode.value = 'create';
  activeRecord.value = {};
  modalError.value = '';
  isRecordModalOpen.value = true;
};

const openViewModal = (row) => {
  modalMode.value = 'view';
  activeRecord.value = { ...row };
  modalError.value = '';
  isRecordModalOpen.value = true;
};

const openEditModal = (row) => {
  modalMode.value = 'edit';
  activeRecord.value = { ...row };
  modalError.value = '';
  isRecordModalOpen.value = true;
};

const saveRecord = async () => {
  isSaving.value = true;
  modalError.value = '';

  const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
  const pk = props.primaryKey || 'id';

  try {
    let url = `/admin/database/tables/${props.tableName}/records`;
    let method = 'POST';

    if (modalMode.value === 'edit') {
      url = `/admin/database/tables/${props.tableName}/records/${activeRecord.value[pk]}`;
      method = 'PUT';
    }

    const res = await fetch(url, {
      method,
      headers: {
        'X-CSRF-TOKEN': csrfToken || '',
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      },
      body: JSON.stringify(activeRecord.value)
    });

    const data = await res.json();
    if (data.success) {
      isRecordModalOpen.value = false;
      router.reload();
    } else {
      modalError.value = data.message || 'Failed to save record.';
    }
  } catch (e) {
    modalError.value = e.message;
  } finally {
    isSaving.value = false;
  }
};

const deleteRecord = async (row) => {
  const pk = props.primaryKey || 'id';
  if (!confirm(`Are you sure you want to delete record #${row[pk]}?`)) return;

  const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
  try {
    const res = await fetch(`/admin/database/tables/${props.tableName}/records/${row[pk]}`, {
      method: 'DELETE',
      headers: {
        'X-CSRF-TOKEN': csrfToken || '',
        'Accept': 'application/json'
      }
    });
    const data = await res.json();
    if (data.success) {
      router.reload();
    } else {
      alert(data.message || 'Failed to delete record.');
    }
  } catch (e) {
    alert('Delete error: ' + e.message);
  }
};
</script>

<template>
  <Head :title="'Table: ' + tableName + ' — Master Admin'" />


  <div class="min-h-screen bg-slate-950 text-slate-100 flex flex-col selection:bg-red-500 selection:text-white">
    <!-- Top Header -->
    <header class="border-b border-slate-800/80 bg-slate-900/60 backdrop-blur-xl sticky top-0 z-40">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3.5 flex items-center justify-between">
        <div class="flex items-center gap-3">
          <Link href="/admin/database/tables" class="w-9 h-9 rounded-xl bg-red-500/10 border border-red-500/30 text-red-500 flex items-center justify-center hover:bg-red-500/20 transition-all">
            <ChevronLeftIcon class="w-5 h-5 stroke-[2.5]" />
          </Link>
          <div>
            <div class="flex items-center gap-2">
              <span class="px-2 py-0.5 text-[10px] font-black uppercase tracking-wider bg-sky-500/20 text-sky-400 border border-sky-500/30 rounded-md">Dynamic Viewer</span>
              <h1 class="text-base font-black text-white tracking-tight">{{ tableName }}</h1>
            </div>
            <p class="text-xs text-slate-400 font-medium">Managing {{ totalRecords.toLocaleString() }} records dynamically</p>
          </div>
        </div>

        <div class="flex items-center gap-2">
          <!-- Column Visibility Toggle -->
          <button
            @click="isColumnDrawerOpen = !isColumnDrawerOpen"
            class="px-3 py-1.5 bg-slate-800 hover:bg-slate-700 text-slate-300 rounded-xl text-xs font-bold transition-all flex items-center gap-1.5"
          >
            <AdjustmentsHorizontalIcon class="w-4 h-4" />
            <span>Columns ({{ visibleColumns.length }})</span>
          </button>

          <!-- Export CSV -->
          <a
            :href="`/admin/database/tables/${tableName}/export/csv`"
            class="px-3 py-1.5 bg-slate-800 hover:bg-slate-700 text-slate-300 border border-slate-700 rounded-xl text-xs font-bold transition-all flex items-center gap-1.5"
          >
            <ArrowDownTrayIcon class="w-4 h-4 text-emerald-400" />
            <span>Export CSV</span>
          </a>

          <!-- Create Record -->
          <button
            @click="openCreateModal"
            class="px-3.5 py-1.5 bg-red-600 hover:bg-red-500 text-white rounded-xl text-xs font-black transition-all flex items-center gap-1.5 shadow-lg shadow-red-600/20"
          >
            <PlusIcon class="w-4 h-4 stroke-[3]" />
            <span>Add Record</span>
          </button>
        </div>
      </div>
    </header>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 flex-1 w-full space-y-6">
      <!-- Search, Date Filters Row -->
      <div class="p-4 rounded-2xl bg-slate-900/60 border border-slate-800/80 flex flex-wrap items-center justify-between gap-4">
        <div class="flex flex-wrap items-center gap-3 flex-1">
          <div class="relative w-full max-w-xs">
            <MagnifyingGlassIcon class="w-4 h-4 text-slate-500 absolute left-3.5 top-1/2 -translate-y-1/2" />
            <input
              type="text"
              v-model="searchQuery"
              @keyup.enter="applyFilters"
              placeholder="Search table..."
              class="w-full pl-9 pr-4 py-1.5 bg-slate-950/80 border border-slate-800 rounded-xl text-xs text-white placeholder:text-slate-500 focus:outline-none focus:border-red-500"
            />
          </div>

          <div class="flex items-center gap-2 text-xs text-slate-400">
            <span>Date:</span>
            <input
              type="date"
              v-model="dateFrom"
              @change="applyFilters"
              class="px-2 py-1 bg-slate-950/80 border border-slate-800 rounded-lg text-xs text-white"
            />
            <span>to</span>
            <input
              type="date"
              v-model="dateTo"
              @change="applyFilters"
              class="px-2 py-1 bg-slate-950/80 border border-slate-800 rounded-lg text-xs text-white"
            />
          </div>

          <button @click="applyFilters" class="px-3 py-1.5 bg-slate-800 hover:bg-slate-700 text-white font-bold text-xs rounded-xl">
            Filter
          </button>
        </div>

        <div class="text-xs text-slate-400 font-medium">
          Total: <span class="font-bold text-white">{{ totalRecords.toLocaleString() }}</span> records
        </div>
      </div>

      <!-- Column Visibility Drawer (Modal/Drawer) -->
      <div v-if="isColumnDrawerOpen" class="p-4 rounded-2xl bg-slate-900 border border-slate-800 flex flex-wrap gap-2 text-xs">
        <span class="text-slate-400 font-bold self-center mr-2">Toggle Columns:</span>
        <button
          v-for="col in columns"
          :key="'col_toggle_' + col.name"
          @click="toggleColumnVisibility(col.name)"
          :class="visibleColumns.includes(col.name) ? 'bg-red-600 text-white' : 'bg-slate-800 text-slate-400'"
          class="px-2.5 py-1 rounded-lg font-mono font-bold transition-all text-[11px]"
        >
          {{ col.name }}
        </button>
      </div>

      <!-- Dynamic Data Table -->
      <div class="rounded-2xl bg-slate-900/60 border border-slate-800/80 overflow-hidden shadow-2xl">
        <div class="overflow-x-auto max-h-[600px]">
          <table class="w-full text-left text-xs text-slate-300">
            <thead class="bg-slate-950/80 text-slate-400 text-[10px] font-black uppercase tracking-wider sticky top-0 z-10 border-b border-slate-800">
              <tr>
                <th
                  v-for="col in columns.filter(c => visibleColumns.includes(c.name))"
                  :key="'th_' + col.name"
                  @click="handleSort(col.name)"
                  class="px-4 py-3 cursor-pointer hover:text-white transition-colors"
                >
                  <div class="flex items-center gap-1.5">
                    <span>{{ col.name }}</span>
                    <span v-if="currentSort === col.name" class="text-red-400">
                      <ArrowUpIcon v-if="currentDirection === 'asc'" class="w-3 h-3 stroke-[3]" />
                      <ArrowDownIcon v-else class="w-3 h-3 stroke-[3]" />
                    </span>
                  </div>
                </th>
                <th class="px-4 py-3 text-right">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-800/60">
              <tr v-for="(row, rIdx) in records.data" :key="'row_' + rIdx" class="hover:bg-slate-800/30">
                <td
                  v-for="col in columns.filter(c => visibleColumns.includes(c.name))"
                  :key="'td_' + col.name + '_' + rIdx"
                  class="px-4 py-3 font-medium text-slate-200 max-w-xs truncate"
                >
                  {{ row[col.name] !== null ? row[col.name] : '—' }}
                </td>
                <td class="px-4 py-3 text-right whitespace-nowrap">
                  <div class="flex items-center justify-end gap-1.5">
                    <button @click="openViewModal(row)" class="p-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-sky-400">
                      <EyeIcon class="w-3.5 h-3.5" />
                    </button>
                    <button @click="openEditModal(row)" class="p-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-amber-400">
                      <PencilSquareIcon class="w-3.5 h-3.5" />
                    </button>
                    <button @click="deleteRecord(row)" class="p-1.5 rounded-lg bg-slate-800 hover:bg-rose-900/40 text-rose-400">
                      <TrashIcon class="w-3.5 h-3.5" />
                    </button>
                  </div>
                </td>
              </tr>
              <tr v-if="records.data.length === 0">
                <td :colspan="visibleColumns.length + 1" class="px-4 py-8 text-center text-slate-500 font-bold">
                  No records found in table.
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <div v-if="records.links && records.links.length > 3" class="px-5 py-3.5 bg-slate-950/60 border-t border-slate-800 flex items-center justify-between text-xs">
          <div class="text-slate-400">
            Showing <span class="font-bold text-white">{{ records.from || 0 }}</span> to <span class="font-bold text-white">{{ records.to || 0 }}</span> of <span class="font-bold text-white">{{ records.total || 0 }}</span>
          </div>
          <div class="flex items-center gap-1">
            <Link
              v-for="(link, lIdx) in records.links"
              :key="'pg_' + lIdx"
              :href="link.url || '#'"
              :class="[
                link.active ? 'bg-red-600 text-white font-black' : 'bg-slate-900 text-slate-400 hover:bg-slate-800',
                !link.url ? 'opacity-40 cursor-not-allowed' : ''
              ]"
              class="px-3 py-1.5 rounded-lg text-xs transition-all"
              v-html="link.label"
            />
          </div>
        </div>
      </div>
    </main>

    <!-- Record View / Edit / Create Modal -->
    <div v-if="isRecordModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm">
      <div class="w-full max-w-2xl bg-slate-900 border border-slate-800 rounded-3xl p-6 shadow-2xl space-y-4 max-h-[90vh] flex flex-col">
        <div class="flex items-center justify-between pb-3 border-b border-slate-800">
          <h3 class="text-base font-black text-white uppercase tracking-wider">
            {{ modalMode === 'create' ? 'Create Record' : modalMode === 'edit' ? 'Edit Record' : 'View Record' }}
          </h3>
          <button @click="isRecordModalOpen = false" class="text-slate-400 hover:text-white">
            <XMarkIcon class="w-5 h-5" />
          </button>
        </div>

        <div v-if="modalError" class="p-3 bg-rose-950/60 border border-rose-600 rounded-xl text-xs text-rose-200">
          {{ modalError }}
        </div>

        <!-- Form fields -->
        <div class="overflow-y-auto space-y-3 flex-1 pr-1">
          <div v-for="col in columns" :key="'fld_' + col.name" class="space-y-1">
            <label class="text-xs font-bold text-slate-400 flex items-center gap-1.5">
              <span>{{ col.name }}</span>
              <span class="text-[10px] text-slate-600 font-mono">({{ col.type }})</span>
            </label>
            <input
              v-if="modalMode !== 'view'"
              type="text"
              v-model="activeRecord[col.name]"
              :disabled="col.name === primaryKey && modalMode === 'edit'"
              class="w-full px-3 py-2 bg-slate-950 border border-slate-800 rounded-xl text-xs text-white focus:outline-none focus:border-red-500 disabled:opacity-40"
            />
            <div v-else class="px-3 py-2 bg-slate-950/60 rounded-xl text-xs text-slate-300 font-medium">
              {{ activeRecord[col.name] !== null ? activeRecord[col.name] : '—' }}
            </div>
          </div>
        </div>

        <!-- Modal Footer -->
        <div class="pt-3 border-t border-slate-800 flex items-center justify-end gap-2">
          <button @click="isRecordModalOpen = false" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 text-slate-300 font-bold text-xs rounded-xl">
            Close
          </button>
          <button
            v-if="modalMode !== 'view'"
            @click="saveRecord"
            :disabled="isSaving"
            class="px-5 py-2 bg-red-600 hover:bg-red-500 text-white font-black text-xs rounded-xl shadow-lg shadow-red-600/20"
          >
            {{ isSaving ? 'Saving...' : 'Save Changes' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
