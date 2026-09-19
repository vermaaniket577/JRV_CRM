<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import {
  ChevronLeftIcon,
  CircleStackIcon,
  DocumentArrowDownIcon,
  ArrowUturnLeftIcon,
  CheckCircleIcon,
  ExclamationTriangleIcon,
  ShieldCheckIcon,
  TableCellsIcon,
  ServerStackIcon
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
  errorsCount: {
    type: Number,
    default: 0
  }
});

const currentImport = computed(() => props.databaseImport?.id ? props.databaseImport : (props.import || {}));
const isRollingBack = ref(false);

const rollback = async () => {
  if (!currentImport.value?.id) return;
  if (!confirm(`Are you sure you want to rollback import #${currentImport.value.id} to its pre-import backup state?`)) {
    return;
  }

  isRollingBack.value = true;
  const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
  try {
    const res = await fetch(`/admin/database/import/${currentImport.value.id}/rollback`, {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': csrfToken || '',
        'Accept': 'application/json'
      }
    });

    const data = await res.json();
    if (data.success) {
      alert(data.message);
      router.reload();
    } else {
      alert('Rollback failed: ' + data.message);
    }
  } catch (e) {
    alert('Error during rollback: ' + e.message);
  } finally {
    isRollingBack.value = false;
  }
};
</script>

<template>
  <Head :title="'Import #' + (currentImport.id || '') + ' Report — Master Admin'" />


  <div class="min-h-screen bg-slate-950 text-slate-100 flex flex-col selection:bg-red-500 selection:text-white">
    <!-- Top Header -->
    <header class="border-b border-slate-800/80 bg-slate-900/60 backdrop-blur-xl sticky top-0 z-40">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3.5 flex items-center justify-between">
        <div class="flex items-center gap-3">
          <Link href="/admin/database/import/history" class="w-9 h-9 rounded-xl bg-red-500/10 border border-red-500/30 text-red-500 flex items-center justify-center hover:bg-red-500/20 transition-all">
            <ChevronLeftIcon class="w-5 h-5 stroke-[2.5]" />
          </Link>
          <div>
            <div class="flex items-center gap-2">
              <span class="px-2 py-0.5 text-[10px] font-black uppercase tracking-wider bg-red-600/20 text-red-400 border border-red-500/30 rounded-md">Audit Report</span>
              <h1 class="text-base font-black text-white tracking-tight">Import Session #{{ currentImport.id }}</h1>
            </div>
            <p class="text-xs text-slate-400 font-medium">{{ currentImport.file_name }} &bull; Executed by {{ currentImport.user?.email || 'Administrator' }}</p>
          </div>
        </div>

        <div class="flex items-center gap-2">
          <a
            v-if="errorsCount > 0"
            :href="`/admin/database/import/${currentImport.id}/errors/export`"
            class="px-3.5 py-1.5 bg-slate-800 hover:bg-slate-700 text-slate-300 border border-slate-700 rounded-xl text-xs font-bold transition-all flex items-center gap-1.5"
          >
            <DocumentArrowDownIcon class="w-4 h-4 text-emerald-400" />
            <span>Download Error CSV ({{ errorsCount }})</span>
          </a>

          <button
            v-if="currentImport.backup && currentImport.status !== 'ROLLED_BACK'"
            @click="rollback"
            :disabled="isRollingBack"
            class="px-3.5 py-1.5 bg-purple-600 hover:bg-purple-500 text-white rounded-xl text-xs font-black transition-all flex items-center gap-1.5 shadow-lg shadow-purple-600/20"
          >
            <ArrowUturnLeftIcon class="w-4 h-4" />
            <span>{{ isRollingBack ? 'Restoring...' : 'Rollback from Backup' }}</span>
          </button>
        </div>
      </div>
    </header>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 flex-1 w-full space-y-6">
      <!-- Metrics Row -->
      <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-6 gap-3">
        <div class="p-4 rounded-2xl bg-slate-900/60 border border-slate-800">
          <div class="text-[10px] font-bold text-slate-400 uppercase">Status</div>
          <div class="text-lg font-black mt-1" :class="currentImport.status === 'COMPLETED' ? 'text-emerald-400' : 'text-amber-400'">
            {{ currentImport.status }}
          </div>
        </div>
        <div class="p-4 rounded-2xl bg-slate-900/60 border border-slate-800">
          <div class="text-[10px] font-bold text-slate-400 uppercase">Mode</div>
          <div class="text-lg font-black text-white mt-1 uppercase text-sm font-mono">{{ currentImport.import_mode }}</div>
        </div>
        <div class="p-4 rounded-2xl bg-slate-900/60 border border-slate-800">
          <div class="text-[10px] font-bold text-slate-400 uppercase">Inserted</div>
          <div class="text-lg font-black text-emerald-400 mt-1">{{ (currentImport.records_inserted || 0).toLocaleString() }}</div>
        </div>
        <div class="p-4 rounded-2xl bg-slate-900/60 border border-slate-800">
          <div class="text-[10px] font-bold text-slate-400 uppercase">Updated</div>
          <div class="text-lg font-black text-sky-400 mt-1">{{ (currentImport.records_updated || 0).toLocaleString() }}</div>
        </div>
        <div class="p-4 rounded-2xl bg-slate-900/60 border border-slate-800">
          <div class="text-[10px] font-bold text-slate-400 uppercase">Skipped</div>
          <div class="text-lg font-black text-slate-400 mt-1">{{ (currentImport.records_skipped || 0).toLocaleString() }}</div>
        </div>
        <div class="p-4 rounded-2xl bg-slate-900/60 border border-slate-800">
          <div class="text-[10px] font-bold text-slate-400 uppercase">Failed</div>
          <div class="text-lg font-black text-rose-400 mt-1">{{ (currentImport.records_failed || 0).toLocaleString() }}</div>
        </div>
      </div>

      <!-- Backup Information Card -->
      <div v-if="currentImport.backup" class="p-5 rounded-2xl bg-slate-900/60 border border-slate-800 flex items-start justify-between gap-4">
        <div class="flex items-start gap-3">
          <ShieldCheckIcon class="w-6 h-6 text-purple-400 shrink-0 mt-0.5" />
          <div class="space-y-0.5">
            <h3 class="font-black text-sm text-white">Database Backup Snapshot Available</h3>
            <p class="text-xs text-slate-400 font-mono">{{ currentImport.backup.backup_name }} ({{ currentImport.backup.file_size }} bytes)</p>
          </div>
        </div>
        <button
          v-if="currentImport.status !== 'ROLLED_BACK'"
          @click="rollback"
          :disabled="isRollingBack"
          class="px-4 py-2 bg-purple-600 hover:bg-purple-500 text-white font-black text-xs rounded-xl shadow-lg shadow-purple-600/20"
        >
          Rollback to this Backup
        </button>
      </div>

      <!-- Tables and Mappings Breakdown -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Tables Summary -->
        <div class="p-5 rounded-2xl bg-slate-900/60 border border-slate-800 space-y-4">
          <h3 class="font-black text-sm text-white flex items-center gap-2">
            <TableCellsIcon class="w-4 h-4 text-sky-400" />
            <span>Synchronized Tables ({{ currentImport.tables?.length || 0 }})</span>
          </h3>
          <div class="space-y-2 max-h-72 overflow-y-auto pr-1">
            <div
              v-for="t in currentImport.tables"
              :key="'tbl_' + t.id"
              class="p-3 bg-slate-950/60 border border-slate-800 rounded-xl flex items-center justify-between text-xs"
            >
              <div class="space-y-0.5">
                <span class="font-bold text-white font-mono">{{ t.table_name }}</span>
                <div class="text-[11px] text-slate-500">{{ t.columns_count }} columns &bull; {{ (t.records_count || 0).toLocaleString() }} rows</div>
              </div>
              <span class="px-2 py-0.5 text-[10px] font-bold uppercase rounded bg-slate-800 text-slate-300">
                {{ t.status }}
              </span>
            </div>
          </div>
        </div>

        <!-- Field Mappings Summary -->
        <div class="p-5 rounded-2xl bg-slate-900/60 border border-slate-800 space-y-4">
          <h3 class="font-black text-sm text-white flex items-center gap-2">
            <ServerStackIcon class="w-4 h-4 text-emerald-400" />
            <span>Applied Field Mappings ({{ currentImport.mappings?.length || 0 }})</span>
          </h3>
          <div class="space-y-2 max-h-72 overflow-y-auto pr-1">
            <div
              v-for="m in currentImport.mappings"
              :key="'map_' + m.id"
              class="p-3 bg-slate-950/60 border border-slate-800 rounded-xl flex items-center justify-between text-xs"
            >
              <span class="font-mono text-slate-300">{{ m.source_table }}.{{ m.source_column }}</span>
              <span class="text-slate-600">&rarr;</span>
              <span class="font-mono text-emerald-400 font-bold">{{ m.target_table }}.{{ m.target_column }}</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Errors Table (if any) -->
      <div v-if="currentImport.errors && currentImport.errors.length > 0" class="rounded-2xl bg-slate-900/60 border border-slate-800 overflow-hidden space-y-3 p-5">
        <div class="flex items-center justify-between">
          <h3 class="font-black text-sm text-white flex items-center gap-2">
            <ExclamationTriangleIcon class="w-4 h-4 text-rose-400" />
            <span>Error Logs (Showing first {{ currentImport.errors.length }} of {{ errorsCount }})</span>
          </h3>
          <a
            :href="`/admin/database/import/${currentImport.id}/errors/export`"
            class="px-3 py-1.5 bg-slate-800 hover:bg-slate-700 text-slate-300 text-xs font-bold rounded-xl flex items-center gap-1.5"
          >
            <DocumentArrowDownIcon class="w-3.5 h-3.5 text-emerald-400" />
            <span>Download CSV</span>
          </a>
        </div>

        <div class="overflow-x-auto">
          <table class="w-full text-left text-xs text-slate-300">
            <thead class="bg-slate-950/80 text-slate-400 text-[10px] font-black uppercase tracking-wider border-b border-slate-800">
              <tr>
                <th class="px-4 py-2.5">Row</th>
                <th class="px-4 py-2.5">Table</th>
                <th class="px-4 py-2.5">Error Reason</th>
                <th class="px-4 py-2.5">Raw Data Snippet</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-800/60">
              <tr v-for="err in currentImport.errors" :key="'err_' + err.id" class="hover:bg-slate-800/30">
                <td class="px-4 py-2.5 font-mono text-slate-400">{{ err.row_number || 'N/A' }}</td>
                <td class="px-4 py-2.5 font-bold text-white">{{ err.table_name }}</td>
                <td class="px-4 py-2.5 text-rose-400">{{ err.error_message }}</td>
                <td class="px-4 py-2.5 font-mono text-[11px] text-slate-400 max-w-xs truncate">
                  {{ err.raw_data ? JSON.stringify(err.raw_data) : '—' }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </main>
  </div>
</template>
