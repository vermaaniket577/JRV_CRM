<script setup>
import { ref, computed } from 'vue';
import { Head, useForm, router, Link } from '@inertiajs/vue3';
import Navbar from '@/Components/Navbar.vue';
import {
  BuildingOfficeIcon,
  BuildingOffice2Icon,
  PlusIcon,
  MagnifyingGlassIcon,
  GlobeAltIcon,
  PhoneIcon,
  MapPinIcon,
  UserGroupIcon,
  CurrencyDollarIcon,
  EllipsisVerticalIcon,
  TrashIcon,
  PencilSquareIcon,
  ArrowTopRightOnSquareIcon,
  SparklesIcon,
  TableCellsIcon,
} from '@heroicons/vue/24/outline';

const props = defineProps({
  companies: {
    type: Array,
    default: () => [],
  },
  metrics: {
    type: Object,
    default: () => ({}),
  },
  industryConfig: {
    type: Object,
    default: () => ({}),
  },
  filters: {
    type: Object,
    default: () => ({}),
  },
});

const searchQuery = ref(props.filters?.search || '');
const isAddModalOpen = ref(false);
const isEditModalOpen = ref(false);
const activeCompany = ref(null);

const handleSearch = () => {
  router.get('/companies', { search: searchQuery.value }, {
    preserveState: true,
    replace: true,
  });
};

// Form for creating company
const companyForm = useForm({
  name: '',
  domain: '',
  industry: props.industryConfig?.name || '',
  phone: '',
  website: '',
  city: '',
  state: '',
  country: 'India',
  employee_count: 50,
  annual_revenue: 1000000,
});

const openAddModal = () => {
  companyForm.reset();
  companyForm.industry = props.industryConfig?.name || 'General';
  isAddModalOpen.value = true;
};

const submitAddCompany = () => {
  companyForm.post('/companies', {
    preserveScroll: true,
    onSuccess: () => {
      isAddModalOpen.value = false;
      companyForm.reset();
    }
  });
};

const openEditModal = (company) => {
  activeCompany.value = company;
  companyForm.name = company.name;
  companyForm.domain = company.domain;
  companyForm.industry = company.industry;
  companyForm.phone = company.phone;
  companyForm.website = company.website;
  companyForm.city = company.city;
  companyForm.state = company.state;
  companyForm.country = company.country;
  companyForm.employee_count = company.employee_count;
  companyForm.annual_revenue = company.annual_revenue;
  isEditModalOpen.value = true;
};

const submitEditCompany = () => {
  if (!activeCompany.value) return;
  companyForm.put(`/companies/${activeCompany.value.id}`, {
    preserveScroll: true,
    onSuccess: () => {
      isEditModalOpen.value = false;
    }
  });
};

const deleteCompany = (company) => {
  if (confirm(`Are you sure you want to remove ${company.name}?`)) {
    router.delete(`/companies/${company.id}`, {
      preserveScroll: true,
    });
  }
};

const formatCurrency = (val) => {
  if (!val) return '₹0';
  return new Intl.NumberFormat('en-IN', { style: 'currency', currency: 'INR', maximumFractionDigits: 0 }).format(val);
};
</script>

<template>
  <Head :title="`${industryConfig?.name || 'Company'} Accounts & Directory - JRV CRM`" />

  <div class="flex h-screen bg-slate-50 overflow-hidden font-sans text-slate-900">
    <!-- Navbar Sidebar -->
    <Navbar />

    <!-- Main Workspace -->
    <div class="flex-1 flex flex-col min-w-0 overflow-hidden">
      <!-- Top Bar Header -->
      <header class="h-16 bg-white border-b border-slate-200 px-6 flex items-center justify-between shrink-0 shadow-2xs">
        <div class="flex items-center gap-3">
          <div class="w-9 h-9 bg-gradient-to-tr from-slate-800 to-slate-900 rounded-xl flex items-center justify-center text-white font-black text-sm shadow-md">
            {{ industryConfig?.icon || '🏢' }}
          </div>
          <div>
            <h1 class="text-sm font-black text-slate-900 leading-tight">
              {{ industryConfig?.name || 'Corporate' }} Accounts & Organizations
            </h1>
            <p class="text-[11px] text-slate-500 font-medium">Enterprise Directory & Institutional Accounts</p>
          </div>
        </div>

        <div class="flex items-center gap-3">
          <Link href="/data-import" class="hidden sm:inline-flex px-3.5 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs rounded-xl shadow-xs items-center gap-1.5 transition">
            <TableCellsIcon class="w-4 h-4 text-emerald-600" />
            <span>Import Companies</span>
          </Link>

          <button
            @click="openAddModal"
            class="px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white text-xs font-extrabold rounded-xl shadow-md transition flex items-center gap-1.5 cursor-pointer"
          >
            <PlusIcon class="w-4 h-4 stroke-[3]" />
            <span>Add Organization</span>
          </button>
        </div>
      </header>

      <!-- Scrollable Body -->
      <div class="p-6 md:p-8 space-y-6 flex-1 overflow-y-auto max-w-7xl mx-auto w-full">
        <!-- Hero Banner -->
        <div class="bg-gradient-to-r from-slate-950 via-slate-900 to-slate-950 border border-slate-800 rounded-3xl p-6 md:p-7 text-white shadow-xl flex flex-col md:flex-row md:items-center justify-between gap-6">
          <div class="space-y-1.5 max-w-2xl">
            <div class="inline-flex items-center gap-2 px-2.5 py-0.5 rounded-md bg-white/10 text-slate-200 text-[10px] font-bold uppercase tracking-wider">
              <span>{{ industryConfig?.icon || '🏢' }}</span>
              <span>{{ industryConfig?.name }} Institutional Accounts</span>
            </div>
            <h2 class="text-xl md:text-2xl font-black tracking-tight text-white">
              Institutional & Partner Organizations
            </h2>
            <p class="text-xs text-slate-300">
              Manage corporate accounts, brokers, hospital groups, agency networks, and institutional clients.
            </p>
          </div>

          <div class="flex items-center gap-3">
            <button
              @click="openAddModal"
              class="px-5 py-2.5 bg-white text-slate-900 hover:bg-slate-100 font-black text-xs rounded-2xl shadow-lg transition cursor-pointer"
            >
              + Register New Account
            </button>
          </div>
        </div>

        <!-- Metric Stat Counters -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
          <div class="p-5 bg-white rounded-2xl border border-slate-200 shadow-xs space-y-1">
            <div class="flex items-center justify-between text-xs font-bold text-slate-500 uppercase">
              <span>Total Organizations</span>
              <BuildingOfficeIcon class="w-4 h-4 text-slate-600" />
            </div>
            <div class="text-3xl font-black text-slate-900">{{ metrics.total_companies || 0 }}</div>
            <p class="text-[11px] text-slate-400">Active corporate entities</p>
          </div>

          <div class="p-5 bg-white rounded-2xl border border-slate-200 shadow-xs space-y-1">
            <div class="flex items-center justify-between text-xs font-bold text-slate-500 uppercase">
              <span>Associated Contacts</span>
              <UserGroupIcon class="w-4 h-4 text-teal-600" />
            </div>
            <div class="text-3xl font-black text-teal-600">{{ metrics.total_contacts || 0 }}</div>
            <p class="text-[11px] text-slate-400">Linked stakeholders</p>
          </div>

          <div class="p-5 bg-white rounded-2xl border border-slate-200 shadow-xs space-y-1">
            <div class="flex items-center justify-between text-xs font-bold text-slate-500 uppercase">
              <span>Active Deals / Claims</span>
              <SparklesIcon class="w-4 h-4 text-indigo-600" />
            </div>
            <div class="text-3xl font-black text-indigo-600">{{ metrics.total_deals || 0 }}</div>
            <p class="text-[11px] text-slate-400">Contracts in progression</p>
          </div>

          <div class="p-5 bg-white rounded-2xl border border-slate-200 shadow-xs space-y-1">
            <div class="flex items-center justify-between text-xs font-bold text-slate-500 uppercase">
              <span>Portfolio Valuation</span>
              <CurrencyDollarIcon class="w-4 h-4 text-emerald-600" />
            </div>
            <div class="text-2xl font-black text-emerald-600 truncate">{{ formatCurrency(metrics.total_revenue) }}</div>
            <p class="text-[11px] text-slate-400">Cumulative institutional revenue</p>
          </div>
        </div>

        <!-- Search Bar & Filters -->
        <div class="bg-white border border-slate-200 rounded-2xl p-4 shadow-xs flex items-center justify-between gap-4">
          <div class="relative flex-1 max-w-md">
            <MagnifyingGlassIcon class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2" />
            <input
              type="text"
              v-model="searchQuery"
              @keyup.enter="handleSearch"
              placeholder="Search companies by name, domain, city, or sector..."
              class="w-full bg-slate-50 border border-slate-200 rounded-xl pl-10 pr-4 py-2 text-xs font-medium text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-slate-900/20"
            />
          </div>

          <button
            @click="handleSearch"
            class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-xl transition cursor-pointer"
          >
            Search
          </button>
        </div>

        <!-- Company Cards Grid -->
        <div v-if="companies.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
          <div
            v-for="company in companies"
            :key="company.id"
            class="bg-white border border-slate-200 hover:border-slate-300 rounded-3xl p-5 shadow-xs hover:shadow-md transition-all space-y-4 flex flex-col justify-between"
          >
            <div class="space-y-3">
              <!-- Top Row: Icon & Actions -->
              <div class="flex items-start justify-between">
                <div class="w-12 h-12 rounded-2xl bg-slate-100 border border-slate-200 flex items-center justify-center text-xl shadow-2xs font-black text-slate-800">
                  {{ company.name?.charAt(0) || '🏢' }}
                </div>

                <div class="flex items-center gap-1">
                  <button
                    @click="openEditModal(company)"
                    class="p-1.5 rounded-lg hover:bg-slate-100 text-slate-400 hover:text-slate-700 transition"
                    title="Edit organization"
                  >
                    <PencilSquareIcon class="w-4 h-4" />
                  </button>
                  <button
                    @click="deleteCompany(company)"
                    class="p-1.5 rounded-lg hover:bg-rose-50 text-slate-400 hover:text-rose-600 transition"
                    title="Delete organization"
                  >
                    <TrashIcon class="w-4 h-4" />
                  </button>
                </div>
              </div>

              <!-- Name & Industry -->
              <div>
                <h3 class="text-sm font-black text-slate-900 leading-snug">{{ company.name }}</h3>
                <span class="inline-block px-2 py-0.5 mt-1 rounded-md bg-slate-100 text-slate-600 text-[10px] font-bold">
                  {{ company.industry || industryConfig?.name || 'Enterprise' }}
                </span>
              </div>

              <!-- Metadata List -->
              <div class="space-y-1.5 text-xs text-slate-500 pt-1">
                <div v-if="company.website || company.domain" class="flex items-center gap-2">
                  <GlobeAltIcon class="w-3.5 h-3.5 text-slate-400 shrink-0" />
                  <a :href="company.website || `https://${company.domain}`" target="_blank" class="hover:text-teal-600 hover:underline truncate">
                    {{ company.domain || company.website }}
                  </a>
                </div>

                <div v-if="company.phone" class="flex items-center gap-2">
                  <PhoneIcon class="w-3.5 h-3.5 text-slate-400 shrink-0" />
                  <span>{{ company.phone }}</span>
                </div>

                <div v-if="company.city || company.state" class="flex items-center gap-2">
                  <MapPinIcon class="w-3.5 h-3.5 text-slate-400 shrink-0" />
                  <span>{{ [company.city, company.state, company.country].filter(Boolean).join(', ') }}</span>
                </div>
              </div>
            </div>

            <!-- Footer Stats -->
            <div class="pt-3 border-t border-slate-100 flex items-center justify-between text-[11px] text-slate-500 font-bold">
              <div>
                <span class="text-slate-900 font-extrabold">{{ company.employee_count || 0 }}</span> Staff
              </div>
              <div class="text-emerald-700 font-extrabold">
                {{ formatCurrency(company.annual_revenue) }}
              </div>
            </div>
          </div>
        </div>

        <!-- Empty State -->
        <div v-else class="p-12 text-center bg-white rounded-3xl border border-slate-200 space-y-3">
          <div class="w-12 h-12 rounded-full bg-slate-100 flex items-center justify-center text-2xl mx-auto">
            🏢
          </div>
          <h3 class="text-sm font-bold text-slate-800">No organizations found</h3>
          <p class="text-xs text-slate-500">Add a new company or upload your database spreadsheet.</p>
          <button
            @click="openAddModal"
            class="mt-2 px-4 py-2 bg-slate-900 text-white text-xs font-bold rounded-xl cursor-pointer"
          >
            + Add Organization
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- ADD / EDIT MODAL -->
  <div v-if="isAddModalOpen || isEditModalOpen" class="fixed inset-0 z-50 overflow-y-auto">
    <div @click="isAddModalOpen = false; isEditModalOpen = false;" class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity"></div>

    <div class="flex min-h-full items-center justify-center p-4">
      <div class="relative w-full max-w-lg bg-white rounded-3xl p-6 md:p-8 shadow-2xl border border-slate-200 space-y-6">
        <div class="border-b border-slate-100 pb-3">
          <h3 class="text-base font-black text-slate-900">
            {{ isEditModalOpen ? 'Edit Organization' : 'Add New Organization / Account' }}
          </h3>
          <p class="text-xs text-slate-500">Corporate and partner details for CRM tracking.</p>
        </div>

        <form @submit.prevent="isEditModalOpen ? submitEditCompany() : submitAddCompany()" class="space-y-4">
          <div>
            <label class="text-xs font-bold text-slate-700 block mb-1">Company / Organization Name *</label>
            <input
              type="text"
              v-model="companyForm.name"
              required
              class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2 text-xs text-slate-900 focus:bg-white focus:outline-none focus:ring-2 focus:ring-slate-900/20"
              placeholder="e.g. Tata AIG General Insurance"
            />
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="text-xs font-bold text-slate-700 block mb-1">Domain</label>
              <input
                type="text"
                v-model="companyForm.domain"
                class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2 text-xs"
                placeholder="e.g. tataaig.com"
              />
            </div>

            <div>
              <label class="text-xs font-bold text-slate-700 block mb-1">Industry / Sector</label>
              <input
                type="text"
                v-model="companyForm.industry"
                class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2 text-xs"
              />
            </div>
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="text-xs font-bold text-slate-700 block mb-1">Phone</label>
              <input
                type="text"
                v-model="companyForm.phone"
                class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2 text-xs"
                placeholder="+91 22 6665 8282"
              />
            </div>

            <div>
              <label class="text-xs font-bold text-slate-700 block mb-1">Website URL</label>
              <input
                type="text"
                v-model="companyForm.website"
                class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2 text-xs"
                placeholder="https://example.com"
              />
            </div>
          </div>

          <div class="grid grid-cols-3 gap-3">
            <div>
              <label class="text-xs font-bold text-slate-700 block mb-1">City</label>
              <input type="text" v-model="companyForm.city" class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3 py-2 text-xs" />
            </div>

            <div>
              <label class="text-xs font-bold text-slate-700 block mb-1">State</label>
              <input type="text" v-model="companyForm.state" class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3 py-2 text-xs" />
            </div>

            <div>
              <label class="text-xs font-bold text-slate-700 block mb-1">Country</label>
              <input type="text" v-model="companyForm.country" class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3 py-2 text-xs" />
            </div>
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="text-xs font-bold text-slate-700 block mb-1">Staff Count</label>
              <input type="number" v-model="companyForm.employee_count" class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2 text-xs" />
            </div>

            <div>
              <label class="text-xs font-bold text-slate-700 block mb-1">Annual Revenue (₹)</label>
              <input type="number" v-model="companyForm.annual_revenue" class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2 text-xs" />
            </div>
          </div>

          <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
            <button
              type="button"
              @click="isAddModalOpen = false; isEditModalOpen = false;"
              class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-xl transition cursor-pointer"
            >
              Cancel
            </button>

            <button
              type="submit"
              :disabled="companyForm.processing"
              class="px-5 py-2 bg-slate-900 hover:bg-slate-800 text-white text-xs font-black rounded-xl shadow-md transition cursor-pointer"
            >
              {{ companyForm.processing ? 'Saving...' : (isEditModalOpen ? 'Update Account' : 'Save Organization') }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>
