<script setup>
import { ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import Navbar from '@/Components/Navbar.vue';
import GlobalSearchModal from '@/Components/GlobalSearchModal.vue';
import CreateTaskModal from '@/Components/CreateTaskModal.vue';
import { 
  BellIcon, 
  FunnelIcon,
  PlusIcon,
  CheckCircleIcon,
  ClockIcon,
  ExclamationCircleIcon,
  ArrowPathIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
  activeTab: String,
  selectedStatus: String,
  receivedCount: Number,
  sentCount: Number,
  statusCounts: Object,
  tasks: Array,
  staffList: Array,
  filters: Object,
});

const isSearchOpen = ref(false);
const isCreateTaskOpen = ref(false);

const timing = ref('All Time');
const priority = ref(props.filters.priority || '');
const staffId = ref(props.filters.staff_id || '');
const searchTitle = ref(props.filters.title || '');

const switchTab = (tab) => {
  router.get('/task-dashboard', {
    type: tab,
    status: props.selectedStatus,
    priority: priority.value,
    staff_id: staffId.value,
    title: searchTitle.value,
  }, { preserveState: true });
};

const filterStatus = (statusKey) => {
  router.get('/task-dashboard', {
    type: props.activeTab,
    status: statusKey,
    priority: priority.value,
    staff_id: staffId.value,
    title: searchTitle.value,
  }, { preserveState: true });
};

const applyFilter = () => {
  router.get('/task-dashboard', {
    type: props.activeTab,
    status: props.selectedStatus,
    priority: priority.value,
    staff_id: staffId.value,
    title: searchTitle.value,
  }, { preserveState: true });
};

const resetFilter = () => {
  timing.value = 'All Time';
  priority.value = '';
  staffId.value = '';
  searchTitle.value = '';

  router.get('/task-dashboard', {
    type: 'received',
    status: 'all',
  });
};

const updateTaskStatus = (taskId, newStatus) => {
  router.patch(`/tasks/${taskId}/status`, { status: newStatus }, {
    preserveScroll: true,
  });
};
</script>

<template>
  <Head title="Task Dashboard - CRM" />

  <div class="min-h-screen bg-slate-100 flex font-sans text-slate-900">
    <Navbar @open-search="isSearchOpen = true" />

    <main class="flex-1 p-6 space-y-6 overflow-y-auto max-w-7xl mx-auto w-full">
      <!-- Top Clean Bar -->
      <div class="flex items-center justify-end gap-4">

        <div class="flex items-center gap-4">
          <div class="relative">
            <button @click="isSearchOpen = true" class="p-2.5 bg-white border border-slate-200 rounded-full hover:bg-slate-100 text-slate-600 relative">
              <BellIcon class="w-6 h-6" />
              <span class="absolute -top-1 -right-1 bg-indigo-600 text-white text-[10px] font-black w-5 h-5 rounded-full flex items-center justify-center border-2 border-white">
                2
              </span>
            </button>
          </div>

          <div class="w-10 h-10 bg-red-600 rounded-full flex items-center justify-center text-white font-black text-lg shadow-md">
            A
          </div>
        </div>
      </div>

      <!-- Task Dashboard Title Bar -->
      <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pt-2">
        <h1 class="text-3xl font-black text-slate-900 tracking-tight">
          Task Dashboard
        </h1>

        <div class="flex items-center gap-3">
          <button class="px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-bold text-sm rounded-xl shadow-md transition-all">
            My Report
          </button>
          <button 
            @click="isCreateTaskOpen = true"
            class="px-5 py-2.5 bg-gradient-to-r from-red-600 to-red-500 hover:from-red-700 hover:to-red-600 text-white font-bold text-sm rounded-xl shadow-md flex items-center gap-2 transition-all"
          >
            <PlusIcon class="w-5 h-5 stroke-[3]" />
            <span>Create Task</span>
          </button>
        </div>
      </div>

      <!-- Received Task vs Send Task Tabs -->
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <button 
          @click="switchTab('received')"
          :class="[
            'py-3.5 px-6 rounded-2xl font-black text-base transition-all text-center border-2',
            activeTab === 'received' 
              ? 'bg-red-600 text-white border-red-600 shadow-md shadow-red-600/20' 
              : 'bg-white text-red-600 border-red-600 hover:bg-red-50'
          ]"
        >
          Received Task - {{ receivedCount }}
        </button>

        <button 
          @click="switchTab('send')"
          :class="[
            'py-3.5 px-6 rounded-2xl font-black text-base transition-all text-center border-2',
            activeTab === 'send' 
              ? 'bg-red-600 text-white border-red-600 shadow-md shadow-red-600/20' 
              : 'bg-white text-red-600 border-red-600 hover:bg-red-50'
          ]"
        >
          Send Task - {{ sentCount }}
        </button>
      </div>

      <!-- Status Metric Cards (Color Coded Grid) -->
      <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-7 gap-3">
        <!-- All Task -->
        <div 
          @click="filterStatus('all')"
          :class="[
            'p-4 rounded-xl border-2 cursor-pointer transition-all text-center space-y-1',
            selectedStatus === 'all' 
              ? 'bg-emerald-50 border-emerald-500 shadow-sm ring-2 ring-emerald-500/20' 
              : 'bg-emerald-50/50 border-emerald-400 hover:border-emerald-500'
          ]"
        >
          <div class="text-2xl font-black text-emerald-800">{{ statusCounts.all }}</div>
          <div class="text-xs font-bold text-emerald-700 tracking-wide">All Task</div>
          <div v-if="selectedStatus === 'all'" class="w-6 h-1 bg-emerald-600 mx-auto rounded-full mt-1"></div>
        </div>

        <!-- Pending -->
        <div 
          @click="filterStatus('pending')"
          :class="[
            'p-4 rounded-xl border-2 cursor-pointer transition-all text-center space-y-1',
            selectedStatus === 'pending' 
              ? 'bg-amber-50 border-amber-500 shadow-sm ring-2 ring-amber-500/20' 
              : 'bg-amber-50/50 border-amber-400 hover:border-amber-500'
          ]"
        >
          <div class="text-2xl font-black text-amber-800">{{ statusCounts.pending }}</div>
          <div class="text-xs font-bold text-amber-700 tracking-wide">Pending</div>
        </div>

        <!-- In Progress -->
        <div 
          @click="filterStatus('in_progress')"
          :class="[
            'p-4 rounded-xl border-2 cursor-pointer transition-all text-center space-y-1',
            selectedStatus === 'in_progress' 
              ? 'bg-sky-50 border-sky-500 shadow-sm ring-2 ring-sky-500/20' 
              : 'bg-sky-50/50 border-sky-400 hover:border-sky-500'
          ]"
        >
          <div class="text-2xl font-black text-sky-800">{{ statusCounts.in_progress }}</div>
          <div class="text-xs font-bold text-sky-700 tracking-wide">In progress</div>
        </div>

        <!-- Hold -->
        <div 
          @click="filterStatus('hold')"
          :class="[
            'p-4 rounded-xl border-2 cursor-pointer transition-all text-center space-y-1',
            selectedStatus === 'hold' 
              ? 'bg-rose-50 border-rose-500 shadow-sm ring-2 ring-rose-500/20' 
              : 'bg-rose-50/50 border-rose-400 hover:border-rose-500'
          ]"
        >
          <div class="text-2xl font-black text-rose-800">{{ statusCounts.hold }}</div>
          <div class="text-xs font-bold text-rose-700 tracking-wide">Hold</div>
        </div>

        <!-- Done -->
        <div 
          @click="filterStatus('done')"
          :class="[
            'p-4 rounded-xl border-2 cursor-pointer transition-all text-center space-y-1',
            selectedStatus === 'done' 
              ? 'bg-emerald-50 border-emerald-500 shadow-sm ring-2 ring-emerald-500/20' 
              : 'bg-emerald-50/50 border-emerald-400 hover:border-emerald-500'
          ]"
        >
          <div class="text-2xl font-black text-emerald-800">{{ statusCounts.done }}</div>
          <div class="text-xs font-bold text-emerald-700 tracking-wide">Done</div>
        </div>

        <!-- Rework -->
        <div 
          @click="filterStatus('rework')"
          :class="[
            'p-4 rounded-xl border-2 cursor-pointer transition-all text-center space-y-1',
            selectedStatus === 'rework' 
              ? 'bg-purple-50 border-purple-500 shadow-sm ring-2 ring-purple-500/20' 
              : 'bg-purple-50/50 border-purple-400 hover:border-purple-500'
          ]"
        >
          <div class="text-2xl font-black text-purple-800">{{ statusCounts.rework }}</div>
          <div class="text-xs font-bold text-purple-700 tracking-wide">Rework</div>
        </div>

        <!-- Verified -->
        <div 
          @click="filterStatus('verified')"
          :class="[
            'p-4 rounded-xl border-2 cursor-pointer transition-all text-center space-y-1',
            selectedStatus === 'verified' 
              ? 'bg-teal-50 border-teal-600 shadow-sm ring-2 ring-teal-600/20' 
              : 'bg-teal-50/50 border-teal-500 hover:border-teal-600'
          ]"
        >
          <div class="text-2xl font-black text-teal-800">{{ statusCounts.verified }}</div>
          <div class="text-xs font-bold text-teal-700 tracking-wide">Verified</div>
        </div>
      </div>

      <!-- Filter Bar -->
      <div class="bg-white border border-slate-200 rounded-2xl p-4 shadow-xs">
        <div class="grid grid-cols-1 md:grid-cols-6 gap-3 items-center">
          <div>
            <select v-model="timing" class="w-full bg-white border border-slate-300 rounded-xl px-3 py-2 text-xs font-semibold text-slate-700 focus:outline-none focus:border-red-500">
              <option>Timing</option>
              <option>All Time</option>
              <option>Today</option>
              <option>This Week</option>
              <option>This Month</option>
            </select>
          </div>

          <div>
            <select v-model="priority" class="w-full bg-white border border-slate-300 rounded-xl px-3 py-2 text-xs font-semibold text-slate-700 focus:outline-none focus:border-red-500">
              <option value="">Priority</option>
              <option value="high">High</option>
              <option value="medium">Medium</option>
              <option value="low">Low</option>
              <option value="urgent">Urgent</option>
            </select>
          </div>

          <div>
            <select v-model="staffId" class="w-full bg-white border border-slate-300 rounded-xl px-3 py-2 text-xs font-semibold text-slate-700 focus:outline-none focus:border-red-500">
              <option value="">Staff Name</option>
              <option v-for="staff in staffList" :key="staff.id" :value="staff.id">
                {{ staff.name }}
              </option>
            </select>
          </div>

          <div>
            <input 
              v-model="searchTitle" 
              type="text" 
              placeholder="Task Title" 
              class="w-full bg-white border border-slate-300 rounded-xl px-3 py-2 text-xs font-semibold text-slate-700 focus:outline-none focus:border-red-500 placeholder:text-slate-400"
            />
          </div>

          <div>
            <button 
              @click="applyFilter"
              class="w-full bg-red-600 hover:bg-red-700 text-white font-bold text-xs py-2.5 rounded-xl shadow-xs transition-all flex items-center justify-center gap-1.5"
            >
              <FunnelIcon class="w-4 h-4" />
              <span>Apply Filter</span>
            </button>
          </div>

          <div>
            <button 
              @click="resetFilter"
              class="w-full bg-white border-2 border-red-600 text-red-600 hover:bg-red-50 font-bold text-xs py-2 rounded-xl transition-all"
            >
              Reset Filter
            </button>
          </div>
        </div>
      </div>

      <!-- Task Table / Cards Area -->
      <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-xs min-h-[300px] flex flex-col justify-between">
        <div v-if="tasks.length > 0" class="divide-y divide-slate-100">
          <div 
            v-for="task in tasks" 
            :key="task.id"
            class="py-4 flex flex-col sm:flex-row sm:items-center justify-between gap-4 hover:bg-slate-50/80 p-3 rounded-xl transition-all"
          >
            <div class="space-y-1">
              <div class="flex items-center gap-3">
                <span class="text-sm font-bold text-slate-900">{{ task.title }}</span>
                <span :class="[
                  'text-[10px] font-extrabold px-2.5 py-0.5 rounded-full uppercase tracking-wider border',
                  task.status === 'completed' ? 'bg-emerald-100 text-emerald-800 border-emerald-300' :
                  task.status === 'in_progress' ? 'bg-sky-100 text-sky-800 border-sky-300' :
                  task.status === 'hold' ? 'bg-rose-100 text-rose-800 border-rose-300' :
                  task.status === 'rework' ? 'bg-purple-100 text-purple-800 border-purple-300' :
                  task.status === 'verified' ? 'bg-teal-100 text-teal-800 border-teal-300' :
                  'bg-amber-100 text-amber-800 border-amber-300'
                ]">
                  {{ task.status === 'completed' ? 'Done' : task.status.replace('_', ' ') }}
                </span>
              </div>
              <p class="text-xs text-slate-500">{{ task.description || 'No additional details provided.' }}</p>
            </div>

            <div class="flex items-center gap-4">
              <div class="text-right">
                <div class="text-xs font-semibold text-slate-700">Assigned: {{ task.assignee?.name || 'Unassigned' }}</div>
                <div class="text-[11px] text-slate-400">Due: {{ task.due_at ? new Date(task.due_at).toLocaleDateString() : 'No date' }}</div>
              </div>

              <!-- Quick Status Transitions -->
              <select 
                :value="task.status" 
                @change="updateTaskStatus(task.id, $event.target.value)"
                class="bg-slate-100 border border-slate-300 text-slate-700 text-xs font-bold rounded-lg px-2.5 py-1.5 focus:outline-none"
              >
                <option value="pending">Pending</option>
                <option value="in_progress">In Progress</option>
                <option value="hold">Hold</option>
                <option value="completed">Done</option>
                <option value="rework">Rework</option>
                <option value="verified">Verified</option>
              </select>
            </div>
          </div>
        </div>

        <div v-else class="text-center py-16 space-y-3">
          <p class="text-sm font-bold text-slate-600">No tasks found for this filter.</p>
          <button @click="resetFilter" class="text-xs font-extrabold text-red-600 hover:text-red-700 underline">
            Show All
          </button>
        </div>
      </div>
    </main>

    <!-- Modals -->
    <GlobalSearchModal :is-open="isSearchOpen" @close="isSearchOpen = false" />
    <CreateTaskModal :is-open="isCreateTaskOpen" :staff-list="staffList" @close="isCreateTaskOpen = false" />
  </div>
</template>
