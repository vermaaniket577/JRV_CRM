<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import FreeTrialUpgradeModal from '@/Components/FreeTrialUpgradeModal.vue';
import { 
  HomeIcon,
  UserIcon,
  UserGroupIcon,
  SignalIcon,
  DocumentTextIcon,
  ArrowPathIcon,
  AcademicCapIcon,
  BriefcaseIcon,
  GlobeAltIcon,
  ShieldCheckIcon,
  Square3Stack3DIcon,
  ClipboardDocumentListIcon,
  BuildingOfficeIcon,
  ChartBarIcon,
  HeartIcon,
  CodeBracketIcon,
  EnvelopeIcon,
  AdjustmentsHorizontalIcon,
  MagnifyingGlassIcon,
  LinkIcon,
  StarIcon,
  FolderIcon,
  MegaphoneIcon,
  Cog6ToothIcon,
  ArrowRightOnRectangleIcon,
  SparklesIcon,
  RocketLaunchIcon,
  CloudIcon,
  ClockIcon,
  CreditCardIcon,
  CheckBadgeIcon,
  ChevronLeftIcon,
  ChevronRightIcon
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
  ShieldCheckIcon,
  Square3Stack3DIcon,
  ClipboardDocumentListIcon,
  BuildingOfficeIcon,
  ChartBarIcon,
  HeartIcon,
  CodeBracketIcon,
  EnvelopeIcon,
  AdjustmentsHorizontalIcon,
  LinkIcon,
  StarIcon,
  FolderIcon,
  MegaphoneIcon,
  Cog6ToothIcon,
  CreditCardIcon,
  CheckBadgeIcon,
  ClockIcon
};

const emit = defineEmits(['open-search']);
const page = usePage();

const isCollapsed = ref(localStorage.getItem('sidebar_collapsed') === 'true');
const isUpgradeModalOpen = ref(false);

const toggleCollapse = () => {
  isCollapsed.value = !isCollapsed.value;
  localStorage.setItem('sidebar_collapsed', isCollapsed.value ? 'true' : 'false');
};

const customNavList = computed(() => page.props.custom_nav_list || []);
const businessSettings = computed(() => page.props.business_settings || { business_name: 'JRV CRM', business_icon: '⚡' });
const tenantIndustry = computed(() => page.props.tenant_industry);
const tenantStorage = computed(() => page.props.tenant_storage || { used_gb: 1.25, limit_gb: 5.0, used_percent: 25, is_over_limit: false });
const authUser = computed(() => page.props.auth?.user);

const isMasterAdminPage = computed(() => {
  const url = page.url || '';
  return url.startsWith('/admin') || url.startsWith('/crm-sales-panel') || url.startsWith('/crm-selling-panel');
});

const isCurrentRoute = (path) => {
  if (!path) return false;
  if (path === '/' && page.url === '/') return true;
  return path !== '/' && page.url.startsWith(path);
};

const resolveIcon = (iconName) => {
  return iconMap[iconName] || LinkIcon;
};

const handleKeyDown = (e) => {
  if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
    e.preventDefault();
    emit('open-search');
  }
};

onMounted(() => window.addEventListener('keydown', handleKeyDown));
onUnmounted(() => window.removeEventListener('keydown', handleKeyDown));
</script>

<template>
  <aside 
    :class="[
      'bg-white border-r border-slate-200 hover:border-red-500 flex flex-col justify-between p-4 shrink-0 sticky top-0 h-screen shadow-xs z-40 overflow-y-auto font-sans transition-all duration-100 ease-out relative group/sidebar',
      isCollapsed ? 'w-20 hover:w-64' : 'w-64'
    ]"
  >
    <!-- Border Collapse/Expand Button -->
    <button 
      @click="toggleCollapse"
      class="absolute -right-3 top-7 w-6 h-6 rounded-full bg-white border border-slate-200 hover:border-red-500 shadow-md flex items-center justify-center text-slate-500 hover:text-red-600 transition-all z-50 cursor-pointer"
      :title="isCollapsed ? 'Expand Sidebar Width' : 'Reduce Sidebar Width'"
    >
      <ChevronLeftIcon v-if="!isCollapsed" class="w-3.5 h-3.5 stroke-[2.5]" />
      <ChevronRightIcon v-else class="w-3.5 h-3.5 stroke-[2.5]" />
    </button>

    <!-- Top Section: Logo, Brand & Navigation Menu -->
    <div class="space-y-6">
      <!-- Logo & Brand Name -->
      <Link href="/" class="flex items-center gap-3 group">
        <div class="w-10 h-10 bg-red-600 rounded-2xl flex items-center justify-center text-white font-black text-xl shadow-md shadow-red-600/30 shrink-0">
          {{ businessSettings.business_icon }}
        </div>
        <div class="min-w-0 transition-opacity duration-75" :class="[isCollapsed ? 'opacity-0 group-hover/sidebar:opacity-100 hidden group-hover/sidebar:block' : 'opacity-100']">
          <span class="font-black text-slate-900 text-base tracking-tight block leading-tight truncate">{{ businessSettings.business_name }}</span>
          <span v-if="tenantIndustry" class="text-[10px] font-bold text-red-600 bg-red-50 px-2 py-0.5 rounded-full border border-red-100 inline-flex items-center gap-1 mt-1 truncate">
            <span>{{ tenantIndustry.icon }}</span>
            <span class="truncate">{{ tenantIndustry.name }}</span>
          </span>
        </div>
      </Link>

      <!-- Vertical Sidebar Menu Items -->
      <nav class="space-y-1.5">
        <div 
          class="text-[10px] font-black text-slate-400 uppercase tracking-widest px-2 mb-2 transition-opacity duration-75"
          :class="[isCollapsed ? 'opacity-0 group-hover/sidebar:opacity-100 hidden group-hover/sidebar:block' : 'opacity-100']"
        >
          Main Navigation
        </div>
        
        <template v-if="customNavList && customNavList.length > 0">
          <Link 
            v-for="item in customNavList"
            :key="item.id || item.key"
            :href="item.route || '/'"
            :title="item.label"
            :class="[
              'px-3 py-2.5 rounded-xl text-xs font-bold transition-all flex items-center gap-3',
              isCurrentRoute(item.route)
                ? 'bg-red-50 text-red-600 border border-red-200 shadow-xs' 
                : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900'
            ]"
          >
            <component :is="resolveIcon(item.icon)" :class="['w-5 h-5 shrink-0', isCurrentRoute(item.route) ? 'text-red-600' : 'text-slate-400']" />
            <span 
              class="truncate transition-opacity duration-75"
              :class="[isCollapsed ? 'opacity-0 group-hover/sidebar:opacity-100 hidden group-hover/sidebar:inline' : 'opacity-100']"
            >
              {{ item.label }}
            </span>
          </Link>
        </template>

        <template v-else>
          <Link 
            href="/"
            title="Dashboard"
            :class="[
              'px-3 py-2.5 rounded-xl text-xs font-bold transition-all flex items-center gap-3',
              isCurrentRoute('/') ? 'bg-red-50 text-red-600 border border-red-200' : 'text-slate-600 hover:bg-slate-100'
            ]"
          >
            <ChartBarIcon class="w-5 h-5 text-red-600 shrink-0" />
            <span 
              class="truncate transition-opacity duration-75"
              :class="[isCollapsed ? 'opacity-0 group-hover/sidebar:opacity-100 hidden group-hover/sidebar:inline' : 'opacity-100']"
            >
              Dashboard
            </span>
          </Link>
        </template>
      </nav>
    </div>

    <!-- Bottom Section: Quick Actions & Profile -->
    <div class="space-y-3 pt-4 border-t border-slate-200">
      <!-- 5 GB Free Trial Storage Indicator Card (Click to open Upgrade Plans Pop-up) -->
      <div 
        v-if="!isMasterAdminPage"
        @click="isUpgradeModalOpen = true"
        title="Click to view subscription upgrade plans"
        class="p-2.5 bg-slate-50 hover:bg-red-50/50 border border-slate-200 hover:border-red-300 rounded-2xl space-y-1.5 transition-all cursor-pointer group/card"
        :class="[isCollapsed ? 'opacity-0 group-hover/sidebar:opacity-100 hidden group-hover/sidebar:block' : 'opacity-100']"
      >
        <div class="flex items-center justify-between text-[11px] font-extrabold text-slate-700">
          <span class="flex items-center gap-1">
            <CloudIcon class="w-3.5 h-3.5 text-red-600 shrink-0 group-hover/card:scale-110 transition-transform" />
            <span>5 GB Trial Space</span>
          </span>
          <span :class="tenantStorage.is_over_limit ? 'text-red-600 font-black' : 'text-slate-500 font-bold'">
            {{ tenantStorage.used_gb }} / {{ tenantStorage.limit_gb }} GB
          </span>
        </div>

        <div class="w-full bg-slate-200 h-2 rounded-full overflow-hidden">
          <div 
            class="h-full transition-all duration-300 rounded-full"
            :class="tenantStorage.is_over_limit ? 'bg-red-600' : (tenantStorage.used_percent > 80 ? 'bg-amber-500' : 'bg-red-600')"
            :style="{ width: tenantStorage.used_percent + '%' }"
          ></div>
        </div>

        <div v-if="tenantStorage.is_over_limit" class="text-[9px] text-red-600 font-extrabold bg-red-50 p-1.5 rounded-lg border border-red-200 leading-tight">
          ⚠️ 5 GB Limit Reached! Pay charge / upgrade plan now →
        </div>
        <div v-else class="text-[9px] text-slate-500 group-hover/card:text-red-700 font-medium leading-tight flex items-center justify-between">
          <span>5 GB Trial assigned.</span>
          <span class="font-extrabold text-red-600 underline">Upgrade →</span>
        </div>
      </div>

      <Link 
        href="/admin"
        title="Master Admin Control Panel"
        :class="[
          'w-full px-3 py-2 bg-slate-900 text-white hover:bg-slate-800 rounded-xl text-xs font-extrabold shadow-sm transition-all flex items-center justify-center gap-2',
          isCurrentRoute('/admin') ? 'ring-2 ring-red-500' : ''
        ]"
      >
        <ShieldCheckIcon class="w-4 h-4 text-red-500 stroke-[2.5] shrink-0" />
        <span 
          class="truncate transition-opacity duration-75"
          :class="[isCollapsed ? 'opacity-0 group-hover/sidebar:opacity-100 hidden group-hover/sidebar:inline' : 'opacity-100']"
        >
          Master Admin
        </span>
      </Link>

      <Link 
        href="/crm-selling-panel"
        title="CRM Selling & Provisioning Panel"
        class="w-full px-3 py-2 bg-gradient-to-r from-red-600 to-red-700 text-white hover:from-red-500 hover:to-red-600 rounded-xl text-xs font-extrabold shadow-sm transition-all flex items-center justify-center gap-2"
      >
        <RocketLaunchIcon class="w-4 h-4 text-white stroke-[2.5] shrink-0" />
        <span 
          class="truncate transition-opacity duration-75"
          :class="[isCollapsed ? 'opacity-0 group-hover/sidebar:opacity-100 hidden group-hover/sidebar:inline' : 'opacity-100']"
        >
          CRM Selling Panel
        </span>
      </Link>

      <Link 
        href="/onboarding"
        title="Switch Industry Sector"
        class="w-full px-3 py-2 bg-amber-50 border border-amber-200 text-amber-800 hover:bg-amber-100 rounded-xl text-xs font-bold transition-all flex items-center justify-center gap-2"
      >
        <SparklesIcon class="w-4 h-4 text-amber-600 shrink-0" />
        <span 
          class="truncate transition-opacity duration-75"
          :class="[isCollapsed ? 'opacity-0 group-hover/sidebar:opacity-100 hidden group-hover/sidebar:inline' : 'opacity-100']"
        >
          Switch Sector
        </span>
      </Link>

      <Link 
        href="/tenant/settings/navigation"
        title="Customize Menu"
        class="w-full px-3 py-2 bg-red-50 border border-red-200 text-red-700 hover:bg-red-100 rounded-xl text-xs font-bold transition-all flex items-center justify-center gap-2"
      >
        <AdjustmentsHorizontalIcon class="w-4 h-4 text-red-600 shrink-0" />
        <span 
          class="truncate transition-opacity duration-75"
          :class="[isCollapsed ? 'opacity-0 group-hover/sidebar:opacity-100 hidden group-hover/sidebar:inline' : 'opacity-100']"
        >
          Customize Menu
        </span>
      </Link>

      <button 
        @click="emit('open-search')"
        title="Quick Search (⌘K)"
        class="w-full px-3 py-2 bg-slate-100 border border-slate-200 hover:bg-slate-200/70 rounded-xl text-xs font-semibold text-slate-600 transition-all flex items-center justify-between cursor-pointer"
      >
        <div class="flex items-center gap-2 min-w-0">
          <MagnifyingGlassIcon class="w-4 h-4 text-slate-500 shrink-0" />
          <span 
            class="truncate transition-opacity duration-75"
            :class="[isCollapsed ? 'opacity-0 group-hover/sidebar:opacity-100 hidden group-hover/sidebar:inline' : 'opacity-100']"
          >
            Quick Search
          </span>
        </div>
        <kbd 
          class="px-1.5 py-0.5 bg-white text-slate-500 text-[10px] rounded border border-slate-300 font-mono shadow-xs shrink-0"
          :class="[isCollapsed ? 'hidden group-hover/sidebar:inline-block' : 'inline-block']"
        >
          ⌘K
        </kbd>
      </button>

      <!-- User Profile Box with Logout -->
      <div class="flex items-center justify-between p-2 rounded-xl bg-slate-50 border border-slate-200">
        <div class="flex items-center gap-2.5 min-w-0">
          <div class="w-8 h-8 rounded-full bg-red-600 text-white font-black flex items-center justify-center text-xs shadow-xs shrink-0">
            {{ authUser?.name ? authUser.name.charAt(0) : businessSettings.business_icon }}
          </div>
          <div 
            class="min-w-0 transition-opacity duration-200"
            :class="[isCollapsed ? 'opacity-0 group-hover/sidebar:opacity-100 hidden group-hover/sidebar:block' : 'opacity-100']"
          >
            <div class="text-xs font-extrabold text-slate-900 truncate">{{ authUser?.name || businessSettings.business_name }}</div>
            <div class="text-[10px] text-slate-500 font-medium truncate">{{ authUser?.email || 'Admin Workspace' }}</div>
          </div>
        </div>
        <Link 
          href="/logout" 
          method="post" 
          as="button" 
          title="Logout of Account"
          class="px-2 py-1 bg-red-50 hover:bg-red-100 text-red-600 font-extrabold text-[11px] rounded-lg border border-red-200 transition-all flex items-center gap-1 shrink-0 cursor-pointer shadow-2xs"
          :class="[isCollapsed ? 'hidden group-hover/sidebar:flex' : 'flex']"
        >
          <ArrowRightOnRectangleIcon class="w-3.5 h-3.5 stroke-[2.5]" />
          <span 
            class="truncate transition-opacity duration-75"
            :class="[isCollapsed ? 'hidden group-hover/sidebar:inline' : 'inline']"
          >
            Logout
          </span>
        </Link>
      </div>
    </div>

    <!-- Free Trial Subscription Upgrade Modal Popup -->
    <FreeTrialUpgradeModal 
      :is-open="isUpgradeModalOpen" 
      :used-gb="tenantStorage.used_gb" 
      :limit-gb="tenantStorage.limit_gb" 
      @close="isUpgradeModalOpen = false" 
    />
  </aside>
</template>

<style scoped>
/* Clean custom scrollbar for left navigation bar */
aside::-webkit-scrollbar {
  width: 4px;
}
aside::-webkit-scrollbar-track {
  background: transparent;
}
aside::-webkit-scrollbar-thumb {
  background: #cbd5e1;
  border-radius: 4px;
}
aside::-webkit-scrollbar-thumb:hover {
  background: #ef4444;
}
</style>
