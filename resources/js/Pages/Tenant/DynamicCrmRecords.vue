<script setup>
import { ref, computed, onMounted } from 'vue';
import { useForm, Head, router } from '@inertiajs/vue3';
import Navbar from '@/Components/Navbar.vue';
import GlobalSearchModal from '@/Components/GlobalSearchModal.vue';
import UploadDatabaseModal from '@/Components/UploadDatabaseModal.vue';
import {
  CircleStackIcon,
  TableCellsIcon,
  PlusIcon,
  MagnifyingGlassIcon,
  ArrowDownTrayIcon,
  TrashIcon,
  CheckCircleIcon,
  ClockIcon,
  CurrencyRupeeIcon,
  UserGroupIcon,
  SparklesIcon,
  AdjustmentsHorizontalIcon,
  XMarkIcon,
  FunnelIcon,
  ChartBarIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
  tenant: Object,
  columns: Array,
  records: Object,
  dbInfo: Object,
  stats: Object,
  filters: Object,
});

const isSearchOpen = ref(false);
const isAddRecordModalOpen = ref(false);
const isAddColumnModalOpen = ref(false);
const isUploadDbModalOpen = ref(false);
const searchQuery = ref(props.filters?.search || '');
const statusFilter = ref(props.filters?.status || 'all');
const showOnboardingSuccess = ref(false);

onMounted(() => {
  if (typeof window !== 'undefined') {
    const params = new URLSearchParams(window.location.search);
    if (params.get('onboarding_success') === '1') {
      showOnboardingSuccess.value = true;
    }
  }
});

// Form for adding new record with dynamic fields
const recordForm = useForm({});

// Initialize record form data with columns keys
const initRecordForm = () => {
  const data = {};
  (props.columns || []).forEach(col => {
    data[col.column_key] = col.default_value || '';
  });
  recordForm.defaults(data);
  recordForm.reset();
};

const openAddRecordModal = () => {
  initRecordForm();
  isAddRecordModalOpen.value = true;
};

const submitRecord = () => {
  recordForm.post('/tenant/crm-records', {
    onSuccess: () => {
      isAddRecordModalOpen.value = false;
      recordForm.reset();
    },
  });
};

// Form for adding new column
const columnForm = useForm({
  label: '',
  type: 'text',
  options: [],
  optionsStr: '',
  is_required: false,
});

const submitNewColumn = () => {
  if (columnForm.type === 'dropdown' && columnForm.optionsStr) {
    columnForm.options = columnForm.optionsStr.split(',').map(s => s.trim()).filter(Boolean);
  }
  columnForm.post('/tenant/database/columns', {
    onSuccess: () => {
      isAddColumnModalOpen.value = false;
      columnForm.reset();
    },
  });
};

const deleteColumn = (col) => {
  if (confirm(`Are you sure you want to remove '${col.column_label}' from this table?`)) {
    router.delete(`/tenant/database/columns/${col.id}`);
  }
};

const applySearch = () => {
  router.get('/tenant/crm-records', {
    search: searchQuery.value,
    status: statusFilter.value,
  }, {
    preserveState: true,
    replace: true,
  });
};

const filterByStatus = (status) => {
  statusFilter.value = status;
  applySearch();
};
</script>

<template>
  <Head :title="`CRM Data & Records - ${tenant?.name || 'Workspace'}`" />

  <div class="min-h-screen bg-slate-100 flex font-sans text-slate-900">
    <!-- Navbar (Fixed Left Sidebar) -->
    <Navbar @open-search="isSearchOpen = true" />

    <!-- Main Content Area -->
    <div class="flex-1 flex flex-col min-w-0 overflow-y-auto">
      
      <!-- Top Header -->
      <header class="bg-white border-b border-slate-200 px-6 py-4 sticky top-0 z-30 shadow-2xs">
        <div class="max-w-7xl mx-auto flex flex-col md:flex-row md:items-center justify-between gap-4">
          
          <div class="flex items-center gap-3.5">
            <div class="w-11 h-11 rounded-2xl bg-red-600/10 border border-red-200 text-red-600 flex items-center justify-center font-bold text-xl shadow-xs">
              <TableCellsIcon class="w-6 h-6" />
            </div>
            <div>
              <div class="flex items-center gap-2.5">
                <h1 class="text-xl font-bold text-slate-900 tracking-tight">{{ tenant?.name || 'My CRM' }} CRM Data & Records Hub</h1>
                <span class="px-3 py-1 rounded-full text-xs font-semibold uppercase bg-emerald-50 text-emerald-700 border border-emerald-200">
                  {{ dbInfo?.status || 'Active 🟢' }}
                </span>
              </div>
              <p class="text-sm text-slate-500 font-mono mt-0.5">
                Database: <span class="font-semibold text-slate-700">{{ dbInfo?.database_name }}</span> • Table: <span class="font-semibold text-slate-700">{{ dbInfo?.table_name }}</span> ({{ columns?.length }} Schema Columns)
              </p>
            </div>
          </div>

          <!-- Actions -->
          <div class="flex items-center gap-3">
            <button
              type="button"
              @click="isUploadDbModalOpen = true"
              class="px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold rounded-xl flex items-center gap-2 transition shadow-sm cursor-pointer"
              title="Upload .sql database or spreadsheet to create table and populate CRM data"
            >
              <CircleStackIcon class="w-4 h-4 stroke-[2.5]" />
              <span>Upload Database</span>
            </button>

            <a
              href="/tenant/crm-records/export"
              class="px-4 py-2.5 bg-white hover:bg-slate-50 border border-slate-300 text-slate-700 text-sm font-semibold rounded-xl flex items-center gap-2 transition shadow-2xs cursor-pointer"
              title="Export all records with custom column headers to CSV"
            >
              <ArrowDownTrayIcon class="w-4 h-4 stroke-[2.5]" />
              <span>Export CSV</span>
            </a>

            <button
              @click="isAddColumnModalOpen = true"
              class="px-4 py-2.5 bg-slate-900 hover:bg-slate-800 text-white text-sm font-semibold rounded-xl flex items-center gap-2 transition shadow-sm cursor-pointer"
            >
              <AdjustmentsHorizontalIcon class="w-4 h-4 stroke-[2.5]" />
              <span>+ Add Column</span>
            </button>

            <button
              @click="openAddRecordModal"
              class="px-5 py-2.5 bg-red-600 hover:bg-red-700 text-white text-sm font-bold rounded-xl shadow-md shadow-red-600/25 flex items-center gap-2 transition cursor-pointer"
            >
              <PlusIcon class="w-4 h-4 stroke-[3]" />
              <span>+ Add Lead / Record</span>
            </button>
          </div>

        </div>
      </header>

      <!-- Main Body -->
      <main class="max-w-7xl mx-auto w-full p-6 space-y-6 flex-1">

        <!-- Onboarding Success Alert Banner -->
        <div v-if="showOnboardingSuccess" class="bg-gradient-to-r from-emerald-500/10 via-emerald-500/5 to-transparent border border-emerald-300/80 rounded-2xl p-4 sm:p-5 flex items-start justify-between gap-4 shadow-xs">
          <div class="flex items-start gap-3.5">
            <div class="w-10 h-10 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center text-xl shrink-0">
              🎉
            </div>
            <div class="space-y-1">
              <div class="flex items-center gap-2">
                <h3 class="font-bold text-slate-900 text-sm sm:text-base">Workspace & Database Ready!</h3>
                <span class="px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800 font-semibold text-xs border border-emerald-200">
                  Live in CRM
                </span>
              </div>
              <p class="text-xs sm:text-sm text-slate-600 leading-relaxed">
                Your database has been deployed to your dedicated MySQL database. All schema tables, custom fields, and data records are automatically configured and displayed below.
              </p>
            </div>
          </div>
          <button @click="showOnboardingSuccess = false" class="text-slate-400 hover:text-slate-600 text-sm font-bold p-1 cursor-pointer">✕</button>
        </div>
        
        <!-- Metrics Bar -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
          <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-xs space-y-1.5">
            <span class="text-xs font-semibold uppercase text-slate-500 tracking-wider">Total CRM Leads</span>
            <p class="text-2xl font-bold text-slate-900">{{ stats?.total_records || 0 }}</p>
          </div>
          <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-xs space-y-1.5">
            <span class="text-xs font-semibold uppercase text-slate-500 tracking-wider">Pipeline Value</span>
            <p class="text-2xl font-bold text-emerald-600">{{ stats?.total_value || '₹0' }}</p>
          </div>
          <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-xs space-y-1.5">
            <span class="text-xs font-semibold uppercase text-slate-500 tracking-wider">Active Schema Columns</span>
            <p class="text-2xl font-bold text-indigo-600">{{ columns?.length || 0 }}</p>
          </div>
          <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-xs space-y-1.5">
            <span class="text-xs font-semibold uppercase text-slate-500 tracking-wider">MySQL Engine Status</span>
            <p class="text-sm font-bold text-emerald-700 font-mono mt-2 flex items-center gap-1.5">
              <span>●</span> {{ dbInfo?.status }}
            </p>
          </div>
        </div>

        <!-- Filter & Search Toolbar -->
        <div class="bg-white border border-slate-200 rounded-2xl p-4 shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-4">
          <div class="relative flex-1 max-w-md">
            <MagnifyingGlassIcon class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2" />
            <input
              v-model="searchQuery"
              @keyup.enter="applySearch"
              type="text"
              placeholder="Search by contact, company, phone, status..."
              class="w-full bg-slate-50 border border-slate-300 rounded-xl pl-10 pr-4 py-2.5 text-sm font-medium text-slate-900 focus:outline-none focus:border-red-500 focus:bg-white transition"
            />
          </div>

          <div class="flex items-center gap-2 overflow-x-auto pb-1 sm:pb-0">
            <button
              v-for="st in ['all', 'New Lead', 'Qualified', 'Proposal Sent', 'Won', 'Lost']"
              :key="st"
              @click="filterByStatus(st)"
              :class="[
                'px-3.5 py-2 rounded-xl text-sm font-semibold transition whitespace-nowrap cursor-pointer',
                statusFilter === st 
                  ? 'bg-red-50 border border-red-300 text-red-700' 
                  : 'bg-slate-50 border border-slate-200 text-slate-600 hover:bg-slate-100'
              ]"
            >
              {{ st === 'all' ? 'All Leads' : st }}
            </button>
          </div>
        </div>

        <!-- Custom Columns Data Grid -->
        <div class="bg-white border border-slate-200 rounded-2xl shadow-xs overflow-hidden">
          <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
              <thead>
                <tr class="bg-slate-50/90 border-b border-slate-200 text-xs font-bold uppercase text-slate-600 tracking-wider">
                  <th class="py-4 px-4">#</th>
                  <th v-for="col in columns" :key="col.id" class="py-4 px-4 whitespace-nowrap">
                    <div class="flex items-center gap-2 group">
                      <span>{{ col.column_label }}</span>
                      <span class="text-xs font-mono text-slate-400 font-normal">({{ col.column_type }})</span>
                      <button 
                        v-if="!col.is_default" 
                        @click="deleteColumn(col)" 
                        class="text-slate-300 hover:text-red-600 p-0.5 opacity-0 group-hover:opacity-100 transition"
                        title="Delete this custom column"
                      >
                        <TrashIcon class="w-4 h-4" />
                      </button>
                    </div>
                  </th>
                  <th class="py-4 px-4">Status</th>
                  <th class="py-4 px-4 text-right">Created</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100 text-sm">
                <tr 
                  v-for="(row, idx) in records?.data" 
                  :key="row.id || idx"
                  class="hover:bg-slate-50/80 transition-colors font-medium text-slate-800"
                >
                  <td class="py-3.5 px-4 font-mono text-slate-400 font-bold">#{{ row.id }}</td>
                  
                  <!-- Dynamic Columns Rendering -->
                  <td v-for="col in columns" :key="col.id" class="py-3.5 px-4 whitespace-nowrap">
                    <span v-if="col.column_type === 'currency'" class="font-bold text-slate-900">
                      ₹{{ Number(row[col.column_key] || 0).toLocaleString() }}
                    </span>
                    <span v-else-if="col.column_type === 'email'" class="text-blue-600 hover:underline">
                      {{ row[col.column_key] || '—' }}
                    </span>
                    <span v-else-if="col.column_key === 'contact_name' || col.column_key === 'name'" class="font-bold text-slate-900">
                      {{ row[col.column_key] || row.contact_name || '—' }}
                    </span>
                    <span v-else>
                      {{ row[col.column_key] || '—' }}
                    </span>
                  </td>

                  <!-- Status -->
                  <td class="py-3.5 px-4">
                    <span :class="[
                      'px-3 py-1 rounded-full text-xs font-bold uppercase',
                      row.status === 'Won' ? 'bg-emerald-100 text-emerald-800 border border-emerald-300' :
                      row.status === 'Lost' ? 'bg-rose-100 text-rose-800 border border-rose-300' :
                      row.status === 'Qualified' ? 'bg-indigo-100 text-indigo-800 border border-indigo-300' :
                      'bg-amber-100 text-amber-800 border border-amber-300'
                    ]">
                      {{ row.status || 'New Lead' }}
                    </span>
                  </td>

                  <!-- Date -->
                  <td class="py-3.5 px-4 text-right text-slate-500 whitespace-nowrap">
                    {{ row.created_at || 'Recently' }}
                  </td>
                </tr>

                <tr v-if="!records?.data || records.data.length === 0">
                  <td :colspan="columns.length + 3" class="py-14 text-center text-slate-400">
                    <TableCellsIcon class="w-12 h-12 mx-auto mb-3 opacity-40 text-slate-500" />
                    <p class="font-bold text-base text-slate-700">No leads / CRM records found</p>
                    <p class="text-sm text-slate-400 mt-1">Click "+ Add Lead / Record" to insert your first data row into the dedicated database.</p>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

      </main>
    </div>

    <!-- ADD LEAD / RECORD DYNAMIC MODAL -->
    <div v-if="isAddRecordModalOpen" class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4 z-50 overflow-y-auto">
      <div class="bg-white rounded-3xl border border-slate-200 p-6 sm:p-8 max-w-2xl w-full shadow-2xl space-y-6 my-8">
        
        <div class="flex items-center justify-between pb-4 border-b border-slate-100">
          <div class="flex items-center gap-3.5">
            <div class="w-11 h-11 rounded-2xl bg-red-50 text-red-600 flex items-center justify-center font-bold text-xl">
              +
            </div>
            <div>
              <h3 class="font-bold text-xl text-slate-900">Add New CRM Record</h3>
              <p class="text-sm text-slate-500 mt-0.5">Insert row with all your chosen database columns</p>
            </div>
          </div>
          <button @click="isAddRecordModalOpen = false" class="text-slate-400 hover:text-slate-600 font-bold text-xl">✕</button>
        </div>

        <form @submit.prevent="submitRecord" class="space-y-5">
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 max-h-[60vh] overflow-y-auto pr-2">
            
            <div v-for="col in columns" :key="col.id" class="space-y-1.5">
              <label class="block text-xs font-bold uppercase text-slate-700 tracking-wider">
                {{ col.column_label }}
                <span v-if="col.is_required" class="text-red-500">*</span>
              </label>

              <!-- Dropdown Input -->
              <select
                v-if="col.column_type === 'dropdown' && col.options"
                v-model="recordForm[col.column_key]"
                :required="col.is_required"
                class="w-full bg-slate-50 border border-slate-300 rounded-xl px-4 py-2.5 text-sm font-medium text-slate-900 focus:outline-none focus:border-red-500 focus:bg-white transition"
              >
                <option value="">Select {{ col.column_label }}</option>
                <option v-for="opt in col.options" :key="opt" :value="opt">{{ opt }}</option>
              </select>

              <!-- Textarea Input -->
              <textarea
                v-else-if="col.column_type === 'textarea'"
                v-model="recordForm[col.column_key]"
                :required="col.is_required"
                rows="2"
                :placeholder="`Enter ${col.column_label}...`"
                class="w-full bg-slate-50 border border-slate-300 rounded-xl px-4 py-2.5 text-sm font-medium text-slate-900 focus:outline-none focus:border-red-500 focus:bg-white transition"
              ></textarea>

              <!-- Date Input -->
              <input
                v-else-if="col.column_type === 'date'"
                v-model="recordForm[col.column_key]"
                type="date"
                :required="col.is_required"
                class="w-full bg-slate-50 border border-slate-300 rounded-xl px-4 py-2.5 text-sm font-medium text-slate-900 focus:outline-none focus:border-red-500 focus:bg-white transition"
              />

              <!-- Number / Currency Input -->
              <input
                v-else-if="col.column_type === 'number' || col.column_type === 'currency'"
                v-model="recordForm[col.column_key]"
                type="number"
                step="any"
                :required="col.is_required"
                :placeholder="col.column_type === 'currency' ? 'e.g. 50000' : '0'"
                class="w-full bg-slate-50 border border-slate-300 rounded-xl px-4 py-2.5 text-sm font-medium text-slate-900 focus:outline-none focus:border-red-500 focus:bg-white transition"
              />

              <!-- Default Text/Email Input -->
              <input
                v-else
                v-model="recordForm[col.column_key]"
                :type="col.column_type === 'email' ? 'email' : 'text'"
                :required="col.is_required"
                :placeholder="`Enter ${col.column_label}`"
                class="w-full bg-slate-50 border border-slate-300 rounded-xl px-4 py-2.5 text-sm font-medium text-slate-900 focus:outline-none focus:border-red-500 focus:bg-white transition"
              />
            </div>

          </div>

          <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
            <button
              type="button"
              @click="isAddRecordModalOpen = false"
              class="px-5 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-semibold rounded-xl"
            >
              Cancel
            </button>
            <button
              type="submit"
              :disabled="recordForm.processing"
              class="px-6 py-2.5 bg-red-600 hover:bg-red-700 text-white text-sm font-bold rounded-xl shadow-md shadow-red-600/30 disabled:opacity-50"
            >
              Save Record into Database
            </button>
          </div>
        </form>

      </div>
    </div>

    <!-- ADD COLUMN MODAL -->
    <div v-if="isAddColumnModalOpen" class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4 z-50">
      <div class="bg-white rounded-3xl border border-slate-200 p-6 sm:p-8 max-w-md w-full shadow-2xl space-y-5">
        
        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-2xl bg-slate-900 text-white flex items-center justify-center font-bold text-lg">
              <AdjustmentsHorizontalIcon class="w-5 h-5" />
            </div>
            <div>
              <h3 class="font-bold text-lg text-slate-900">Add Column to Database</h3>
              <p class="text-xs text-slate-500 mt-0.5">Alters {{ dbInfo?.table_name }} schema in real-time</p>
            </div>
          </div>
          <button @click="isAddColumnModalOpen = false" class="text-slate-400 hover:text-slate-600 font-bold text-lg">✕</button>
        </div>

        <form @submit.prevent="submitNewColumn" class="space-y-4 text-sm">
          <div class="space-y-1.5">
            <label class="block font-bold text-slate-700 text-xs uppercase tracking-wider">Column Label Name</label>
            <input 
              v-model="columnForm.label"
              type="text" 
              required
              placeholder="e.g. Budget Range, License Number, Locality..." 
              class="w-full bg-slate-50 border border-slate-300 rounded-xl px-4 py-2.5 font-medium text-slate-900 focus:outline-none focus:border-red-500 focus:bg-white"
            />
          </div>

          <div class="space-y-1.5">
            <label class="block font-bold text-slate-700 text-xs uppercase tracking-wider">Data / SQL Type</label>
            <select 
              v-model="columnForm.type"
              class="w-full bg-slate-50 border border-slate-300 rounded-xl px-4 py-2.5 font-medium text-slate-900 focus:outline-none focus:border-red-500 focus:bg-white"
            >
              <option value="text">Text (VARCHAR 255)</option>
              <option value="number">Number (Integer)</option>
              <option value="currency">Currency / Value (DECIMAL 15,2)</option>
              <option value="date">Date (YYYY-MM-DD)</option>
              <option value="dropdown">Dropdown (Select list)</option>
              <option value="email">Email Address</option>
              <option value="textarea">Long Text / Notes (TEXT)</option>
              <option value="boolean">Yes / No (Boolean)</option>
            </select>
          </div>

          <div v-if="columnForm.type === 'dropdown'" class="space-y-1.5">
            <label class="block font-bold text-slate-700 text-xs uppercase tracking-wider">Dropdown Options (Comma separated)</label>
            <input 
              v-model="columnForm.optionsStr"
              type="text" 
              placeholder="e.g. Bronze, Silver, Gold, Platinum" 
              class="w-full bg-slate-50 border border-slate-300 rounded-xl px-4 py-2.5 font-medium text-slate-900 focus:outline-none focus:border-red-500 focus:bg-white"
            />
          </div>

          <label class="flex items-center gap-2.5 cursor-pointer pt-1">
            <input type="checkbox" v-model="columnForm.is_required" class="w-4 h-4 text-red-600 rounded" />
            <span class="font-medium text-slate-700 text-sm">Make this column required</span>
          </label>

          <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
            <button 
              type="button"
              @click="isAddColumnModalOpen = false"
              class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold rounded-xl text-sm"
            >
              Cancel
            </button>
            <button 
              type="submit"
              :disabled="columnForm.processing"
              class="px-5 py-2.5 bg-red-600 hover:bg-red-700 text-white font-bold rounded-xl shadow-md shadow-red-600/20 disabled:opacity-50 text-sm"
            >
              ALTER TABLE & Add Column
            </button>
          </div>
        </form>

      </div>
    </div>

    <!-- Global Search Modal -->
    <GlobalSearchModal :is-open="isSearchOpen" @close="isSearchOpen = false" />
    <UploadDatabaseModal :is-open="isUploadDbModalOpen" @close="isUploadDbModalOpen = false" />
  </div>
</template>
