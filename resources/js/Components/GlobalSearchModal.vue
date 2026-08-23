<script setup>
import { ref, watch, nextTick } from 'vue';
import { router } from '@inertiajs/vue3';
import { 
  MagnifyingGlassIcon, 
  XMarkIcon, 
  BanknotesIcon, 
  UserIcon, 
  BuildingOfficeIcon,
  DocumentTextIcon,
  ClipboardDocumentCheckIcon,
  UserGroupIcon,
  Squares2X2Icon,
  ArrowRightIcon,
  SparklesIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
  isOpen: Boolean,
});

const emit = defineEmits(['close']);
const searchQuery = ref('');
const activeCategory = ref('all');
const results = ref({ 
  modules: [], 
  members: [], 
  deals: [], 
  tasks: [], 
  contacts: [], 
  employees: [] 
});
const isLoading = ref(false);
const searchInput = ref(null);

const categories = [
  { id: 'all', label: 'All Results' },
  { id: 'members', label: 'Biodata & Members' },
  { id: 'deals', label: 'Deals & Sales' },
  { id: 'tasks', label: 'Tasks' },
  { id: 'employees', label: 'Staff' },
  { id: 'modules', label: 'CRM Pages' },
];

const performSearch = async () => {
  if (!searchQuery.value || searchQuery.value.trim().length < 2) {
    results.value = { modules: [], members: [], deals: [], tasks: [], contacts: [], employees: [] };
    return;
  }

  isLoading.value = true;

  try {
    const res = await fetch(`/global-search?query=${encodeURIComponent(searchQuery.value)}&category=${activeCategory.value}`);
    if (res.ok) {
      results.value = await res.json();
    }
  } catch (e) {
    console.error('Search error:', e);
  } finally {
    isLoading.value = false;
  }
};

watch([searchQuery, activeCategory], () => {
  performSearch();
});

watch(() => props.isOpen, (newVal) => {
  if (newVal) {
    nextTick(() => {
      if (searchInput.value) {
        searchInput.value.focus();
      }
    });
  } else {
    searchQuery.value = '';
    activeCategory.value = 'all';
    results.value = { modules: [], members: [], deals: [], tasks: [], contacts: [], employees: [] };
  }
});

const navigateTo = (path) => {
  emit('close');
  router.visit(path);
};

const totalResultsCount = () => {
  return (results.value.modules?.length || 0) +
         (results.value.members?.length || 0) +
         (results.value.deals?.length || 0) +
         (results.value.tasks?.length || 0) +
         (results.value.contacts?.length || 0) +
         (results.value.employees?.length || 0);
};
</script>

<template>
  <Transition
    enter-active-class="transition duration-200 ease-out"
    enter-from-class="opacity-0 scale-95"
    enter-to-class="opacity-100 scale-100"
    leave-active-class="transition duration-150 ease-in"
    leave-from-class="opacity-100 scale-100"
    leave-to-class="opacity-0 scale-95"
  >
    <div v-if="isOpen" class="fixed inset-0 z-50 flex items-start justify-center pt-16 sm:pt-24 p-4 bg-slate-950/60 backdrop-blur-sm">
      <div 
        class="bg-white border border-slate-200/90 rounded-3xl max-w-2xl w-full shadow-2xl flex flex-col overflow-hidden font-sans"
        @click.stop
      >
        <!-- Search Input Header -->
        <div class="p-4 sm:p-5 border-b border-slate-100 flex items-center gap-3 bg-slate-50/50">
          <div class="p-2 bg-red-50 text-red-600 rounded-xl">
            <MagnifyingGlassIcon class="w-5 h-5" />
          </div>
          <input 
            ref="searchInput"
            v-model="searchQuery" 
            type="text" 
            placeholder="Search biodata, deals, staff, tasks, or CRM pages..." 
            class="w-full bg-transparent border-none text-slate-900 text-sm font-semibold focus:outline-none focus:ring-0 placeholder:text-slate-400"
          />
          <kbd class="hidden sm:inline-block px-2 py-0.5 bg-slate-200 text-slate-600 text-[10px] font-mono rounded-lg border border-slate-300">
            ESC
          </kbd>
          <button @click="emit('close')" class="p-1.5 text-slate-400 hover:text-slate-700 rounded-lg transition cursor-pointer">
            <XMarkIcon class="w-5 h-5" />
          </button>
        </div>

        <!-- Filter Category Tabs -->
        <div class="flex items-center gap-1.5 px-4 py-2 bg-slate-100/60 border-b border-slate-200 overflow-x-auto">
          <button 
            v-for="cat in categories"
            :key="cat.id"
            @click="activeCategory = cat.id"
            :class="[
              'px-3 py-1 rounded-lg text-xs font-bold transition whitespace-nowrap cursor-pointer',
              activeCategory === cat.id ? 'bg-red-600 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-200/60'
            ]"
          >
            {{ cat.label }}
          </button>
        </div>

        <!-- Results Body -->
        <div class="p-4 sm:p-5 max-h-[60vh] overflow-y-auto space-y-4">
          <div v-if="isLoading" class="text-center py-8 text-xs text-slate-500 font-bold flex items-center justify-center gap-2">
            <div class="w-4 h-4 border-2 border-red-600 border-t-transparent rounded-full animate-spin"></div>
            Searching CRM workspace...
          </div>

          <div v-else-if="!searchQuery" class="text-center py-8 space-y-2">
            <div class="w-10 h-10 rounded-2xl bg-red-50 text-red-600 flex items-center justify-center mx-auto text-xl font-bold">
              ⚡
            </div>
            <p class="text-xs font-bold text-slate-700">Quick Global Search</p>
            <p class="text-[11px] text-slate-400 max-w-sm mx-auto">
              Type to search profiles, members, phone numbers, deals, tasks, staff, or system settings.
            </p>
          </div>

          <div v-else-if="totalResultsCount() === 0" class="text-center py-8 space-y-1">
            <p class="text-xs font-bold text-slate-600">No matching records found for "{{ searchQuery }}".</p>
            <p class="text-[11px] text-slate-400">Try searching for a different keyword or check spelling.</p>
          </div>

          <!-- 1. CRM Modules & Navigation Pages -->
          <div v-if="results.modules && results.modules.length > 0" class="space-y-1.5">
            <div class="text-[10px] font-black text-slate-400 uppercase tracking-widest px-1">CRM Pages & Modules</div>
            <div 
              v-for="mod in results.modules" 
              :key="'mod-' + mod.id"
              @click="navigateTo(mod.route)"
              class="p-3 bg-slate-50 hover:bg-red-50/60 rounded-2xl cursor-pointer flex items-center justify-between border border-slate-200/80 transition group"
            >
              <div class="flex items-center gap-3">
                <div class="p-2 bg-white border border-slate-200 rounded-xl text-red-600 group-hover:bg-red-600 group-hover:text-white transition">
                  <Squares2X2Icon class="w-4 h-4" />
                </div>
                <div>
                  <h4 class="text-xs font-extrabold text-slate-900 group-hover:text-red-700">{{ mod.title }}</h4>
                  <p class="text-[10px] text-slate-500">{{ mod.subtitle }}</p>
                </div>
              </div>
              <ArrowRightIcon class="w-3.5 h-3.5 text-slate-400 group-hover:text-red-600 transition" />
            </div>
          </div>

          <!-- 2. Biodata & Matrimonial Members -->
          <div v-if="results.members && results.members.length > 0" class="space-y-1.5">
            <div class="text-[10px] font-black text-red-600 uppercase tracking-widest px-1">Biodata Directory & Profiles</div>
            <div 
              v-for="m in results.members" 
              :key="'mem-' + m.id"
              @click="navigateTo(m.route)"
              class="p-3 bg-slate-50 hover:bg-red-50/60 rounded-2xl cursor-pointer flex items-center justify-between border border-slate-200/80 transition group"
            >
              <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-full bg-red-100 text-red-700 font-extrabold text-xs flex items-center justify-center">
                  {{ m.title.charAt(0) }}
                </div>
                <div>
                  <h4 class="text-xs font-extrabold text-slate-900 group-hover:text-red-700">{{ m.title }}</h4>
                  <p class="text-[10px] text-slate-500 font-medium">{{ m.subtitle }}</p>
                </div>
              </div>
              <span class="px-2 py-0.5 bg-emerald-50 text-emerald-700 border border-emerald-200 text-[10px] font-bold rounded-full">
                {{ m.status }}
              </span>
            </div>
          </div>

          <!-- 3. Deals & Pipeline -->
          <div v-if="results.deals && results.deals.length > 0" class="space-y-1.5">
            <div class="text-[10px] font-black text-emerald-600 uppercase tracking-widest px-1">Deals & Opportunities</div>
            <div 
              v-for="deal in results.deals" 
              :key="'deal-' + deal.id"
              @click="navigateTo(deal.route)"
              class="p-3 bg-slate-50 hover:bg-emerald-50/60 rounded-2xl cursor-pointer flex items-center justify-between border border-slate-200/80 transition group"
            >
              <div class="flex items-center gap-3">
                <div class="p-2 bg-emerald-100 text-emerald-700 rounded-xl">
                  <BanknotesIcon class="w-4 h-4" />
                </div>
                <div>
                  <h4 class="text-xs font-extrabold text-slate-900 group-hover:text-emerald-700">{{ deal.title }}</h4>
                  <p class="text-[10px] text-slate-500">{{ deal.subtitle }}</p>
                </div>
              </div>
              <ArrowRightIcon class="w-3.5 h-3.5 text-slate-400 group-hover:text-emerald-600 transition" />
            </div>
          </div>

          <!-- 4. Tasks -->
          <div v-if="results.tasks && results.tasks.length > 0" class="space-y-1.5">
            <div class="text-[10px] font-black text-indigo-600 uppercase tracking-widest px-1">Tasks & Reminders</div>
            <div 
              v-for="task in results.tasks" 
              :key="'task-' + task.id"
              @click="navigateTo(task.route)"
              class="p-3 bg-slate-50 hover:bg-indigo-50/60 rounded-2xl cursor-pointer flex items-center justify-between border border-slate-200/80 transition group"
            >
              <div class="flex items-center gap-3">
                <div class="p-2 bg-indigo-100 text-indigo-700 rounded-xl">
                  <ClipboardDocumentCheckIcon class="w-4 h-4" />
                </div>
                <div>
                  <h4 class="text-xs font-extrabold text-slate-900 group-hover:text-indigo-700">{{ task.title }}</h4>
                  <p class="text-[10px] text-slate-500">{{ task.subtitle }}</p>
                </div>
              </div>
              <ArrowRightIcon class="w-3.5 h-3.5 text-slate-400 group-hover:text-indigo-600 transition" />
            </div>
          </div>

          <!-- 5. Employees & Staff -->
          <div v-if="results.employees && results.employees.length > 0" class="space-y-1.5">
            <div class="text-[10px] font-black text-amber-600 uppercase tracking-widest px-1">Team & Staff Users</div>
            <div 
              v-for="emp in results.employees" 
              :key="'emp-' + emp.id"
              @click="navigateTo(emp.route)"
              class="p-3 bg-slate-50 hover:bg-amber-50/60 rounded-2xl cursor-pointer flex items-center justify-between border border-slate-200/80 transition group"
            >
              <div class="flex items-center gap-3">
                <div class="p-2 bg-amber-100 text-amber-800 rounded-xl">
                  <UserGroupIcon class="w-4 h-4" />
                </div>
                <div>
                  <h4 class="text-xs font-extrabold text-slate-900 group-hover:text-amber-800">{{ emp.title }}</h4>
                  <p class="text-[10px] text-slate-500 font-mono">{{ emp.subtitle }}</p>
                </div>
              </div>
              <ArrowRightIcon class="w-3.5 h-3.5 text-slate-400 group-hover:text-amber-600 transition" />
            </div>
          </div>
        </div>

        <!-- Footer Shortcuts -->
        <div class="p-3 bg-slate-50 border-t border-slate-200 text-[11px] text-slate-500 flex items-center justify-between px-5">
          <span>Search CRM database across all active modules</span>
          <span class="flex items-center gap-1 font-mono text-[10px] text-slate-400">
            Press <kbd class="px-1 bg-white border border-slate-200 rounded">ESC</kbd> to close
          </span>
        </div>
      </div>
    </div>
  </Transition>
</template>
