<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import Navbar from '@/Components/Navbar.vue';
import GlobalSearchModal from '@/Components/GlobalSearchModal.vue';
import CreateMemberModal from '@/Components/CreateMemberModal.vue';
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
  PlusIcon,
  MagnifyingGlassIcon,
  FunnelIcon,
  ChevronLeftIcon,
  ChevronRightIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
  members: Object,
  metrics: Object,
  counselors: Array,
  filters: Object,
});

const page = usePage();
const customNav = computed(() => page.props.custom_nav || {});
const businessSettings = computed(() => page.props.business_settings || { business_name: 'JRV CRM', business_icon: '♥' });

const getNavLabel = (key, fallback) => customNav.value[key]?.label || fallback;
const getNavRoute = (key, fallback) => customNav.value[key]?.route || fallback;

const isSearchOpen = ref(false);
const isCreateMemberOpen = ref(false);
const showAdvanceFilter = ref(false);
const isDense = ref(false);
const perPage = ref(props.filters.per_page || 25);

// Filter Form Inputs
const userEnterFromDate = ref('');
const userEnterToDate = ref('');
const userActivityFromDate = ref('');
const userActivityToDate = ref('');
const specialCase = ref('');
const religiousVerification = ref('');
const professionalStatus = ref('');
const installmentExpired = ref('');
const remainingInstallment = ref('');
const searchTitle = ref(props.filters.query || '');
const sortBy = ref('Registration');
const sortOrder = ref('Desc');

const applyFilter = () => {
  router.get('/matrimonial/directory', {
    query: searchTitle.value,
    verification_status: religiousVerification.value,
    per_page: perPage.value,
  }, { preserveState: true });
};

const changePerPage = (event) => {
  perPage.value = event.target.value;
  router.get('/matrimonial/directory', {
    ...props.filters,
    per_page: perPage.value,
  }, { preserveState: true });
};

const navigateToPage = (url) => {
  if (url) {
    router.get(url, {}, { preserveState: true, preserveScroll: true });
  }
};
</script>

<template>
  <Head title="Bio-data Directory & Pagination - JRV CRM" />

  <div class="min-h-screen bg-slate-100 flex font-sans text-slate-900">
    <!-- Navbar (Fixed Left Vertical Sidebar) -->
    <Navbar @open-search="isSearchOpen = true" />

    <!-- Main Content Area -->
    <div class="flex-1 flex flex-col min-w-0">
      <!-- Top Header Action Bar -->
      <header class="bg-white border-b border-slate-200 px-6 py-3.5 flex flex-wrap items-center justify-between gap-3 shadow-xs">
        <div class="flex flex-wrap items-center gap-2">
          <button class="px-4 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white font-extrabold text-xs rounded-xl shadow-xs transition-all">
            My Work
          </button>
          <button class="px-4 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white font-extrabold text-xs rounded-xl shadow-xs transition-all">
            Login History
          </button>
          <button class="px-4 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white font-extrabold text-xs rounded-xl shadow-xs transition-all">
            My Task
          </button>
          <button @click="isCreateMemberOpen = true" class="px-4 py-1.5 bg-red-600 hover:bg-red-700 text-white font-extrabold text-xs rounded-xl shadow-xs transition-all flex items-center gap-1.5">
            <PlusIcon class="w-4 h-4 stroke-[3]" />
            <span>+ Add Bio-Data Profile</span>
          </button>
          <Link href="/tenant/settings/navigation" class="px-4 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white font-extrabold text-xs rounded-xl shadow-xs transition-all flex items-center gap-1.5">
            <AdjustmentsHorizontalIcon class="w-4 h-4" />
            <span>Customize Brand & Menu</span>
          </Link>
        </div>

        <div class="flex items-center gap-3">
          <div class="relative">
            <button @click="isSearchOpen = true" class="p-2 bg-slate-100 rounded-full text-slate-600 hover:bg-slate-200 relative">
              <BellIcon class="w-5 h-5" />
              <span class="absolute -top-1 -right-1 bg-indigo-600 text-white text-[10px] font-black w-4 h-4 rounded-full flex items-center justify-center">
                2
              </span>
            </button>
          </div>

          <div class="w-9 h-9 bg-red-600 rounded-full flex items-center justify-center text-white font-black text-sm shadow-md">
            {{ businessSettings.business_icon }}
          </div>
        </div>
      </header>

      <!-- Page Body Container -->
      <div class="p-6 space-y-6 flex-1 overflow-y-auto w-full">
        <!-- Top Action Bar & Get Count Button -->
        <div class="flex items-center justify-between">
          <button class="px-4 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-lg shadow-xs transition-all">
            Get Count
          </button>
        </div>

        <!-- Comprehensive Multi-Criteria Bio-data Filter Bar -->
        <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-xs space-y-4">
          <!-- Row 1: Bilingual Date Filters & Special Case -->
          <div class="grid grid-cols-1 md:grid-cols-5 gap-3">
            <div class="space-y-1">
              <label class="text-[10px] text-slate-500 font-medium">User Enter From Date (प्रविष्टि प्रारंभ तिथि)</label>
              <input v-model="userEnterFromDate" type="date" class="w-full bg-slate-50 border border-slate-300 rounded-lg px-2.5 py-1.5 text-xs text-slate-700 font-semibold" />
            </div>

            <div class="space-y-1">
              <label class="text-[10px] text-slate-500 font-medium">User Enter to Date (प्रविष्टि तक तिथि)</label>
              <input v-model="userEnterToDate" type="date" class="w-full bg-slate-50 border border-slate-300 rounded-lg px-2.5 py-1.5 text-xs text-slate-700 font-semibold" />
            </div>

            <div class="space-y-1">
              <label class="text-[10px] text-slate-500 font-medium">User Activity From Date (गतिविधि प्रारंभ तिथि)</label>
              <input v-model="userActivityFromDate" type="date" class="w-full bg-slate-50 border border-slate-300 rounded-lg px-2.5 py-1.5 text-xs text-slate-700 font-semibold" />
            </div>

            <div class="space-y-1">
              <label class="text-[10px] text-slate-500 font-medium">User Activity to Date (गतिविधि तक तिथि)</label>
              <input v-model="userActivityToDate" type="date" class="w-full bg-slate-50 border border-slate-300 rounded-lg px-2.5 py-1.5 text-xs text-slate-700 font-semibold" />
            </div>

            <div class="space-y-1">
              <label class="text-[10px] text-slate-500 font-medium">&nbsp;</label>
              <select v-model="specialCase" class="w-full bg-white border border-slate-300 rounded-lg px-2.5 py-1.5 text-xs text-slate-700 font-semibold">
                <option value="">Special Case</option>
                <option value="VIP">VIP Case</option>
                <option value="Urgent">Urgent Match</option>
              </select>
            </div>
          </div>

          <!-- Row 2: Status Dropdowns & Filter Buttons -->
          <div class="grid grid-cols-1 md:grid-cols-6 gap-3 items-end">
            <div>
              <select v-model="religiousVerification" class="w-full bg-white border border-slate-300 rounded-lg px-2.5 py-1.5 text-xs text-slate-700 font-semibold">
                <option value="">Religious Verification</option>
                <option value="Verified">Verified Only</option>
                <option value="Pending Review">Pending Review</option>
              </select>
            </div>

            <div>
              <select v-model="professionalStatus" class="w-full bg-white border border-slate-300 rounded-lg px-2.5 py-1.5 text-xs text-slate-700 font-semibold">
                <option value="">Professional Details Status</option>
                <option value="Filled">Details Filled</option>
                <option value="Pending">Pending Info</option>
              </select>
            </div>

            <div>
              <select v-model="installmentExpired" class="w-full bg-white border border-slate-300 rounded-lg px-2.5 py-1.5 text-xs text-slate-700 font-semibold">
                <option value="">Installment Expired</option>
                <option value="Yes">Yes</option>
                <option value="No">No</option>
              </select>
            </div>

            <div>
              <select v-model="remainingInstallment" class="w-full bg-white border border-slate-300 rounded-lg px-2.5 py-1.5 text-xs text-slate-700 font-semibold">
                <option value="">Remaining Installment</option>
                <option value="0">0</option>
                <option value="1">1</option>
              </select>
            </div>

            <div>
              <button @click="showAdvanceFilter = !showAdvanceFilter" class="w-full px-3 py-1.5 bg-red-600 hover:bg-red-700 text-white font-extrabold text-xs rounded-lg shadow-xs transition-all">
                Show Advance Filter
              </button>
            </div>

            <div class="flex justify-end">
              <button @click="applyFilter" class="px-5 py-1.5 bg-red-600 hover:bg-red-700 text-white font-extrabold text-xs rounded-lg shadow-md transition-all">
                Apply Filter
              </button>
            </div>
          </div>

          <!-- Row 3: Sorting Options & Toggle Switch -->
          <div class="pt-2 border-t border-slate-100 flex flex-wrap items-center justify-between gap-4 text-xs font-bold text-slate-700">
            <div class="flex items-center gap-4">
              <label class="flex items-center gap-1.5 cursor-pointer">
                <input type="radio" v-model="sortBy" value="Name" class="text-red-600 focus:ring-red-500" />
                <span>Name</span>
              </label>

              <label class="flex items-center gap-1.5 cursor-pointer">
                <input type="radio" v-model="sortBy" value="Id" class="text-red-600 focus:ring-red-500" />
                <span>Id</span>
              </label>

              <label class="flex items-center gap-1.5 cursor-pointer">
                <input type="radio" v-model="sortBy" value="Registration" class="text-red-600 focus:ring-red-500" />
                <span class="flex items-center gap-1">
                  Registration 
                  <span class="w-4 h-4 bg-red-600 text-white text-[10px] font-black rounded-full flex items-center justify-center">1</span>
                </span>
              </label>
            </div>

            <div class="flex items-center gap-3">
              <span class="text-slate-500 font-semibold">Sort Order:</span>
              <label class="flex items-center gap-1 cursor-pointer">
                <input type="radio" v-model="sortOrder" value="Asc" class="text-red-600" />
                <span>Asc</span>
              </label>
              <label class="flex items-center gap-1 cursor-pointer">
                <input type="radio" v-model="sortOrder" value="Desc" class="text-red-600" />
                <span class="text-red-600 font-black">Desc</span>
              </label>
            </div>
          </div>
        </div>

        <!-- Detailed Bio-data Member Cards Stack -->
        <div class="space-y-6">
          <div 
            v-for="member in members.data" 
            :key="member.id"
            :class="[
              'border-2 border-amber-400 rounded-2xl bg-white p-4 shadow-sm space-y-3 transition-all',
              isDense ? 'p-2 space-y-1 text-xs' : 'p-4 space-y-3'
            ]"
          >
            <!-- Top Header Yellow Bar Highlight -->
            <div class="text-[11px] font-bold text-amber-900 bg-amber-50 px-3 py-1 rounded-lg border border-amber-200 flex flex-wrap items-center gap-1">
              <span class="text-red-600 font-extrabold">Available in :</span>
              <span class="text-slate-800">Biodata | offline | WhatsApp response | paid promotion | social media | group service | incoming user</span>
            </div>

            <!-- Card Content Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 items-start pt-1">
              <!-- Left Column: Avatar & Quick Badges -->
              <div class="lg:col-span-2 flex flex-col items-center space-y-2 text-center">
                <div class="w-24 h-24 bg-slate-200 rounded-xl overflow-hidden border border-slate-300 flex items-center justify-center shadow-xs">
                  <img 
                    :src="member.gender === 'Female' ? 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=150&auto=format&fit=crop&q=80' : 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=150&auto=format&fit=crop&q=80'" 
                    :alt="member.full_name"
                    class="w-full h-full object-cover" 
                  />
                </div>

                <div class="flex flex-col items-center gap-1 w-full">
                  <span class="px-3 py-0.5 bg-cyan-100 text-cyan-800 font-black text-[10px] rounded uppercase tracking-wider w-full">
                    MASTER
                  </span>
                  <span class="px-3 py-0.5 bg-emerald-600 text-white font-bold text-[10px] rounded flex items-center justify-center gap-1 w-full">
                    <span>LIVE</span>
                    <span class="w-1.5 h-1.5 rounded-full bg-white animate-pulse"></span>
                  </span>
                  <div class="text-[9px] text-red-600 font-bold">
                    Last Active: {{ member.created_at_formatted }}
                  </div>
                </div>

                <button class="w-full py-1 bg-red-600 hover:bg-red-700 text-white font-extrabold text-[10px] rounded shadow-xs transition-all">
                  Use For Special Service
                </button>
              </div>

              <!-- Center Column: Core Bio-data Attributes -->
              <div class="lg:col-span-6 space-y-2 text-xs text-slate-800">
                <div class="flex flex-wrap items-center gap-2">
                  <span class="font-extrabold text-slate-900 text-sm">
                    {{ member.member_code }}
                  </span>
                  <span class="w-4 h-4 bg-red-600 text-white text-[10px] font-black rounded-full flex items-center justify-center">1</span>
                  
                  <span class="font-extrabold text-red-600 text-sm">
                    {{ member.first_name }} {{ member.last_name }} ({{ member.age }} years)
                  </span>
                </div>

                <div class="flex items-center gap-3 text-slate-600 font-bold text-[11px]">
                  <span class="flex items-center gap-1">
                    <UserIcon class="w-3.5 h-3.5 text-slate-500" />
                    {{ member.gender }}
                  </span>
                  <span>|</span>
                  <span>Self</span>
                  <span>|</span>
                  <span class="text-slate-500">Create: {{ member.created_at_formatted }} (Self)</span>
                </div>

                <div class="flex flex-wrap items-center gap-2 pt-1 font-mono font-bold text-slate-900">
                  <span>{{ member.phone }}</span>
                  <span>•</span>
                  <span>{{ member.alternate_phone || '8088788209' }}</span>
                </div>

                <!-- Horizontal Badge Pills Bar -->
                <div class="flex flex-wrap items-center gap-1.5 pt-1">
                  <span class="px-2 py-0.5 bg-red-600 text-white font-black text-[9px] rounded uppercase">FORMS</span>
                  <span class="px-2.5 py-0.5 bg-white border border-slate-300 text-slate-700 font-bold text-[10px] rounded shadow-xs">Jain Contact</span>
                  <span class="px-2.5 py-0.5 bg-white border border-slate-300 text-slate-700 font-bold text-[10px] rounded shadow-xs">PIC</span>
                  <span class="px-2 py-0.5 bg-amber-200 text-amber-900 font-bold text-[10px] rounded">BIODATA</span>
                  <span class="px-2 py-0.5 bg-red-100 text-red-700 font-black text-[9px] rounded uppercase">OTP BACK</span>
                </div>

                <div class="pt-2">
                  <button class="px-4 py-1 bg-emerald-500 hover:bg-emerald-600 text-white font-bold text-[11px] rounded-lg shadow-xs transition-all">
                    Show Other Info
                  </button>
                </div>
              </div>

              <!-- Right Column: Action Pills & Status Radio Group -->
              <div class="lg:col-span-4 flex flex-col sm:flex-row items-start justify-between gap-4 border-l border-slate-100 pl-4">
                <div class="space-y-1 w-full sm:w-auto">
                  <button class="w-full px-3 py-1 bg-blue-600 text-white font-extrabold text-[10px] rounded-full shadow-xs">Call status</button>
                  <button class="w-full px-3 py-1 bg-lime-500 text-white font-extrabold text-[10px] rounded-full shadow-xs">All Notes</button>
                  <button class="w-full px-3 py-1 bg-emerald-600 text-white font-extrabold text-[10px] rounded-full shadow-xs">Paid</button>
                  <button class="w-full px-3 py-1 bg-amber-500 text-white font-extrabold text-[10px] rounded-full shadow-xs">Partner Preference</button>
                  <button class="w-full px-3 py-1 bg-slate-600 text-white font-extrabold text-[10px] rounded-full shadow-xs">Fill Professional Info</button>
                  <button class="w-full px-3 py-1 bg-orange-500 text-white font-extrabold text-[10px] rounded-full shadow-xs">Member Details</button>
                  <button class="w-full px-3 py-1 bg-red-600 text-white font-extrabold text-[10px] rounded-full shadow-xs">Activity</button>
                </div>

                <div class="space-y-1 text-[10px] font-bold text-slate-700">
                  <label class="flex items-center gap-1.5 cursor-pointer">
                    <input type="radio" :name="'status_' + member.id" value="PRIVATE" class="text-red-600" />
                    <span>PRIVATE</span>
                  </label>
                  <label class="flex items-center gap-1.5 cursor-pointer">
                    <input type="radio" :name="'status_' + member.id" value="LIVE" checked class="text-red-600" />
                    <span>LIVE</span>
                  </label>
                  <label class="flex items-center gap-1.5 cursor-pointer">
                    <input type="radio" :name="'status_' + member.id" value="APPROVAL" class="text-red-600" />
                    <span>APPROVAL</span>
                  </label>
                  <label class="flex items-center gap-1.5 cursor-pointer">
                    <input type="radio" :name="'status_' + member.id" value="PUBLISH_LIVE" class="text-red-600" />
                    <span>PUBLISH &amp; LIVE</span>
                  </label>
                  <label class="flex items-center gap-1.5 cursor-pointer">
                    <input type="radio" :name="'status_' + member.id" value="TEST_USER" class="text-red-600" />
                    <span>TEST USER</span>
                  </label>
                  <label class="flex items-center gap-1.5 cursor-pointer">
                    <input type="radio" :name="'status_' + member.id" value="HIDE" class="text-red-600" />
                    <span>HIDE</span>
                  </label>
                  <label class="flex items-center gap-1.5 cursor-pointer text-emerald-600 font-extrabold">
                    <input type="radio" :name="'status_' + member.id" value="SERVICE_VERIFIED" checked class="text-emerald-600" />
                    <span>Service Verified</span>
                  </label>
                </div>

                <div class="space-y-1.5 w-full sm:w-auto">
                  <button class="w-full px-4 py-1 bg-blue-600 hover:bg-blue-700 text-white font-extrabold text-[10px] rounded shadow-xs">Note</button>
                  <button class="w-full px-4 py-1 bg-red-600 hover:bg-red-700 text-white font-extrabold text-[10px] rounded shadow-xs">Open</button>
                  <button class="w-full px-4 py-1 bg-cyan-400 hover:bg-cyan-500 text-slate-900 font-extrabold text-[10px] rounded shadow-xs">@LINK</button>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- BOTTOM PAGINATION BAR (Exact Layout matching Screenshot) -->
        <div class="bg-white border border-slate-200 rounded-3xl p-6 shadow-xs space-y-4">
          <!-- Top Row of Bottom Bar: Dense Toggle Switch & Rows per page & Count -->
          <div class="flex flex-wrap items-center justify-between gap-4">
            <!-- Left Side: Dense Red Switch Toggle -->
            <div class="flex items-center gap-3">
              <button 
                @click="isDense = !isDense"
                :class="[
                  'w-11 h-6 rounded-full p-1 transition-colors flex items-center shadow-xs',
                  isDense ? 'bg-red-600 justify-end' : 'bg-red-500 justify-start'
                ]"
              >
                <div class="w-4 h-4 rounded-full bg-white shadow-md"></div>
              </button>
              <span class="text-xs font-bold text-slate-700">Dense</span>
            </div>

            <!-- Right Side: Rows per page & Record Count range -->
            <div class="flex items-center gap-6 text-xs text-slate-600 font-semibold">
              <div class="flex items-center gap-2">
                <span>Rows per page:</span>
                <select 
                  :value="perPage" 
                  @change="changePerPage"
                  class="bg-white border border-slate-300 rounded-lg px-2 py-1 text-xs font-bold text-slate-900 focus:outline-none"
                >
                  <option value="10">10</option>
                  <option value="25">25</option>
                  <option value="50">50</option>
                  <option value="100">100</option>
                </select>
              </div>

              <div>
                <span>{{ members.meta.from || 1 }}–{{ members.meta.to || members.meta.total }} of {{ members.meta.total }}</span>
              </div>

              <!-- Top Right Chevrons -->
              <div class="flex items-center gap-1 text-slate-400">
                <button @click="navigateToPage(members.links.prev)" :disabled="!members.links.prev" class="p-1 hover:text-slate-700 disabled:opacity-30">
                  <ChevronLeftIcon class="w-4 h-4" />
                </button>
                <button @click="navigateToPage(members.links.next)" :disabled="!members.links.next" class="p-1 hover:text-slate-700 disabled:opacity-30">
                  <ChevronRightIcon class="w-4 h-4" />
                </button>
              </div>
            </div>
          </div>

          <!-- Bottom Row of Pagination Bar: Numbered Box Buttons Grid (< 1 2 3 4 5 ... 4607 >) -->
          <div class="flex items-center gap-2 pt-2 border-t border-slate-100">
            <!-- Prev Button -->
            <button 
              @click="navigateToPage(members.links.prev)"
              :disabled="!members.links.prev"
              class="w-8 h-8 rounded-lg border border-slate-200 bg-white text-slate-600 font-bold flex items-center justify-center hover:bg-slate-50 disabled:opacity-40"
            >
              <ChevronLeftIcon class="w-4 h-4" />
            </button>

            <!-- Page Number Links -->
            <template v-for="(link, idx) in members.meta.links" :key="idx">
              <button 
                v-if="link.url && !link.label.includes('Previous') && !link.label.includes('Next')"
                @click="navigateToPage(link.url)"
                :class="[
                  'px-3.5 h-8 rounded-lg text-xs font-bold transition-all border',
                  link.active 
                    ? 'bg-indigo-100 text-indigo-600 border-indigo-300 font-black shadow-xs' 
                    : 'bg-white text-slate-700 border-slate-200 hover:bg-slate-50'
                ]"
              >
                {{ link.label }}
              </button>
            </template>

            <!-- Next Button -->
            <button 
              @click="navigateToPage(members.links.next)"
              :disabled="!members.links.next"
              class="w-8 h-8 rounded-lg border border-slate-200 bg-white text-slate-600 font-bold flex items-center justify-center hover:bg-slate-50 disabled:opacity-40"
            >
              <ChevronRightIcon class="w-4 h-4" />
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Modals -->
    <GlobalSearchModal :is-open="isSearchOpen" @close="isSearchOpen = false" />
    <CreateMemberModal :is-open="isCreateMemberOpen" :counselors="counselors" @close="isCreateMemberOpen = false" />
  </div>
</template>
