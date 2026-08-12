<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import Navbar from '@/Components/Navbar.vue';
import GlobalSearchModal from '@/Components/GlobalSearchModal.vue';
import CreateUserModal from '@/Components/CreateUserModal.vue';
import { 
  BellIcon, 
  UserGroupIcon, 
  HomeIcon, 
  UserIcon, 
  SignalIcon, 
  DocumentTextIcon, 
  ArrowPathIcon,
  AcademicCapIcon,
  BriefcaseIcon,
  GlobeAltIcon,
  PlusIcon,
  XMarkIcon,
  AdjustmentsHorizontalIcon,
  EnvelopeIcon,
  CheckCircleIcon,
  SparklesIcon,
  ChartBarIcon,
  RocketLaunchIcon,
  FunnelIcon,
  CreditCardIcon,
  ClipboardDocumentListIcon,
  LinkIcon
} from '@heroicons/vue/24/outline';

const iconMap = {
  HomeIcon,
  UserIcon,
  UserGroupIcon,
  SignalIcon,
  DocumentTextIcon,
  ArrowPathIcon,
  AcademicCapIcon,
  BriefcaseIcon,
  GlobeAltIcon,
  ChartBarIcon,
  RocketLaunchIcon,
  FunnelIcon,
  CreditCardIcon,
  ClipboardDocumentListIcon
};

const props = defineProps({
  metrics: Object,
  users: Array,
  filters: Object,
});

const page = usePage();
const customNavList = computed(() => page.props.custom_nav_list || []);
const customNav = computed(() => page.props.custom_nav || {});
const businessSettings = computed(() => page.props.business_settings || { business_name: 'JRV CRM', business_icon: '♥' });
const authUser = computed(() => page.props.auth?.user);

const getNavLabel = (key, fallback) => customNav.value[key]?.label || fallback;

const resolveIcon = (iconName) => iconMap[iconName] || LinkIcon;

const isCurrentRoute = (path) => {
  if (!path) return false;
  if (path === '/online-users' && page.url.startsWith('/online-users')) return true;
  if (path === '/' && page.url === '/') return true;
  return path !== '/' && page.url.startsWith(path);
};

const isSearchOpen = ref(false);
const isCreateUserModalOpen = ref(false);

const enterFrom = ref(props.filters.enter_from || '');
const enterTo = ref(props.filters.enter_to || '');
const activityFrom = ref(props.filters.activity_from || '');
const activityTo = ref(props.filters.activity_to || '');
const specialCase = ref(props.filters.special_case || '');
const religiousVerification = ref(props.filters.religious_verification || '');
const sortMode = ref(props.filters.sort_mode || 'Name');
const sortOrder = ref(props.filters.sort_order || 'Asc');

const clearFilter = () => {
  enterFrom.value = '';
  enterTo.value = '';
  activityFrom.value = '';
  activityTo.value = '';
  specialCase.value = '';
  religiousVerification.value = '';
  sortMode.value = 'Name';
  sortOrder.value = 'Asc';

  router.get('/online-users');
};
</script>

<template>
  <Head title="Customer Directory - CRM" />

  <div class="min-h-screen bg-slate-100 flex font-sans text-slate-900">
    <!-- Navbar (Fixed Left Vertical Sidebar) -->
    <Navbar @open-search="isSearchOpen = true" />

    <!-- RIGHT MAIN CONTENT AREA -->
    <div class="flex-1 flex flex-col min-w-0">
      <!-- Top Action Bar (Red & Indigo Pill Buttons) -->
      <header class="bg-white border-b border-slate-200 px-6 py-3.5 flex flex-wrap items-center justify-between gap-3 shadow-xs">
        <div class="flex flex-wrap items-center gap-2">
          <button class="px-4 py-1.5 bg-red-600 hover:bg-red-700 text-white font-extrabold text-xs rounded-xl shadow-xs transition-all">
            My Work
          </button>
          <button class="px-4 py-1.5 bg-red-600 hover:bg-red-700 text-white font-extrabold text-xs rounded-xl shadow-xs transition-all">
            Login History
          </button>
          <button class="px-4 py-1.5 bg-red-600 hover:bg-red-700 text-white font-extrabold text-xs rounded-xl shadow-xs transition-all">
            My Task
          </button>

          <Link href="/crm-selling-panel" class="px-4 py-1.5 bg-gradient-to-r from-emerald-600 to-indigo-600 hover:from-emerald-500 hover:to-indigo-500 text-white font-extrabold text-xs rounded-xl shadow-xs transition-all flex items-center gap-1.5">
            <SparklesIcon class="w-4 h-4" />
            <span>CRM Selling Panel</span>
          </Link>

          <Link href="/tenant/settings/navigation" class="px-4 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white font-extrabold text-xs rounded-xl shadow-xs transition-all flex items-center gap-1.5">
            <AdjustmentsHorizontalIcon class="w-4 h-4" />
            <span>Customize Menu Names</span>
          </Link>
        </div>

        <div class="flex items-center gap-3">
          <button @click="isSearchOpen = true" class="p-2 bg-slate-100 rounded-full text-slate-600 hover:bg-slate-200 relative">
            <BellIcon class="w-5 h-5" />
            <span class="absolute -top-1 -right-1 bg-red-600 text-white text-[10px] font-black w-4 h-4 rounded-full flex items-center justify-center">
              2
            </span>
          </button>

          <div class="w-9 h-9 bg-indigo-600 rounded-full flex items-center justify-center text-white font-black text-sm shadow-md">
            {{ authUser?.name ? authUser.name.charAt(0) : 'A' }}
          </div>
        </div>
      </header>

      <!-- Main Scrollable Page Area -->
      <div class="p-8 space-y-6 flex-1 overflow-y-auto">
        <!-- Title & Action Button Bar -->
        <div class="space-y-1">
          <button @click="clearFilter" class="text-xs font-bold text-red-600 hover:text-red-700 flex items-center gap-1">
            <XMarkIcon class="w-3.5 h-3.5 stroke-[3]" />
            <span>Clear Filters</span>
          </button>

          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pt-1">
            <div>
              <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">
                {{ getNavLabel('online_user', 'Online User') }}
              </h1>
              <p class="text-xs text-slate-500 font-medium mt-0.5">Manage organization accounts and user access</p>
            </div>

            <!-- Red Pill + New User Button -->
            <button 
              @click="isCreateUserModalOpen = true"
              class="px-5 py-2.5 bg-red-600 hover:bg-red-700 text-white font-extrabold text-xs rounded-2xl shadow-md shadow-red-600/25 flex items-center gap-1.5 transition-all cursor-pointer"
            >
              <PlusIcon class="w-4 h-4 stroke-[3]" />
              <span>+ New User</span>
            </button>
          </div>
        </div>

        <!-- 3 Rows of 6 Pastel Metric Cards Grid -->
        <div class="space-y-4">
          <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4">
            <div v-for="(item, idx) in metrics.row1" :key="idx" :class="['p-5 rounded-2xl shadow-xs text-center space-y-1', item.bg]">
              <div class="text-2xl font-black">{{ item.count }}</div>
              <div class="text-xs font-bold leading-tight">{{ item.label }}</div>
            </div>
          </div>

          <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4">
            <div v-for="(item, idx) in metrics.row2" :key="idx" :class="['p-5 rounded-2xl shadow-xs text-center space-y-1', item.bg]">
              <div class="text-2xl font-black">{{ item.ratio || item.count }}</div>
              <div class="text-xs font-bold leading-tight">{{ item.label }}</div>
            </div>
          </div>

          <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4">
            <div v-for="(item, idx) in metrics.row3" :key="idx" :class="['p-5 rounded-2xl shadow-xs text-center space-y-1', item.bg]">
              <div class="text-2xl font-black">{{ item.count }}</div>
              <div class="text-xs font-bold leading-tight">{{ item.label }}</div>
            </div>
          </div>
        </div>

        <!-- Filter Banner & Users Table Content Area -->
        <div class="bg-white border border-slate-200 rounded-3xl p-6 shadow-xs space-y-4">
          <div class="flex items-center justify-between">
            <div class="p-3 bg-amber-100/70 border border-amber-200 rounded-xl text-xs font-bold text-amber-900 flex items-center gap-2">
              <CheckCircleIcon class="w-4 h-4 text-amber-700 shrink-0" />
              <span>Available in : Biodata | offline | WhatsApp response | paid promotion | social media | group service</span>
            </div>

            <div class="text-xs font-bold text-slate-500">
              Total {{ users?.length || 0 }} User(s)
            </div>
          </div>

          <!-- Table Header Controls & Filter Tabs -->
          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-2">
            <div class="flex items-center gap-2">
              <span class="text-xs font-black text-slate-900 uppercase tracking-wider">Show Account Types:</span>
              <span class="px-3 py-1 rounded-full bg-slate-900 text-white text-[11px] font-extrabold shadow-2xs">
                All Users ({{ users?.length || 0 }})
              </span>
              <span class="px-3 py-1 rounded-full bg-emerald-50 text-emerald-800 border border-emerald-200 text-[11px] font-extrabold shadow-2xs">
                💎 Paid Users ({{ users?.filter(u => u.account_type === 'paid').length || 0 }})
              </span>
              <span class="px-3 py-1 rounded-full bg-amber-50 text-amber-800 border border-amber-200 text-[11px] font-extrabold shadow-2xs">
                🎁 Free Trial Users ({{ users?.filter(u => u.account_type === 'free').length || 0 }})
              </span>
            </div>
          </div>

          <!-- Table of Created Users -->
          <div v-if="users && users.length > 0" class="overflow-x-auto rounded-2xl border border-slate-200">
            <table class="w-full text-left text-xs font-medium text-slate-700">
              <thead class="bg-slate-50 border-b border-slate-200 text-slate-500 font-extrabold uppercase tracking-wider text-[10px]">
                <tr>
                  <th class="px-5 py-3.5">User</th>
                  <th class="px-5 py-3.5">Email</th>
                  <th class="px-5 py-3.5">Role</th>
                  <th class="px-5 py-3.5">Account Type & Plan</th>
                  <th class="px-5 py-3.5">Status</th>
                  <th class="px-5 py-3.5">Joined Date</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100 bg-white">
                <tr v-for="user in users" :key="user.id" class="hover:bg-slate-50/80 transition-colors">
                  <td class="px-5 py-4 flex items-center gap-3">
                    <div class="w-9 h-9 rounded-full bg-red-600 text-white font-extrabold flex items-center justify-center text-sm shadow-xs">
                      {{ user.name ? user.name.charAt(0).toUpperCase() : 'U' }}
                    </div>
                    <div>
                      <div class="font-bold text-slate-900 text-sm">{{ user.name }}</div>
                      <div class="text-[11px] text-slate-400 font-medium">ID #{{ user.id }}</div>
                    </div>
                  </td>
                  <td class="px-5 py-4 font-mono text-slate-600">
                    <div class="flex items-center gap-1.5">
                      <EnvelopeIcon class="w-3.5 h-3.5 text-slate-400" />
                      <span>{{ user.email }}</span>
                    </div>
                  </td>
                  <td class="px-5 py-4">
                    <span :class="['px-2.5 py-1 rounded-full text-[10px] font-extrabold uppercase tracking-wider', user.is_tenant_admin ? 'bg-indigo-100 text-indigo-800' : 'bg-slate-100 text-slate-700']">
                      {{ user.is_tenant_admin ? 'Tenant Admin' : 'Staff User' }}
                    </span>
                  </td>
                  <td class="px-5 py-4">
                    <span v-if="user.account_type === 'paid'" class="whitespace-nowrap inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-extrabold uppercase bg-emerald-50 text-emerald-800 border border-emerald-200">
                      <span>💎</span>
                      <span>Paid Account</span>
                    </span>
                    <span v-else class="whitespace-nowrap inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-extrabold uppercase bg-amber-50 text-amber-800 border border-amber-200">
                      <span>🎁</span>
                      <span>Free Trial User</span>
                    </span>
                  </td>
                  <td class="px-5 py-4">
                    <span :class="['px-2.5 py-1 rounded-full text-[10px] font-extrabold uppercase tracking-wider', user.status === 'active' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800']">
                      {{ user.status || 'Active' }}
                    </span>
                  </td>
                  <td class="px-5 py-4 text-slate-500 font-medium">
                    {{ user.created_at ? new Date(user.created_at).toLocaleDateString() : 'Today' }}
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <div v-else class="text-center py-16 space-y-3">
            <div class="w-12 h-12 rounded-full bg-slate-100 text-slate-400 mx-auto flex items-center justify-center">
              <UserIcon class="w-6 h-6" />
            </div>
            <p class="text-base font-bold text-slate-600">No users found</p>
            <p class="text-xs text-slate-400 font-medium">Click "+ New User" above to create your first user account.</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Modals -->
    <GlobalSearchModal :is-open="isSearchOpen" @close="isSearchOpen = false" />
    <CreateUserModal :is-open="isCreateUserModalOpen" @close="isCreateUserModalOpen = false" />
  </div>
</template>
