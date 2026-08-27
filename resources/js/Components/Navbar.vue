<script setup>
import { ref, computed } from 'vue';
import { usePage, Link } from '@inertiajs/vue3';
import GlobalSearchModal from '@/Components/GlobalSearchModal.vue';
import FreeTrialUpgradeModal from '@/Components/FreeTrialUpgradeModal.vue';
import CookieConsentBanner from '@/Components/CookieConsentBanner.vue';
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
  ChevronLeftIcon,
  ChevronRightIcon,
  SparklesIcon,
  ArrowRightOnRectangleIcon,
  CloudIcon,
  RocketLaunchIcon,
  CircleStackIcon,
  KeyIcon,
  ShieldCheckIcon,
  Square3Stack3DIcon,
  ClipboardDocumentListIcon,
  BuildingOfficeIcon,
  BuildingOffice2Icon,
  ChartBarIcon,
  ChartPieIcon,
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
  CreditCardIcon,
  CheckBadgeIcon,
  ClockIcon,
  TableCellsIcon,
  ArrowDownTrayIcon,
  CpuChipIcon,
  CurrencyRupeeIcon,
  CurrencyDollarIcon,
  BookOpenIcon,
  ClipboardDocumentCheckIcon
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
  BuildingOffice2Icon,
  ChartBarIcon,
  ChartPieIcon,
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
  CreditCardIcon,
  CheckBadgeIcon,
  ClockIcon,
  TableCellsIcon,
  ArrowDownTrayIcon,
  SparklesIcon,
  CpuChipIcon,
  CurrencyRupeeIcon,
  CurrencyDollarIcon,
  BookOpenIcon,
  ClipboardDocumentCheckIcon
};

const emit = defineEmits(['open-search']);
const page = usePage();

const isCollapsed = ref(localStorage.getItem('sidebar_collapsed') === 'true');
const isUpgradeModalOpen = ref(false);
const isSearchOpen = ref(false);

const toggleCollapse = () => {
  isCollapsed.value = !isCollapsed.value;
  localStorage.setItem('sidebar_collapsed', isCollapsed.value ? 'true' : 'false');
};

const customNavList = computed(() => {
  const list = page.props.custom_nav_list || [];
  const seenKeys = new Set();
  const seenRoutes = new Set();
  return list.filter(item => {
    if (!item.is_enabled) return false;
    const key = item.key || item.label;
    const route = (item.route || '/').split('?')[0].replace(/\/$/, '') || '/';
    if (seenKeys.has(key) || (route !== '/' && seenRoutes.has(route))) {
      return false;
    }
    seenKeys.add(key);
    if (route !== '/') seenRoutes.add(route);
    return true;
  });
});
const businessSettings = computed(() => page.props.business_settings || { business_name: 'JRV CRM', business_icon: '⚡' });
const tenantIndustry = computed(() => page.props.tenant_industry);
const currentTenant = computed(() => page.props.current_tenant);
const tenantStorage = computed(() => page.props.tenant_storage || { used_gb: 1.25, limit_gb: 5.0, used_percent: 25, is_over_limit: false });
const authUser = computed(() => page.props.auth?.user);
const isMasterAdmin = computed(() => Boolean(page.props.auth?.is_master_admin || authUser.value?.is_super_admin));

const isMasterAdminPage = computed(() => {
  const url = page.url || '';
  return url.startsWith('/admin') || url.startsWith('/crm-sales-panel') || url.startsWith('/crm-selling-panel');
});

const isCurrentRoute = (path) => {
  if (!path) return false;
  const currentUrl = page.url || '/';
  
  if (currentUrl === path) return true;
  
  const currentPath = currentUrl.split('?')[0].replace(/\/$/, '') || '/';
  const targetPath = path.split('?')[0].replace(/\/$/, '') || '/';
  
  if (path.includes('?')) {
    const targetQuery = path.split('?')[1];
    return currentPath === targetPath && currentUrl.includes(targetQuery);
  }
  
  if (currentPath === targetPath) {
    return true;
  }
  
  if (targetPath !== '/' && targetPath !== '/matrimonial' && targetPath !== '/admin' && currentPath.startsWith(targetPath + '/')) {
    return true;
  }
  
  return false;
};

const resolveIcon = (iconName) => {
  return iconMap[iconName] || LinkIcon;
};
</script>

<template>
  <aside 
    :class="[
      'fixed top-0 left-0 bottom-0 h-screen bg-white border-r border-slate-200/90 transition-all duration-300 z-40 flex flex-col justify-between p-4 select-none group/sidebar overflow-y-auto',
      isCollapsed ? 'w-20 hover:w-64 shadow-lg' : 'w-64 shadow-xs'
    ]"
  >
    <!-- Top Section: Logo & Navigation Items -->
    <div class="space-y-5">
      <!-- Top Brand Header with Toggle Collapse Arrow -->
      <div class="flex items-center justify-between relative">
        <Link 
          href="/" 
          class="flex items-center gap-3 overflow-hidden cursor-pointer"
        >
          <!-- Dynamic Sector & Business Logo Icon Box -->
          <div class="w-10 h-10 rounded-xl bg-red-600 text-white font-bold flex items-center justify-center text-lg shadow-sm shadow-red-600/25 shrink-0">
            {{ businessSettings.business_icon }}
          </div>

          <!-- Dynamic Business Name & Sector Details -->
          <div 
            class="flex flex-col min-w-0 transition-opacity duration-200"
            :class="[isCollapsed ? 'opacity-0 group-hover/sidebar:opacity-100 hidden group-hover/sidebar:flex' : 'opacity-100 flex']"
          >
            <span class="font-bold text-sm text-slate-900 tracking-tight truncate">
              {{ businessSettings.business_name }}
            </span>
            <span class="text-xs text-slate-500 font-medium truncate flex items-center gap-1">
              <span>{{ tenantIndustry?.name || 'CRM SaaS' }}</span>
              <span class="text-red-500 font-bold">●</span>
            </span>
          </div>
        </Link>

        <!-- Toggle Collapse Button -->
        <button
          @click="toggleCollapse"
          class="w-7 h-7 rounded-lg border border-slate-200 bg-slate-50 hover:bg-slate-100 text-slate-500 flex items-center justify-center transition-colors cursor-pointer shrink-0"
          :title="isCollapsed ? 'Expand sidebar' : 'Collapse sidebar'"
        >
          <ChevronRightIcon v-if="isCollapsed" class="w-4 h-4" />
          <ChevronLeftIcon v-else class="w-4 h-4" />
        </button>
      </div>

      <!-- Current Sector Quick Switch Badge -->
      <Link 
        v-if="tenantIndustry"
        href="/onboarding"
        title="Click to switch CRM sector"
        class="block p-2 rounded-xl bg-slate-50 border border-slate-200/80 hover:bg-red-50/60 hover:border-red-200 transition group/badge"
        :class="[isCollapsed ? 'opacity-0 group-hover/sidebar:opacity-100 hidden group-hover/sidebar:block' : 'opacity-100']"
      >
        <div class="flex items-center justify-between text-xs">
          <span class="text-slate-500 font-normal group-hover/badge:text-red-600 transition-colors">Sector</span>
          <span class="font-medium text-slate-800 flex items-center gap-1">
            <span>{{ tenantIndustry.icon }}</span>
            <span class="truncate">{{ tenantIndustry.name }}</span>
          </span>
        </div>
      </Link>

      <!-- Dedicated Subdomain & Database Health Badge -->
      <div 
        v-if="currentTenant?.subdomain"
        class="p-2 rounded-xl bg-slate-50 border border-slate-200/80 space-y-1 text-xs"
        :class="[isCollapsed ? 'opacity-0 group-hover/sidebar:opacity-100 hidden group-hover/sidebar:block' : 'opacity-100']"
      >
        <div class="flex items-center justify-between">
          <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Subdomain</span>
          <span class="font-mono font-bold text-red-600 text-[11px] truncate max-w-[120px]">{{ currentTenant.subdomain }}.jrvcrm.com</span>
        </div>
        <div class="flex items-center justify-between text-[10px] text-slate-500 pt-0.5 border-t border-slate-100">
          <span class="flex items-center gap-1">
            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
            <span>DB: {{ currentTenant.database_name ? 'Dedicated' : 'Isolated' }}</span>
          </span>
          <span class="font-bold text-emerald-700">Active</span>
        </div>
      </div>

      <!-- Global Search Bar Button -->
      <button 
        @click="isSearchOpen = true"
        title="Global Search (⌘K / Ctrl+K)"
        class="w-full px-3 py-2 bg-slate-50 hover:bg-slate-100 border border-slate-200/90 rounded-xl text-xs font-medium text-slate-600 transition flex items-center justify-between cursor-pointer group shadow-2xs"
      >
        <div class="flex items-center gap-2">
          <MagnifyingGlassIcon class="w-4 h-4 text-slate-400 group-hover:text-red-600 transition" />
          <span :class="[isCollapsed ? 'opacity-0 group-hover/sidebar:opacity-100 hidden group-hover/sidebar:inline' : 'opacity-100']">
            Search CRM...
          </span>
        </div>
        <kbd 
          class="px-1.5 py-0.5 bg-white text-slate-400 group-hover:text-red-600 text-[10px] font-mono font-semibold rounded border border-slate-200"
          :class="[isCollapsed ? 'opacity-0 group-hover/sidebar:opacity-100 hidden group-hover/sidebar:inline' : 'opacity-100']"
        >
          ⌘K
        </kbd>
      </button>

      <!-- Vertical Sidebar Menu Items -->
      <nav class="space-y-1">
        <div 
          class="text-xs font-semibold text-slate-400 uppercase tracking-wider px-2.5 mb-1.5 transition-opacity duration-75"
          :class="[isCollapsed ? 'opacity-0 group-hover/sidebar:opacity-100 hidden group-hover/sidebar:block' : 'opacity-100']"
        >
          Navigation
        </div>
        
        <template v-if="customNavList && customNavList.length > 0">
          <Link 
            v-for="item in customNavList"
            :key="item.id || item.key"
            :href="item.route || '/'"
            :title="item.label"
            :class="[
              'px-3 py-2 rounded-xl text-sm font-medium transition-all flex items-center gap-3',
              isCurrentRoute(item.route)
                ? 'bg-red-50 text-red-600 font-semibold border border-red-100' 
                : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900'
            ]"
          >
            <component :is="resolveIcon(item.icon)" :class="['w-4 h-4 shrink-0', isCurrentRoute(item.route) ? 'text-red-600' : 'text-slate-400']" />
            <span 
              class="truncate transition-opacity duration-75"
              :class="[isCollapsed ? 'opacity-0 group-hover/sidebar:opacity-100 hidden group-hover/sidebar:inline' : 'opacity-100']"
            >
              {{ item.label }}
            </span>
          </Link>
        </template>

        <!-- Dedicated Dynamic CRM Database & Custom Columns Hub -->
        <Link 
          href="/tenant/crm-records"
          title="Dynamic Database & Columns"
          :class="[
            'px-3 py-2 rounded-xl text-sm font-medium transition-all flex items-center gap-3 mt-1.5',
            isCurrentRoute('/tenant/crm-records')
              ? 'bg-red-600 text-white font-semibold shadow-xs' 
              : 'text-red-700 bg-red-50/70 border border-red-100 hover:bg-red-100/70'
          ]"
        >
          <CircleStackIcon :class="['w-4 h-4 shrink-0', isCurrentRoute('/tenant/crm-records') ? 'text-white' : 'text-red-600']" />
          <span 
            class="truncate font-semibold transition-opacity duration-75"
            :class="[isCollapsed ? 'opacity-0 group-hover/sidebar:opacity-100 hidden group-hover/sidebar:inline' : 'opacity-100']"
          >
            Database Hub
          </span>
        </Link>

        <!-- Customer Payment Plans & Invoices Hub -->
        <Link 
          href="/payment-plans"
          title="Customer Payment Plans & Invoices"
          :class="[
            'px-3 py-2 rounded-xl text-sm font-medium transition-all flex items-center gap-3 mt-1',
            isCurrentRoute('/payment-plans')
              ? 'bg-red-600 text-white font-semibold shadow-xs' 
              : 'text-slate-700 bg-slate-50/80 border border-slate-200/80 hover:bg-slate-100 hover:text-slate-900'
          ]"
        >
          <CreditCardIcon :class="['w-4 h-4 shrink-0', isCurrentRoute('/payment-plans') ? 'text-white' : 'text-slate-600']" />
          <span 
            class="truncate font-semibold transition-opacity duration-75"
            :class="[isCollapsed ? 'opacity-0 group-hover/sidebar:opacity-100 hidden group-hover/sidebar:inline' : 'opacity-100']"
          >
            Payment Plans
          </span>
        </Link>
      </nav>
    </div>

    <!-- Bottom Section: Quick Actions & Profile -->
    <div class="space-y-2.5 pt-3 border-t border-slate-200">
      <!-- 5 GB Free Trial Storage Indicator Card -->
      <div 
        v-if="!isMasterAdminPage"
        @click="isUpgradeModalOpen = true"
        title="Click to view subscription upgrade plans"
        class="p-2.5 bg-slate-50 hover:bg-red-50/40 border border-slate-200 rounded-xl space-y-1.5 transition cursor-pointer group/card"
        :class="[isCollapsed ? 'opacity-0 group-hover/sidebar:opacity-100 hidden group-hover/sidebar:block' : 'opacity-100']"
      >
        <div class="flex items-center justify-between text-xs font-medium text-slate-700">
          <span class="flex items-center gap-1.5">
            <CloudIcon class="w-3.5 h-3.5 text-red-600 shrink-0" />
            <span>5 GB Trial</span>
          </span>
          <span class="text-slate-500 font-normal">
            {{ tenantStorage.used_gb }} / {{ tenantStorage.limit_gb }} GB
          </span>
        </div>

        <div class="w-full bg-slate-200 h-1.5 rounded-full overflow-hidden">
          <div 
            class="h-full transition-all duration-300 rounded-full"
            :class="tenantStorage.is_over_limit ? 'bg-red-600' : (tenantStorage.used_percent > 80 ? 'bg-amber-500' : 'bg-red-600')"
            :style="{ width: tenantStorage.used_percent + '%' }"
          ></div>
        </div>

        <div class="text-[11px] text-slate-500 font-normal leading-tight flex items-center justify-between">
          <span>Free quota active</span>
          <span class="font-semibold text-red-600 hover:underline">Upgrade →</span>
        </div>
      </div>

      <!-- Master Admin & Selling Panel Action Buttons (STRICTLY Master Admin Panel Only) -->
      <template v-if="isMasterAdmin && isMasterAdminPage">
        <Link 
          href="/admin"
          title="Master Admin Control Panel"
          :class="[
            'w-full px-3 py-2 bg-slate-900 text-white hover:bg-slate-800 rounded-xl text-xs font-semibold shadow-xs transition flex items-center justify-center gap-2',
            isCurrentRoute('/admin') ? 'ring-2 ring-red-500' : ''
          ]"
        >
          <ShieldCheckIcon class="w-4 h-4 text-red-500 shrink-0" />
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
          class="w-full px-3 py-2 bg-red-600 hover:bg-red-700 text-white rounded-xl text-xs font-semibold shadow-xs transition flex items-center justify-center gap-2"
        >
          <RocketLaunchIcon class="w-4 h-4 text-white shrink-0" />
          <span 
            class="truncate transition-opacity duration-75"
            :class="[isCollapsed ? 'opacity-0 group-hover/sidebar:opacity-100 hidden group-hover/sidebar:inline' : 'opacity-100']"
          >
            CRM Selling Panel
          </span>
        </Link>
      </template>

      <div :class="[isCollapsed ? 'hidden group-hover/sidebar:block' : 'block']">
        <Link 
          href="/tenant/settings/navigation"
          title="Customize Menu"
          class="w-full px-2.5 py-1.5 bg-slate-50 border border-slate-200 text-slate-700 hover:bg-slate-100 hover:text-slate-900 rounded-lg text-xs font-semibold transition flex items-center justify-center gap-1.5"
        >
          <AdjustmentsHorizontalIcon class="w-3.5 h-3.5 text-slate-500" />
          <span>Customize Menu</span>
        </Link>
      </div>

      <!-- User Profile Box with Logout -->
      <div class="flex items-center justify-between p-2 rounded-xl bg-slate-50 border border-slate-200">
        <div class="flex items-center gap-2.5 min-w-0">
          <div class="w-7 h-7 rounded-lg bg-red-600 text-white font-bold flex items-center justify-center text-xs shrink-0">
            {{ authUser?.name ? authUser.name.charAt(0) : 'A' }}
          </div>
          <div 
            class="min-w-0 transition-opacity duration-200"
            :class="[isCollapsed ? 'opacity-0 group-hover/sidebar:opacity-100 hidden group-hover/sidebar:block' : 'opacity-100']"
          >
            <div class="text-xs font-semibold text-slate-900 truncate">{{ authUser?.name || businessSettings.business_name }}</div>
            <div class="text-[11px] text-slate-500 font-normal truncate">{{ authUser?.email || 'Admin Workspace' }}</div>
          </div>
        </div>
        <Link 
          href="/logout" 
          method="post" 
          as="button" 
          title="Logout of Account"
          class="px-2 py-1 bg-white hover:bg-red-50 text-slate-600 hover:text-red-600 font-medium text-xs rounded-lg border border-slate-200 hover:border-red-200 transition flex items-center gap-1 shrink-0 cursor-pointer shadow-2xs"
          :class="[isCollapsed ? 'hidden group-hover/sidebar:flex' : 'flex']"
        >
          <ArrowRightOnRectangleIcon class="w-3.5 h-3.5" />
          <span 
            class="truncate transition-opacity duration-75"
            :class="[isCollapsed ? 'hidden group-hover/sidebar:inline' : 'inline']"
          >
            Logout
          </span>
        </Link>
      </div>
    </div>

    <!-- Global Search Modal -->
    <GlobalSearchModal 
      :is-open="isSearchOpen" 
      @close="isSearchOpen = false" 
    />

    <!-- Free Trial Subscription Upgrade Modal Popup -->
    <FreeTrialUpgradeModal 
      :is-open="isUpgradeModalOpen" 
      :used-gb="tenantStorage.used_gb" 
      :limit-gb="tenantStorage.limit_gb" 
      @close="isUpgradeModalOpen = false" 
    />

    <!-- Global GDPR Cookie Consent Banner -->
    <CookieConsentBanner />
  </aside>

  <!-- Layout Spacer (preserves space in flex containers for the fixed sidebar) -->
  <div 
    aria-hidden="true" 
    :class="[
      'shrink-0 transition-all duration-300 pointer-events-none hidden md:block',
      isCollapsed ? 'w-20' : 'w-64'
    ]"
  />
</template>

<style scoped>
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
