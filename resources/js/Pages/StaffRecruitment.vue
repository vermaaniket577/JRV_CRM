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
  ClockIcon,
  HeartIcon,
  ShieldCheckIcon,
  BuildingOffice2Icon,
  BuildingOfficeIcon,
  MagnifyingGlassIcon,
  FunnelIcon,
  MapPinIcon,
  CheckCircleIcon,
  XMarkIcon,
  EnvelopeIcon,
  PhoneIcon,
  SparklesIcon,
  IdentificationIcon,
  DocumentCheckIcon,
  TableCellsIcon,
  CloudArrowUpIcon,
  ArrowUpTrayIcon,
  CircleStackIcon,
  DocumentArrowDownIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
  jobPostings: Array,
  applications: Array,
  metrics: Object,
});

const page = usePage();
const customNav = computed(() => page.props.custom_nav || {});
const tenantIndustry = computed(() => page.props.tenant_industry || { name: 'Staff', icon: '💼', color: 'teal', slug: 'recruitment' });
const industryName = computed(() => tenantIndustry.value.name || 'Staff');
const industryIcon = computed(() => tenantIndustry.value.icon || '💼');
const businessSettings = computed(() => page.props.business_settings || { business_name: `JRV ${industryName.value} CRM`, business_icon: industryIcon.value });

const isSearchOpen = ref(false);
const isAddJobOpen = ref(false);
const isImportModalOpen = ref(false);
const importActiveTab = ref('excel'); // 'excel' | 'demo'
const quickSelectedFileName = ref('');

const selectedDepartment = ref('All');
const candidateSearchQuery = ref('');
const selectedStageFilter = ref('All');

const departmentsList = [
  'All',
  'Cardiology & Heart Care',
  'Emergency & Intensive Care (ICU)',
  'Radiology & Diagnostics',
  'Emergency Medicine',
  'General Medicine & Surgery',
  'Pediatrics & Neonatal Care',
  'Nursing & Patient Care',
  'Pharmacy & Pharmacology'
];

const form = useForm({
  title: 'Consultant Interventional Cardiologist',
  department: 'Cardiology & Heart Care',
  location: 'Metro General Hospital, New York, NY',
  employment_type: 'Full-Time',
  salary_min: 180000,
  salary_max: 260000,
  description: 'Responsible for leading cardiac catheterization labs, inpatient consultations, diagnostic echocardiography, and emergency angioplasties.',
});

const quickImportForm = useForm({
  file: null,
  entity_type: 'healthcare_candidates',
});

const onQuickFileChange = (e) => {
  const file = e.target.files[0];
  if (file) {
    quickImportForm.file = file;
    quickSelectedFileName.value = file.name;
  }
};

const submitQuickImport = () => {
  if (!quickImportForm.file) return;
  quickImportForm.post('/data-import/excel', {
    preserveScroll: true,
    onSuccess: () => {
      quickImportForm.reset();
      quickSelectedFileName.value = '';
      isImportModalOpen.value = false;
    }
  });
};

const isQuickSeeding = ref(false);
const triggerQuickDemo = () => {
  isQuickSeeding.value = true;
  router.post('/data-import/demo-seed', {}, {
    preserveScroll: true,
    onSuccess: () => {
      isImportModalOpen.value = false;
    },
    onFinish: () => {
      isQuickSeeding.value = false;
    }
  });
};

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

const filteredJobPostings = computed(() => {
  if (selectedDepartment.value === 'All') {
    return props.jobPostings || [];
  }
  return (props.jobPostings || []).filter(job => job.department === selectedDepartment.value);
});

const filteredApplications = computed(() => {
  let list = props.applications || [];
  
  if (selectedStageFilter.value !== 'All') {
    list = list.filter(app => app.stage === selectedStageFilter.value);
  }
  
  if (candidateSearchQuery.value.trim()) {
    const q = candidateSearchQuery.value.toLowerCase();
    list = list.filter(app => 
      (app.applicant_name && app.applicant_name.toLowerCase().includes(q)) ||
      (app.email && app.email.toLowerCase().includes(q)) ||
      (app.job_posting?.title && app.job_posting.title.toLowerCase().includes(q)) ||
      (app.job_posting?.department && app.job_posting.department.toLowerCase().includes(q))
    );
  }
  
  return list;
});

const getStageBadgeClass = (stage) => {
  switch (stage) {
    case 'Hired':
      return 'bg-emerald-50 text-emerald-700 border-emerald-200 ring-emerald-500/20';
    case 'Offer Sent':
      return 'bg-indigo-50 text-indigo-700 border-indigo-200 ring-indigo-500/20';
    case 'Interview Scheduled':
      return 'bg-cyan-50 text-cyan-700 border-cyan-200 ring-cyan-500/20';
    case 'Screening':
      return 'bg-amber-50 text-amber-700 border-amber-200 ring-amber-500/20';
    case 'Rejected':
      return 'bg-rose-50 text-rose-700 border-rose-200 ring-rose-500/20';
    default:
      return 'bg-slate-100 text-slate-700 border-slate-200 ring-slate-400/20';
  }
};

const getDepartmentColor = (dept) => {
  if (!dept) return 'bg-teal-50 text-teal-700 border-teal-200';
  if (dept.includes('Cardio')) return 'bg-rose-50 text-rose-700 border-rose-200';
  if (dept.includes('Emergency') || dept.includes('ICU')) return 'bg-amber-50 text-amber-700 border-amber-200';
  if (dept.includes('Radiology')) return 'bg-violet-50 text-violet-700 border-violet-200';
  if (dept.includes('Pediatrics')) return 'bg-sky-50 text-sky-700 border-sky-200';
  if (dept.includes('Nursing')) return 'bg-emerald-50 text-emerald-700 border-emerald-200';
  return 'bg-teal-50 text-teal-700 border-teal-200';
};
</script>

<template>
  <Head title="Healthcare & Clinical Staff Recruitment - JRV CRM" />

  <div class="min-h-screen bg-slate-50 flex font-sans text-slate-900">
    <!-- Navbar (Fixed Left Vertical Sidebar) -->
    <Navbar @open-search="isSearchOpen = true" />

    <!-- Main Content Area -->
    <div class="flex-1 flex flex-col min-w-0">
      <!-- Top Bar -->
      <header class="bg-white/95 backdrop-blur-md border-b border-slate-200 px-6 py-3.5 flex items-center justify-between gap-3 sticky top-0 z-30 shadow-xs">
        <div class="flex items-center gap-3">
          <div class="flex items-center gap-2 px-3 py-1 bg-teal-50 border border-teal-200/80 rounded-full">
            <span class="w-2 h-2 rounded-full bg-teal-500 animate-pulse"></span>
            <span class="text-[11px] font-bold text-teal-800 tracking-wide uppercase">{{ industryName }} Careers & Recruitment</span>
          </div>
          <button @click="isImportModalOpen = true" class="hidden sm:inline-flex px-3.5 py-1.5 bg-emerald-50 hover:bg-emerald-100 text-emerald-800 font-extrabold text-xs rounded-xl border border-emerald-200 shadow-xs items-center gap-1.5 transition-all cursor-pointer">
            <TableCellsIcon class="w-3.5 h-3.5 text-emerald-600" />
            <span>Import (Excel / Database)</span>
          </button>
          <button @click="isAddJobOpen = true" class="hidden sm:inline-flex px-3.5 py-1.5 bg-teal-600 hover:bg-teal-700 text-white font-bold text-xs rounded-xl shadow-xs items-center gap-1.5 transition-all cursor-pointer">
            <PlusIcon class="w-3.5 h-3.5 stroke-[3]" />
            <span>Post {{ industryName }} Role</span>
          </button>
        </div>

        <div class="flex items-center gap-3">
          <Link href="/data-import" class="hidden md:inline-flex items-center gap-1.5 text-xs font-extrabold text-slate-600 hover:text-emerald-700 bg-slate-100 hover:bg-emerald-50 py-1.5 px-3 rounded-xl border border-slate-200 transition-all">
            <CircleStackIcon class="w-3.5 h-3.5 text-emerald-600" />
            <span>{{ industryName }} Data Import Hub</span>
          </Link>
          <div class="w-9 h-9 bg-gradient-to-tr from-teal-600 to-emerald-500 rounded-full flex items-center justify-center text-white font-black text-sm shadow-md ring-2 ring-teal-100">
            {{ industryIcon }}
          </div>
        </div>
      </header>

      <div class="p-6 md:p-8 space-y-7 flex-1 overflow-y-auto max-w-7xl mx-auto w-full">
        <!-- Hero Header -->
        <div class="bg-gradient-to-r from-slate-950 via-slate-900 to-slate-950 border border-slate-800 rounded-3xl p-6 md:p-8 text-white shadow-xl relative overflow-hidden">
          <div class="absolute -right-10 -bottom-10 w-72 h-72 bg-teal-500/15 rounded-full blur-3xl pointer-events-none"></div>
          <div class="absolute -left-10 -top-10 w-64 h-64 bg-emerald-500/15 rounded-full blur-2xl pointer-events-none"></div>

          <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div class="space-y-2 max-w-2xl">
              <div class="inline-flex items-center gap-2 px-2.5 py-0.5 rounded-md bg-teal-500/20 border border-teal-400/30 text-teal-300 text-[11px] font-bold tracking-wider uppercase">
                <span>{{ industryIcon }}</span>
                <span>{{ industryName }} Talent & Staffing</span>
              </div>
              <h1 class="text-2xl md:text-3xl font-black tracking-tight text-white drop-shadow-xs flex items-center gap-2">
                <span>{{ industryIcon }}</span>
                <span>{{ industryName }} Staff & Careers Recruitment</span>
              </h1>
              <p class="text-xs md:text-sm text-slate-200 font-normal leading-relaxed">
                Streamline talent acquisition, applicants, and pipeline interviews for {{ industryName }} organizations and departments.
              </p>
            </div>

            <div class="flex items-center gap-3 flex-wrap">
              <button 
                @click="isImportModalOpen = true" 
                class="px-4 py-2.5 bg-slate-800 hover:bg-slate-700 text-white font-bold text-xs rounded-2xl border border-emerald-400/40 shadow-md flex items-center gap-2 transition-all cursor-pointer"
              >
                <TableCellsIcon class="w-4 h-4 text-emerald-400" />
                <span>Import (Excel / DB)</span>
              </button>

              <button 
                @click="isAddJobOpen = true" 
                class="px-5 py-2.5 bg-gradient-to-r from-teal-500 to-emerald-500 hover:from-teal-600 hover:to-emerald-600 text-white font-bold text-xs rounded-2xl shadow-lg shadow-teal-900/50 flex items-center gap-2 transition-all transform hover:-translate-y-0.5 cursor-pointer"
              >
                <PlusIcon class="w-4 h-4 stroke-[3]" />
                <span>Post {{ industryName }} Vacancy</span>
              </button>
            </div>
          </div>
        </div>

        <!-- Healthcare Metrics Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
          <!-- Metric 1 -->
          <div class="p-5 bg-white rounded-2xl border border-slate-200/90 shadow-xs hover:shadow-md transition-all space-y-3 relative overflow-hidden group">
            <div class="flex items-center justify-between">
              <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Open Clinical Roles</span>
              <div class="w-8 h-8 rounded-xl bg-teal-50 flex items-center justify-center text-teal-600">
                <BuildingOffice2Icon class="w-4 h-4" />
              </div>
            </div>
            <div class="flex items-baseline gap-2">
              <div class="text-3xl font-black text-slate-900">{{ metrics.open_positions }}</div>
              <span class="text-xs font-semibold text-teal-600 bg-teal-50 px-2 py-0.5 rounded-md">Vacancies</span>
            </div>
            <p class="text-[11px] text-slate-500 font-medium">Physicians, Surgeons & Nursing</p>
          </div>

          <!-- Metric 2 -->
          <div class="p-5 bg-white rounded-2xl border border-slate-200/90 shadow-xs hover:shadow-md transition-all space-y-3 relative overflow-hidden group">
            <div class="flex items-center justify-between">
              <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Medical Applicants</span>
              <div class="w-8 h-8 rounded-xl bg-emerald-50 flex items-center justify-center text-emerald-600">
                <UserGroupIcon class="w-4 h-4" />
              </div>
            </div>
            <div class="flex items-baseline gap-2">
              <div class="text-3xl font-black text-emerald-600">{{ metrics.total_applicants }}</div>
              <span class="text-xs font-semibold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-md">Profiles</span>
            </div>
            <p class="text-[11px] text-slate-500 font-medium">Licensed healthcare professionals</p>
          </div>

          <!-- Metric 3 -->
          <div class="p-5 bg-white rounded-2xl border border-slate-200/90 shadow-xs hover:shadow-md transition-all space-y-3 relative overflow-hidden group">
            <div class="flex items-center justify-between">
              <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Clinical Interviews</span>
              <div class="w-8 h-8 rounded-xl bg-cyan-50 flex items-center justify-center text-cyan-600">
                <DocumentCheckIcon class="w-4 h-4" />
              </div>
            </div>
            <div class="flex items-baseline gap-2">
              <div class="text-3xl font-black text-cyan-600">{{ metrics.interviews_scheduled }}</div>
              <span class="text-xs font-semibold text-cyan-600 bg-cyan-50 px-2 py-0.5 rounded-md">Scheduled</span>
            </div>
            <p class="text-[11px] text-slate-500 font-medium">Board reviews & hospital rounds</p>
          </div>

          <!-- Metric 4 -->
          <div class="p-5 bg-white rounded-2xl border border-slate-200/90 shadow-xs hover:shadow-md transition-all space-y-3 relative overflow-hidden group">
            <div class="flex items-center justify-between">
              <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Doctors & Staff Hired</span>
              <div class="w-8 h-8 rounded-xl bg-indigo-50 flex items-center justify-center text-indigo-600">
                <CheckCircleIcon class="w-4 h-4" />
              </div>
            </div>
            <div class="flex items-baseline gap-2">
              <div class="text-3xl font-black text-indigo-600">{{ metrics.hired_this_month }}</div>
              <span class="text-xs font-semibold text-indigo-600 bg-indigo-50 px-2 py-0.5 rounded-md">This Month</span>
            </div>
            <p class="text-[11px] text-slate-500 font-medium">Credentialed & onboarded</p>
          </div>
        </div>

        <!-- Active Clinical Vacancies Section -->
        <div class="bg-white border border-slate-200/90 rounded-3xl p-6 shadow-xs space-y-5">
          <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 pb-4 border-b border-slate-100">
            <div>
              <h2 class="text-lg font-black text-slate-900 flex items-center gap-2">
                <BuildingOfficeIcon class="w-5 h-5 text-teal-600" />
                <span>Active Clinical Vacancies</span>
              </h2>
              <p class="text-xs text-slate-500 mt-0.5">Open postings across hospital departments and specialty clinics</p>
            </div>

            <!-- Department Filter Pills -->
            <div class="flex items-center gap-1.5 overflow-x-auto py-1 max-w-full">
              <button 
                v-for="dept in departmentsList" 
                :key="dept"
                @click="selectedDepartment = dept"
                :class="[
                  'px-3 py-1 text-xs font-bold rounded-xl whitespace-nowrap transition-all cursor-pointer',
                  selectedDepartment === dept 
                    ? 'bg-teal-600 text-white shadow-xs' 
                    : 'bg-slate-100 text-slate-600 hover:bg-slate-200'
                ]"
              >
                {{ dept }}
              </button>
            </div>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div 
              v-for="job in filteredJobPostings" 
              :key="job.id" 
              class="p-5 border border-slate-200 rounded-2xl bg-slate-50/70 hover:bg-slate-50 hover:border-teal-300 transition-all space-y-3 shadow-2xs group"
            >
              <div class="flex items-center justify-between gap-2">
                <span :class="['text-[11px] font-black border px-2.5 py-0.5 rounded-full uppercase tracking-wider', getDepartmentColor(job.department)]">
                  {{ job.department }}
                </span>
                <span class="text-[11px] font-bold text-slate-700 bg-white border border-slate-200 px-2 py-0.5 rounded-lg shadow-2xs">
                  {{ job.employment_type }}
                </span>
              </div>

              <div>
                <h3 class="text-sm font-extrabold text-slate-900 group-hover:text-teal-700 transition-colors">
                  {{ job.title }}
                </h3>
                <p v-if="job.description" class="text-xs text-slate-500 mt-1 line-clamp-2 leading-relaxed">
                  {{ job.description }}
                </p>
              </div>

              <div class="pt-2 border-t border-slate-200/80 flex flex-wrap items-center justify-between text-xs text-slate-600 gap-2">
                <div class="flex items-center gap-1 text-[11px] font-medium text-slate-500">
                  <MapPinIcon class="w-3.5 h-3.5 text-teal-600 shrink-0" />
                  <span>{{ job.location }}</span>
                </div>
                <div class="font-extrabold text-slate-800 text-[11px]">
                  Salary: ₹{{ Number(job.salary_min).toLocaleString() }} - ₹{{ Number(job.salary_max).toLocaleString() }}
                </div>
              </div>
            </div>

            <div v-if="filteredJobPostings.length === 0" class="col-span-2 text-center py-10 text-slate-400 text-xs">
              No vacancies found in the selected department.
            </div>
          </div>
        </div>

        <!-- Healthcare Candidate Pipeline & Applications Table -->
        <div class="bg-white border border-slate-200/90 rounded-3xl overflow-hidden shadow-xs space-y-4">
          <div class="p-6 border-b border-slate-100 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
              <h2 class="text-lg font-black text-slate-900 flex items-center gap-2">
                <AcademicCapIcon class="w-5 h-5 text-teal-600" />
                <span>Medical Candidates & Credential Pipeline</span>
              </h2>
              <p class="text-xs text-slate-500 mt-0.5">Track licensed doctors, nurses, and clinical specialists across hiring stages</p>
            </div>

            <div class="flex flex-wrap items-center gap-3">
              <!-- Stage Filter -->
              <div class="flex items-center gap-1.5 bg-slate-100 px-3 py-1.5 rounded-xl border border-slate-200">
                <FunnelIcon class="w-3.5 h-3.5 text-slate-500" />
                <select 
                  v-model="selectedStageFilter"
                  class="bg-transparent border-0 text-xs font-bold text-slate-700 focus:ring-0 p-0 cursor-pointer"
                >
                  <option value="All">All Hiring Stages</option>
                  <option value="Applied">Applied</option>
                  <option value="Screening">Screening</option>
                  <option value="Interview Scheduled">Interview Scheduled</option>
                  <option value="Offer Sent">Offer Sent</option>
                  <option value="Hired">Hired</option>
                  <option value="Rejected">Rejected</option>
                </select>
              </div>

              <!-- Search Bar -->
              <div class="relative">
                <MagnifyingGlassIcon class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" />
                <input 
                  v-model="candidateSearchQuery"
                  type="text" 
                  placeholder="Search doctor, nurse or role..." 
                  class="bg-slate-50 border border-slate-200 text-xs font-medium rounded-xl pl-9 pr-3 py-1.5 w-56 focus:bg-white focus:border-teal-500 focus:outline-none transition-all"
                />
              </div>
            </div>
          </div>

          <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
              <thead>
                <tr class="bg-slate-50/80 border-b border-slate-200 text-slate-500 font-bold uppercase text-[10px] tracking-wider">
                  <th class="py-3 px-5">Medical Candidate</th>
                  <th class="py-3 px-4">Applied Position & Department</th>
                  <th class="py-3 px-4">Clinical Experience</th>
                  <th class="py-3 px-4">Contact Info</th>
                  <th class="py-3 px-5">Credential Stage</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100">
                <tr v-for="app in filteredApplications" :key="app.id" class="hover:bg-slate-50/90 transition-colors">
                  <!-- Candidate Name -->
                  <td class="py-4 px-5">
                    <div class="flex items-center gap-3">
                      <div class="w-9 h-9 rounded-full bg-teal-100 text-teal-800 font-extrabold flex items-center justify-center text-xs shrink-0 ring-2 ring-teal-200/60 shadow-xs">
                        {{ app.applicant_name ? app.applicant_name.split(' ').map(n => n[0]).slice(0, 2).join('') : 'MD' }}
                      </div>
                      <div>
                        <div class="font-black text-slate-900 text-xs flex items-center gap-1.5">
                          <span>{{ app.applicant_name }}</span>
                          <span v-if="app.notes && app.notes.includes('Board certified')" class="px-1.5 py-0.2 bg-emerald-100 text-emerald-800 text-[9px] font-bold rounded">MD Certified</span>
                        </div>
                        <div v-if="app.notes" class="text-[11px] text-slate-500 line-clamp-1 max-w-xs mt-0.5">
                          {{ app.notes }}
                        </div>
                      </div>
                    </div>
                  </td>

                  <!-- Position & Dept -->
                  <td class="py-4 px-4">
                    <div class="font-bold text-slate-800 text-xs">
                      {{ app.job_posting?.title || 'Clinical Specialist' }}
                    </div>
                    <div class="text-[10px] font-semibold text-teal-700 mt-0.5">
                      {{ app.job_posting?.department || 'General Medicine' }}
                    </div>
                  </td>

                  <!-- Experience -->
                  <td class="py-4 px-4">
                    <span class="font-extrabold text-slate-800 bg-slate-100 border border-slate-200 px-2 py-1 rounded-md text-[11px]">
                      {{ app.experience_years }}
                    </span>
                  </td>

                  <!-- Contact -->
                  <td class="py-4 px-4 space-y-0.5">
                    <div class="flex items-center gap-1.5 text-slate-700 font-medium text-[11px]">
                      <PhoneIcon class="w-3 h-3 text-slate-400" />
                      <span>{{ app.phone }}</span>
                    </div>
                    <div class="flex items-center gap-1.5 text-teal-600 font-medium text-[11px]">
                      <EnvelopeIcon class="w-3 h-3 text-teal-400" />
                      <span>{{ app.email }}</span>
                    </div>
                  </td>

                  <!-- Stage Selector -->
                  <td class="py-4 px-5">
                    <div class="relative inline-block">
                      <select 
                        :value="app.stage" 
                        @change="updateApplicantStage(app.id, $event.target.value)"
                        :class="[
                          'border text-[11px] font-black rounded-xl px-3 py-1.5 pr-8 appearance-none cursor-pointer focus:outline-none focus:ring-2 transition-all shadow-2xs',
                          getStageBadgeClass(app.stage)
                        ]"
                      >
                        <option value="Applied">Applied</option>
                        <option value="Screening">Clinical Screening</option>
                        <option value="Interview Scheduled">Interview Scheduled</option>
                        <option value="Offer Sent">Offer Extended</option>
                        <option value="Hired">Board Approved / Hired</option>
                        <option value="Rejected">Rejected</option>
                      </select>
                      <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-slate-500">
                        <svg class="fill-current h-3.5 w-3.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path d="M9.293 12.95l.707.707L15.657 8l-1.414-1.414L10 10.828 5.757 6.586 4.343 8z"/></svg>
                      </div>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>

            <div v-if="filteredApplications.length === 0" class="p-8 text-center text-slate-400 text-xs">
              No healthcare candidate applications match your criteria.
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Post Medical Job Modal -->
    <div v-if="isAddJobOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/60 backdrop-blur-xs">
      <div class="bg-white border border-slate-200 rounded-3xl max-w-lg w-full shadow-2xl p-6 md:p-7 space-y-5">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
          <div class="flex items-center gap-2.5">
            <div class="w-8 h-8 rounded-xl bg-teal-50 flex items-center justify-center text-teal-600 font-bold text-sm">
              🏥
            </div>
            <div>
              <h3 class="text-base font-black text-slate-900">Post New Medical Vacancy</h3>
              <p class="text-xs text-slate-500">Hospital department hiring & clinical credentials</p>
            </div>
          </div>
          <button @click="isAddJobOpen = false" class="text-slate-400 hover:text-slate-600 p-1 rounded-lg">
            <XMarkIcon class="w-5 h-5" />
          </button>
        </div>

        <form @submit.prevent="submitJob" class="space-y-4">
          <div>
            <label class="text-xs font-bold text-slate-700 block mb-1">Clinical Position Title</label>
            <input 
              v-model="form.title" 
              type="text" 
              placeholder="e.g. Senior Consultant Cardiologist" 
              required 
              class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2 text-xs font-bold text-slate-800 focus:bg-white focus:border-teal-500 focus:outline-none" 
            />
          </div>

          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label class="text-xs font-bold text-slate-700 block mb-1">Department / Specialty</label>
              <select 
                v-model="form.department" 
                required 
                class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3 py-2 text-xs font-bold text-slate-800 focus:bg-white focus:border-teal-500 focus:outline-none"
              >
                <option value="Cardiology & Heart Care">Cardiology & Heart Care</option>
                <option value="Emergency & Intensive Care (ICU)">Emergency & Intensive Care (ICU)</option>
                <option value="Radiology & Diagnostics">Radiology & Diagnostics</option>
                <option value="Emergency Medicine">Emergency Medicine</option>
                <option value="General Medicine & Surgery">General Medicine & Surgery</option>
                <option value="Pediatrics & Neonatal Care">Pediatrics & Neonatal Care</option>
                <option value="Nursing & Patient Care">Nursing & Patient Care</option>
                <option value="Pharmacy & Pharmacology">Pharmacy & Pharmacology</option>
              </select>
            </div>

            <div>
              <label class="text-xs font-bold text-slate-700 block mb-1">Employment Type</label>
              <select 
                v-model="form.employment_type" 
                required 
                class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3 py-2 text-xs font-bold text-slate-800 focus:bg-white focus:border-teal-500 focus:outline-none"
              >
                <option value="Full-Time">Full-Time</option>
                <option value="Locum Tenens">Locum Tenens</option>
                <option value="Part-Time">Part-Time</option>
                <option value="Residency / Fellowship">Residency / Fellowship</option>
                <option value="On-Call Shift">On-Call Shift</option>
              </select>
            </div>
          </div>

          <div>
            <label class="text-xs font-bold text-slate-700 block mb-1">Hospital / Clinic Facility & Location</label>
            <input 
              v-model="form.location" 
              type="text" 
              placeholder="e.g. Metro General Hospital, New York, NY" 
              required 
              class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2 text-xs font-bold text-slate-800 focus:bg-white focus:border-teal-500 focus:outline-none" 
            />
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="text-xs font-bold text-slate-700 block mb-1">Min Salary (₹ / $)</label>
              <input 
                v-model="form.salary_min" 
                type="number" 
                required 
                class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2 text-xs font-bold text-slate-800 focus:bg-white focus:border-teal-500 focus:outline-none" 
              />
            </div>
            <div>
              <label class="text-xs font-bold text-slate-700 block mb-1">Max Salary (₹ / $)</label>
              <input 
                v-model="form.salary_max" 
                type="number" 
                required 
                class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2 text-xs font-bold text-slate-800 focus:bg-white focus:border-teal-500 focus:outline-none" 
              />
            </div>
          </div>

          <div>
            <label class="text-xs font-bold text-slate-700 block mb-1">Clinical Responsibilities & Qualifications</label>
            <textarea 
              v-model="form.description" 
              rows="3" 
              placeholder="Describe clinical responsibilities, patient care requirements, board certifications..." 
              class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2 text-xs font-medium text-slate-800 focus:bg-white focus:border-teal-500 focus:outline-none"
            ></textarea>
          </div>

          <div class="flex justify-end gap-3 pt-3 border-t border-slate-100">
            <button 
              type="button" 
              @click="isAddJobOpen = false" 
              class="px-4 py-2 text-xs font-bold text-slate-600 hover:bg-slate-100 rounded-xl transition-all"
            >
              Cancel
            </button>
            <button 
              type="submit" 
              class="px-5 py-2 bg-gradient-to-r from-teal-600 to-emerald-600 hover:from-teal-700 hover:to-emerald-700 text-white text-xs font-black rounded-xl shadow-md transition-all cursor-pointer"
            >
              Post Medical Vacancy
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Quick Import Modal (Excel & Database) -->
    <div v-if="isImportModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/60 backdrop-blur-xs">
      <div class="bg-white border border-slate-200 rounded-3xl max-w-lg w-full shadow-2xl p-6 md:p-7 space-y-5">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
          <div class="flex items-center gap-2.5">
            <div class="w-8 h-8 rounded-xl bg-emerald-50 flex items-center justify-center text-emerald-600 font-bold text-sm">
              📥
            </div>
            <div>
              <h3 class="text-base font-black text-slate-900">Import Medical Candidates & Vacancies</h3>
              <p class="text-xs text-slate-500">From Excel / CSV spreadsheets or Database Seeder</p>
            </div>
          </div>
          <button @click="isImportModalOpen = false" class="text-slate-400 hover:text-slate-600 p-1 rounded-lg">
            <XMarkIcon class="w-5 h-5" />
          </button>
        </div>

        <!-- Mode Switcher -->
        <div class="flex items-center gap-2 bg-slate-100 p-1 rounded-xl">
          <button 
            @click="importActiveTab = 'excel'" 
            :class="[
              'flex-1 py-1.5 text-xs font-black rounded-lg transition-all',
              importActiveTab === 'excel' ? 'bg-white text-emerald-800 shadow-xs' : 'text-slate-600 hover:text-slate-900'
            ]"
          >
            Excel / CSV File
          </button>
          <button 
            @click="importActiveTab = 'demo'" 
            :class="[
              'flex-1 py-1.5 text-xs font-black rounded-lg transition-all',
              importActiveTab === 'demo' ? 'bg-white text-emerald-800 shadow-xs' : 'text-slate-600 hover:text-slate-900'
            ]"
          >
            Database Seeder / Demo
          </button>
        </div>

        <!-- Tab 1: Excel / CSV File Upload -->
        <div v-if="importActiveTab === 'excel'" class="space-y-4">
          <form @submit.prevent="submitQuickImport" class="space-y-4">
            <div>
              <label class="text-xs font-bold text-slate-700 block mb-1">Target Entity</label>
              <select 
                v-model="quickImportForm.entity_type" 
                class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3 py-2 text-xs font-bold text-slate-800 focus:bg-white focus:border-emerald-500 focus:outline-none"
              >
                <option value="healthcare_candidates">🩺 Medical Candidates (Doctors & Nurses)</option>
                <option value="healthcare_vacancies">🏥 Clinical Vacancies (Job Postings)</option>
              </select>
            </div>

            <div 
              class="border-2 border-dashed border-slate-300 hover:border-emerald-500 rounded-2xl p-6 text-center bg-slate-50/70 hover:bg-emerald-50/30 transition-all cursor-pointer"
              @click="$refs.quickFileInput.click()"
            >
              <input 
                ref="quickFileInput" 
                type="file" 
                accept=".xlsx,.xls,.csv,.tsv,.txt" 
                @change="onQuickFileChange" 
                class="hidden" 
              />
              <TableCellsIcon class="w-8 h-8 text-emerald-600 mx-auto mb-2" />
              <p v-if="!quickSelectedFileName" class="text-xs font-bold text-slate-700">
                Click to browse Excel (.xlsx) or CSV file
              </p>
              <p v-else class="text-xs font-black text-emerald-700">
                Selected: {{ quickSelectedFileName }}
              </p>
              <p class="text-[10px] text-slate-400 mt-1">Supports XLSX, XLS, CSV, TSV (Max 20MB)</p>
            </div>

            <!-- Download Template Links -->
            <div class="flex items-center justify-between pt-1 text-[11px]">
              <span class="text-slate-500">Need a template?</span>
              <a 
                :href="`/data-import/sample/${quickImportForm.entity_type}`" 
                class="font-bold text-emerald-700 hover:underline flex items-center gap-1"
              >
                <DocumentArrowDownIcon class="w-3.5 h-3.5" />
                <span>Download Sample Template</span>
              </a>
            </div>

            <div class="flex justify-end gap-3 pt-3 border-t border-slate-100">
              <button 
                type="button" 
                @click="isImportModalOpen = false" 
                class="px-4 py-2 text-xs font-bold text-slate-600 hover:bg-slate-100 rounded-xl transition-all"
              >
                Cancel
              </button>
              <button 
                type="submit" 
                :disabled="quickImportForm.processing || !quickImportForm.file"
                class="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-black rounded-xl shadow-md transition-all disabled:opacity-50"
              >
                {{ quickImportForm.processing ? 'Importing...' : 'Upload & Import' }}
              </button>
            </div>
          </form>
        </div>

        <!-- Tab 2: 1-Click Database Demo Seeder & Hub Link -->
        <div v-else class="space-y-4">
          <div class="p-4 bg-emerald-50 rounded-2xl border border-emerald-200 space-y-2">
            <h4 class="text-xs font-black text-emerald-900 flex items-center gap-1.5">
              <SparklesIcon class="w-4 h-4 text-emerald-700" />
              <span>1-Click Medical Database Seeder</span>
            </h4>
            <p class="text-[11px] text-emerald-800 leading-relaxed">
              Instantly populate board-certified specialist doctor profiles, trauma nurse applications, and open clinical surgical vacancies into your database.
            </p>
            <button 
              type="button" 
              @click="triggerQuickDemo" 
              :disabled="isQuickSeeding"
              class="mt-2 w-full py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-black rounded-xl shadow-md transition-all flex items-center justify-center gap-2 cursor-pointer disabled:opacity-50"
            >
              <CircleStackIcon class="w-4 h-4" />
              <span>{{ isQuickSeeding ? 'Importing Demo Records...' : 'Execute 1-Click Demo Import' }}</span>
            </button>
          </div>

          <div class="p-4 bg-slate-50 rounded-2xl border border-slate-200 space-y-2">
            <h4 class="text-xs font-black text-slate-800">Advanced Database Sync (MySQL / MariaDB)</h4>
            <p class="text-[11px] text-slate-500">
              Need to connect to an external live SQL database server or migrate legacy tables?
            </p>
            <Link 
              href="/data-import" 
              class="inline-flex items-center gap-1.5 text-xs font-black text-teal-700 hover:underline pt-1"
            >
              <span>Open Universal Data Import Hub &rarr;</span>
            </Link>
          </div>
        </div>
      </div>
    </div>

    <!-- Modals -->
    <GlobalSearchModal :is-open="isSearchOpen" @close="isSearchOpen = false" />
  </div>
</template>
