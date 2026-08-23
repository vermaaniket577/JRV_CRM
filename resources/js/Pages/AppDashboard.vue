<script setup>
import { ref, computed } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import Navbar from '@/Components/Navbar.vue';
import GlobalSearchModal from '@/Components/GlobalSearchModal.vue';
import LoginHistoryModal from '@/Components/LoginHistoryModal.vue';
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
  ArrowUpRightIcon,
  ArrowDownRightIcon,
  StarIcon,
  HeartIcon,
  HeartIcon as HeartIconFilled,
  EllipsisVerticalIcon,
  ChartBarIcon,
  RocketLaunchIcon,
  FunnelIcon,
  CreditCardIcon,
  ClipboardDocumentListIcon,
  LinkIcon,
  SparklesIcon
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
  userName: String,
  metrics: Object,
  invoices: Array,
  topApplications: Array,
  countries: Array,
  topAuthors: Array,
});

const page = usePage();
const customNavList = computed(() => page.props.custom_nav_list || []);
const customNav = computed(() => page.props.custom_nav || {});
const businessSettings = computed(() => page.props.business_settings || { business_name: 'JRV CRM', business_icon: '🎓' });
const tenantIndustry = computed(() => page.props.tenant_industry || { slug: 'education', name: 'Education & Training' });

const isMatrimonial = computed(() => tenantIndustry.value?.slug === 'matrimonial');
const isRealEstate = computed(() => tenantIndustry.value?.slug === 'real-estate');
const isEducation = computed(() => tenantIndustry.value?.slug === 'education');

const primaryActionButtonLabel = computed(() => {
  if (isMatrimonial.value) return '+ Add Bio-Data Profile';
  if (isRealEstate.value) return '+ Add Property / Rental';
  if (isEducation.value) return '+ Add Admission / Student';
  return '+ Add CRM Lead';
});

const primaryActionRoute = computed(() => {
  if (isMatrimonial.value) return '/matrimonial/directory';
  if (isRealEstate.value) return '/properties';
  return '/tenant/crm-records';
});

const myWorkRoute = computed(() => {
  if (isMatrimonial.value) return '/matrimonial/directory';
  if (isRealEstate.value) return '/properties';
  return '/tenant/crm-records';
});

const resolveIcon = (iconName) => iconMap[iconName] || LinkIcon;

const isCurrentRoute = (path) => {
  if (!path) return false;
  if (path === '/' && page.url === '/') return true;
  return path !== '/' && page.url.startsWith(path);
};

const isSearchOpen = ref(false);
const isLoginHistoryOpen = ref(false);
</script>

<template>
  <Head :title="`${businessSettings.business_name || 'Dashboard'} - ${tenantIndustry?.name || 'CRM SaaS Engine'}`" />

  <div class="min-h-screen bg-slate-100 flex font-sans text-slate-900">
    <!-- Navbar (Fixed Left Vertical Sidebar) -->
    <Navbar @open-search="isSearchOpen = true" />

    <!-- Main Content Area -->
    <div class="flex-1 flex flex-col min-w-0">
      <!-- Top Action Bar -->
      <header class="bg-white border-b border-slate-200 px-6 py-3 flex flex-wrap items-center justify-between gap-3 shadow-xs">
        <div class="flex items-center gap-2">
          <!-- Primary Industry Action Button -->
          <Link :href="primaryActionRoute" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-extrabold text-xs rounded-xl shadow-xs transition-all flex items-center gap-1.5">
            <PlusIcon class="w-4 h-4 stroke-[3]" />
            <span>{{ primaryActionButtonLabel }}</span>
          </Link>

          <!-- Customize Brand & Menu Button -->
          <Link href="/tenant/settings/navigation" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-extrabold text-xs rounded-xl shadow-xs transition-all flex items-center gap-1.5">
            <AdjustmentsHorizontalIcon class="w-4 h-4" />
            <span>Customize Brand & Menu</span>
          </Link>
        </div>

        <div class="flex items-center gap-3">
          <div class="relative">
            <button @click="isSearchOpen = true" class="p-2 bg-slate-100 rounded-full text-slate-600 hover:bg-slate-200 relative">
              <BellIcon class="w-5 h-5" />
              <span class="absolute -top-1 -right-1 bg-red-600 text-white text-[10px] font-black w-4 h-4 rounded-full flex items-center justify-center">
                2
              </span>
            </button>
          </div>

          <div class="w-9 h-9 bg-red-600 rounded-full flex items-center justify-center text-white font-black text-sm shadow-md">
            {{ businessSettings.business_icon }}
          </div>
        </div>
      </header>

      <!-- Dashboard Body Container -->
      <div class="p-8 space-y-6 flex-1 overflow-y-auto max-w-7xl mx-auto w-full">
        <!-- ROW 1: Welcome Banner (Soft Pink) & Dark Hero Slide -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-stretch">
          <!-- Welcome Pink Card -->
          <div class="lg:col-span-8 bg-gradient-to-r from-red-100 via-rose-100 to-pink-100 border border-red-200 rounded-3xl p-8 flex flex-col justify-between shadow-xs relative overflow-hidden">
            <div class="space-y-3 max-w-lg z-10">
              <h2 class="text-2xl font-black text-slate-900 tracking-tight">
                Welcome back! <br />
                <span class="text-red-600">{{ userName }}</span>
              </h2>
              <p class="text-xs text-slate-600 leading-relaxed">
                {{ isRealEstate ? 'Manage your real estate listings, rental inquiries, leads, site visits, and client pipelines seamlessly.' : (isMatrimonial ? 'Manage your matrimonial community bio-datas, staff tasks, verification requests, and real-time portal statistics seamlessly.' : `Manage your ${tenantIndustry?.name || 'CRM'} enquiries, student admissions, courses, staff tasks, and pipelines seamlessly.`) }}
              </p>
            </div>
            <div class="pt-6 z-10">
              <Link :href="myWorkRoute" class="px-6 py-2.5 bg-red-600 hover:bg-red-700 text-white text-xs font-black rounded-xl shadow-md inline-flex items-center gap-2 transition-all">
                <span>Go Now</span>
              </Link>
            </div>
          </div>

          <!-- Dark Hero Widget -->
          <div class="lg:col-span-4 bg-slate-900 text-white rounded-3xl p-8 flex flex-col justify-between shadow-lg relative overflow-hidden">
            <div class="space-y-2 z-10">
              <span class="text-[10px] font-black text-indigo-400 uppercase tracking-widest">Featured Update</span>
              <h3 class="text-lg font-bold text-white">Lightroom mobile : Kolara</h3>
              <p class="text-xs text-slate-400">Don't Waste Time! 7 Tools And Feedback You Need for Community Directory Growth.</p>
            </div>
            <div class="pt-6 z-10 flex items-center gap-2 text-[10px] font-bold text-slate-400">
              <div class="w-2 h-2 rounded-full bg-red-500"></div>
              <span>System Version 0.4.23 Active</span>
            </div>
          </div>
        </div>

        <!-- ROW 2: 3 Summary Metric Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
          <!-- Total Active Users -->
          <div class="bg-white border border-slate-200 rounded-3xl p-6 shadow-xs flex items-center justify-between">
            <div class="space-y-1">
              <div class="text-xs font-bold text-slate-500">Total Active Users</div>
              <div class="text-3xl font-black text-slate-900">{{ metrics.active_users }}</div>
              <div class="text-[11px] font-bold text-emerald-600 flex items-center gap-1">
                <ArrowUpRightIcon class="w-3.5 h-3.5" />
                <span>+2.6% vs last week</span>
              </div>
            </div>
            <div class="w-12 h-12 bg-pink-50 text-pink-600 rounded-2xl flex items-center justify-center font-bold text-xl">
              📈
            </div>
          </div>

          <!-- Total Installed -->
          <div class="bg-white border border-slate-200 rounded-3xl p-6 shadow-xs flex items-center justify-between">
            <div class="space-y-1">
              <div class="text-xs font-bold text-slate-500">Total Installed</div>
              <div class="text-3xl font-black text-slate-900">{{ metrics.total_installed }}</div>
              <div class="text-[11px] font-bold text-emerald-600 flex items-center gap-1">
                <ArrowUpRightIcon class="w-3.5 h-3.5" />
                <span>+0.2% vs last week</span>
              </div>
            </div>
            <div class="w-12 h-12 bg-cyan-50 text-cyan-600 rounded-2xl flex items-center justify-center font-bold text-xl">
              📲
            </div>
          </div>

          <!-- Total Downloads -->
          <div class="bg-white border border-slate-200 rounded-3xl p-6 shadow-xs flex items-center justify-between">
            <div class="space-y-1">
              <div class="text-xs font-bold text-slate-500">Total Downloads</div>
              <div class="text-3xl font-black text-slate-900">{{ metrics.total_downloads }}</div>
              <div class="text-[11px] font-bold text-amber-600 flex items-center gap-1">
                <ArrowDownRightIcon class="w-3.5 h-3.5" />
                <span>-0.1% vs last week</span>
              </div>
            </div>
            <div class="w-12 h-12 bg-amber-50 text-amber-600 rounded-2xl flex items-center justify-center font-bold text-xl">
              ⚡
            </div>
          </div>
        </div>

        <!-- ROW 3: Analytics Charts (Donut & Area Growth) -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
          <!-- Donut Chart Card -->
          <div class="lg:col-span-5 bg-white border border-slate-200 rounded-3xl p-6 shadow-xs space-y-4">
            <div class="flex items-center justify-between">
              <h3 class="text-sm font-bold text-slate-900">Current Download</h3>
            </div>

            <!-- SVG Multi-Colored Donut Ring -->
            <div class="flex flex-col items-center justify-center py-4 space-y-4">
              <div class="relative w-44 h-44 flex items-center justify-center">
                <svg class="w-full h-full transform -rotate-90" viewBox="0 0 36 36">
                  <path class="text-slate-100" stroke-width="3.8" stroke="currentColor" fill="none" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                  <path class="text-red-500" stroke-dasharray="35, 100" stroke-width="3.8" stroke="currentColor" fill="none" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                  <path class="text-amber-400" stroke-dasharray="25, 100" stroke-dashoffset="-35" stroke-width="3.8" stroke="currentColor" fill="none" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                  <path class="text-sky-400" stroke-dasharray="20, 100" stroke-dashoffset="-60" stroke-width="3.8" stroke="currentColor" fill="none" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" />
                </svg>
                <div class="absolute text-center">
                  <div class="text-xs font-semibold text-slate-400 uppercase">Total</div>
                  <div class="text-xl font-black text-slate-900">188,245</div>
                </div>
              </div>

              <div class="grid grid-cols-4 gap-3 text-center text-xs font-bold pt-2 border-t border-slate-100 w-full">
                <div><span class="inline-block w-2.5 h-2.5 bg-red-500 rounded-full mr-1"></span>Mac</div>
                <div><span class="inline-block w-2.5 h-2.5 bg-amber-400 rounded-full mr-1"></span>Window</div>
                <div><span class="inline-block w-2.5 h-2.5 bg-sky-400 rounded-full mr-1"></span>iOS</div>
                <div><span class="inline-block w-2.5 h-2.5 bg-emerald-500 rounded-full mr-1"></span>Android</div>
              </div>
            </div>
          </div>

          <!-- Area Growth Chart Card -->
          <div class="lg:col-span-7 bg-white border border-slate-200 rounded-3xl p-6 shadow-xs space-y-4">
            <div class="flex items-center justify-between">
              <div>
                <h3 class="text-sm font-bold text-slate-900">Area Installed</h3>
                <p class="text-[11px] text-emerald-600 font-semibold">(+43%) than last year</p>
              </div>

              <select class="bg-slate-100 border border-slate-300 text-xs font-bold rounded-lg px-2.5 py-1">
                <option>2023</option>
                <option>2024</option>
              </select>
            </div>

            <!-- Simulated Curved Line Area Graph SVG -->
            <div class="h-48 w-full flex items-end pt-4">
              <svg class="w-full h-full" viewBox="0 0 500 150" fill="none">
                <path d="M0 130 Q 70 80, 140 100 T 280 60 T 420 80 T 500 20" stroke="#ef4444" stroke-width="3" fill="none" />
                <path d="M0 140 Q 70 110, 140 120 T 280 90 T 420 100 T 500 60" stroke="#f59e0b" stroke-width="3" stroke-dasharray="4 4" fill="none" />
              </svg>
            </div>
          </div>
        </div>

        <!-- ROW 4: Red Table Header & Top Applications Rating -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
          <!-- New Service / Invoice Table with Solid Red Header -->
          <div class="lg:col-span-7 bg-white border border-slate-200 rounded-3xl overflow-hidden shadow-xs space-y-3">
            <div class="p-4 bg-red-600 text-white font-bold text-sm flex items-center justify-between">
              <span>New Service Requests & Transactions</span>
            </div>

            <div class="p-4 overflow-x-auto">
              <table class="w-full text-left border-collapse text-xs">
                <thead>
                  <tr class="border-b border-slate-200 text-slate-400 font-bold uppercase text-[10px]">
                    <th class="py-2.5 px-3">Invoice ID</th>
                    <th class="py-2.5 px-3">Category</th>
                    <th class="py-2.5 px-3">Price</th>
                    <th class="py-2.5 px-3">Status</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                  <tr v-for="inv in invoices" :key="inv.id" class="hover:bg-slate-50">
                    <td class="py-3 px-3 font-mono font-bold text-slate-700">{{ inv.id }}</td>
                    <td class="py-3 px-3 font-semibold text-slate-900">{{ inv.category }}</td>
                    <td class="py-3 px-3 font-bold text-emerald-700">{{ inv.price }}</td>
                    <td class="py-3 px-3">
                      <span :class="['px-2.5 py-0.5 rounded-full text-[10px] font-extrabold border', inv.badge_class]">
                        {{ inv.status }}
                      </span>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <div class="p-3 border-t border-slate-100 text-right">
              <Link :href="myWorkRoute" class="text-xs font-bold text-red-600 hover:underline">
                View All Records &gt;
              </Link>
            </div>
          </div>

          <!-- Top Related Applications List -->
          <div class="lg:col-span-5 bg-white border border-slate-200 rounded-3xl p-6 shadow-xs space-y-4">
            <div class="flex items-center justify-between">
              <h3 class="text-sm font-bold text-slate-900">Top Related Applications</h3>
            </div>

            <div class="divide-y divide-slate-100">
              <div v-for="(app, idx) in topApplications" :key="idx" class="py-3 flex items-center justify-between">
                <div class="flex items-center gap-3">
                  <div class="w-9 h-9 rounded-xl bg-slate-100 flex items-center justify-center text-lg">
                    {{ app.icon }}
                  </div>
                  <div>
                    <div class="flex items-center gap-2">
                      <span class="font-bold text-xs text-slate-900">{{ app.name }}</span>
                      <span :class="['text-[9px] font-black px-1.5 py-0.5 rounded uppercase', app.tag_bg]">{{ app.tag }}</span>
                    </div>
                    <div class="flex items-center gap-1 text-[10px] text-amber-500 font-bold mt-0.5">
                      <StarIcon class="w-3 h-3 fill-amber-400 stroke-none" />
                      <span>5.0</span>
                      <span class="text-slate-400 font-normal">({{ app.reviews }})</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- ROW 5: Top Countries, Top Authors & Stat Banners -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
          <!-- Countries -->
          <div class="bg-white border border-slate-200 rounded-3xl p-6 shadow-xs space-y-4">
            <h3 class="text-sm font-bold text-slate-900">Top Installed Countries</h3>
            <div class="space-y-3">
              <div v-for="(c, idx) in countries" :key="idx" class="space-y-1">
                <div class="flex items-center justify-between text-xs font-bold">
                  <span>{{ c.flag }} {{ c.name }}</span>
                  <span class="text-slate-500">{{ c.downloads }}</span>
                </div>
                <div class="w-full h-1.5 bg-slate-100 rounded-full overflow-hidden">
                  <div class="h-full bg-red-600 rounded-full" :style="{ width: c.percentage + '%' }"></div>
                </div>
              </div>
            </div>
          </div>

          <!-- Top Authors -->
          <div class="bg-white border border-slate-200 rounded-3xl p-6 shadow-xs space-y-4">
            <h3 class="text-sm font-bold text-slate-900">Top Authors</h3>
            <div class="divide-y divide-slate-100">
              <div v-for="(author, idx) in topAuthors" :key="idx" class="py-3 flex items-center justify-between">
                <div class="flex items-center gap-3">
                  <div class="w-8 h-8 rounded-full bg-red-100 text-red-700 font-bold flex items-center justify-center text-xs">
                    {{ author.name.charAt(0) }}
                  </div>
                  <div>
                    <div class="font-bold text-xs text-slate-900">{{ author.name }}</div>
                    <div class="text-[10px] text-slate-500">{{ author.role }}</div>
                  </div>
                </div>
                <div class="flex items-center gap-1 text-xs font-bold text-rose-600">
                  <HeartIconFilled class="w-3.5 h-3.5 fill-rose-600 stroke-none" />
                  <span>{{ author.likes }}</span>
                </div>
              </div>
            </div>
          </div>

          <!-- Stat Banners -->
          <div class="space-y-4 flex flex-col justify-between">
            <div class="p-6 rounded-3xl bg-gradient-to-r from-red-600 to-rose-600 text-white shadow-md flex items-center justify-between">
              <div>
                <div class="text-2xl font-black">38,566</div>
                <div class="text-xs font-bold opacity-90">{{ tenantIndustry?.name ? `${tenantIndustry.name} Conversions` : 'Active Conversions' }}</div>
              </div>
              <div class="w-10 h-10 bg-white/20 rounded-full flex items-center justify-center font-bold text-lg">{{ businessSettings.business_icon }}</div>
            </div>

            <div class="p-6 rounded-3xl bg-gradient-to-r from-sky-600 to-indigo-600 text-white shadow-md flex items-center justify-between">
              <div>
                <div class="text-2xl font-black">55,566</div>
                <div class="text-xs font-bold opacity-90">Applications</div>
              </div>
              <div class="w-10 h-10 bg-white/20 rounded-full flex items-center justify-center font-bold">📲</div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Modals -->
    <GlobalSearchModal :is-open="isSearchOpen" @close="isSearchOpen = false" />
    <LoginHistoryModal :is-open="isLoginHistoryOpen" @close="isLoginHistoryOpen = false" />
  </div>
</template>
