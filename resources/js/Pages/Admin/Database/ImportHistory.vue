<script setup>
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import {
  ClockIcon,
  ChevronLeftIcon,
  EyeIcon,
  ArrowUturnLeftIcon,
  DocumentArrowDownIcon,
  CircleStackIcon,
  CheckCircleIcon,
  ExclamationCircleIcon,
  PlusIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
  imports: {
    type: Object,
    default: () => ({ data: [], links: [] })
  }
});

const rollback = async (importItem) => {
  if (!confirm(`Are you sure you want to rollback import #${importItem.id} (${importItem.file_name}) to its pre-import backup state?`)) {
    return;
  }

  const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
  try {
    const res = await fetch(`/admin/database/import/${importItem.id}/rollback`, {
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
  }
};
</script>

<template>
  <Head title="Import History & Audit Trail — Master Admin" />

  <div class="min-h-screen bg-slate-950 text-slate-100 flex flex-col selection:bg-red-500 selection:text-white">
    <!-- Top Header -->
    <header class="border-b border-slate-800/80 bg-slate-900/60 backdrop-blur-xl sticky top-0 z-40">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-3.5 flex items-center justify-between">
        <div class="flex items-center gap-3">
          <Link href="/admin/database/import" class="w-9 h-9 rounded-xl bg-red-500/10 border border-red-500/30 text-red-500 flex items-center justify-center hover:bg-red-500/20 transition-all">
            <ChevronLeftIcon class="w-5 h-5 stroke-[2.5]" />
          </Link>
          <div>
            <div class="flex items-center gap-2">
              <span class="px-2 py-0.5 text-[10px] font-black uppercase tracking-wider bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 rounded-md">Audit Log</span>
              <h1 class="text-base font-black text-white tracking-tight">Database Import History</h1>
            </div>
            <p class="text-xs text-slate-400 font-medium">Complete record of all database imports, backups, and rollback snapshots</p>
          </div>
        </div>

        <div class="flex items-center gap-2">
          <Link
            href="/admin/database/import"
            class="px-3.5 py-1.5 bg-red-600 hover:bg-red-500 text-white rounded-xl text-xs font-black transition-all flex items-center gap-1.5 shadow-lg shadow-red-600/20"
          >
            <PlusIcon class="w-4 h-4 stroke-[3]" />
            <span>New SQL Import</span>
          </Link>
        </div>
      </div>
    </header>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 flex-1 w-full space-y-6">
      <div class="rounded-2xl bg-slate-900/60 border border-slate-800/80 overflow-hidden shadow-2xl">
        <div class="overflow-x-auto">
          <table class="w-full text-left text-xs text-slate-300">
            <thead class="bg-slate-950/80 text-slate-400 text-[10px] font-black uppercase tracking-wider border-b border-slate-800">
              <tr>
                <th class="px-5 py-3">Import ID</th>
                <th class="px-5 py-3">File Name</th>
                <th class="px-5 py-3">Status</th>
                <th class="px-5 py-3">Mode</th>
                <th class="px-5 py-3">Inserted</th>
                <th class="px-5 py-3">Updated</th>
                <th class="px-5 py-3">Errors</th>
                <th class="px-5 py-3">Backup Snapshot</th>
                <th class="px-5 py-3">Timestamp</th>
                <th class="px-5 py-3 text-right">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-800/60">
              <tr v-for="imp in imports.data" :key="imp.id" class="hover:bg-slate-800/30">
                <td class="px-5 py-3 font-mono font-bold text-white">#{{ imp.id }}</td>
                <td class="px-5 py-3 font-medium text-slate-200 flex items-center gap-2">
                  <CircleStackIcon class="w-4 h-4 text-red-500 shrink-0" />
                  <span class="truncate max-w-xs">{{ imp.file_name }}</span>
                </td>
                <td class="px-5 py-3">
                  <span
                    class="px-2 py-0.5 text-[10px] font-black uppercase rounded-md border"
                    :class="[
                      imp.status === 'COMPLETED' ? 'bg-emerald-500/10 text-emerald-400 border-emerald-500/30' :
                      imp.status === 'PARTIAL' ? 'bg-amber-500/10 text-amber-400 border-amber-500/30' :
                      imp.status === 'FAILED' ? 'bg-rose-500/10 text-rose-400 border-rose-500/30' :
                      imp.status === 'ROLLED_BACK' ? 'bg-purple-500/10 text-purple-400 border-purple-500/30' :
                      'bg-slate-800 text-slate-400 border-slate-700'
                    ]"
                  >
                    {{ imp.status }}
                  </span>
                </td>
                <td class="px-5 py-3 font-mono uppercase text-[10px] text-slate-400">{{ imp.import_mode }}</td>
                <td class="px-5 py-3 font-bold text-emerald-400">{{ (imp.records_inserted || 0).toLocaleString() }}</td>
                <td class="px-5 py-3 font-bold text-sky-400">{{ (imp.records_updated || 0).toLocaleString() }}</td>
                <td class="px-5 py-3 font-bold" :class="imp.records_failed > 0 ? 'text-rose-400' : 'text-slate-500'">
                  {{ imp.records_failed || 0 }}
                </td>
                <td class="px-5 py-3 font-mono text-[11px] text-slate-400 truncate max-w-xs">
                  {{ imp.backup?.backup_name || 'None' }}
                </td>
                <td class="px-5 py-3 text-slate-500 whitespace-nowrap">{{ imp.created_at ? new Date(imp.created_at).toLocaleString() : '—' }}</td>
                <td class="px-5 py-3 text-right whitespace-nowrap">
                  <div class="flex items-center justify-end gap-1.5">
                    <Link
                      :href="`/admin/database/import/${imp.id}`"
                      class="p-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-sky-400"
                      title="View Details"
                    >
                      <EyeIcon class="w-3.5 h-3.5" />
                    </Link>

                    <a
                      v-if="imp.records_failed > 0"
                      :href="`/admin/database/import/${imp.id}/errors/export`"
                      class="p-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-emerald-400"
                      title="Download Errors CSV"
                    >
                      <DocumentArrowDownIcon class="w-3.5 h-3.5" />
                    </a>

                    <button
                      v-if="imp.backup && imp.status !== 'ROLLED_BACK'"
                      @click="rollback(imp)"
                      class="p-1.5 rounded-lg bg-slate-800 hover:bg-purple-900/40 text-purple-400"
                      title="Rollback from Backup"
                    >
                      <ArrowUturnLeftIcon class="w-3.5 h-3.5" />
                    </button>
                  </div>
                </td>
              </tr>
              <tr v-if="imports.data.length === 0">
                <td colspan="10" class="px-5 py-8 text-center text-slate-500 font-bold">
                  No database import history found.
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <div v-if="imports.links && imports.links.length > 3" class="px-5 py-3.5 bg-slate-950/60 border-t border-slate-800 flex items-center justify-between text-xs">
          <div class="text-slate-400">
            Showing <span class="font-bold text-white">{{ imports.from || 0 }}</span> to <span class="font-bold text-white">{{ imports.to || 0 }}</span> of <span class="font-bold text-white">{{ imports.total || 0 }}</span>
          </div>
          <div class="flex items-center gap-1">
            <Link
              v-for="(link, lIdx) in imports.links"
              :key="'imp_pg_' + lIdx"
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
  </div>
</template>
