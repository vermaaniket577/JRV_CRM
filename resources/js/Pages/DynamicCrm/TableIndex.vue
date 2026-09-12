<script setup>
import { ref, computed, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import Navbar from '@/Components/Navbar.vue';
import {
  MagnifyingGlassIcon, PlusIcon, TrashIcon, PencilSquareIcon,
  EyeIcon, FunnelIcon, ArrowsUpDownIcon, ChevronLeftIcon,
  ChevronRightIcon, TableCellsIcon, AdjustmentsHorizontalIcon,
  ArrowUpTrayIcon, CogIcon, XMarkIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
  tenant: Object,
  tableMeta: Object,
  headers: Array,
  records: Object,
  resolvedRecords: Array,
  allTables: Array,
  filterDefinitions: Array,
  filters: Object,
});

const searchQuery = ref(props.filters?.search || '');
const sortColumn = ref(props.filters?.sort || '');
const sortDirection = ref(props.filters?.direction || 'desc');
const showDeleteConfirm = ref(null);
const isDeleting = ref(false);

const applySearch = () => {
  router.get(`/dynamic-crm/${props.tableMeta.table_name}`, {
    search: searchQuery.value || undefined,
    sort: sortColumn.value || undefined,
    direction: sortDirection.value || undefined,
  }, { preserveState: true, preserveScroll: true });
};

const toggleSort = (col) => {
  if (sortColumn.value === col) {
    sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc';
  } else {
    sortColumn.value = col;
    sortDirection.value = 'asc';
  }
  applySearch();
};

const getCellValue = (record, header) => {
  // Check for FK resolved display value
  const fkKey = `_fk_${header.key}_display`;
  if (header.is_foreign_key && record[fkKey]) {
    return record[fkKey];
  }
  const val = record[header.key];
  if (val === null || val === undefined) return '—';
  if (typeof val === 'boolean') return val ? '✓' : '✗';
  return val;
};

const formatCellValue = (val, type) => {
  if (val === null || val === undefined || val === '—') return '—';
  if (type === 'decimal' || type === 'float' || type === 'double') {
    return Number(val).toLocaleString(undefined, { minimumFractionDigits: 2 });
  }
  if (type === 'date') return new Date(val).toLocaleDateString();
  if (type === 'datetime' || type === 'timestamp') return new Date(val).toLocaleString();
  return val;
};

const deleteRecord = async (id) => {
  isDeleting.value = true;
  try {
    await fetch(`/dynamic-crm/${props.tableMeta.table_name}/${id}`, {
      method: 'DELETE',
      headers: {
        'Accept': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
      },
    });
    showDeleteConfirm.value = null;
    router.reload();
  } catch (e) {}
  isDeleting.value = false;
};

const visibleHeaders = computed(() => {
  return (props.headers || []).filter(h => !h.is_primary || h.key === 'id').slice(0, 10);
});
</script>

<template>
  <Head :title="tableMeta?.display_name || 'Table'" />
  <div class="min-h-screen bg-[#0f0f23] flex font-sans text-slate-100">
    <Navbar />

    <div class="flex-1 min-w-0 table-page">
      <!-- Sidebar -->
    <aside class="table-sidebar">
      <div class="sidebar-header">
        <TableCellsIcon class="w-5 h-5 text-indigo-400" />
        <span>CRM Tables</span>
      </div>
      <Link href="/dynamic-crm/dashboard" class="sidebar-item">📊 Dashboard</Link>
      <Link
        v-for="t in allTables"
        :key="t.table_name"
        :href="`/dynamic-crm/${t.table_name}`"
        class="sidebar-item"
        :class="{ 'sidebar-active': t.table_name === tableMeta?.table_name }"
      >
        <span class="sidebar-label">{{ t.display_name }}</span>
        <span class="sidebar-count">{{ t.record_count }}</span>
      </Link>
      <Link href="/dynamic-crm/database/upload" class="sidebar-item sidebar-upload">
        <ArrowUpTrayIcon class="w-4 h-4" /> Upload DB
      </Link>
    </aside>

    <!-- Main Content -->
    <main class="table-main">
      <!-- Header -->
      <div class="table-header">
        <div>
          <h1>{{ tableMeta?.display_name }}</h1>
          <p>{{ records?.total || 0 }} records</p>
        </div>
        <div class="header-actions">
          <Link :href="`/dynamic-crm/configure/${tableMeta?.table_name}`" class="btn-config">
            <CogIcon class="w-4 h-4" /> Configure
          </Link>
          <Link :href="`/dynamic-crm/${tableMeta?.table_name}/create`" class="btn-create">
            <PlusIcon class="w-4 h-4" /> Add Record
          </Link>
        </div>
      </div>

      <!-- Search Bar -->
      <div class="search-bar">
        <MagnifyingGlassIcon class="w-5 h-5 text-indigo-400" />
        <input
          v-model="searchQuery"
          @keyup.enter="applySearch"
          placeholder="Search records..."
          class="search-input"
        />
        <button @click="applySearch" class="btn-search">Search</button>
      </div>

      <!-- Data Table -->
      <div class="data-table-wrap">
        <table class="data-table" v-if="resolvedRecords && resolvedRecords.length > 0">
          <thead>
            <tr>
              <th v-for="header in visibleHeaders" :key="header.key" @click="toggleSort(header.key)" class="sortable-th">
                {{ header.label }}
                <ArrowsUpDownIcon v-if="sortColumn !== header.key" class="w-3 h-3 sort-icon" />
                <span v-else class="sort-active">{{ sortDirection === 'asc' ? '↑' : '↓' }}</span>
              </th>
              <th class="actions-th">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="record in resolvedRecords" :key="record[tableMeta?.primary_key || 'id']">
              <td v-for="header in visibleHeaders" :key="header.key" class="cell" :class="header.is_foreign_key ? 'fk-cell' : ''">
                {{ formatCellValue(getCellValue(record, header), header.type) }}
              </td>
              <td class="actions-cell">
                <Link :href="`/dynamic-crm/${tableMeta.table_name}/${record[tableMeta?.primary_key || 'id']}`" class="action-btn view-btn" title="View">
                  <EyeIcon class="w-4 h-4" />
                </Link>
                <Link :href="`/dynamic-crm/${tableMeta.table_name}/${record[tableMeta?.primary_key || 'id']}/edit`" class="action-btn edit-btn" title="Edit">
                  <PencilSquareIcon class="w-4 h-4" />
                </Link>
                <button @click="showDeleteConfirm = record[tableMeta?.primary_key || 'id']" class="action-btn delete-btn" title="Delete">
                  <TrashIcon class="w-4 h-4" />
                </button>
              </td>
            </tr>
          </tbody>
        </table>

        <div v-else class="empty-table">
          <TableCellsIcon class="w-12 h-12 text-gray-600" />
          <p>No records found</p>
          <Link :href="`/dynamic-crm/${tableMeta?.table_name}/create`" class="btn-create-sm">
            <PlusIcon class="w-4 h-4" /> Add First Record
          </Link>
        </div>
      </div>

      <!-- Pagination -->
      <div v-if="records?.last_page > 1" class="pagination">
        <Link
          v-if="records.current_page > 1"
          :href="records.prev_page_url"
          class="page-btn"
        ><ChevronLeftIcon class="w-4 h-4" /></Link>
        <span class="page-info">Page {{ records.current_page }} of {{ records.last_page }}</span>
        <Link
          v-if="records.current_page < records.last_page"
          :href="records.next_page_url"
          class="page-btn"
        ><ChevronRightIcon class="w-4 h-4" /></Link>
      </div>
    </main>

    <!-- Delete Confirmation Modal -->
    <Teleport to="body">
      <div v-if="showDeleteConfirm" class="modal-overlay" @click.self="showDeleteConfirm = null">
        <div class="modal-box">
          <h3>Delete Record #{{ showDeleteConfirm }}?</h3>
          <p>This action cannot be undone.</p>
          <div class="modal-actions">
            <button @click="showDeleteConfirm = null" class="btn-cancel">Cancel</button>
            <button @click="deleteRecord(showDeleteConfirm)" :disabled="isDeleting" class="btn-delete">
              {{ isDeleting ? 'Deleting...' : 'Delete' }}
            </button>
          </div>
        </div>
      </div>
    </Teleport>
    </div>
  </div>
</template>

<style scoped>
.table-page {
  min-height: 100vh;
  background: linear-gradient(135deg, #0f0f23 0%, #1a1a3e 50%, #0f0f23 100%);
  display: flex;
  color: #e2e8f0;
}

/* Sidebar */
.table-sidebar {
  width: 240px; min-height: 100vh; padding: 20px 12px;
  background: rgba(15, 15, 35, 0.95); border-right: 1px solid rgba(99, 102, 241, 0.1);
  display: flex; flex-direction: column; gap: 4px; position: sticky; top: 0;
  overflow-y: auto;
}
.sidebar-header {
  display: flex; align-items: center; gap: 8px; padding: 8px 12px;
  font-size: 0.8rem; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em;
  margin-bottom: 8px;
}
.sidebar-item {
  display: flex; align-items: center; justify-content: space-between; gap: 8px;
  padding: 10px 14px; border-radius: 10px; text-decoration: none;
  color: #94a3b8; font-size: 0.88rem; transition: all 0.2s;
}
.sidebar-item:hover { background: rgba(99, 102, 241, 0.1); color: #c4b5fd; }
.sidebar-active { background: rgba(99, 102, 241, 0.15) !important; color: #c4b5fd !important; font-weight: 600; border-left: 3px solid #6366f1; }
.sidebar-count { font-size: 0.75rem; background: rgba(99, 102, 241, 0.15); padding: 2px 8px; border-radius: 10px; color: #8b5cf6; }
.sidebar-upload { margin-top: auto; color: #6366f1; gap: 6px; }

/* Main */
.table-main { flex: 1; padding: 24px 32px; min-width: 0; }
.table-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
.table-header h1 { font-size: 1.6rem; font-weight: 800; color: #fff; margin: 0; }
.table-header p { color: #64748b; margin: 4px 0 0; font-size: 0.9rem; }
.header-actions { display: flex; gap: 10px; }
.btn-config {
  display: inline-flex; align-items: center; gap: 6px; padding: 10px 18px;
  background: rgba(99, 102, 241, 0.1); border: 1px solid rgba(99, 102, 241, 0.2);
  border-radius: 10px; color: #c4b5fd; text-decoration: none; font-size: 0.88rem; transition: all 0.2s;
}
.btn-config:hover { background: rgba(99, 102, 241, 0.2); }
.btn-create {
  display: inline-flex; align-items: center; gap: 6px; padding: 10px 18px;
  background: linear-gradient(135deg, #6366f1, #8b5cf6); color: white;
  border-radius: 10px; text-decoration: none; font-size: 0.88rem; font-weight: 600; transition: all 0.2s;
}
.btn-create:hover { transform: translateY(-1px); }

.search-bar {
  display: flex; align-items: center; gap: 10px; padding: 12px 18px;
  background: rgba(30, 30, 60, 0.6); border: 1px solid rgba(99, 102, 241, 0.1);
  border-radius: 14px; margin-bottom: 20px;
}
.search-input { flex: 1; background: transparent; border: none; outline: none; color: #e2e8f0; font-size: 0.9rem; }
.search-input::placeholder { color: #475569; }
.btn-search { padding: 6px 16px; background: rgba(99, 102, 241, 0.15); border: 1px solid rgba(99, 102, 241, 0.2); border-radius: 8px; color: #c4b5fd; font-size: 0.85rem; cursor: pointer; }

.data-table-wrap { overflow-x: auto; margin-bottom: 20px; border-radius: 14px; border: 1px solid rgba(99, 102, 241, 0.1); }
.data-table { width: 100%; border-collapse: collapse; }
.data-table thead { background: rgba(30, 30, 60, 0.8); }
.data-table th {
  padding: 14px 16px; text-align: left; font-size: 0.8rem;
  color: #8b5cf6; text-transform: uppercase; letter-spacing: 0.05em;
  font-weight: 600; white-space: nowrap; border-bottom: 1px solid rgba(99, 102, 241, 0.1);
}
.sortable-th { cursor: pointer; user-select: none; }
.sortable-th:hover { color: #c4b5fd; }
.sort-icon { display: inline-block; vertical-align: middle; margin-left: 4px; color: #475569; }
.sort-active { margin-left: 4px; color: #c4b5fd; }
.actions-th { width: 120px; text-align: center; }

.data-table tbody tr { transition: background 0.2s; }
.data-table tbody tr:hover { background: rgba(99, 102, 241, 0.05); }
.data-table td {
  padding: 12px 16px; border-bottom: 1px solid rgba(99, 102, 241, 0.05);
  font-size: 0.9rem; color: #e2e8f0; max-width: 250px; overflow: hidden;
  text-overflow: ellipsis; white-space: nowrap;
}
.fk-cell { color: #8b5cf6; font-weight: 500; }

.actions-cell { display: flex; gap: 6px; justify-content: center; }
.action-btn {
  width: 32px; height: 32px; border: none; border-radius: 8px; cursor: pointer;
  display: flex; align-items: center; justify-content: center; transition: all 0.2s;
}
.view-btn { background: rgba(99, 102, 241, 0.15); color: #8b5cf6; }
.view-btn:hover { background: rgba(99, 102, 241, 0.3); }
.edit-btn { background: rgba(16, 185, 129, 0.15); color: #34d399; text-decoration: none; }
.edit-btn:hover { background: rgba(16, 185, 129, 0.3); }
.delete-btn { background: rgba(239, 68, 68, 0.15); color: #f87171; }
.delete-btn:hover { background: rgba(239, 68, 68, 0.3); }

.empty-table { text-align: center; padding: 60px 24px; }
.empty-table p { color: #64748b; margin: 12px 0 20px; }
.btn-create-sm {
  display: inline-flex; align-items: center; gap: 6px; padding: 10px 20px;
  background: linear-gradient(135deg, #6366f1, #8b5cf6); color: white;
  border-radius: 10px; text-decoration: none; font-weight: 600; font-size: 0.9rem;
}

.pagination {
  display: flex; align-items: center; justify-content: center; gap: 16px;
}
.page-btn {
  width: 36px; height: 36px; border-radius: 10px;
  background: rgba(99, 102, 241, 0.15); border: 1px solid rgba(99, 102, 241, 0.2);
  display: flex; align-items: center; justify-content: center;
  color: #c4b5fd; text-decoration: none;
}
.page-info { font-size: 0.85rem; color: #64748b; }

/* Modal */
.modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: 100; display: flex; align-items: center; justify-content: center; }
.modal-box { background: #1e1e3e; border: 1px solid rgba(239, 68, 68, 0.2); border-radius: 16px; padding: 32px; text-align: center; max-width: 400px; }
.modal-box h3 { color: #fff; margin: 0 0 8px; }
.modal-box p { color: #94a3b8; margin: 0 0 24px; font-size: 0.9rem; }
.modal-actions { display: flex; gap: 12px; justify-content: center; }
.btn-cancel { padding: 10px 24px; background: rgba(100, 116, 139, 0.2); border: none; border-radius: 10px; color: #94a3b8; cursor: pointer; }
.btn-delete { padding: 10px 24px; background: #ef4444; border: none; border-radius: 10px; color: white; font-weight: 600; cursor: pointer; }
.btn-delete:disabled { opacity: 0.6; }

@media (max-width: 768px) {
  .table-sidebar { display: none; }
  .table-main { padding: 16px; }
  .table-header { flex-direction: column; gap: 12px; align-items: flex-start; }
}
</style>
