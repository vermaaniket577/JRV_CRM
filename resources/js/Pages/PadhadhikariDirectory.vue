<script setup>
import { ref, computed } from 'vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import Navbar from '@/Components/Navbar.vue';
import GlobalSearchModal from '@/Components/GlobalSearchModal.vue';
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
  PlusIcon,
  PhoneIcon,
  EnvelopeIcon,
  BuildingOfficeIcon,
  ShieldCheckIcon,
  ArrowRightOnRectangleIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
  officers: Object,
  metrics: Object,
});

const page = usePage();
const customNav = computed(() => page.props.custom_nav || {});
const businessSettings = computed(() => page.props.business_settings || { business_name: 'JRV CRM', business_icon: '♥' });

const getNavLabel = (key, fallback) => customNav.value[key]?.label || fallback;
const getNavRoute = (key, fallback) => customNav.value[key]?.route || fallback;

const isSearchOpen = ref(false);
const isAddOpen = ref(false);
const activeTab = ref('all');

const form = useForm({
  name: '',
  designation: 'National President',
  caste_group: 'Jain Digambar',
  region: 'National',
  contact_number: '+91 9876543210',
  email: '',
  term_start: '2025-01-01',
  term_end: '2027-12-31',
  responsibilities: 'Overall community directory administration & state events management.',
});

const submit = () => {
  form.post('/padhadhikari-directory', {
    onSuccess: () => {
      form.reset();
      isAddOpen.value = false;
    },
  });
};
</script>

<template>
  <Head title="Padhadhikari Directory - JRV CRM" />

  <div class="min-h-screen bg-slate-100 flex font-sans text-slate-900">
    <!-- Navbar (Fixed Left Vertical Sidebar) -->
    <Navbar @open-search="isSearchOpen = true" />

    <!-- Main Content Area -->
    <div class="flex-1 flex flex-col min-w-0">
      <header class="bg-white border-b border-slate-200 px-6 py-3.5 flex flex-wrap items-center justify-between gap-3 shadow-xs">
        <div class="flex flex-wrap items-center gap-2">
          <button 
            @click="activeTab = 'all'"
            :class="[
              'px-4 py-1.5 font-extrabold text-xs rounded-xl shadow-xs transition-all cursor-pointer',
              activeTab === 'all' ? 'bg-red-600 text-white' : 'bg-slate-100 text-slate-700 hover:bg-slate-200'
            ]"
          >
            All Bearers
          </button>
          <button @click="isAddOpen = true" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-extrabold text-xs rounded-xl shadow-xs flex items-center gap-1.5 cursor-pointer">
            <PlusIcon class="w-4 h-4 stroke-[3]" />
            <span>+ Add Office Bearer</span>
          </button>
        </div>

        <div class="flex items-center gap-3">
          <Link 
            href="/logout" 
            method="post" 
            as="button" 
            title="Logout" 
            class="px-3.5 py-1.5 bg-red-50 hover:bg-red-100 text-red-600 border border-red-200 rounded-xl font-extrabold text-xs flex items-center gap-1.5 shadow-2xs cursor-pointer transition-all"
          >
            <ArrowRightOnRectangleIcon class="w-4 h-4 stroke-[2.5]" />
            <span>Logout</span>
          </Link>
          <div class="w-9 h-9 bg-red-600 rounded-full flex items-center justify-center text-white font-black text-sm shadow-md">
            {{ businessSettings.business_icon }}
          </div>
        </div>
      </header>

      <div class="p-8 space-y-6 flex-1 overflow-y-auto max-w-7xl mx-auto w-full">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-200 pb-6">
          <div>
            <div class="flex items-center gap-2 text-xs font-bold text-red-600 uppercase tracking-wider">
              <AcademicCapIcon class="w-4 h-4" />
              <span>Community Leadership Council</span>
            </div>
            <h1 class="text-3xl font-black tracking-tight text-slate-900 mt-0.5">
              Padhadhikari (Office Bearers) Directory
            </h1>
          </div>

          <button @click="isAddOpen = true" class="px-6 py-2.5 bg-red-600 hover:bg-red-700 text-white font-bold text-sm rounded-xl shadow-md flex items-center gap-2 transition-all">
            <PlusIcon class="w-5 h-5 stroke-[3]" />
            <span>Add Padhadhikari</span>
          </button>
        </div>

        <!-- Metrics Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
          <div class="p-6 bg-white rounded-2xl border border-slate-200 shadow-xs space-y-2">
            <div class="text-xs font-bold text-slate-500 uppercase">Total Office Bearers</div>
            <div class="text-3xl font-black text-slate-900">{{ metrics.total_officers }}</div>
            <p class="text-xs text-indigo-600 font-semibold">Active board members</p>
          </div>

          <div class="p-6 bg-white rounded-2xl border border-slate-200 shadow-xs space-y-2">
            <div class="text-xs font-bold text-slate-500 uppercase">Active Regions</div>
            <div class="text-3xl font-black text-emerald-600">{{ metrics.active_regions }}</div>
            <p class="text-xs text-emerald-600 font-semibold">State & regional chapters</p>
          </div>

          <div class="p-6 bg-white rounded-2xl border border-slate-200 shadow-xs space-y-2">
            <div class="text-xs font-bold text-slate-500 uppercase">National Board</div>
            <div class="text-3xl font-black text-sky-600">{{ metrics.national_board_members }}</div>
            <p class="text-xs text-sky-600 font-semibold">Executive committee</p>
          </div>

          <div class="p-6 bg-white rounded-2xl border border-slate-200 shadow-xs space-y-2">
            <div class="text-xs font-bold text-slate-500 uppercase">Term Renewals</div>
            <div class="text-3xl font-black text-amber-600">{{ metrics.renewals_due }}</div>
            <p class="text-xs text-amber-600 font-semibold">Due next quarter</p>
          </div>
        </div>

        <!-- TAB 1: ALL OFFICE BEARERS DIRECTORY -->
        <div v-if="activeTab === 'all'" class="space-y-6">
          <div v-if="officers.data.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <div v-for="o in officers.data" :key="o.id" class="bg-white border border-slate-200 rounded-3xl p-6 shadow-xs space-y-4 relative flex flex-col justify-between hover:border-red-300 transition-all">
              <div class="space-y-3">
                <div class="flex items-center gap-3">
                  <div class="w-12 h-12 rounded-full bg-red-100 text-red-700 font-black text-base flex items-center justify-center">
                    {{ o.name.charAt(0) }}
                  </div>
                  <div>
                    <h3 class="text-base font-extrabold text-slate-900">{{ o.name }}</h3>
                    <span class="px-2.5 py-0.5 bg-red-50 text-red-600 border border-red-200 rounded-full text-[10px] font-extrabold uppercase">
                      {{ o.designation }}
                    </span>
                  </div>
                </div>

                <div class="space-y-1.5 text-xs text-slate-600 bg-slate-50 p-3 rounded-2xl border border-slate-100">
                  <div><span class="font-bold text-slate-400">Community Group:</span> {{ o.caste_group }}</div>
                  <div><span class="font-bold text-slate-400">Region Jurisdiction:</span> {{ o.region }}</div>
                  <div><span class="font-bold text-slate-400">Contact:</span> {{ o.contact_number }}</div>
                  <div><span class="font-bold text-slate-400">Email:</span> {{ o.email }}</div>
                </div>
              </div>

              <div class="pt-3 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500 font-semibold">
                <span>Status: <strong class="text-emerald-600">{{ o.status }}</strong></span>
                <span>Term: {{ o.term_start ? o.term_start.substring(0, 10) : '' }} - {{ o.term_end ? o.term_end.substring(0, 10) : 'Present' }}</span>
              </div>
            </div>
          </div>
          <div v-else class="p-12 text-center bg-white rounded-3xl border border-slate-200 space-y-3">
            <AcademicCapIcon class="w-12 h-12 text-slate-300 mx-auto" />
            <h3 class="text-lg font-extrabold text-slate-900">No Office Bearers Registered Yet</h3>
            <p class="text-xs text-slate-500 max-w-sm mx-auto">Click "+ Add Office Bearer" above to register your first national or regional leader.</p>
          </div>
        </div>

        <!-- TAB 2: MY WORK PORTFOLIO -->
        <div v-if="activeTab === 'my_work'" class="space-y-6">
          <div class="bg-white rounded-3xl border border-slate-200 p-6 space-y-4">
            <h3 class="text-lg font-extrabold text-slate-900 flex items-center gap-2">
              <BriefcaseIcon class="w-5 h-5 text-red-600" />
              <span>Active Leadership Work & Projects</span>
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200 space-y-2">
                <div class="flex items-center justify-between">
                  <span class="font-extrabold text-xs text-slate-900">Annual Leadership Convention 2026</span>
                  <span class="px-2 py-0.5 bg-amber-50 text-amber-700 text-[10px] font-bold rounded-full">In Progress</span>
                </div>
                <p class="text-xs text-slate-500">Coordinating state delegates, venue booking, and key notes for national conference.</p>
              </div>
              <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200 space-y-2">
                <div class="flex items-center justify-between">
                  <span class="font-extrabold text-xs text-slate-900">Bio-Data Verification & Audit</span>
                  <span class="px-2 py-0.5 bg-emerald-50 text-emerald-700 text-[10px] font-bold rounded-full">Active</span>
                </div>
                <p class="text-xs text-slate-500">Reviewing and approving incoming community matrimonial profiles across 12 zones.</p>
              </div>
            </div>
          </div>
        </div>

        <!-- TAB 3: LOGIN HISTORY LOGS -->
        <div v-if="activeTab === 'login_history'" class="bg-white rounded-3xl border border-slate-200 overflow-hidden">
          <div class="p-6 border-b border-slate-100 flex items-center justify-between">
            <h3 class="text-base font-extrabold text-slate-900 flex items-center gap-2">
              <ShieldCheckIcon class="w-5 h-5 text-red-600" />
              <span>Recent Office Bearer Login Logs</span>
            </h3>
            <span class="text-xs font-bold text-slate-400">Security Audit Trail</span>
          </div>
          <table class="w-full text-left text-xs font-medium text-slate-700">
            <thead class="bg-slate-50 text-slate-500 font-extrabold uppercase border-b border-slate-200">
              <tr>
                <th class="px-6 py-3">User / Officer</th>
                <th class="px-6 py-3">IP Address</th>
                <th class="px-6 py-3">Login Time</th>
                <th class="px-6 py-3">Status</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
              <tr class="hover:bg-slate-50">
                <td class="px-6 py-3.5 font-bold text-slate-900">Alex Mercer (President)</td>
                <td class="px-6 py-3.5 text-slate-500 font-mono">127.0.0.1</td>
                <td class="px-6 py-3.5 text-slate-500">Today, 03:45 PM</td>
                <td class="px-6 py-3.5"><span class="px-2.5 py-0.5 bg-emerald-50 text-emerald-700 font-bold text-[10px] rounded-full">Success</span></td>
              </tr>
              <tr class="hover:bg-slate-50">
                <td class="px-6 py-3.5 font-bold text-slate-900">Sarah Connor (Secretary)</td>
                <td class="px-6 py-3.5 text-slate-500 font-mono">192.168.1.45</td>
                <td class="px-6 py-3.5 text-slate-500">Today, 01:20 PM</td>
                <td class="px-6 py-3.5"><span class="px-2.5 py-0.5 bg-emerald-50 text-emerald-700 font-bold text-[10px] rounded-full">Success</span></td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- TAB 4: MY TASK DASHBOARD -->
        <div v-if="activeTab === 'my_tasks'" class="space-y-4">
          <div class="bg-white rounded-3xl border border-slate-200 p-6 space-y-4">
            <h3 class="text-base font-extrabold text-slate-900 flex items-center gap-2">
              <DocumentTextIcon class="w-5 h-5 text-red-600" />
              <span>Assigned Board Tasks</span>
            </h3>
            <div class="space-y-3">
              <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200 flex items-center justify-between">
                <div class="space-y-0.5">
                  <div class="font-extrabold text-xs text-slate-900">Review State Executive Committee Nominations</div>
                  <div class="text-[11px] text-slate-500">Submit recommendation report before month-end.</div>
                </div>
                <span class="px-3 py-1 bg-red-50 text-red-600 font-extrabold text-[10px] rounded-xl border border-red-200">High Priority</span>
              </div>
              <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200 flex items-center justify-between">
                <div class="space-y-0.5">
                  <div class="font-extrabold text-xs text-slate-900">Quarterly Financial & Budget Review</div>
                  <div class="text-[11px] text-slate-500">Validate regional office bearer expense vouchers.</div>
                </div>
                <span class="px-3 py-1 bg-amber-50 text-amber-700 font-extrabold text-[10px] rounded-xl border border-amber-200">Pending Review</span>
              </div>
            </div>
          </div>
        </div>

        <div v-else class="bg-white border border-slate-200 rounded-3xl p-16 text-center space-y-3">
          <p class="text-base font-bold text-slate-500">No Padhadhikari office bearers registered yet.</p>
          <button @click="isAddOpen = true" class="text-xs font-bold text-red-600 underline">Add First Office Bearer</button>
        </div>
      </div>
    </div>

    <!-- Add Modal -->
    <div v-if="isAddOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-xs">
      <div class="bg-white border border-slate-200 rounded-3xl max-w-lg w-full shadow-2xl p-6 space-y-4">
        <h3 class="text-lg font-bold text-slate-900">Add Padhadhikari (Office Bearer)</h3>
        <form @submit.prevent="submit" class="space-y-4">
          <div>
            <label class="text-xs font-bold text-slate-700">Full Name</label>
            <input v-model="form.name" type="text" placeholder="Full Name" required class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3 py-2 text-xs font-bold" />
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="text-xs font-bold text-slate-700">Designation</label>
              <input v-model="form.designation" type="text" placeholder="National President" required class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3 py-2 text-xs font-bold" />
            </div>
            <div>
              <label class="text-xs font-bold text-slate-700">Region</label>
              <input v-model="form.region" type="text" placeholder="California Chapter" required class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3 py-2 text-xs font-bold" />
            </div>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="text-xs font-bold text-slate-700">Phone</label>
              <input v-model="form.contact_number" type="text" required class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3 py-2 text-xs font-bold" />
            </div>
            <div>
              <label class="text-xs font-bold text-slate-700">Email</label>
              <input v-model="form.email" type="email" required class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3 py-2 text-xs font-bold" />
            </div>
          </div>

          <div class="flex justify-end gap-3 pt-2">
            <button type="button" @click="isAddOpen = false" class="px-4 py-2 text-xs font-bold text-slate-500">Cancel</button>
            <button type="submit" class="px-5 py-2 bg-red-600 hover:bg-red-700 text-white text-xs font-extrabold rounded-xl shadow-md">Add Officer</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Modals -->
    <GlobalSearchModal :is-open="isSearchOpen" @close="isSearchOpen = false" />
  </div>
</template>
