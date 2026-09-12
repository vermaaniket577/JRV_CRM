<script setup>
import { ref, reactive } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import Navbar from '@/Components/Navbar.vue';
import {
  CogIcon, ArrowLeftIcon, CheckCircleIcon, TableCellsIcon,
  EyeIcon, EyeSlashIcon, MagnifyingGlassIcon, PencilSquareIcon,
  LockClosedIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
  tenant: Object,
  tableMeta: Object,
  columns: Array,
  allTables: Array,
});

const isSaving = ref(false);
const saveSuccess = ref(false);

const tableConfig = reactive({
  display_name: props.tableMeta?.display_name || '',
  icon: props.tableMeta?.icon || 'TableCellsIcon',
  is_visible_in_menu: props.tableMeta?.is_visible_in_menu ?? true,
  menu_order: props.tableMeta?.menu_order || 0,
  display_column: props.tableMeta?.display_column || '',
});

const columnsConfig = reactive(
  (props.columns || []).map(col => ({ ...col }))
);

const saveConfig = async () => {
  isSaving.value = true;
  saveSuccess.value = false;

  try {
    const res = await fetch(`/dynamic-crm/configure/${props.tableMeta.table_name}`, {
      method: 'PUT',
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
      },
      body: JSON.stringify({
        ...tableConfig,
        columns: columnsConfig.map(c => ({
          id: c.id,
          display_name: c.display_name,
          is_visible: c.is_visible,
          is_searchable: c.is_searchable,
          is_editable: c.is_editable,
          is_required: c.is_required,
          display_order: c.display_order,
        })),
      }),
    });

    if (res.ok) {
      saveSuccess.value = true;
      setTimeout(() => saveSuccess.value = false, 3000);
    }
  } catch (e) {}
  isSaving.value = false;
};

const iconOptions = [
  'TableCellsIcon', 'UserGroupIcon', 'ShoppingCartIcon', 'CubeIcon',
  'DocumentTextIcon', 'CreditCardIcon', 'HomeIcon', 'TagIcon',
  'AcademicCapIcon', 'HeartIcon', 'FolderIcon', 'ChartBarIcon',
];
</script>

<template>
  <Head :title="`Configure: ${tableMeta?.display_name}`" />
  <div class="min-h-screen bg-[#0f0f23] flex font-sans text-slate-100">
    <Navbar />

    <div class="flex-1 min-w-0 config-page">
      <!-- Sidebar -->
    <aside class="config-sidebar">
      <div class="sidebar-header"><TableCellsIcon class="w-5 h-5 text-indigo-400" /><span>CRM Tables</span></div>
      <Link href="/dynamic-crm/dashboard" class="sidebar-item">📊 Dashboard</Link>
      <Link v-for="t in allTables" :key="t.table_name" :href="`/dynamic-crm/${t.table_name}`" class="sidebar-item" :class="{ 'sidebar-active': t.table_name === tableMeta?.table_name }">
        <span>{{ t.display_name }}</span>
        <span class="sidebar-count">{{ t.record_count }}</span>
      </Link>
    </aside>

    <!-- Main -->
    <main class="config-main">
      <div class="breadcrumb">
        <Link :href="`/dynamic-crm/${tableMeta?.table_name}`" class="bread-link">
          <ArrowLeftIcon class="w-4 h-4" /> {{ tableMeta?.display_name }}
        </Link>
        <span class="bread-sep">›</span>
        <span class="bread-current">Configure</span>
      </div>

      <h1><CogIcon class="w-7 h-7 inline text-indigo-400" /> Table Configuration</h1>

      <!-- Success Banner -->
      <div v-if="saveSuccess" class="success-banner">
        <CheckCircleIcon class="w-5 h-5" /> Configuration saved successfully!
      </div>

      <!-- Table Settings -->
      <div class="config-section">
        <h2>Table Settings</h2>
        <div class="config-grid">
          <div class="config-field">
            <label>Display Name</label>
            <input v-model="tableConfig.display_name" type="text" class="config-input" />
          </div>
          <div class="config-field">
            <label>Icon</label>
            <select v-model="tableConfig.icon" class="config-input">
              <option v-for="icon in iconOptions" :key="icon" :value="icon">{{ icon }}</option>
            </select>
          </div>
          <div class="config-field">
            <label>Menu Order</label>
            <input v-model.number="tableConfig.menu_order" type="number" class="config-input" />
          </div>
          <div class="config-field">
            <label>Display Column (for FK labels)</label>
            <select v-model="tableConfig.display_column" class="config-input">
              <option value="">Auto-detect</option>
              <option v-for="col in columns" :key="col.column_name" :value="col.column_name">{{ col.column_name }}</option>
            </select>
          </div>
          <div class="config-field checkbox-field">
            <input v-model="tableConfig.is_visible_in_menu" type="checkbox" class="config-checkbox" />
            <label>Show in sidebar menu</label>
          </div>
        </div>
      </div>

      <!-- Column Settings -->
      <div class="config-section">
        <h2>Column Settings</h2>
        <div class="columns-table-wrap">
          <table class="columns-table">
            <thead>
              <tr>
                <th>Column</th>
                <th>Display Name</th>
                <th>Type</th>
                <th class="center-th"><EyeIcon class="w-4 h-4 inline" /> Visible</th>
                <th class="center-th"><MagnifyingGlassIcon class="w-4 h-4 inline" /> Searchable</th>
                <th class="center-th"><PencilSquareIcon class="w-4 h-4 inline" /> Editable</th>
                <th class="center-th"><LockClosedIcon class="w-4 h-4 inline" /> Required</th>
                <th>Order</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="col in columnsConfig" :key="col.id" :class="{ 'pk-row': col.is_primary }">
                <td class="col-name">
                  {{ col.column_name }}
                  <span v-if="col.is_primary" class="badge-pk">PK</span>
                  <span v-if="col.is_foreign_key" class="badge-fk">FK</span>
                </td>
                <td><input v-model="col.display_name" type="text" class="inline-input" /></td>
                <td class="col-type">{{ col.data_type }}</td>
                <td class="center-td"><input v-model="col.is_visible" type="checkbox" /></td>
                <td class="center-td"><input v-model="col.is_searchable" type="checkbox" /></td>
                <td class="center-td"><input v-model="col.is_editable" type="checkbox" :disabled="col.is_primary" /></td>
                <td class="center-td"><input v-model="col.is_required" type="checkbox" /></td>
                <td><input v-model.number="col.display_order" type="number" class="order-input" /></td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Save -->
      <div class="save-actions">
        <button @click="saveConfig" :disabled="isSaving" class="btn-save">
          <CheckCircleIcon class="w-5 h-5" />
          {{ isSaving ? 'Saving...' : 'Save Configuration' }}
        </button>
      </div>
    </main>
    </div>
  </div>
</template>

<style scoped>
.config-page { min-height: 100vh; background: linear-gradient(135deg, #0f0f23 0%, #1a1a3e 50%, #0f0f23 100%); display: flex; color: #e2e8f0; }
.config-sidebar { width: 240px; min-height: 100vh; padding: 20px 12px; background: rgba(15, 15, 35, 0.95); border-right: 1px solid rgba(99, 102, 241, 0.1); display: flex; flex-direction: column; gap: 4px; position: sticky; top: 0; }
.sidebar-header { display: flex; align-items: center; gap: 8px; padding: 8px 12px; font-size: 0.8rem; color: #64748b; text-transform: uppercase; margin-bottom: 8px; }
.sidebar-item { display: flex; align-items: center; justify-content: space-between; padding: 10px 14px; border-radius: 10px; text-decoration: none; color: #94a3b8; font-size: 0.88rem; transition: all 0.2s; }
.sidebar-item:hover { background: rgba(99, 102, 241, 0.1); }
.sidebar-active { background: rgba(99, 102, 241, 0.15) !important; color: #c4b5fd !important; font-weight: 600; }
.sidebar-count { font-size: 0.75rem; background: rgba(99, 102, 241, 0.15); padding: 2px 8px; border-radius: 10px; color: #8b5cf6; }

.config-main { flex: 1; padding: 24px 32px; }
.breadcrumb { display: flex; align-items: center; gap: 8px; margin-bottom: 20px; font-size: 0.9rem; }
.bread-link { display: flex; align-items: center; gap: 4px; color: #8b5cf6; text-decoration: none; }
.bread-sep { color: #475569; }
.bread-current { color: #94a3b8; }
h1 { font-size: 1.5rem; font-weight: 800; color: #fff; margin: 0 0 24px; }

.success-banner { display: flex; align-items: center; gap: 8px; padding: 14px 20px; background: rgba(16, 185, 129, 0.1); border: 1px solid rgba(16, 185, 129, 0.2); border-radius: 12px; color: #34d399; margin-bottom: 24px; animation: fadeIn 0.3s; }
@keyframes fadeIn { from { opacity: 0; transform: translateY(-8px); } to { opacity: 1; transform: none; } }

.config-section { background: rgba(30, 30, 60, 0.6); border: 1px solid rgba(99, 102, 241, 0.1); border-radius: 16px; padding: 24px; margin-bottom: 24px; }
.config-section h2 { font-size: 1.1rem; font-weight: 700; color: #c4b5fd; margin: 0 0 16px; }

.config-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); gap: 16px; }
.config-field { display: flex; flex-direction: column; gap: 6px; }
.config-field label { font-size: 0.8rem; color: #64748b; }
.config-input { padding: 10px 14px; background: rgba(15, 15, 40, 0.6); border: 1px solid rgba(99, 102, 241, 0.15); border-radius: 10px; color: #e2e8f0; font-size: 0.9rem; outline: none; }
.config-input:focus { border-color: #6366f1; }
.config-input option { background: #1e1e3e; color: #e2e8f0; }
.checkbox-field { flex-direction: row; align-items: center; gap: 10px; }
.config-checkbox { width: 18px; height: 18px; accent-color: #6366f1; }

.columns-table-wrap { overflow-x: auto; }
.columns-table { width: 100%; border-collapse: collapse; }
.columns-table th { padding: 10px 12px; text-align: left; font-size: 0.75rem; color: #8b5cf6; text-transform: uppercase; border-bottom: 1px solid rgba(99, 102, 241, 0.1); }
.center-th { text-align: center; }
.columns-table td { padding: 10px 12px; border-bottom: 1px solid rgba(99, 102, 241, 0.05); font-size: 0.85rem; }
.center-td { text-align: center; }
.center-td input { accent-color: #6366f1; width: 18px; height: 18px; }
.col-name { font-weight: 600; color: #e2e8f0; white-space: nowrap; }
.col-type { color: #64748b; font-family: monospace; font-size: 0.8rem; }
.pk-row { background: rgba(99, 102, 241, 0.05); }
.badge-pk { background: rgba(99, 102, 241, 0.2); color: #8b5cf6; padding: 1px 6px; border-radius: 4px; font-size: 0.65rem; margin-left: 6px; }
.badge-fk { background: rgba(16, 185, 129, 0.2); color: #34d399; padding: 1px 6px; border-radius: 4px; font-size: 0.65rem; margin-left: 4px; }
.inline-input { background: transparent; border: 1px solid rgba(99, 102, 241, 0.1); border-radius: 6px; padding: 6px 10px; color: #e2e8f0; font-size: 0.85rem; width: 100%; outline: none; }
.inline-input:focus { border-color: #6366f1; }
.order-input { width: 60px; background: transparent; border: 1px solid rgba(99, 102, 241, 0.1); border-radius: 6px; padding: 6px 8px; color: #e2e8f0; font-size: 0.85rem; text-align: center; outline: none; }

.save-actions { display: flex; justify-content: flex-end; }
.btn-save { display: inline-flex; align-items: center; gap: 8px; padding: 14px 32px; background: linear-gradient(135deg, #6366f1, #8b5cf6); color: white; border: none; border-radius: 14px; font-size: 1rem; font-weight: 700; cursor: pointer; transition: all 0.2s; }
.btn-save:hover { transform: translateY(-1px); }
.btn-save:disabled { opacity: 0.6; }

@media (max-width: 768px) { .config-sidebar { display: none; } .config-main { padding: 16px; } .config-grid { grid-template-columns: 1fr; } }
</style>
