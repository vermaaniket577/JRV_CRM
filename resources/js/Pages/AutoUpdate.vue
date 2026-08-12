<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import GlobalSearchModal from '@/Components/GlobalSearchModal.vue';
import { 
  BellIcon, 
  HomeIcon, 
  UserIcon, 
  SignalIcon, 
  DocumentTextIcon, 
  ArrowPathIcon,
  AcademicCapIcon,
  BriefcaseIcon,
  UserGroupIcon,
  GlobeAltIcon,
  AdjustmentsHorizontalIcon,
  PlayIcon,
  CheckCircleIcon,
  ExclamationTriangleIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
  rules: Array,
  metrics: Object,
});

const page = usePage();
const customNav = computed(() => page.props.custom_nav || {});
const businessSettings = computed(() => page.props.business_settings || { business_name: 'JRV CRM', business_icon: '♥' });

const getNavLabel = (key, fallback) => customNav.value[key]?.label || fallback;
const getNavRoute = (key, fallback) => customNav.value[key]?.route || fallback;

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

  <div class="min-h-screen bg-slate-100 flex font-sans text-slate-900">
    <!-- Left Vertical Sidebar -->
    <aside class="w-24 bg-white border-r border-slate-200 flex flex-col items-center py-4 space-y-6 shrink-0 shadow-xs">
      <Link href="/" class="flex flex-col items-center gap-1 group">
        <div class="w-12 h-12 bg-red-600 rounded-2xl flex items-center justify-center text-white font-black text-xl shadow-md shadow-red-600/30">
          {{ businessSettings.business_icon }}
        </div>
        <span class="text-[9px] font-black text-red-600 tracking-tighter uppercase text-center px-1 leading-tight line-clamp-1">
          {{ businessSettings.business_name }}
        </span>
      </Link>

      <nav class="flex-1 w-full space-y-3 px-2">
        <Link :href="getNavRoute('app', '/')" class="flex flex-col items-center justify-center p-2 rounded-xl text-slate-500 hover:bg-slate-100 transition-all text-center">
          <HomeIcon class="w-5 h-5 text-slate-400" />
          <span class="text-[9px] font-bold mt-1 leading-tight line-clamp-1">{{ getNavLabel('app', 'App') }}</span>
        </Link>

        <Link href="/matrimonial/directory" class="flex flex-col items-center justify-center p-2 rounded-xl text-slate-500 hover:bg-slate-100 transition-all text-center">
          <DocumentTextIcon class="w-5 h-5 text-slate-400" />
          <span class="text-[9px] font-bold mt-1 leading-tight line-clamp-1">{{ getNavLabel('biodata', 'Biodata') }}</span>
        </Link>

        <Link href="/broadcast-message" class="flex flex-col items-center justify-center p-2 rounded-xl text-slate-500 hover:bg-slate-100 transition-all text-center">
          <SignalIcon class="w-5 h-5 text-slate-400" />
          <span class="text-[9px] font-bold mt-1 leading-tight line-clamp-1">{{ getNavLabel('broadcast', 'BroadCast...') }}</span>
        </Link>

        <Link href="/online-users" class="flex flex-col items-center justify-center p-2 rounded-xl text-slate-500 hover:bg-slate-100 transition-all text-center">
          <UserIcon class="w-5 h-5 text-slate-400" />
          <span class="text-[9px] font-bold mt-1 leading-tight line-clamp-1">{{ getNavLabel('online_user', 'Online User') }}</span>
        </Link>

        <!-- Active Item: Auto Update -->
        <Link href="/auto-update" class="flex flex-col items-center justify-center p-2 rounded-xl bg-red-50 text-red-600 font-bold border border-red-200 text-center shadow-xs">
          <ArrowPathIcon class="w-6 h-6 text-red-600" />
          <span class="text-[9px] font-black mt-1 leading-tight line-clamp-1">{{ getNavLabel('auto_update', 'Auto Update') }}</span>
        </Link>

        <Link href="/padhadhikari-directory" class="flex flex-col items-center justify-center p-2 rounded-xl text-slate-500 hover:bg-slate-100 transition-all text-center">
          <AcademicCapIcon class="w-5 h-5 text-slate-400" />
          <span class="text-[9px] font-bold mt-1 leading-tight line-clamp-1">{{ getNavLabel('padhadhikari', 'Padhadhikari') }}</span>
        </Link>

        <Link href="/staff-recruitment" class="flex flex-col items-center justify-center p-2 rounded-xl text-slate-500 hover:bg-slate-100 transition-all text-center">
          <BriefcaseIcon class="w-5 h-5 text-slate-400" />
          <span class="text-[9px] font-bold mt-1 leading-tight line-clamp-1">{{ getNavLabel('staff_recruit', 'Staff Recruit') }}</span>
        </Link>

        <Link href="/employee-management" class="flex flex-col items-center justify-center p-2 rounded-xl text-slate-500 hover:bg-slate-100 transition-all text-center">
          <UserGroupIcon class="w-5 h-5 text-slate-400" />
          <span class="text-[9px] font-bold mt-1 leading-tight line-clamp-1">{{ getNavLabel('staff_management', 'Staff Manag...') }}</span>
        </Link>

        <Link href="/online-users" class="flex flex-col items-center justify-center p-2 rounded-xl text-slate-500 hover:bg-slate-100 transition-all text-center">
          <GlobeAltIcon class="w-5 h-5 text-slate-400" />
          <span class="text-[9px] font-bold mt-1 leading-tight line-clamp-1">{{ getNavLabel('universal', 'Universal') }}</span>
        </Link>
      </nav>
    </aside>

    <!-- Main Content Area -->
    <div class="flex-1 flex flex-col min-w-0">
      <!-- Header -->
      <header class="bg-white border-b border-slate-200 px-6 py-3.5 flex flex-wrap items-center justify-between gap-3 shadow-xs">
        <div class="flex flex-wrap items-center gap-2">
          <button class="px-4 py-1.5 bg-red-600 text-white font-extrabold text-xs rounded-xl shadow-xs">My Work</button>
          <button class="px-4 py-1.5 bg-red-600 text-white font-extrabold text-xs rounded-xl shadow-xs">Login History</button>
          <button class="px-4 py-1.5 bg-red-600 text-white font-extrabold text-xs rounded-xl shadow-xs">My Task</button>
        </div>

        <div class="flex items-center gap-3">
          <div class="w-9 h-9 bg-red-600 rounded-full flex items-center justify-center text-white font-black text-sm shadow-md">
            {{ businessSettings.business_icon }}
          </div>
        </div>
      </header>

      <!-- Body -->
      <div class="p-8 space-y-6 flex-1 overflow-y-auto max-w-7xl mx-auto w-full">
        <div class="border-b border-slate-200 pb-6">
          <div class="flex items-center gap-2 text-xs font-bold text-red-600 uppercase tracking-wider">
            <ArrowPathIcon class="w-4 h-4" />
            <span>Automated Profile Renewal & Sync Engine</span>
          </div>
          <h1 class="text-3xl font-black tracking-tight text-slate-900 mt-0.5">
            Auto Update Rules & Trigger Controls
          </h1>
        </div>

        <!-- 4 Summary Metrics Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
          <div class="p-6 bg-white rounded-2xl border border-slate-200 shadow-xs space-y-2">
            <div class="text-xs font-bold text-slate-500 uppercase">Active Automation Rules</div>
            <div class="text-3xl font-black text-slate-900">{{ metrics.active_rules }}</div>
            <p class="text-xs text-emerald-600 font-semibold">Running in background</p>
          </div>

          <div class="p-6 bg-white rounded-2xl border border-slate-200 shadow-xs space-y-2">
            <div class="text-xs font-bold text-slate-500 uppercase">Processed Today</div>
            <div class="text-3xl font-black text-emerald-600">{{ metrics.processed_today }}</div>
            <p class="text-xs text-emerald-600 font-semibold">Bio-datas updated</p>
          </div>

          <div class="p-6 bg-white rounded-2xl border border-slate-200 shadow-xs space-y-2">
            <div class="text-xs font-bold text-slate-500 uppercase">Pending Reminders</div>
            <div class="text-3xl font-black text-amber-600">{{ metrics.pending_reminders }}</div>
            <p class="text-xs text-amber-600 font-semibold">Queued for delivery</p>
          </div>

          <div class="p-6 bg-white rounded-2xl border border-slate-200 shadow-xs space-y-2">
            <div class="text-xs font-bold text-slate-500 uppercase">System Sync Health</div>
            <div class="text-3xl font-black text-rose-600">{{ metrics.system_health }}</div>
            <p class="text-xs text-rose-600 font-semibold">No sync errors detected</p>
          </div>
        </div>

        <!-- Rules Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div v-for="rule in rules" :key="rule.id" class="bg-white border border-slate-200 rounded-3xl p-6 shadow-xs space-y-4 flex flex-col justify-between">
            <div class="space-y-2">
              <div class="flex items-center justify-between">
                <span class="text-xs font-black text-red-600 uppercase tracking-wider bg-red-50 px-2.5 py-0.5 rounded-full border border-red-200">
                  {{ rule.frequency }} Trigger
                </span>
                <button @click="toggleRule(rule.id)" :class="['w-10 h-5 rounded-full p-0.5 transition-colors flex items-center', rule.is_active ? 'bg-emerald-600 justify-end' : 'bg-slate-300 justify-start']">
                  <div class="w-4 h-4 rounded-full bg-white shadow-xs"></div>
                </button>
              </div>

              <h3 class="text-base font-extrabold text-slate-900">{{ rule.rule_name }}</h3>
              <p class="text-xs text-slate-600 font-medium">Trigger Event: <span class="font-bold text-slate-900">{{ rule.trigger_event }}</span></p>
              <p class="text-xs text-slate-600 font-medium">Action: <span class="font-bold text-slate-900">{{ rule.action_type }}</span></p>
            </div>

            <div class="pt-4 border-t border-slate-100 flex items-center justify-between text-xs">
              <div class="text-slate-500 font-semibold">
                Processed: <span class="font-black text-slate-900">{{ rule.processed_count }}</span> profiles
              </div>
              <button @click="runNow(rule.id)" class="px-3.5 py-1.5 bg-red-600 hover:bg-red-700 text-white font-bold rounded-xl shadow-xs flex items-center gap-1.5 transition-all">
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
