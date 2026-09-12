<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import Navbar from '@/Components/Navbar.vue';
import {
  CircleStackIcon, TableCellsIcon, ArrowUpRightIcon,
  ClockIcon, MagnifyingGlassIcon, ArrowPathIcon,
  PlusIcon, ChartBarIcon, CubeIcon,
  UserGroupIcon, ShoppingCartIcon, DocumentTextIcon,
  CreditCardIcon, HomeIcon, TagIcon,
  AcademicCapIcon, HeartIcon, FolderIcon,
  ArrowUpTrayIcon, CogIcon, SparklesIcon,
  BoltIcon, LinkIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
  tenant: Object,
  tables: Array,
  dbInfo: Object,
  stats: Object,
  recentActivity: Array,
  relationships: Array,
});

const iconMap = {
  TableCellsIcon, UserGroupIcon, ShoppingCartIcon, CubeIcon,
  DocumentTextIcon, CreditCardIcon, HomeIcon, TagIcon,
  AcademicCapIcon, HeartIcon, FolderIcon, ChartBarIcon,
  CircleStackIcon, CogIcon,
};

const getIcon = (iconName) => iconMap[iconName] || TableCellsIcon;

const searchQuery = ref('');
const isSearching = ref(false);
const searchResults = ref([]);

const performSearch = async () => {
  if (searchQuery.value.length < 2) { searchResults.value = []; return; }
  isSearching.value = true;
  try {
    const res = await fetch(`/dynamic-crm/search?q=${encodeURIComponent(searchQuery.value)}`, {
      headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
    });
    const data = await res.json();
    searchResults.value = data.results || [];
  } catch (e) { searchResults.value = []; }
  isSearching.value = false;
};

const actionLabel = (action) => {
  const map = { created: '✚ Created', updated: '✎ Updated', deleted: '✕ Deleted', searched: '🔍 Searched', database_uploaded: '📤 Database Uploaded' };
  return map[action] || action;
};

const totalRecords = computed(() => props.stats?.total_records?.toLocaleString() || '0');
const totalTables = computed(() => props.stats?.total_tables || 0);

const gradientColors = [
  'from-violet-500 to-purple-600', 'from-blue-500 to-cyan-500', 'from-emerald-500 to-teal-500',
  'from-amber-500 to-orange-500', 'from-rose-500 to-pink-500', 'from-indigo-500 to-blue-500',
  'from-fuchsia-500 to-purple-500', 'from-lime-500 to-green-500',
];
</script>

<template>
  <Head title="Dynamic CRM Dashboard" />
  <div class="min-h-screen bg-[#0f0f23] flex font-sans text-slate-100">
    <Navbar />

    <main class="flex-1 min-w-0 overflow-y-auto">
      <div class="dynamic-crm-dashboard">
        <!-- Hero Header -->
    <div class="dashboard-hero">
      <div class="hero-content">
        <div class="hero-badge">
          <SparklesIcon class="w-4 h-4" />
          <span>Dynamic CRM Engine</span>
        </div>
        <h1 class="hero-title">CRM Dashboard</h1>
        <p class="hero-subtitle" v-if="dbInfo">
          Database <strong>{{ dbInfo.name }}</strong> — {{ dbInfo.tables_count }} tables, {{ dbInfo.total_records?.toLocaleString() }} records
        </p>
        <p class="hero-subtitle" v-else>Upload a SQL database to get started</p>
      </div>
      <div class="hero-actions">
        <Link href="/dynamic-crm/database/upload" class="btn-hero-action">
          <ArrowUpTrayIcon class="w-5 h-5" />
          Upload Database
        </Link>
      </div>
    </div>

    <!-- Global Search -->
    <div class="search-bar-container">
      <div class="search-bar">
        <MagnifyingGlassIcon class="search-icon" />
        <input
          v-model="searchQuery"
          @input="performSearch"
          type="text"
          placeholder="Search across all tables... (customers, orders, products...)"
          class="search-input"
        />
        <div v-if="isSearching" class="search-spinner">
          <ArrowPathIcon class="w-5 h-5 animate-spin" />
        </div>
      </div>
      <!-- Search Results Dropdown -->
      <div v-if="searchResults.length > 0" class="search-results-dropdown">
        <div v-for="group in searchResults" :key="group.table_name" class="search-group">
          <div class="search-group-header">
            <component :is="getIcon(group.icon)" class="w-4 h-4" />
            <span>{{ group.display_name }}</span>
            <span class="search-count">{{ group.total_count }} results</span>
          </div>
          <Link
            v-for="item in group.results"
            :key="item._primary_key"
            :href="`/dynamic-crm/${group.table_name}/${item._primary_key}`"
            class="search-result-item"
          >
            {{ item._display_value }}
          </Link>
        </div>
      </div>
    </div>

    <!-- Stats Cards -->
    <div class="stats-grid">
      <div class="stat-card stat-card-primary">
        <div class="stat-icon-wrap gradient-1"><CircleStackIcon class="w-7 h-7 text-white" /></div>
        <div class="stat-info">
          <div class="stat-value">{{ totalTables }}</div>
          <div class="stat-label">Active Tables</div>
        </div>
      </div>
      <div class="stat-card stat-card-primary">
        <div class="stat-icon-wrap gradient-2"><ChartBarIcon class="w-7 h-7 text-white" /></div>
        <div class="stat-info">
          <div class="stat-value">{{ totalRecords }}</div>
          <div class="stat-label">Total Records</div>
        </div>
      </div>
      <div class="stat-card stat-card-primary">
        <div class="stat-icon-wrap gradient-3"><LinkIcon class="w-7 h-7 text-white" /></div>
        <div class="stat-info">
          <div class="stat-value">{{ stats?.total_relationships || 0 }}</div>
          <div class="stat-label">Relationships</div>
        </div>
      </div>
      <div class="stat-card stat-card-primary" v-if="dbInfo">
        <div class="stat-icon-wrap gradient-4"><BoltIcon class="w-7 h-7 text-white" /></div>
        <div class="stat-info">
          <div class="stat-value status-active">Active</div>
          <div class="stat-label">{{ dbInfo.imported_at }}</div>
        </div>
      </div>
    </div>

    <!-- Tables Grid -->
    <div class="section-header">
      <h2>Your CRM Tables</h2>
      <Link href="/dynamic-crm/database/upload" class="btn-sm"><PlusIcon class="w-4 h-4" /> Upload New</Link>
    </div>

    <div class="tables-grid" v-if="tables && tables.length > 0">
      <Link
        v-for="(table, idx) in tables"
        :key="table.table_name"
        :href="`/dynamic-crm/${table.table_name}`"
        class="table-card"
        :class="`gradient-border-${(idx % 8) + 1}`"
      >
        <div class="table-card-icon" :class="`bg-gradient-to-br ${gradientColors[idx % gradientColors.length]}`">
          <component :is="getIcon(table.icon)" class="w-8 h-8 text-white" />
        </div>
        <div class="table-card-info">
          <h3>{{ table.display_name }}</h3>
          <p>{{ table.record_count?.toLocaleString() || 0 }} records</p>
        </div>
        <ArrowUpRightIcon class="table-card-arrow" />
      </Link>
    </div>

    <div v-else class="empty-state">
      <CircleStackIcon class="w-16 h-16 text-gray-400" />
      <h3>No Database Uploaded Yet</h3>
      <p>Upload a SQL file to automatically generate your CRM</p>
      <Link href="/dynamic-crm/database/upload" class="btn-primary">
        <ArrowUpTrayIcon class="w-5 h-5" /> Upload SQL Database
      </Link>
    </div>

    <!-- Recent Activity -->
    <div v-if="recentActivity && recentActivity.length > 0" class="activity-section">
      <div class="section-header">
        <h2>Recent Activity</h2>
      </div>
      <div class="activity-list">
        <div v-for="act in recentActivity" :key="act.id" class="activity-item">
          <div class="activity-dot" :class="act.action === 'deleted' ? 'dot-red' : act.action === 'created' ? 'dot-green' : 'dot-blue'"></div>
          <div class="activity-content">
            <span class="activity-action">{{ actionLabel(act.action) }}</span>
            <span v-if="act.table_name" class="activity-table">
              in <Link :href="`/dynamic-crm/${act.table_name}`" class="activity-link">{{ act.table_name }}</Link>
            </span>
            <span v-if="act.record_id" class="activity-record">#{{ act.record_id }}</span>
          </div>
          <span class="activity-time">{{ act.time }}</span>
        </div>
      </div>
    </div>

    <!-- Database Info -->
    <div v-if="dbInfo" class="db-info-card">
      <div class="section-header"><h2>Database Information</h2></div>
      <div class="db-info-grid">
        <div class="db-info-item"><span class="db-label">Name</span><span class="db-value">{{ dbInfo.name }}</span></div>
        <div class="db-info-item"><span class="db-label">MySQL Database</span><span class="db-value font-mono">{{ dbInfo.database_name }}</span></div>
        <div class="db-info-item"><span class="db-label">Original File</span><span class="db-value">{{ dbInfo.original_file }}</span></div>
        <div class="db-info-item"><span class="db-label">Status</span><span class="db-value text-emerald-400">{{ dbInfo.status }}</span></div>
        <div class="db-info-item"><span class="db-label">Tables</span><span class="db-value">{{ dbInfo.tables_count }}</span></div>
        <div class="db-info-item"><span class="db-label">Relationships</span><span class="db-value">{{ dbInfo.relationships_count }}</span></div>
        <div class="db-info-item"><span class="db-label">Records</span><span class="db-value">{{ dbInfo.total_records?.toLocaleString() }}</span></div>
        <div class="db-info-item"><span class="db-label">Imported</span><span class="db-value">{{ dbInfo.imported_at }}</span></div>
      </div>
    </div>
  </div>
</main>
</div>
</template>

<style scoped>
.dynamic-crm-dashboard {
  min-height: 100vh;
  background: linear-gradient(135deg, #0f0f23 0%, #1a1a3e 50%, #0f0f23 100%);
  padding: 32px 32px 40px;
  max-width: 1400px;
  margin: 0 auto;
  color: #e2e8f0;
}

.dashboard-hero {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 32px;
  padding: 32px;
  background: linear-gradient(135deg, rgba(99, 102, 241, 0.15), rgba(139, 92, 246, 0.1));
  border: 1px solid rgba(99, 102, 241, 0.2);
  border-radius: 20px;
  backdrop-filter: blur(16px);
}
.hero-badge {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 4px 14px;
  background: rgba(139, 92, 246, 0.2);
  border: 1px solid rgba(139, 92, 246, 0.3);
  border-radius: 20px;
  font-size: 0.75rem;
  color: #c4b5fd;
  margin-bottom: 12px;
}
.hero-title { font-size: 2rem; font-weight: 800; color: #fff; margin: 0 0 8px; }
.hero-subtitle { font-size: 0.95rem; color: #94a3b8; margin: 0; }
.hero-subtitle strong { color: #c4b5fd; }
.btn-hero-action {
  display: inline-flex; align-items: center; gap: 8px; padding: 12px 24px;
  background: linear-gradient(135deg, #6366f1, #8b5cf6); color: white;
  border-radius: 12px; font-weight: 600; text-decoration: none;
  transition: all 0.3s; box-shadow: 0 4px 20px rgba(99, 102, 241, 0.3);
}
.btn-hero-action:hover { transform: translateY(-2px); box-shadow: 0 8px 30px rgba(99, 102, 241, 0.4); }

.search-bar-container { position: relative; margin-bottom: 32px; }
.search-bar {
  display: flex; align-items: center; gap: 12px; padding: 14px 20px;
  background: rgba(30, 30, 60, 0.8); border: 1px solid rgba(99, 102, 241, 0.15);
  border-radius: 16px; backdrop-filter: blur(12px);
}
.search-icon { width: 20px; height: 20px; color: #6366f1; flex-shrink: 0; }
.search-input {
  flex: 1; background: transparent; border: none; outline: none;
  color: #e2e8f0; font-size: 0.95rem;
}
.search-input::placeholder { color: #64748b; }
.search-spinner { color: #6366f1; }
.search-results-dropdown {
  position: absolute; top: 100%; left: 0; right: 0; margin-top: 8px;
  background: rgba(20, 20, 50, 0.98); border: 1px solid rgba(99, 102, 241, 0.2);
  border-radius: 16px; max-height: 400px; overflow-y: auto; z-index: 50;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
}
.search-group { padding: 12px 16px; border-bottom: 1px solid rgba(99, 102, 241, 0.1); }
.search-group:last-child { border-bottom: none; }
.search-group-header {
  display: flex; align-items: center; gap: 8px; font-size: 0.8rem;
  color: #8b5cf6; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em;
  margin-bottom: 8px;
}
.search-count { margin-left: auto; color: #64748b; font-size: 0.75rem; }
.search-result-item {
  display: block; padding: 8px 12px; border-radius: 8px; color: #e2e8f0;
  text-decoration: none; font-size: 0.9rem; transition: background 0.2s;
}
.search-result-item:hover { background: rgba(99, 102, 241, 0.15); }

.stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; margin-bottom: 40px; }
.stat-card {
  display: flex; align-items: center; gap: 16px; padding: 20px;
  background: rgba(30, 30, 60, 0.6); border: 1px solid rgba(99, 102, 241, 0.1);
  border-radius: 16px; backdrop-filter: blur(12px); transition: all 0.3s;
}
.stat-card:hover { border-color: rgba(99, 102, 241, 0.3); transform: translateY(-2px); }
.stat-icon-wrap { width: 52px; height: 52px; border-radius: 14px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.gradient-1 { background: linear-gradient(135deg, #6366f1, #8b5cf6); }
.gradient-2 { background: linear-gradient(135deg, #06b6d4, #3b82f6); }
.gradient-3 { background: linear-gradient(135deg, #10b981, #14b8a6); }
.gradient-4 { background: linear-gradient(135deg, #f59e0b, #ef4444); }
.stat-value { font-size: 1.5rem; font-weight: 800; color: #fff; }
.stat-label { font-size: 0.8rem; color: #64748b; }
.status-active { color: #34d399 !important; font-size: 1.1rem !important; }

.section-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
.section-header h2 { font-size: 1.3rem; font-weight: 700; color: #fff; margin: 0; }
.btn-sm {
  display: inline-flex; align-items: center; gap: 6px; padding: 8px 16px;
  background: rgba(99, 102, 241, 0.15); border: 1px solid rgba(99, 102, 241, 0.2);
  border-radius: 10px; color: #c4b5fd; font-size: 0.85rem; text-decoration: none;
  transition: all 0.2s;
}
.btn-sm:hover { background: rgba(99, 102, 241, 0.25); }

.tables-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 16px; margin-bottom: 40px; }
.table-card {
  display: flex; align-items: center; gap: 16px; padding: 20px;
  background: rgba(30, 30, 60, 0.6); border: 1px solid rgba(99, 102, 241, 0.1);
  border-radius: 16px; text-decoration: none; color: #e2e8f0;
  transition: all 0.3s; cursor: pointer;
}
.table-card:hover { border-color: rgba(99, 102, 241, 0.4); transform: translateY(-3px); box-shadow: 0 8px 30px rgba(99, 102, 241, 0.15); }
.table-card-icon { width: 56px; height: 56px; border-radius: 14px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.table-card-info h3 { font-size: 1rem; font-weight: 700; color: #fff; margin: 0 0 4px; }
.table-card-info p { font-size: 0.8rem; color: #64748b; margin: 0; }
.table-card-arrow { width: 18px; height: 18px; margin-left: auto; color: #475569; transition: color 0.2s; }
.table-card:hover .table-card-arrow { color: #8b5cf6; }

.empty-state {
  text-align: center; padding: 60px 24px;
  background: rgba(30, 30, 60, 0.4); border: 2px dashed rgba(99, 102, 241, 0.2);
  border-radius: 20px; margin-bottom: 40px;
}
.empty-state h3 { font-size: 1.3rem; color: #fff; margin: 16px 0 8px; }
.empty-state p { color: #64748b; margin: 0 0 24px; }
.btn-primary {
  display: inline-flex; align-items: center; gap: 8px; padding: 12px 24px;
  background: linear-gradient(135deg, #6366f1, #8b5cf6); color: white;
  border-radius: 12px; font-weight: 600; text-decoration: none; border: none; cursor: pointer;
}

.activity-section { margin-bottom: 40px; }
.activity-list {
  background: rgba(30, 30, 60, 0.6); border: 1px solid rgba(99, 102, 241, 0.1);
  border-radius: 16px; overflow: hidden;
}
.activity-item {
  display: flex; align-items: center; gap: 12px; padding: 14px 20px;
  border-bottom: 1px solid rgba(99, 102, 241, 0.06); transition: background 0.2s;
}
.activity-item:hover { background: rgba(99, 102, 241, 0.05); }
.activity-item:last-child { border-bottom: none; }
.activity-dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }
.dot-green { background: #34d399; }
.dot-blue { background: #60a5fa; }
.dot-red { background: #f87171; }
.activity-content { flex: 1; font-size: 0.9rem; }
.activity-action { color: #e2e8f0; font-weight: 500; }
.activity-table { color: #94a3b8; }
.activity-link { color: #8b5cf6; text-decoration: none; }
.activity-link:hover { text-decoration: underline; }
.activity-record { color: #64748b; font-size: 0.8rem; }
.activity-time { font-size: 0.75rem; color: #475569; white-space: nowrap; }

.db-info-card {
  background: rgba(30, 30, 60, 0.6); border: 1px solid rgba(99, 102, 241, 0.1);
  border-radius: 16px; padding: 24px; margin-bottom: 40px;
}
.db-info-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 16px; }
.db-info-item { display: flex; flex-direction: column; gap: 4px; }
.db-label { font-size: 0.75rem; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em; }
.db-value { font-size: 0.95rem; color: #e2e8f0; font-weight: 500; }

@media (max-width: 768px) {
  .dashboard-hero { flex-direction: column; text-align: center; gap: 16px; }
  .hero-title { font-size: 1.5rem; }
  .stats-grid { grid-template-columns: repeat(2, 1fr); }
  .tables-grid { grid-template-columns: 1fr; }
}
</style>
