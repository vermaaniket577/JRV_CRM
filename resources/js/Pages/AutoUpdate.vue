<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import Navbar from '@/Components/Navbar.vue';
import GlobalSearchModal from '@/Components/GlobalSearchModal.vue';
import { 
  ArrowPathIcon,
  PlayIcon,
  CheckCircleIcon,
  ExclamationTriangleIcon,
  ClockIcon,
  BoltIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
  rules: Array,
  metrics: Object,
});

const page = usePage();
const tenantIndustry = computed(() => page.props.tenant_industry || { name: 'CRM', icon: '⚡', color: 'indigo' });

const isSearchOpen = ref(false);

const toggleRule = (ruleId) => {
  router.post(`/auto-update/${ruleId}/toggle`, {}, { preserveScroll: true });
};

const runNow = (ruleId) => {
  router.post(`/auto-update/${ruleId}/run`, {}, { preserveScroll: true });
};
</script>

<template>
  <Head title="Auto Update Automation Engine - JRV CRM" />

  <div class="flex h-screen bg-slate-50 overflow-hidden font-sans text-slate-900">
    <!-- Navbar Sidebar -->
    <Navbar />

    <!-- Main Content Area -->
    <div class="flex-1 flex flex-col min-w-0 overflow-hidden">
      <!-- Top Bar Header -->
      <header class="h-16 bg-white border-b border-slate-200 px-6 flex items-center justify-between shrink-0 shadow-2xs">
        <div class="flex items-center gap-3">
          <div class="w-9 h-9 bg-slate-900 rounded-xl flex items-center justify-center text-white font-black text-sm shadow-md">
            <ArrowPathIcon class="w-5 h-5 text-indigo-400" />
          </div>
          <div>
            <h1 class="text-sm font-black text-slate-900 leading-tight">
              Auto Update & Background Rules
            </h1>
            <p class="text-[11px] text-slate-500 font-medium">Automated Trigger Workflows & Sync Schedules</p>
          </div>
        </div>

        <div class="flex items-center gap-3">
          <Link href="/ai-crm-modifier" class="hidden sm:inline-flex px-3.5 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs rounded-xl shadow-xs items-center gap-1.5 transition">
            <BoltIcon class="w-4 h-4 text-amber-500" />
            <span>AI Automation Studio</span>
          </Link>
        </div>
      </header>

      <!-- Scrollable Body -->
      <div class="p-6 md:p-8 space-y-6 flex-1 overflow-y-auto max-w-7xl mx-auto w-full">
        <!-- Hero Header Banner -->
        <div class="bg-gradient-to-r from-slate-950 via-slate-900 to-slate-950 border border-slate-800 rounded-3xl p-6 md:p-7 text-white shadow-xl flex flex-col md:flex-row md:items-center justify-between gap-6">
          <div class="space-y-1.5 max-w-2xl">
            <div class="inline-flex items-center gap-2 px-2.5 py-0.5 rounded-md bg-white/10 text-slate-200 text-[10px] font-bold uppercase tracking-wider">
              <span>{{ tenantIndustry.icon }}</span>
              <span>{{ tenantIndustry.name }} Automation Engine</span>
            </div>
            <h2 class="text-xl md:text-2xl font-black tracking-tight text-white">
              Auto Update Rules & Trigger Controls
            </h2>
            <p class="text-xs text-slate-300">
              Manage automatic profile updates, renewal alerts, status transitions, and scheduled sync jobs.
            </p>
          </div>
        </div>

        <!-- 4 Summary Metrics Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
          <div class="p-5 bg-white rounded-2xl border border-slate-200 shadow-xs space-y-1">
            <div class="text-xs font-bold text-slate-500 uppercase">Active Automation Rules</div>
            <div class="text-3xl font-black text-slate-900">{{ metrics.active_rules }}</div>
            <p class="text-xs text-emerald-600 font-semibold">Running in background</p>
          </div>

          <div class="p-5 bg-white rounded-2xl border border-slate-200 shadow-xs space-y-1">
            <div class="text-xs font-bold text-slate-500 uppercase">Processed Today</div>
            <div class="text-3xl font-black text-emerald-600">{{ metrics.processed_today }}</div>
            <p class="text-xs text-emerald-600 font-semibold">Records updated</p>
          </div>

          <div class="p-5 bg-white rounded-2xl border border-slate-200 shadow-xs space-y-1">
            <div class="text-xs font-bold text-slate-500 uppercase">Pending Reminders</div>
            <div class="text-3xl font-black text-amber-600">{{ metrics.pending_reminders }}</div>
            <p class="text-xs text-amber-600 font-semibold">Queued for delivery</p>
          </div>

          <div class="p-5 bg-white rounded-2xl border border-slate-200 shadow-xs space-y-1">
            <div class="text-xs font-bold text-slate-500 uppercase">System Sync Health</div>
            <div class="text-3xl font-black text-indigo-600">{{ metrics.system_health }}</div>
            <p class="text-xs text-indigo-600 font-semibold">No sync errors detected</p>
          </div>
        </div>

        <!-- Rules Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div v-for="rule in rules" :key="rule.id" class="bg-white border border-slate-200 hover:border-slate-300 rounded-3xl p-6 shadow-xs space-y-4 flex flex-col justify-between transition-all">
            <div class="space-y-2">
              <div class="flex items-center justify-between">
                <span class="text-xs font-black text-slate-700 uppercase tracking-wider bg-slate-100 px-2.5 py-0.5 rounded-md">
                  {{ rule.frequency }} Trigger
                </span>
                <button @click="toggleRule(rule.id)" :class="['w-10 h-5 rounded-full p-0.5 transition-colors flex items-center cursor-pointer', rule.is_active ? 'bg-emerald-600 justify-end' : 'bg-slate-300 justify-start']">
                  <div class="w-4 h-4 rounded-full bg-white shadow-xs"></div>
                </button>
              </div>

              <h3 class="text-base font-extrabold text-slate-900">{{ rule.rule_name }}</h3>
              <p class="text-xs text-slate-600 font-medium">Trigger Event: <span class="font-bold text-slate-900">{{ rule.trigger_event }}</span></p>
              <p class="text-xs text-slate-600 font-medium">Action: <span class="font-bold text-slate-900">{{ rule.action_type }}</span></p>
            </div>

            <div class="pt-4 border-t border-slate-100 flex items-center justify-between text-xs">
              <div class="text-slate-500 font-semibold">
                Processed: <span class="font-black text-slate-900">{{ rule.processed_count }}</span> records
              </div>
              <button @click="runNow(rule.id)" class="px-3.5 py-1.5 bg-slate-900 hover:bg-slate-800 text-white font-bold rounded-xl shadow-xs flex items-center gap-1.5 transition-all cursor-pointer">
                <PlayIcon class="w-3.5 h-3.5 fill-white" />
                <span>Run Trigger Now</span>
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Modals -->
    <GlobalSearchModal :is-open="isSearchOpen" @close="isSearchOpen = false" />
  </div>
</template>
