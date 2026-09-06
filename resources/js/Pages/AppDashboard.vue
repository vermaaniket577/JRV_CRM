<script setup>
import { ref, computed, onMounted } from 'vue';
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
  SparklesIcon,
  PlusIcon,
  CheckIcon,
  CheckCircleIcon,
  ClipboardDocumentIcon,
  XMarkIcon,
  BuildingOfficeIcon,
  ArrowTopRightOnSquareIcon,
  ArrowRightIcon,
  CircleStackIcon
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
const currentTenant = computed(() => page.props.current_tenant || {});

const tenantSubdomainUrl = computed(() => {
  if (currentTenant.value?.subdomain_url) return currentTenant.value.subdomain_url;
  if (currentTenant.value?.subdomain) return `https://${currentTenant.value.subdomain}.jrvcrm.com`;
  return 'https://yourcompany.jrvcrm.com';
});

const isMatrimonial = computed(() => tenantIndustry.value?.slug === 'matrimonial');
const isRealEstate = computed(() => tenantIndustry.value?.slug === 'real-estate');
const isEducation = computed(() => tenantIndustry.value?.slug === 'education');
const isResearchPublication = computed(() => ['research-publication', 'journal-publication', 'publication'].includes(tenantIndustry.value?.slug));

const primaryActionButtonLabel = computed(() => {
  if (isResearchPublication.value) return '+ Submit Manuscript / Paper';
  if (isMatrimonial.value) return '+ Add Bio-Data Profile';
  if (isRealEstate.value) return '+ Add Property / Rental';
  if (isEducation.value) return '+ Add Admission / Student';
  return '+ Add CRM Lead';
});

const primaryActionRoute = computed(() => {
  if (isResearchPublication.value) return '/tenant/crm-records?type=manuscripts';
  if (isMatrimonial.value) return '/matrimonial/directory';
  if (isRealEstate.value) return '/properties';
  return '/tenant/crm-records';
});

const myWorkRoute = computed(() => {
  if (isResearchPublication.value) return '/tenant/crm-records?type=manuscripts';
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

const isNewlyOnboarded = ref(false);
const showLaunchpad = ref(true);
const subdomainCopied = ref(false);

onMounted(() => {
  if (typeof window !== 'undefined') {
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('onboarding_success') === '1') {
      isNewlyOnboarded.value = true;
      showLaunchpad.value = true;
    }
  }
});

const dismissLaunchpad = () => {
  showLaunchpad.value = false;
};

const copySubdomain = async () => {
  try {
    if (navigator?.clipboard?.writeText) {
      await navigator.clipboard.writeText(tenantSubdomainUrl.value);
    }
    subdomainCopied.value = true;
    setTimeout(() => {
      subdomainCopied.value = false;
    }, 2500);
  } catch (err) {
    subdomainCopied.value = true;
    setTimeout(() => {
      subdomainCopied.value = false;
    }, 2500);
  }
};
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
              <span class="absolute -top-1 -right-1 bg-red-600 text-white text-xs font-bold w-4 h-4 rounded-full flex items-center justify-center">
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

        <!-- QUICK-START BUSINESS LAUNCHPAD BANNER -->
        <transition enter-active-class="transition duration-300 ease-out" enter-from-class="opacity-0 -translate-y-2" enter-to-class="opacity-100 translate-y-0" leave-active-class="transition duration-200 ease-in" leave-from-class="opacity-100" leave-to-class="opacity-0">
          <div v-if="showLaunchpad" class="bg-gradient-to-r from-slate-900 via-slate-800 to-indigo-950 text-white rounded-3xl p-6 sm:p-7 shadow-xl border border-slate-700/80 relative overflow-hidden">
            <!-- Decorative background accents -->
            <div class="absolute -right-16 -top-16 w-64 h-64 bg-red-600/15 rounded-full blur-3xl pointer-events-none"></div>
            <div class="absolute -left-16 -bottom-16 w-64 h-64 bg-indigo-600/20 rounded-full blur-3xl pointer-events-none"></div>

            <div class="relative z-10 space-y-5">
              <!-- Top Row Badges -->
              <div class="flex flex-wrap items-center justify-between gap-3">
                <div class="flex items-center gap-2">
                  <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">
                    <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                    <span>Workspace Database Active</span>
                  </span>
                  <span class="px-2.5 py-1 rounded-full text-xs font-medium bg-white/10 text-slate-200 border border-white/10">
                    {{ tenantIndustry?.name || 'Enterprise CRM' }}
                  </span>
                </div>
                <button @click="dismissLaunchpad" class="text-slate-400 hover:text-white p-1 rounded-lg hover:bg-white/10 transition-all text-xs flex items-center gap-1">
                  <span>Dismiss</span>
                  <XMarkIcon class="w-4 h-4" />
                </button>
              </div>

              <!-- Welcome Headline -->
              <div>
                <h2 class="text-xl sm:text-2xl font-black text-white tracking-tight flex items-center gap-2">
                  <span>Welcome to {{ businessSettings.business_name || currentTenant.name || 'Your Workspace' }}</span>
                </h2>
                <p class="text-xs sm:text-sm text-slate-300 mt-1 max-w-2xl leading-relaxed">
                  Your dedicated CRM workspace is active and ready. Here are recommended next steps to set up your workflows and begin managing client records.
                </p>
              </div>

              <!-- Subdomain & Database Status Strip -->
              <div class="bg-black/35 border border-white/10 rounded-2xl p-4 flex flex-wrap items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                  <div class="w-10 h-10 rounded-xl bg-red-600/20 border border-red-500/30 flex items-center justify-center text-lg">
                    🌐
                  </div>
                  <div>
                    <div class="text-xs uppercase font-medium text-slate-400 tracking-wider">Workspace Subdomain</div>
                    <div class="text-sm font-semibold text-white font-mono flex items-center gap-2">
                      <span>{{ tenantSubdomainUrl }}</span>
                      <span class="text-xs font-medium px-2 py-0.5 rounded-md bg-emerald-950 text-emerald-300 border border-emerald-800">Online</span>
                    </div>
                  </div>
                </div>
                <div class="flex items-center gap-2">
                  <button @click="copySubdomain" class="px-3.5 py-2 bg-white/10 hover:bg-white/20 text-white text-xs font-semibold rounded-xl border border-white/20 flex items-center gap-1.5 transition-all">
                    <CheckIcon v-if="subdomainCopied" class="w-4 h-4 text-emerald-400" />
                    <ClipboardDocumentIcon v-else class="w-4 h-4 text-slate-300" />
                    <span>{{ subdomainCopied ? 'Copied!' : 'Copy Subdomain' }}</span>
                  </button>
                  <a :href="tenantSubdomainUrl" target="_blank" rel="noopener noreferrer" class="px-3.5 py-2 bg-red-600 hover:bg-red-700 text-white text-xs font-semibold rounded-xl flex items-center gap-1.5 transition-all shadow-md">
                    <span>Open Subdomain</span>
                    <ArrowTopRightOnSquareIcon class="w-3.5 h-3.5" />
                  </a>
                </div>
              </div>

              <!-- 3 Clear Next Steps -->
              <div class="grid grid-cols-1 md:grid-cols-3 gap-3.5 pt-1">
                <!-- Action 1: Add First Record -->
                <Link :href="primaryActionRoute" class="group bg-slate-800/70 hover:bg-slate-800 border border-slate-700 hover:border-red-500/50 rounded-2xl p-4 transition-all flex flex-col justify-between space-y-3">
                  <div class="flex items-start justify-between">
                    <div class="w-9 h-9 rounded-xl bg-red-500/10 text-red-400 flex items-center justify-center font-bold text-base group-hover:scale-110 transition-transform">
                      1️⃣
                    </div>
                    <span class="text-xs font-medium uppercase px-2 py-0.5 rounded bg-red-600/20 text-red-300">Quick Start</span>
                  </div>
                  <div>
                    <div class="font-semibold text-sm text-white group-hover:text-red-300 transition-colors">
                      {{ primaryActionButtonLabel }}
                    </div>
                    <p class="text-xs text-slate-400 mt-1 leading-relaxed">
                      Create your first lead record or client entry to test your pipeline workflow.
                    </p>
                  </div>
                  <div class="text-xs font-semibold text-red-400 flex items-center gap-1 group-hover:translate-x-1 transition-transform">
                    <span>Create Record</span>
                    <ArrowRightIcon class="w-3.5 h-3.5" />
                  </div>
                </Link>

                <!-- Action 2: Connect Website & Lead Capture Form -->
                <Link href="/integration" class="group bg-slate-800/70 hover:bg-slate-800 border border-slate-700 hover:border-indigo-500/50 rounded-2xl p-4 transition-all flex flex-col justify-between space-y-3">
                  <div class="flex items-start justify-between">
                    <div class="w-9 h-9 rounded-xl bg-indigo-500/10 text-indigo-400 flex items-center justify-center font-bold text-base group-hover:scale-110 transition-transform">
                      2️⃣
                    </div>
                    <span class="text-xs font-medium uppercase px-2 py-0.5 rounded bg-indigo-600/20 text-indigo-300">Automate</span>
                  </div>
                  <div>
                    <div class="font-semibold text-sm text-white group-hover:text-indigo-300 transition-colors">
                      Connect Website & Forms
                    </div>
                    <p class="text-xs text-slate-400 mt-1 leading-relaxed">
                      Embed public inquiry forms on your website or use API endpoints to capture leads automatically.
                    </p>
                  </div>
                  <div class="text-xs font-semibold text-indigo-400 flex items-center gap-1 group-hover:translate-x-1 transition-transform">
                    <span>Website Hub</span>
                    <ArrowRightIcon class="w-3.5 h-3.5" />
                  </div>
                </Link>

                <!-- Action 3: Customize Brand & Staff Roles -->
                <Link href="/tenant/settings/navigation" class="group bg-slate-800/70 hover:bg-slate-800 border border-slate-700 hover:border-amber-500/50 rounded-2xl p-4 transition-all flex flex-col justify-between space-y-3">
                  <div class="flex items-start justify-between">
                    <div class="w-9 h-9 rounded-xl bg-amber-500/10 text-amber-400 flex items-center justify-center font-bold text-base group-hover:scale-110 transition-transform">
                      3️⃣
                    </div>
                    <span class="text-xs font-medium uppercase px-2 py-0.5 rounded bg-amber-600/20 text-amber-300">Customization</span>
                  </div>
                  <div>
                    <div class="font-semibold text-sm text-white group-hover:text-amber-300 transition-colors">
                      Personalize Logo & Navigation
                    </div>
                    <p class="text-xs text-slate-400 mt-1 leading-relaxed">
                      Customize brand colors, reorder navigation links, and configure staff permission roles.
                    </p>
                  </div>
                  <div class="text-xs font-semibold text-amber-400 flex items-center gap-1 group-hover:translate-x-1 transition-transform">
                    <span>Configure</span>
                    <ArrowRightIcon class="w-3.5 h-3.5" />
                  </div>
                </Link>
              </div>
            </div>
          </div>
        </transition>

        <!-- Re-open launchpad toggle if dismissed -->
        <div v-if="!showLaunchpad" class="flex justify-end">
          <button @click="showLaunchpad = true" class="text-xs font-semibold text-slate-600 hover:text-slate-900 flex items-center gap-1.5 py-1.5 px-3 bg-white border border-slate-200 rounded-xl shadow-xs hover:border-slate-300 transition-all">
            <span>🚀 Open Company Launchpad</span>
          </button>
        </div>

        <!-- ROW 1: Welcome Banner & System Status -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-stretch">
          <!-- Welcome Pink Card -->
          <div class="lg:col-span-8 bg-gradient-to-r from-red-100 via-rose-100 to-pink-100 border border-red-200 rounded-3xl p-8 flex flex-col justify-between shadow-xs relative overflow-hidden">
            <div class="space-y-3 max-w-lg z-10">
              <h2 class="text-2xl font-black text-slate-900 tracking-tight">
                Welcome back! <br />
                <span class="text-red-600">{{ userName }}</span>
              </h2>
              <p class="text-xs text-slate-600 leading-relaxed">
                {{ isRealEstate ? 'Manage your real estate listings, rental inquiries, leads, site visits, and client pipelines seamlessly.' : (isMatrimonial ? 'Manage your matrimonial community bio-datas, staff tasks, verification requests, and real-time portal statistics seamlessly.' : (isResearchPublication ? 'Manage your academic research manuscripts, peer review tracking, author submissions, journal publications, and APC fees seamlessly.' : `Manage your ${tenantIndustry?.name || 'CRM'} enquiries, records, and pipelines seamlessly.`)) }}
              </p>
            </div>
            <div class="pt-6 z-10">
              <Link :href="myWorkRoute" class="px-6 py-2.5 bg-red-600 hover:bg-red-700 text-white text-xs font-bold rounded-xl shadow-md inline-flex items-center gap-2 transition-all">
                <span>View Pipeline</span>
              </Link>
            </div>
          </div>

          <!-- Dark Hero Widget -->
          <div class="lg:col-span-4 bg-slate-900 text-white rounded-3xl p-8 flex flex-col justify-between shadow-lg relative overflow-hidden">
            <div class="space-y-2 z-10">
              <span class="text-xs font-semibold text-indigo-400 uppercase tracking-widest">System Status</span>
              <h3 class="text-lg font-bold text-white">CRM Cloud Platform</h3>
              <p class="text-xs text-slate-400">Automate follow-ups, sync client communications, and monitor pipeline conversion rates in real time.</p>
            </div>
            <div class="pt-6 z-10 flex items-center gap-2 text-xs font-medium text-slate-400">
              <div class="w-2 h-2 rounded-full bg-emerald-500"></div>
              <span>Platform Version 2.4.0 • Enterprise Active</span>
            </div>
          </div>
        </div>

        <!-- ROW 2: 3 Summary Metric Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
          <!-- Total Active Contacts -->
          <div class="bg-white border border-slate-200 rounded-3xl p-6 shadow-xs flex items-center justify-between">
            <div class="space-y-1">
              <div class="text-xs font-semibold text-slate-500">Active Contacts</div>
              <div class="text-3xl font-bold text-slate-900 tracking-tight">{{ metrics.active_users }}</div>
              <div class="text-xs font-medium text-emerald-600 flex items-center gap-1">
                <ArrowUpRightIcon class="w-3.5 h-3.5" />
                <span>+2.6% vs last week</span>
              </div>
            </div>
            <div class="w-12 h-12 bg-pink-50 text-pink-600 rounded-2xl flex items-center justify-center font-bold text-xl">
              📈
            </div>
          </div>

          <!-- Total Inquiries -->
          <div class="bg-white border border-slate-200 rounded-3xl p-6 shadow-xs flex items-center justify-between">
            <div class="space-y-1">
              <div class="text-xs font-semibold text-slate-500">Total Inquiries</div>
              <div class="text-3xl font-bold text-slate-900 tracking-tight">{{ metrics.total_installed }}</div>
              <div class="text-xs font-medium text-emerald-600 flex items-center gap-1">
                <ArrowUpRightIcon class="w-3.5 h-3.5" />
                <span>+0.2% vs last week</span>
              </div>
            </div>
            <div class="w-12 h-12 bg-cyan-50 text-cyan-600 rounded-2xl flex items-center justify-center font-bold text-xl">
              📲
            </div>
          </div>

          <!-- Pipeline Transactions -->
          <div class="bg-white border border-slate-200 rounded-3xl p-6 shadow-xs flex items-center justify-between">
            <div class="space-y-1">
              <div class="text-xs font-semibold text-slate-500">Pipeline Transactions</div>
              <div class="text-3xl font-bold text-slate-900 tracking-tight">{{ metrics.total_downloads }}</div>
              <div class="text-xs font-medium text-amber-600 flex items-center gap-1">
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
              <h3 class="text-sm font-bold text-slate-900">Lead Channel Breakdown</h3>
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
                  <div class="text-xs font-semibold text-slate-400 uppercase">Total Leads</div>
                  <div class="text-xl font-black text-slate-900">188,245</div>
                </div>
              </div>

              <div class="grid grid-cols-4 gap-3 text-center text-xs font-bold pt-2 border-t border-slate-100 w-full">
                <div><span class="inline-block w-2.5 h-2.5 bg-red-500 rounded-full mr-1"></span>Mac</div>
                <div><span class="inline-block w-2.5 h-2.5 bg-amber-400 rounded-full mr-1"></span>Windows</div>
                <div><span class="inline-block w-2.5 h-2.5 bg-sky-400 rounded-full mr-1"></span>iOS</div>
                <div><span class="inline-block w-2.5 h-2.5 bg-emerald-500 rounded-full mr-1"></span>Android</div>
              </div>
            </div>
          </div>

          <!-- Area Growth Chart Card -->
          <div class="lg:col-span-7 bg-white border border-slate-200 rounded-3xl p-6 shadow-xs space-y-4">
            <div class="flex items-center justify-between">
              <div>
                <h3 class="text-sm font-bold text-slate-900">Monthly Growth & Inquiries</h3>
                <p class="text-xs text-emerald-600 font-medium">(+43%) vs previous period</p>
              </div>

              <select class="bg-slate-100 border border-slate-300 text-xs font-bold rounded-lg px-2.5 py-1">
                <option>2024</option>
                <option>2025</option>
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

        <!-- ROW 4: Table Header & Connected Integrations -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
          <!-- New Service / Invoice Table with Solid Red Header -->
          <div class="lg:col-span-7 bg-white border border-slate-200 rounded-3xl overflow-hidden shadow-xs space-y-3">
            <div class="p-4 bg-red-600 text-white font-bold text-sm flex items-center justify-between">
              <span>Recent Transactions & Inquiries</span>
            </div>

            <div class="p-4 overflow-x-auto">
              <table class="w-full text-left border-collapse text-xs">
                <thead>
                  <tr class="border-b border-slate-200 text-slate-400 font-semibold uppercase text-xs tracking-wider">
                    <th class="py-2.5 px-3">Invoice ID</th>
                    <th class="py-2.5 px-3">Category</th>
                    <th class="py-2.5 px-3">Amount</th>
                    <th class="py-2.5 px-3">Status</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                  <tr v-for="inv in invoices" :key="inv.id" class="hover:bg-slate-50">
                    <td class="py-3 px-3 font-mono font-medium text-slate-700">{{ inv.id }}</td>
                    <td class="py-3 px-3 font-semibold text-slate-900">{{ inv.category }}</td>
                    <td class="py-3 px-3 font-bold text-emerald-700">{{ inv.price }}</td>
                    <td class="py-3 px-3">
                      <span :class="['px-2.5 py-0.5 rounded-full text-xs font-semibold border', inv.badge_class]">
                        {{ inv.status }}
                      </span>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <div class="p-3 border-t border-slate-100 text-right">
              <Link :href="myWorkRoute" class="text-xs font-bold text-red-600 hover:underline">
                View All Records →
              </Link>
            </div>
          </div>

          <!-- Top Related Applications List -->
          <div class="lg:col-span-5 bg-white border border-slate-200 rounded-3xl p-6 shadow-xs space-y-4">
            <div class="flex items-center justify-between">
              <h3 class="text-sm font-bold text-slate-900">Connected Integrations & Tools</h3>
            </div>

            <div class="divide-y divide-slate-100">
              <div v-for="(app, idx) in topApplications" :key="idx" class="py-3 flex items-center justify-between">
                <div class="flex items-center gap-3">
                  <div class="w-9 h-9 rounded-xl bg-slate-100 flex items-center justify-center text-lg">
                    {{ app.icon }}
                  </div>
                  <div>
                    <div class="flex items-center gap-2">
                      <span class="font-semibold text-sm text-slate-900">{{ app.name }}</span>
                      <span :class="['text-xs font-semibold px-2 py-0.5 rounded uppercase', app.tag_bg]">{{ app.tag }}</span>
                    </div>
                    <div class="flex items-center gap-1 text-xs text-amber-500 font-medium mt-0.5">
                      <StarIcon class="w-3.5 h-3.5 fill-amber-400 stroke-none" />
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
            <h3 class="text-sm font-bold text-slate-900">Client Geographic Distribution</h3>
            <div class="space-y-3">
              <div v-for="(c, idx) in countries" :key="idx" class="space-y-1">
                <div class="flex items-center justify-between text-xs font-medium">
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
            <h3 class="text-sm font-bold text-slate-900">Top Performing Team Members</h3>
            <div class="divide-y divide-slate-100">
              <div v-for="(author, idx) in topAuthors" :key="idx" class="py-3 flex items-center justify-between">
                <div class="flex items-center gap-3">
                  <div class="w-8 h-8 rounded-full bg-red-100 text-red-700 font-bold flex items-center justify-center text-xs">
                    {{ author.name.charAt(0) }}
                  </div>
                  <div>
                    <div class="font-semibold text-xs text-slate-900">{{ author.name }}</div>
                    <div class="text-xs text-slate-500">{{ author.role }}</div>
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
                <div class="text-xs font-bold opacity-90">{{ tenantIndustry?.name ? `${tenantIndustry.name} Deals Closed` : 'Total Deals Closed' }}</div>
              </div>
              <div class="w-10 h-10 bg-white/20 rounded-full flex items-center justify-center font-bold text-lg">{{ businessSettings.business_icon }}</div>
            </div>

            <div class="p-6 rounded-3xl bg-gradient-to-r from-sky-600 to-indigo-600 text-white shadow-md flex items-center justify-between">
              <div>
                <div class="text-2xl font-black">55,566</div>
                <div class="text-xs font-bold opacity-90">Total Inquiries Processed</div>
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
