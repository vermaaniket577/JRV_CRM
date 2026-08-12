<script setup>
import { ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import Navbar from '@/Components/Navbar.vue';
import GlobalSearchModal from '@/Components/GlobalSearchModal.vue';
import CreateDealModal from '@/Components/CreateDealModal.vue';
import { 
  UsersIcon, 
  UserGroupIcon,
  BanknotesIcon, 
  ClipboardDocumentCheckIcon,
  PlusIcon,
  ArrowRightIcon,
  ClipboardDocumentListIcon,
  UserIcon,
  Square3Stack3DIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
  metrics: Object,
  upcomingTasks: Array,
  recentDeals: Array,
});

const isSearchOpen = ref(false);
const isCreateDealOpen = ref(false);

const formatINR = (amount) => {
  return '₹' + Number(amount || 0).toLocaleString('en-IN', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  });
};
</script>

<template>
  <Head title="Dashboard - CRM" />

  <div class="min-h-screen bg-slate-100 flex font-sans text-slate-900">
    <Navbar @open-search="isSearchOpen = true" />

    <main class="flex-1 p-8 space-y-8 overflow-y-auto min-w-0 max-w-7xl mx-auto w-full">
      <!-- Header Bar -->
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-slate-200 pb-6">
        <div>
          <h1 class="text-3xl font-extrabold tracking-tight text-slate-900">
            CRM Data Information Overview
          </h1>
          <p class="text-slate-500 text-sm mt-1">Real-time workspace activity, employee counts, task status, and deal records in INR (₹).</p>
        </div>

        <div class="flex items-center gap-3">
          <button 
            @click="isSearchOpen = true"
            class="px-4 py-2 bg-white border border-slate-300 hover:bg-slate-100 rounded-lg text-xs font-semibold text-slate-700 shadow-xs transition-all flex items-center gap-2"
          >
            <span>Global Search</span>
            <kbd class="px-1.5 py-0.5 bg-slate-100 text-slate-500 text-[10px] rounded border border-slate-300 font-mono">⌘K</kbd>
          </button>
          <button 
            @click="isCreateDealOpen = true"
            class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg text-sm font-semibold shadow-md shadow-red-600/20 transition-all flex items-center gap-2"
          >
            <PlusIcon class="w-4 h-4" />
            <span>New Opportunity</span>
          </button>
        </div>
      </div>

      <!-- Metric Cards Grid (Operational Data Focus in INR ₹) -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- Total Staff / Employees -->
        <div class="p-6 bg-white rounded-2xl border border-slate-200 shadow-xs hover:shadow-md transition-all space-y-3 relative overflow-hidden group">
          <div class="flex items-center justify-between text-slate-500">
            <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Total Employees</span>
            <div class="p-2.5 bg-indigo-50 text-indigo-600 rounded-xl">
              <UserGroupIcon class="w-5 h-5" />
            </div>
          </div>
          <div class="text-3xl font-black text-slate-900 tracking-tight">{{ metrics.total_employees }}</div>
          <p class="text-xs text-indigo-600 font-semibold">Active organization staff</p>
        </div>

        <!-- Total Leads -->
        <div class="p-6 bg-white rounded-2xl border border-slate-200 shadow-xs hover:shadow-md transition-all space-y-3 relative overflow-hidden group">
          <div class="flex items-center justify-between text-slate-500">
            <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Total Contacts & Leads</span>
            <div class="p-2.5 bg-blue-50 text-blue-600 rounded-xl">
              <UsersIcon class="w-5 h-5" />
            </div>
          </div>
          <div class="text-3xl font-black text-slate-900 tracking-tight">{{ metrics.total_contacts }}</div>
          <p class="text-xs text-emerald-600 flex items-center gap-1 font-semibold">↑ 12% vs last month</p>
        </div>

        <!-- Pipeline Volume in INR (₹) -->
        <div class="p-6 bg-white rounded-2xl border border-slate-200 shadow-xs hover:shadow-md transition-all space-y-3 relative overflow-hidden group">
          <div class="flex items-center justify-between text-slate-500">
            <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Pipeline Volume</span>
            <div class="p-2.5 bg-emerald-50 text-emerald-600 rounded-xl">
              <BanknotesIcon class="w-5 h-5" />
            </div>
          </div>
          <div class="text-3xl font-black text-slate-900 tracking-tight">{{ formatINR(63500) }}</div>
          <p class="text-xs text-slate-500 font-medium">Active deals formatted in INR (₹)</p>
        </div>

        <!-- Pending Tasks -->
        <div class="p-6 bg-white rounded-2xl border border-slate-200 shadow-xs hover:shadow-md transition-all space-y-3 relative overflow-hidden group">
          <div class="flex items-center justify-between text-slate-500">
            <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Action Items & Tasks</span>
            <div class="p-2.5 bg-amber-50 text-amber-600 rounded-xl">
              <ClipboardDocumentCheckIcon class="w-5 h-5" />
            </div>
          </div>
          <div class="text-3xl font-black text-slate-900 tracking-tight">{{ metrics.pending_tasks_count }}</div>
          <p class="text-xs text-amber-600 font-semibold">Requires team attention</p>
        </div>
      </div>

      <!-- Quick Module Navigation Bar -->
      <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-xs flex flex-wrap items-center justify-between gap-4">
        <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Quick Module Access</span>
        <div class="flex flex-wrap items-center gap-3">
          <Link href="/task-dashboard" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-800 rounded-xl text-xs font-bold flex items-center gap-2 transition-all">
            <ClipboardDocumentListIcon class="w-4 h-4 text-red-600" />
            <span>Task Dashboard</span>
          </Link>
          <Link href="/employee-management" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-800 rounded-xl text-xs font-bold flex items-center gap-2 transition-all">
            <UserGroupIcon class="w-4 h-4 text-red-600" />
            <span>Employee Management</span>
          </Link>
          <Link href="/online-users" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-800 rounded-xl text-xs font-bold flex items-center gap-2 transition-all">
            <UserIcon class="w-4 h-4 text-red-600" />
            <span>Online Users</span>
          </Link>
          <Link href="/deals" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-800 rounded-xl text-xs font-bold flex items-center gap-2 transition-all">
            <Square3Stack3DIcon class="w-4 h-4 text-red-600" />
            <span>Deals Pipeline</span>
          </Link>
        </div>
      </div>

      <!-- Data Information Tables Grid -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Recent Deals / Opportunities (Formated in INR ₹) -->
        <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-xs space-y-4">
          <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <h2 class="text-lg font-bold text-slate-900">Recent Opportunities (INR ₹)</h2>
            <Link href="/deals" class="text-xs text-indigo-600 hover:text-indigo-700 font-semibold flex items-center gap-1">
              View All Deals <ArrowRightIcon class="w-3.5 h-3.5" />
            </Link>
          </div>

          <div class="divide-y divide-slate-100">
            <div v-for="deal in recentDeals" :key="deal.id" class="py-3 flex items-center justify-between">
              <div>
                <div class="font-semibold text-slate-900 text-sm">{{ deal.title }}</div>
                <div class="text-xs text-slate-500">{{ deal.company?.name || deal.contact?.first_name || 'Individual' }}</div>
              </div>
              <div class="text-right">
                <div class="text-sm font-bold text-emerald-700">{{ formatINR(deal.value) }}</div>
                <span class="text-[10px] px-2.5 py-0.5 rounded-full bg-slate-100 text-slate-600 border border-slate-200 font-semibold">
                  {{ deal.stage?.name }}
                </span>
              </div>
            </div>
          </div>
        </div>

        <!-- Upcoming Tasks Data Feed -->
        <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-xs space-y-4">
          <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <h2 class="text-lg font-bold text-slate-900">Action Items & Tasks Feed</h2>
            <span class="text-xs text-slate-500 font-medium">Next 7 Days</span>
          </div>

          <div class="space-y-3">
            <div v-for="task in upcomingTasks" :key="task.id" class="p-3.5 bg-slate-50 border border-slate-200 rounded-xl flex items-center justify-between">
              <div class="space-y-0.5">
                <div class="text-sm font-semibold text-slate-900">{{ task.title }}</div>
                <div class="text-xs text-slate-500">{{ task.description }}</div>
              </div>
              <div class="text-right">
                <span class="text-[10px] font-bold px-2.5 py-0.5 rounded uppercase tracking-wider bg-amber-100 text-amber-800 border border-amber-200">
                  {{ task.priority }}
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </main>

    <!-- Modals -->
    <GlobalSearchModal :is-open="isSearchOpen" @close="isSearchOpen = false" />
    <CreateDealModal :is-open="isCreateDealOpen" :stages="recentDeals.map(d => d.stage).filter(Boolean)" @close="isCreateDealOpen = false" />
  </div>
</template>
