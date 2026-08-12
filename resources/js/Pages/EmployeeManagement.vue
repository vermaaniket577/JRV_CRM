<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import Navbar from '@/Components/Navbar.vue';
import GlobalSearchModal from '@/Components/GlobalSearchModal.vue';
import CreateEmployeeModal from '@/Components/CreateEmployeeModal.vue';
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
  AdjustmentsHorizontalIcon,
  PlusIcon,
  TrashIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
  metrics: Object,
  employees: Array,
  filters: Object,
});

const page = usePage();
const customNav = computed(() => page.props.custom_nav || {});
const businessSettings = computed(() => page.props.business_settings || { business_name: 'JRV CRM', business_icon: '♥' });

const getNavLabel = (key, fallback) => {
  return customNav.value[key]?.label || fallback;
};

const getNavRoute = (key, fallback) => {
  return customNav.value[key]?.route || fallback;
};

const isSearchOpen = ref(false);
const isCreateEmployeeOpen = ref(false);

const searchType = ref(props.filters.type || 'ID');
const searchQuery = ref(props.filters.query || '');
const department = ref(props.filters.department || '');
const attendanceStatus = ref(props.filters.attendance_status || '');
const timing = ref('');
const gender = ref(props.filters.gender || '');
const paymentStatus = ref(props.filters.payment_status || '');
const state = ref(props.filters.state || '');
const city = ref(props.filters.city || '');
const age = ref('');
const isActiveToggle = ref(props.filters.active !== false);

const applyFilter = () => {
  router.get('/employee-management', {
    type: searchType.value,
    query: searchQuery.value,
    department: department.value,
    attendance_status: attendanceStatus.value,
    gender: gender.value,
    payment_status: paymentStatus.value,
    state: state.value,
    city: city.value,
    active: isActiveToggle.value ? 1 : 0,
  }, { preserveState: true });
};

const removeFilter = () => {
  searchType.value = 'ID';
  searchQuery.value = '';
  department.value = '';
  attendanceStatus.value = '';
  timing.value = '';
  gender.value = '';
  paymentStatus.value = '';
  state.value = '';
  city.value = '';
  age.value = '';
  isActiveToggle.value = true;

  router.get('/employee-management');
};

const deleteEmployee = (id) => {
  if (confirm('Are you sure you want to delete this employee record?')) {
    router.delete(`/employee-management/${id}`, { preserveScroll: true });
  }
};

const formatINR = (val) => {
  return '₹' + Number(val || 0).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
};
</script>

<template>
  <Head title="Employee Management - CRM" />

  <div class="min-h-screen bg-slate-100 flex font-sans text-slate-900">
    <!-- Navbar (Fixed Left Vertical Sidebar) -->
    <Navbar @open-search="isSearchOpen = true" />

    <!-- Main Content Area -->
    <div class="flex-1 flex flex-col min-w-0">
      <!-- Top Action Bar -->
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
          <Link href="/tenant/settings/navigation" class="px-4 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white font-extrabold text-xs rounded-xl shadow-xs transition-all flex items-center gap-1.5">
            <AdjustmentsHorizontalIcon class="w-4 h-4" />
            <span>Customize Menu Names</span>
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

      <!-- Page Content -->
      <div class="p-8 space-y-6 flex-1 overflow-y-auto">
        <!-- Header Bar with Add Employee Action -->
        <div class="flex items-center justify-between">
          <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight">
            {{ getNavLabel('staff_management', 'Employee Management') }}
          </h1>

          <button 
            @click="isCreateEmployeeOpen = true"
            class="px-5 py-2.5 bg-red-600 hover:bg-red-700 text-white font-extrabold text-xs rounded-xl shadow-md flex items-center gap-2 transition-all"
          >
            <PlusIcon class="w-4 h-4 stroke-[3]" />
            <span>Add Employee</span>
          </button>
        </div>

        <!-- 5 Gradient Metric Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-3 lg:grid-cols-5 gap-5">
          <div class="p-6 rounded-2xl bg-gradient-to-r from-indigo-500 via-indigo-600 to-blue-500 text-white shadow-lg space-y-1 text-center">
            <div class="text-3xl font-black">{{ metrics.total_employees }}</div>
            <div class="text-sm font-bold tracking-wide">Total Employees</div>
          </div>

          <div class="p-6 rounded-2xl bg-gradient-to-r from-amber-400 to-amber-500 text-white shadow-lg space-y-1 text-center">
            <div class="text-3xl font-black">{{ metrics.total_attendance }}</div>
            <div class="text-sm font-bold tracking-wide">Total Attendance</div>
          </div>

          <div class="p-6 rounded-2xl bg-gradient-to-r from-cyan-400 to-blue-500 text-white shadow-lg space-y-1 text-center">
            <div class="text-3xl font-black">{{ metrics.salary_requests }}</div>
            <div class="text-sm font-bold tracking-wide">Salary Request</div>
          </div>

          <div class="p-6 rounded-2xl bg-gradient-to-r from-blue-500 via-indigo-500 to-purple-600 text-white shadow-lg space-y-1 text-center">
            <div class="text-3xl font-black">{{ metrics.leave_requests }}</div>
            <div class="text-sm font-bold tracking-wide">Leave Request</div>
          </div>

          <div class="p-6 rounded-2xl bg-gradient-to-r from-amber-400 via-orange-400 to-orange-500 text-white shadow-lg space-y-1 text-center">
            <div class="text-3xl font-black">{{ metrics.absent_count }}</div>
            <div class="text-sm font-bold tracking-wide">Absent</div>
          </div>
        </div>

        <!-- Multi-Row Filter Box -->
        <div class="bg-white border border-slate-200 rounded-3xl p-6 shadow-xs space-y-4">
          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="relative">
              <label class="absolute -top-2 left-3 bg-white px-1 text-[10px] font-semibold text-slate-400">Type</label>
              <select v-model="searchType" class="w-full bg-white border border-slate-300 rounded-xl px-3.5 py-2.5 text-xs font-semibold text-slate-700 focus:outline-none focus:border-red-500">
                <option value="ID">ID / Code</option>
                <option value="Name">Name</option>
                <option value="Email">Email</option>
              </select>
            </div>

            <div>
              <input 
                v-model="searchQuery" 
                type="text" 
                placeholder="Search Code or Name..." 
                class="w-full bg-white border border-slate-300 rounded-xl px-3.5 py-2.5 text-xs font-semibold text-slate-800 focus:outline-none focus:border-red-500"
              />
            </div>

            <div>
              <select v-model="department" class="w-full bg-white border border-slate-300 rounded-xl px-3.5 py-2.5 text-xs font-semibold text-slate-700 focus:outline-none focus:border-red-500">
                <option value="">Department</option>
                <option value="Sales">Sales</option>
                <option value="Engineering">Engineering</option>
                <option value="Support">Support</option>
                <option value="Finance">Finance</option>
                <option value="HR">Human Resources</option>
              </select>
            </div>

            <div>
              <select v-model="attendanceStatus" class="w-full bg-white border border-slate-300 rounded-xl px-3.5 py-2.5 text-xs font-semibold text-slate-700 focus:outline-none focus:border-red-500">
                <option value="">Attendance status</option>
                <option value="Present">Present</option>
                <option value="Absent">Absent</option>
                <option value="On Leave">On Leave</option>
                <option value="Half Day">Half Day</option>
              </select>
            </div>
          </div>

          <div class="flex items-center justify-end gap-4 pt-2">
            <button 
              @click="applyFilter"
              class="px-6 py-2 bg-red-600 hover:bg-red-700 text-white font-bold text-xs rounded-xl shadow-xs transition-all"
            >
              Apply Filter
            </button>

            <button 
              @click="removeFilter"
              class="px-6 py-2 bg-white border border-red-600 text-red-600 hover:bg-red-50 font-bold text-xs rounded-xl transition-all"
            >
              Remove Filter
            </button>
          </div>
        </div>

        <!-- Interactive Employee Directory Data Table -->
        <div class="bg-white border border-slate-200 rounded-3xl p-6 shadow-xs overflow-hidden">
          <div v-if="employees.length > 0" class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
              <thead>
                <tr class="border-b border-slate-200 text-slate-400 uppercase tracking-wider font-bold text-[10px]">
                  <th class="py-3 px-4">Code / ID</th>
                  <th class="py-3 px-4">Employee</th>
                  <th class="py-3 px-4">Department & Role</th>
                  <th class="py-3 px-4">Salary (INR ₹)</th>
                  <th class="py-3 px-4">Attendance</th>
                  <th class="py-3 px-4">Location</th>
                  <th class="py-3 px-4 text-right">Actions</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100 font-medium">
                <tr v-for="emp in employees" :key="emp.id" class="hover:bg-slate-50/80 transition-colors">
                  <td class="py-3.5 px-4 font-mono font-bold text-slate-600">
                    {{ emp.employee_profile?.employee_code || ('EMP-' + String(emp.id).padStart(4, '0')) }}
                  </td>

                  <td class="py-3.5 px-4">
                    <div class="flex items-center gap-3">
                      <div class="w-8 h-8 rounded-full bg-red-100 text-red-700 font-black flex items-center justify-center text-xs">
                        {{ emp.name.charAt(0) }}
                      </div>
                      <div>
                        <div class="font-bold text-slate-900 text-xs">{{ emp.name }}</div>
                        <div class="text-[11px] text-slate-400">{{ emp.email }}</div>
                      </div>
                    </div>
                  </td>

                  <td class="py-3.5 px-4">
                    <div class="font-bold text-slate-800">{{ emp.employee_profile?.department || 'General' }}</div>
                    <div class="text-[11px] text-slate-500">{{ emp.employee_profile?.designation || 'Staff' }}</div>
                  </td>

                  <td class="py-3.5 px-4 font-bold text-emerald-700">
                    {{ formatINR(emp.employee_profile?.salary || 35000) }}
                  </td>

                  <td class="py-3.5 px-4">
                    <span :class="[
                      'px-2.5 py-0.5 rounded-full text-[10px] font-extrabold uppercase tracking-wider border',
                      (emp.employee_profile?.attendance_status || 'Present') === 'Present' ? 'bg-emerald-100 text-emerald-800 border-emerald-300' :
                      (emp.employee_profile?.attendance_status) === 'On Leave' ? 'bg-purple-100 text-purple-800 border-purple-300' :
                      'bg-rose-100 text-rose-800 border-rose-300'
                    ]">
                      {{ emp.employee_profile?.attendance_status || 'Present' }}
                    </span>
                  </td>

                  <td class="py-3.5 px-4 text-slate-600 text-xs">
                    {{ emp.employee_profile?.city || 'San Francisco' }}, {{ emp.employee_profile?.state || 'California' }}
                  </td>

                  <td class="py-3.5 px-4 text-right">
                    <button 
                      @click="deleteEmployee(emp.id)" 
                      class="p-1.5 text-slate-400 hover:text-red-600 rounded-lg transition-colors"
                      title="Delete Employee"
                    >
                      <TrashIcon class="w-4 h-4" />
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <div v-else class="text-center py-16 space-y-3">
            <p class="text-base font-bold text-slate-500">No employees found for this filter.</p>
            <button @click="isCreateEmployeeOpen = true" class="px-4 py-2 bg-red-600 text-white font-bold text-xs rounded-xl shadow-xs">
              + Add First Employee
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Modals -->
    <GlobalSearchModal :is-open="isSearchOpen" @close="isSearchOpen = false" />
    <CreateEmployeeModal :is-open="isCreateEmployeeOpen" @close="isCreateEmployeeOpen = false" />
  </div>
</template>
