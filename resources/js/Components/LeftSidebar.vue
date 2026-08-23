<script setup>
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
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

const page = usePage();
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

const resolveIcon = (iconName) => iconMap[iconName] || LinkIcon;

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
</script>

<template>
  <aside class="w-24 bg-white border-r border-slate-200 flex flex-col items-center py-4 space-y-6 shrink-0 shadow-xs z-30 min-h-screen">
    <!-- Logo -->
    <Link href="/" class="flex flex-col items-center gap-1 group">
      <div class="w-12 h-12 bg-red-600 rounded-2xl flex items-center justify-center text-white font-black text-xl shadow-md shadow-red-600/30">
        {{ businessSettings.business_icon }}
      </div>
      <span class="text-[9px] font-black text-red-600 tracking-tighter text-center leading-tight line-clamp-1 px-1">
        {{ businessSettings.business_name }}
      </span>
    </Link>

    <!-- Dynamic Vertical Sidebar Menu Items -->
    <nav class="flex-1 w-full space-y-3 px-2 overflow-y-auto max-h-[calc(100vh-6rem)] no-scrollbar">
      <template v-if="customNavList && customNavList.length > 0">
        <Link 
          v-for="item in customNavList"
          :key="item.id || item.key"
          :href="item.route || '/'"
          :class="[
            'flex flex-col items-center justify-center p-2 rounded-2xl transition-all text-center',
            isCurrentRoute(item.route)
              ? 'bg-red-50 text-red-600 font-bold border border-red-200 shadow-xs' 
              : 'text-slate-500 hover:bg-slate-100 hover:text-slate-900'
          ]"
        >
          <component :is="resolveIcon(item.icon)" :class="['w-5 h-5', isCurrentRoute(item.route) ? 'text-red-600' : 'text-slate-400']" />
          <span class="text-[9px] font-extrabold mt-1 leading-tight line-clamp-1">{{ item.label }}</span>
        </Link>
      </template>
    </nav>
  </aside>
</template>

<style scoped>
.no-scrollbar::-webkit-scrollbar {
  display: none;
  width: 0;
  height: 0;
}
.no-scrollbar {
  -ms-overflow-style: none;
  scrollbar-width: none;
}
</style>
