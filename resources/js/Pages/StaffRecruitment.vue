<script setup>
import { ref, computed } from 'vue';
import { Head, Link, useForm, router, usePage } from '@inertiajs/vue3';
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
  CurrencyRupeeIcon,
  ClockIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
  jobPostings: Array,
  applications: Array,
  metrics: Object,
});

const page = usePage();
const customNav = computed(() => page.props.custom_nav || {});
const businessSettings = computed(() => page.props.business_settings || { business_name: 'JRV CRM', business_icon: '♥' });

const getNavLabel = (key, fallback) => customNav.value[key]?.label || fallback;
const getNavRoute = (key, fallback) => customNav.value[key]?.route || fallback;

const isSearchOpen = ref(false);
const isAddJobOpen = ref(false);

const form = useForm({
  title: 'Community Matchmaker & Counselor',
  department: 'Counseling & Matchmaking',
  location: 'San Francisco, CA',
  employment_type: 'Full-Time',
  salary_min: 35000,
  salary_max: 55000,
  description: 'Responsible for conducting member bio-data verification and candidate shortlisting.',
});

const submitJob = () => {
  form.post('/staff-recruitment/jobs', {
    onSuccess: () => {
      form.reset();
      isAddJobOpen.value = false;
    },
  });
};

const updateApplicantStage = (appId, stage) => {
  router.post(`/staff-recruitment/applications/${appId}/stage`, { stage }, { preserveScroll: true });
};
</script>

<template>
  <Head title="Staff Recruitment Pipeline - JRV CRM" />

  <div class="min-h-screen bg-slate-100 flex font-sans text-slate-900">
    <!-- Navbar (Fixed Left Vertical Sidebar) -->
    <Navbar @open-search="isSearchOpen = true" />

    <!-- Main Content Area -->
    <div class="flex-1 flex flex-col min-w-0">
      <header class="bg-white border-b border-slate-200 px-6 py-3.5 flex flex-wrap items-center justify-between gap-3 shadow-xs">
        <div class="flex flex-wrap items-center gap-2">
          <button class="px-4 py-1.5 bg-indigo-600 text-white font-extrabold text-xs rounded-xl shadow-xs">My Work</button>
          <button class="px-4 py-1.5 bg-indigo-600 text-white font-extrabold text-xs rounded-xl shadow-xs">Login History</button>
          <button class="px-4 py-1.5 bg-indigo-600 text-white font-extrabold text-xs rounded-xl shadow-xs">My Task</button>
          <button @click="isAddJobOpen = true" class="px-4 py-1.5 bg-indigo-600 text-white font-extrabold text-xs rounded-xl shadow-xs flex items-center gap-1.5 cursor-pointer">
            <PlusIcon class="w-4 h-4 stroke-[3]" />
            <span>+ Post New Job Vacancy</span>
          </button>
        </div>

        <div class="flex items-center gap-3">
          <div class="w-9 h-9 bg-red-600 rounded-full flex items-center justify-center text-white font-black text-sm shadow-md">
            {{ businessSettings.business_icon }}
          </div>
        </div>
      </header>

      <div class="p-8 space-y-6 flex-1 overflow-y-auto max-w-7xl mx-auto w-full">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-200 pb-6">
          <div>
            <div class="flex items-center gap-2 text-xs font-bold text-red-600 uppercase tracking-wider">
              <BriefcaseIcon class="w-4 h-4" />
              <span>Hiring & Applicant Tracking</span>
            </div>
            <h1 class="text-3xl font-black tracking-tight text-slate-900 mt-0.5">
              Staff Recruitment Pipeline
            </h1>
          </div>

          <button @click="isAddJobOpen = true" class="px-6 py-2.5 bg-red-600 hover:bg-red-700 text-white font-bold text-sm rounded-xl shadow-md flex items-center gap-2 transition-all">
            <PlusIcon class="w-5 h-5 stroke-[3]" />
            <span>Post New Vacancy</span>
          </button>
        </div>

        <!-- Metrics -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
          <div class="p-6 bg-white rounded-2xl border border-slate-200 shadow-xs space-y-2">
            <div class="text-xs font-bold text-slate-500 uppercase">Open Positions</div>
            <div class="text-3xl font-black text-slate-900">{{ metrics.open_positions }}</div>
            <p class="text-xs text-indigo-600 font-semibold">Active job postings</p>
          </div>

          <div class="p-6 bg-white rounded-2xl border border-slate-200 shadow-xs space-y-2">
            <div class="text-xs font-bold text-slate-500 uppercase">Total Applicants</div>
            <div class="text-3xl font-black text-emerald-600">{{ metrics.total_applicants }}</div>
            <p class="text-xs text-emerald-600 font-semibold">Candidate applications</p>
          </div>

          <div class="p-6 bg-white rounded-2xl border border-slate-200 shadow-xs space-y-2">
            <div class="text-xs font-bold text-slate-500 uppercase">Interviews Scheduled</div>
            <div class="text-3xl font-black text-sky-600">{{ metrics.interviews_scheduled }}</div>
            <p class="text-xs text-sky-600 font-semibold">Scheduled this week</p>
          </div>

          <div class="p-6 bg-white rounded-2xl border border-slate-200 shadow-xs space-y-2">
            <div class="text-xs font-bold text-slate-500 uppercase">Hired This Month</div>
            <div class="text-3xl font-black text-rose-600">{{ metrics.hired_this_month }}</div>
            <p class="text-xs text-rose-600 font-semibold">Onboarded employees</p>
          </div>
        </div>

        <!-- Job Vacancies Grid -->
        <div class="bg-white border border-slate-200 rounded-3xl p-6 shadow-xs space-y-4">
          <h3 class="text-base font-bold text-slate-900">Active Job Vacancies</h3>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div v-for="job in jobPostings" :key="job.id" class="p-4 border border-slate-200 rounded-2xl bg-slate-50 space-y-2">
              <div class="flex items-center justify-between">
                <span class="text-xs font-black text-red-600 bg-red-100 border border-red-200 px-2.5 py-0.5 rounded-full">
                  {{ job.department }}
                </span>
                <span class="text-[11px] font-bold text-emerald-600">{{ job.employment_type }}</span>
              </div>
              <h4 class="text-sm font-extrabold text-slate-900">{{ job.title }}</h4>
              <p class="text-xs text-slate-600">Location: {{ job.location }} • Salary Range: ₹{{ Number(job.salary_min).toLocaleString() }} - ₹{{ Number(job.salary_max).toLocaleString() }}</p>
            </div>
          </div>
        </div>

        <!-- Applicant Pipeline Table -->
        <div class="bg-white border border-slate-200 rounded-3xl overflow-hidden shadow-xs space-y-4">
          <div class="p-5 border-b border-slate-200 flex items-center justify-between">
            <h3 class="text-base font-bold text-slate-900">Candidate Pipeline & Applications</h3>
          </div>

          <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
              <thead>
                <tr class="bg-slate-50 border-b border-slate-200 text-slate-500 font-bold uppercase text-[10px]">
                  <th class="py-3 px-4">Applicant Name</th>
                  <th class="py-3 px-4">Applied Position</th>
                  <th class="py-3 px-4">Experience</th>
                  <th class="py-3 px-4">Contact</th>
                  <th class="py-3 px-4">Pipeline Stage</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100">
                <tr v-for="app in applications" :key="app.id" class="hover:bg-slate-50">
                  <td class="py-3.5 px-4 font-bold text-slate-900">{{ app.applicant_name }}</td>
                  <td class="py-3.5 px-4 font-semibold text-slate-700">{{ app.job_posting?.title || 'Counselor' }}</td>
                  <td class="py-3.5 px-4 font-bold text-slate-800">{{ app.experience_years }}</td>
                  <td class="py-3.5 px-4 text-slate-600">{{ app.phone }} • {{ app.email }}</td>
                  <td class="py-3.5 px-4">
                    <select 
                      :value="app.stage" 
                      @change="updateApplicantStage(app.id, $event.target.value)"
                      class="bg-slate-100 border border-slate-300 text-slate-800 text-[11px] font-extrabold rounded-lg px-2.5 py-1"
                    >
                      <option value="Applied">Applied</option>
                      <option value="Screening">Screening</option>
                      <option value="Interview Scheduled">Interview Scheduled</option>
                      <option value="Offer Sent">Offer Sent</option>
                      <option value="Hired">Hired</option>
                      <option value="Rejected">Rejected</option>
                    </select>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    <!-- Post Job Modal -->
    <div v-if="isAddJobOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-xs">
      <div class="bg-white border border-slate-200 rounded-3xl max-w-lg w-full shadow-2xl p-6 space-y-4">
        <h3 class="text-lg font-bold text-slate-900">Post New Staff Vacancy</h3>
        <form @submit.prevent="submitJob" class="space-y-4">
          <div>
            <label class="text-xs font-bold text-slate-700">Job Title</label>
            <input v-model="form.title" type="text" required class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3 py-2 text-xs font-bold" />
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="text-xs font-bold text-slate-700">Department</label>
              <input v-model="form.department" type="text" required class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3 py-2 text-xs font-bold" />
            </div>
            <div>
              <label class="text-xs font-bold text-slate-700">Location</label>
              <input v-model="form.location" type="text" required class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3 py-2 text-xs font-bold" />
            </div>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="text-xs font-bold text-slate-700">Min Salary (₹)</label>
              <input v-model="form.salary_min" type="number" required class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3 py-2 text-xs font-bold" />
            </div>
            <div>
              <label class="text-xs font-bold text-slate-700">Max Salary (₹)</label>
              <input v-model="form.salary_max" type="number" required class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3 py-2 text-xs font-bold" />
            </div>
          </div>

          <div class="flex justify-end gap-3 pt-2">
            <button type="button" @click="isAddJobOpen = false" class="px-4 py-2 text-xs font-bold text-slate-500">Cancel</button>
            <button type="submit" class="px-5 py-2 bg-red-600 hover:bg-red-700 text-white text-xs font-extrabold rounded-xl shadow-md">Post Vacancy</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Modals -->
    <GlobalSearchModal :is-open="isSearchOpen" @close="isSearchOpen = false" />
  </div>
</template>
