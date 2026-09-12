<script setup>
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import Navbar from '@/Components/Navbar.vue';
import {
  PencilSquareIcon, TrashIcon, ArrowLeftIcon, LinkIcon,
  TableCellsIcon, EyeIcon, ArrowUpTrayIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
  tenant: Object,
  tableMeta: Object,
  record: Object,
  headers: Array,
  relationships: Object,
  relatedRecords: Array,
  allTables: Array,
});

const showDeleteConfirm = ref(false);
const isDeleting = ref(false);

const pk = props.tableMeta?.primary_key || 'id';
const recordId = props.record?.[pk];
const displayValue = props.record?.[props.tableMeta?.display_column] || `Record #${recordId}`;

const isInternalKey = (key) => {
  return key.startsWith('_') || ['_related'].includes(key);
};

const formatLabel = (key) => {
  if (key.startsWith('_fk_') && key.endsWith('_display')) {
    const base = key.replace('_fk_', '').replace('_display', '');
    return base.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase()) + ' (Resolved)';
  }
  return key.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase());
};

const formatValue = (val) => {
  if (val === null || val === undefined) return '—';
  if (typeof val === 'boolean') return val ? '✓ Yes' : '✗ No';
  if (typeof val === 'object') return JSON.stringify(val);
  return val;
};

const deleteRecord = async () => {
  isDeleting.value = true;
  try {
    await fetch(`/dynamic-crm/${props.tableMeta.table_name}/${recordId}`, {
      method: 'DELETE',
      headers: {
        'Accept': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
      },
    });
    router.visit(`/dynamic-crm/${props.tableMeta.table_name}`);
  } catch (e) {}
  isDeleting.value = false;
};

const getFieldEntries = () => {
  if (!props.record) return [];
  return Object.entries(props.record).filter(([key]) => {
    return !isInternalKey(key) && key !== '_related';
  });
};
</script>

<template>
  <Head :title="`${displayValue} — ${tableMeta?.display_name}`" />
  <div class="min-h-screen bg-[#0f0f23] flex font-sans text-slate-100">
    <Navbar />

    <div class="flex-1 min-w-0 show-page">
      <!-- Sidebar -->
    <aside class="show-sidebar">
      <div class="sidebar-header"><TableCellsIcon class="w-5 h-5 text-indigo-400" /><span>CRM Tables</span></div>
      <Link href="/dynamic-crm/dashboard" class="sidebar-item">📊 Dashboard</Link>
      <Link
        v-for="t in allTables" :key="t.table_name"
        :href="`/dynamic-crm/${t.table_name}`"
        class="sidebar-item" :class="{ 'sidebar-active': t.table_name === tableMeta?.table_name }"
      >
        <span>{{ t.display_name }}</span>
        <span class="sidebar-count">{{ t.record_count }}</span>
      </Link>
    </aside>

    <!-- Main -->
    <main class="show-main">
      <!-- Breadcrumb -->
      <div class="breadcrumb">
        <Link :href="`/dynamic-crm/${tableMeta?.table_name}`" class="bread-link">
          <ArrowLeftIcon class="w-4 h-4" /> {{ tableMeta?.display_name }}
        </Link>
        <span class="bread-sep">›</span>
        <span class="bread-current">{{ displayValue }}</span>
      </div>

      <!-- Record Header -->
      <div class="record-header">
        <div>
          <h1>{{ displayValue }}</h1>
          <p>{{ tableMeta?.display_name }} #{{ recordId }}</p>
        </div>
        <div class="header-actions">
          <Link :href="`/dynamic-crm/${tableMeta?.table_name}/${recordId}/edit`" class="btn-edit">
            <PencilSquareIcon class="w-4 h-4" /> Edit
          </Link>
          <button @click="showDeleteConfirm = true" class="btn-delete-trigger">
            <TrashIcon class="w-4 h-4" /> Delete
          </button>
        </div>
      </div>

      <!-- Record Fields -->
      <div class="fields-card">
        <div class="fields-grid">
          <div v-for="[key, val] in getFieldEntries()" :key="key" class="field-item" :class="{ 'fk-resolved': key.startsWith('_fk_') }">
            <div class="field-label">{{ formatLabel(key) }}</div>
            <div class="field-value" :class="{ 'field-fk': key.startsWith('_fk_') }">
              {{ formatValue(val) }}
            </div>
          </div>
        </div>
      </div>

      <!-- Related Records -->
      <div v-if="relatedRecords && relatedRecords.length > 0" class="related-section">
        <h2><LinkIcon class="w-5 h-5 inline text-indigo-400" /> Related Records</h2>
        <div v-for="rel in relatedRecords" :key="rel.table_name" class="related-block">
          <div class="related-header">
            <h3>{{ rel.display_name }}</h3>
            <span class="related-count">{{ rel.count }} records</span>
          </div>
          <div class="related-table-wrap" v-if="rel.records.length > 0">
            <table class="related-table">
              <thead>
                <tr>
                  <th v-for="key in Object.keys(rel.records[0]).slice(0, 6)" :key="key">
                    {{ key.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase()) }}
                  </th>
                  <th>Action</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="(row, idx) in rel.records.slice(0, 10)" :key="idx">
                  <td v-for="key in Object.keys(rel.records[0]).slice(0, 6)" :key="key">
                    {{ row[key] ?? '—' }}
                  </td>
                  <td>
                    <Link v-if="row.id" :href="`/dynamic-crm/${rel.table_name}/${row.id}`" class="action-link">
                      <EyeIcon class="w-4 h-4" /> View
                    </Link>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          <p v-else class="no-related">No related records found.</p>
        </div>
      </div>
    </main>

    <!-- Delete Modal -->
    <Teleport to="body">
      <div v-if="showDeleteConfirm" class="modal-overlay" @click.self="showDeleteConfirm = false">
        <div class="modal-box">
          <h3>Delete {{ displayValue }}?</h3>
          <p>This action cannot be undone.</p>
          <div class="modal-actions">
            <button @click="showDeleteConfirm = false" class="btn-cancel">Cancel</button>
            <button @click="deleteRecord" :disabled="isDeleting" class="btn-delete-confirm">
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
.show-page { min-height: 100vh; background: linear-gradient(135deg, #0f0f23 0%, #1a1a3e 50%, #0f0f23 100%); display: flex; color: #e2e8f0; }
.show-sidebar { width: 240px; min-height: 100vh; padding: 20px 12px; background: rgba(15, 15, 35, 0.95); border-right: 1px solid rgba(99, 102, 241, 0.1); display: flex; flex-direction: column; gap: 4px; position: sticky; top: 0; overflow-y: auto; }
.sidebar-header { display: flex; align-items: center; gap: 8px; padding: 8px 12px; font-size: 0.8rem; color: #64748b; text-transform: uppercase; margin-bottom: 8px; }
.sidebar-item { display: flex; align-items: center; justify-content: space-between; padding: 10px 14px; border-radius: 10px; text-decoration: none; color: #94a3b8; font-size: 0.88rem; transition: all 0.2s; }
.sidebar-item:hover { background: rgba(99, 102, 241, 0.1); color: #c4b5fd; }
.sidebar-active { background: rgba(99, 102, 241, 0.15) !important; color: #c4b5fd !important; font-weight: 600; }
.sidebar-count { font-size: 0.75rem; background: rgba(99, 102, 241, 0.15); padding: 2px 8px; border-radius: 10px; color: #8b5cf6; }

.show-main { flex: 1; padding: 24px 32px; }
.breadcrumb { display: flex; align-items: center; gap: 8px; margin-bottom: 20px; font-size: 0.9rem; }
.bread-link { display: flex; align-items: center; gap: 4px; color: #8b5cf6; text-decoration: none; }
.bread-sep { color: #475569; }
.bread-current { color: #94a3b8; }

.record-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 28px; }
.record-header h1 { font-size: 1.8rem; font-weight: 800; color: #fff; margin: 0; }
.record-header p { color: #64748b; margin: 4px 0 0; }
.header-actions { display: flex; gap: 10px; }
.btn-edit { display: inline-flex; align-items: center; gap: 6px; padding: 10px 18px; background: rgba(16, 185, 129, 0.15); border: 1px solid rgba(16, 185, 129, 0.2); border-radius: 10px; color: #34d399; text-decoration: none; font-size: 0.88rem; }
.btn-delete-trigger { display: inline-flex; align-items: center; gap: 6px; padding: 10px 18px; background: rgba(239, 68, 68, 0.15); border: 1px solid rgba(239, 68, 68, 0.2); border-radius: 10px; color: #f87171; font-size: 0.88rem; cursor: pointer; }

.fields-card { background: rgba(30, 30, 60, 0.6); border: 1px solid rgba(99, 102, 241, 0.1); border-radius: 16px; padding: 28px; margin-bottom: 32px; }
.fields-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px; }
.field-item { display: flex; flex-direction: column; gap: 4px; }
.field-label { font-size: 0.75rem; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em; }
.field-value { font-size: 0.95rem; color: #e2e8f0; word-break: break-word; }
.field-fk { color: #8b5cf6; font-weight: 600; }
.fk-resolved { border-left: 2px solid #6366f1; padding-left: 12px; }

.related-section { margin-bottom: 40px; }
.related-section h2 { font-size: 1.2rem; font-weight: 700; color: #fff; margin: 0 0 20px; }
.related-block { background: rgba(30, 30, 60, 0.5); border: 1px solid rgba(99, 102, 241, 0.1); border-radius: 14px; padding: 20px; margin-bottom: 16px; }
.related-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; }
.related-header h3 { font-size: 1rem; color: #c4b5fd; margin: 0; }
.related-count { font-size: 0.8rem; color: #64748b; }
.related-table-wrap { overflow-x: auto; }
.related-table { width: 100%; border-collapse: collapse; }
.related-table th { padding: 10px 12px; text-align: left; font-size: 0.75rem; color: #8b5cf6; text-transform: uppercase; border-bottom: 1px solid rgba(99, 102, 241, 0.1); }
.related-table td { padding: 10px 12px; font-size: 0.85rem; color: #e2e8f0; border-bottom: 1px solid rgba(99, 102, 241, 0.05); max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.action-link { display: inline-flex; align-items: center; gap: 4px; color: #8b5cf6; text-decoration: none; font-size: 0.85rem; }
.no-related { color: #475569; font-size: 0.9rem; margin: 0; }

.modal-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.6); z-index: 100; display: flex; align-items: center; justify-content: center; }
.modal-box { background: #1e1e3e; border: 1px solid rgba(239, 68, 68, 0.2); border-radius: 16px; padding: 32px; text-align: center; max-width: 400px; }
.modal-box h3 { color: #fff; margin: 0 0 8px; }
.modal-box p { color: #94a3b8; margin: 0 0 24px; font-size: 0.9rem; }
.modal-actions { display: flex; gap: 12px; justify-content: center; }
.btn-cancel { padding: 10px 24px; background: rgba(100, 116, 139, 0.2); border: none; border-radius: 10px; color: #94a3b8; cursor: pointer; }
.btn-delete-confirm { padding: 10px 24px; background: #ef4444; border: none; border-radius: 10px; color: white; font-weight: 600; cursor: pointer; }

@media (max-width: 768px) { .show-sidebar { display: none; } .show-main { padding: 16px; } .record-header { flex-direction: column; gap: 12px; } .fields-grid { grid-template-columns: 1fr; } }
</style>
