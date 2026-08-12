<script setup>
import { ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import Navbar from '@/Components/Navbar.vue';
import { 
  CheckBadgeIcon, 
  BuildingOfficeIcon, 
  CloudIcon, 
  UserGroupIcon, 
  CreditCardIcon, 
  SparklesIcon,
  RocketLaunchIcon,
  EnvelopeIcon,
  PhoneIcon,
  ShieldCheckIcon,
  XMarkIcon,
  GlobeAltIcon,
  ArrowLeftIcon,
  UserIcon
} from '@heroicons/vue/24/outline';

const goBack = () => {
  if (window.history.length > 1) {
    window.history.back();
  } else {
    router.visit('/admin');
  }
};

const props = defineProps({
  paidLeads: Array,
  tenants: Array,
  plans: Array,
});

const isSearchOpen = ref(false);
const isProfileModalOpen = ref(false);
const selectedUserProfile = ref(null);

const openUserProfile = (user) => {
  selectedUserProfile.value = user;
  isProfileModalOpen.value = true;
};

const getInitials = (name) => {
  if (!name) return 'CR';
  return name.split(' ').map(n => n[0]).join('').substring(0, 2).toUpperCase();
};
</script>

<template>
  <Head title="Paid Users & Active Subscriptions" />

  <div class="min-h-screen bg-slate-100 flex font-sans text-slate-900">
    <!-- Navbar (Fixed Left Vertical Sidebar) -->
    <Navbar @open-search="isSearchOpen = true" />

    <!-- MAIN CONTENT -->
    <main class="flex-1 p-6 sm:p-8 space-y-6 overflow-y-auto max-w-7xl mx-auto w-full">
      <!-- Header -->
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-slate-200 pb-5">
        <div class="space-y-1">
          <div class="flex items-center gap-2">
            <button 
              @click="goBack"
              class="px-3 py-1 bg-white hover:bg-slate-100 border border-slate-200 text-slate-700 font-extrabold text-xs rounded-xl shadow-2xs flex items-center gap-1.5 cursor-pointer transition-all whitespace-nowrap"
            >
              <ArrowLeftIcon class="w-3.5 h-3.5 text-slate-500" />
              <span>Back</span>
            </button>
            <div class="inline-flex items-center gap-1.5 px-3 py-0.5 rounded-full bg-emerald-50 border border-emerald-200 text-xs font-bold text-emerald-700">
              <CheckBadgeIcon class="w-4 h-4 text-emerald-600" />
              <span>Active Paid CRM Clients</span>
            </div>
          </div>
          <h1 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">Paid User Workspaces & Accounts</h1>
          <p class="text-slate-500 text-xs sm:text-sm font-medium">Real-time overview of active paid tenant workspaces, storage quotas, and billing status. Click any user card to view profile.</p>
        </div>

        <Link 
          href="/admin"
          class="px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white font-extrabold text-xs rounded-xl shadow-xs flex items-center gap-1.5 cursor-pointer transition-all w-fit"
        >
          <span>Back to Control Center</span>
        </Link>
      </div>

      <!-- Paid User Metrics -->
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="p-5 rounded-2xl bg-white border border-slate-200 shadow-2xs space-y-1">
          <div class="text-[10px] font-black uppercase text-slate-400 tracking-wider">Total Paid Clients</div>
          <div class="text-3xl font-black text-emerald-600">{{ paidLeads.length + tenants.length }}</div>
          <div class="text-xs font-bold text-slate-500">Active Tenant Workspaces</div>
        </div>
        <div class="p-5 rounded-2xl bg-white border border-slate-200 shadow-2xs space-y-1">
          <div class="text-[10px] font-black uppercase text-slate-400 tracking-wider">Monthly Recurring Revenue</div>
          <div class="text-3xl font-black text-slate-900">₹1,43,988</div>
          <div class="text-xs font-bold text-purple-700">Active Paid Subscriptions</div>
        </div>
        <div class="p-5 rounded-2xl bg-white border border-slate-200 shadow-2xs space-y-1">
          <div class="text-[10px] font-black uppercase text-slate-400 tracking-wider">Storage Provisioned</div>
          <div class="text-3xl font-black text-sky-600">125 GB</div>
          <div class="text-xs font-bold text-slate-500">Total Cloud Allocation</div>
        </div>
      </div>

      <!-- Paid Clients Table -->
      <div class="bg-white border border-slate-200 rounded-3xl shadow-2xs overflow-hidden">
        <div class="p-5 border-b border-slate-200 flex items-center justify-between">
          <div class="space-y-0.5">
            <h2 class="text-base font-black text-slate-900">Active Paid Client Accounts</h2>
            <p class="text-xs text-slate-500 font-medium">Click on any client card or row below to view full user profile & account details.</p>
          </div>
          <span class="px-3 py-1 bg-emerald-50 text-emerald-700 border border-emerald-200 text-xs font-extrabold rounded-full">
            {{ paidLeads.length + tenants.length }} Accounts Listed
          </span>
        </div>

        <div class="overflow-x-auto">
          <table class="w-full text-left border-collapse">
            <thead>
              <tr class="border-b border-slate-200 bg-slate-50 text-[9px] font-black uppercase text-slate-400 tracking-wider">
                <th class="py-2.5 px-3.5">Client / Firm Name</th>
                <th class="py-2.5 px-3.5">CRM Sector Engine</th>
                <th class="py-2.5 px-3.5">Subscription Plan</th>
                <th class="py-2.5 px-3.5">Plan Validity</th>
                <th class="py-2.5 px-3.5">Contact Details</th>
                <th class="py-2.5 px-3.5">Storage Quota</th>
                <th class="py-2.5 px-3.5">Payment Status</th>
                <th class="py-2.5 px-3.5 text-right">Workspace Action</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-xs">
              <tr 
                v-for="lead in paidLeads" 
                :key="'lead-'+lead.id" 
                @click="openUserProfile({
                  company_name: lead.company_name,
                  customer_name: lead.customer_name,
                  industry: lead.industry || 'Real Estate Sector',
                  email: lead.email,
                  phone: lead.phone || '+91 98123 55678',
                  plan_name: 'Growth Plan',
                  validity: 'Valid till Aug 12, 2027 (Annual)',
                  mrr: '₹14,999/mo',
                  storage: '100 GB Included',
                  domain: (lead.company_name.toLowerCase().replace(/[^a-z0-9]/g, '') || 'client') + '.jrvcrm.com',
                  initials: getInitials(lead.company_name)
                })"
                class="hover:bg-red-50/40 cursor-pointer transition-all group"
              >
                <td class="py-3 px-3.5">
                  <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-xl bg-slate-900 text-white font-black text-[11px] flex items-center justify-center group-hover:bg-red-600 transition-colors shrink-0">
                      {{ getInitials(lead.company_name) }}
                    </div>
                    <div class="truncate max-w-[150px]">
                      <div class="font-extrabold text-slate-900 text-xs truncate group-hover:text-red-600 transition-colors">{{ lead.company_name }}</div>
                      <div class="text-slate-500 text-[10px] font-medium truncate">{{ lead.customer_name }}</div>
                    </div>
                  </div>
                </td>
                <td class="py-3 px-3.5">
                  <span class="whitespace-nowrap inline-flex items-center gap-1 px-2.5 py-0.5 bg-red-50 text-red-700 border border-red-200 font-extrabold rounded-full text-[10px] shadow-2xs">
                    <span>{{ lead.industry?.includes('Lawyer') ? '⚖️' : (lead.industry?.includes('CS') ? '📜' : '🏠') }}</span>
                    <span>{{ lead.industry || 'Real Estate' }}</span>
                  </span>
                </td>
                <td class="py-3 px-3.5">
                  <span class="whitespace-nowrap inline-flex items-center px-2.5 py-0.5 bg-purple-50 text-purple-700 border border-purple-200 font-extrabold rounded-lg text-[10px]">
                    Growth Plan (₹14,999/mo)
                  </span>
                </td>
                <td class="py-3 px-3.5">
                  <div class="space-y-0.5">
                    <div class="flex items-center gap-1 text-slate-900 font-extrabold text-[11px] whitespace-nowrap">
                      <ClockIcon class="w-3.5 h-3.5 text-emerald-600 shrink-0" />
                      <span>Aug 12, 2027</span>
                    </div>
                    <div class="text-[9px] text-emerald-600 font-bold whitespace-nowrap">Annual (365 Days Left)</div>
                  </div>
                </td>
                <td class="py-3 px-3.5 space-y-0.5">
                  <div class="flex items-center gap-1 text-slate-700 font-medium text-[11px] truncate max-w-[160px]">
                    <EnvelopeIcon class="w-3.5 h-3.5 text-slate-400 shrink-0" />
                    <span class="truncate">{{ lead.email }}</span>
                  </div>
                  <div v-if="lead.phone" class="flex items-center gap-1 text-slate-500 text-[10px]">
                    <PhoneIcon class="w-3 h-3 text-slate-400 shrink-0" />
                    <span>{{ lead.phone }}</span>
                  </div>
                </td>
                <td class="py-3 px-3.5">
                  <div class="flex items-center gap-1.5 whitespace-nowrap">
                    <CloudIcon class="w-3.5 h-3.5 text-sky-600 shrink-0" />
                    <span class="font-extrabold text-slate-900 text-[11px]">100 GB</span>
                  </div>
                </td>
                <td class="py-3 px-3.5">
                  <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200 font-extrabold text-[10px] whitespace-nowrap">
                    <ShieldCheckIcon class="w-3 h-3" />
                    <span>Paid & Active</span>
                  </span>
                </td>
                <td class="py-3 px-3.5 text-right" @click.stop>
                  <Link 
                    href="/crm-selling-panel" 
                    class="px-3 py-1.5 bg-slate-900 hover:bg-slate-800 text-white font-extrabold text-[11px] rounded-xl shadow-2xs inline-flex items-center gap-1 cursor-pointer transition-all whitespace-nowrap"
                  >
                    <RocketLaunchIcon class="w-3 h-3 text-red-500" />
                    <span>Launch</span>
                  </Link>
                </td>
              </tr>

              <tr 
                v-for="tenant in tenants" 
                :key="'tenant-'+tenant.id" 
                @click="openUserProfile({
                  company_name: tenant.name,
                  customer_name: 'Administrator',
                  industry: 'Enterprise SaaS & Software',
                  email: 'admin@' + (tenant.domain || tenant.slug + '.jrvcrm.com'),
                  phone: '+91 98765 43210',
                  plan_name: 'Enterprise Plan',
                  validity: 'Valid till Sep 12, 2026 (Monthly)',
                  mrr: '₹39,999/mo',
                  storage: tenant.storage_used_gb + ' / ' + tenant.storage_limit_gb + ' GB',
                  domain: tenant.domain || tenant.slug + '.jrvcrm.com',
                  initials: getInitials(tenant.name)
                })"
                class="hover:bg-red-50/40 cursor-pointer transition-all group"
              >
                <td class="py-3 px-3.5">
                  <div class="flex items-center gap-2.5">
                    <div class="w-8 h-8 rounded-xl bg-red-600 text-white font-black text-[11px] flex items-center justify-center shrink-0">
                      {{ getInitials(tenant.name) }}
                    </div>
                    <div class="truncate max-w-[150px]">
                      <div class="font-extrabold text-slate-900 text-xs truncate group-hover:text-red-600 transition-colors">{{ tenant.name }}</div>
                      <div class="text-slate-500 text-[10px] font-medium truncate">{{ tenant.domain || tenant.slug + '.jrvcrm.com' }}</div>
                    </div>
                  </div>
                </td>
                <td class="py-3 px-3.5">
                  <span class="whitespace-nowrap inline-flex items-center gap-1 px-2.5 py-0.5 bg-red-50 text-red-700 border border-red-200 font-extrabold rounded-full text-[10px] shadow-2xs">
                    <span>💻</span>
                    <span>IT & Software</span>
                  </span>
                </td>
                <td class="py-3 px-3.5">
                  <span class="whitespace-nowrap inline-flex items-center px-2.5 py-0.5 bg-red-50 text-red-700 border border-red-200 font-extrabold rounded-lg text-[10px]">
                    Enterprise Plan (₹39,999/mo)
                  </span>
                </td>
                <td class="py-3 px-3.5">
                  <div class="space-y-0.5">
                    <div class="flex items-center gap-1 text-slate-900 font-extrabold text-[11px] whitespace-nowrap">
                      <ClockIcon class="w-3.5 h-3.5 text-purple-600 shrink-0" />
                      <span>Sep 12, 2026</span>
                    </div>
                    <div class="text-[9px] text-purple-600 font-bold whitespace-nowrap">Monthly (31 Days Left)</div>
                  </div>
                </td>
                <td class="py-3 px-3.5 text-slate-600 font-medium text-[11px] whitespace-nowrap">
                  Provisioned Instance
                </td>
                <td class="py-3 px-3.5">
                  <div class="flex items-center gap-1.5 whitespace-nowrap">
                    <CloudIcon class="w-3.5 h-3.5 text-sky-600 shrink-0" />
                    <span class="font-extrabold text-slate-900 text-[11px]">{{ tenant.storage_used_gb }} / {{ tenant.storage_limit_gb }} GB</span>
                  </div>
                </td>
                <td class="py-3 px-3.5">
                  <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200 font-extrabold text-[10px] whitespace-nowrap">
                    <ShieldCheckIcon class="w-3 h-3" />
                    <span>Paid & Active</span>
                  </span>
                </td>
                <td class="py-3 px-3.5 text-right" @click.stop>
                  <Link 
                    href="/crm-selling-panel" 
                    class="px-3 py-1.5 bg-red-600 hover:bg-red-700 text-white font-extrabold text-[11px] rounded-xl shadow-2xs inline-flex items-center gap-1 cursor-pointer transition-all whitespace-nowrap"
                  >
                    <RocketLaunchIcon class="w-3 h-3" />
                    <span>Manage</span>
                  </Link>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- USER PROFILE MODAL POPUP -->
      <div v-if="isProfileModalOpen && selectedUserProfile" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
        <div class="bg-white rounded-3xl max-w-xl w-full border border-slate-200 shadow-2xl overflow-hidden space-y-0 my-4">
          <!-- Header Banner -->
          <div class="bg-gradient-to-r from-red-600 via-red-700 to-slate-900 p-6 text-white relative">
            <button 
              @click="isProfileModalOpen = false" 
              class="absolute top-4 right-4 text-white/80 hover:text-white p-1.5 rounded-full hover:bg-white/10 transition-colors cursor-pointer"
            >
              <XMarkIcon class="w-5 h-5" />
            </button>

            <div class="flex items-center gap-3.5">
              <div class="w-14 h-14 rounded-2xl bg-white/20 text-white font-black text-xl flex items-center justify-center border border-white/20 backdrop-blur-xs shrink-0">
                {{ selectedUserProfile.initials }}
              </div>
              <div>
                <div class="flex items-center gap-2">
                  <h3 class="text-xl font-black text-white">{{ selectedUserProfile.company_name }}</h3>
                  <span class="px-2 py-0.5 rounded-full bg-emerald-400 text-slate-900 text-[10px] font-black uppercase tracking-wider">Paid Client</span>
                </div>
                <p class="text-xs text-red-100 font-medium mt-0.5">{{ selectedUserProfile.customer_name }} • {{ selectedUserProfile.industry }}</p>
              </div>
            </div>
          </div>

          <!-- Body Content -->
          <div class="p-6 space-y-5">
            <!-- Stats Row -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
              <div class="p-3 rounded-2xl bg-slate-50 border border-slate-200 space-y-0.5">
                <div class="text-[10px] font-black uppercase text-slate-400">Subscription Tier</div>
                <div class="text-xs font-black text-red-600 truncate">{{ selectedUserProfile.plan_name }}</div>
              </div>
              <div class="p-3 rounded-2xl bg-slate-50 border border-slate-200 space-y-0.5">
                <div class="text-[10px] font-black uppercase text-slate-400">Plan Rate</div>
                <div class="text-xs font-black text-slate-900 truncate">{{ selectedUserProfile.mrr }}</div>
              </div>
              <div class="p-3 rounded-2xl bg-slate-50 border border-slate-200 space-y-0.5">
                <div class="text-[10px] font-black uppercase text-slate-400">Plan Validity</div>
                <div class="text-[11px] font-black text-emerald-600 truncate">{{ selectedUserProfile.validity || 'Valid till Aug 2027' }}</div>
              </div>
              <div class="p-3 rounded-2xl bg-slate-50 border border-slate-200 space-y-0.5">
                <div class="text-[10px] font-black uppercase text-slate-400">Storage Quota</div>
                <div class="text-xs font-black text-sky-600 truncate">{{ selectedUserProfile.storage }}</div>
              </div>
            </div>

            <!-- Detailed Contact Info -->
            <div class="space-y-3 pt-2 border-t border-slate-100">
              <h4 class="text-xs font-black uppercase text-slate-400 tracking-wider">Account Profile & Contact Details</h4>
              
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
                <div class="flex items-center gap-3 p-3 rounded-xl bg-slate-50/80 border border-slate-200">
                  <EnvelopeIcon class="w-4 h-4 text-red-600 shrink-0" />
                  <div class="truncate">
                    <div class="text-[10px] font-bold text-slate-400">Email Address</div>
                    <div class="font-extrabold text-slate-900 truncate">{{ selectedUserProfile.email }}</div>
                  </div>
                </div>

                <div class="flex items-center gap-3 p-3 rounded-xl bg-slate-50/80 border border-slate-200">
                  <PhoneIcon class="w-4 h-4 text-red-600 shrink-0" />
                  <div>
                    <div class="text-[10px] font-bold text-slate-400">Phone Contact</div>
                    <div class="font-extrabold text-slate-900">{{ selectedUserProfile.phone }}</div>
                  </div>
                </div>

                <div class="flex items-center gap-3 p-3 rounded-xl bg-slate-50/80 border border-slate-200 sm:col-span-2">
                  <GlobeAltIcon class="w-4 h-4 text-red-600 shrink-0" />
                  <div class="truncate">
                    <div class="text-[10px] font-bold text-slate-400">Workspace Domain URL</div>
                    <div class="font-extrabold text-slate-900 truncate">{{ selectedUserProfile.domain }}</div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Footer Actions -->
            <div class="flex items-center justify-between gap-3 pt-3 border-t border-slate-200">
              <button 
                @click="isProfileModalOpen = false" 
                class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs rounded-xl cursor-pointer transition-colors"
              >
                Close Profile
              </button>
              <div class="flex items-center gap-2">
                <a 
                  :href="'https://wa.me/' + selectedUserProfile.phone.replace(/[^0-9]/g, '')" 
                  target="_blank" 
                  class="px-4 py-2 bg-emerald-50 hover:bg-emerald-100 text-emerald-800 border border-emerald-200 font-extrabold text-xs rounded-xl flex items-center gap-1.5 cursor-pointer transition-colors"
                >
                  <span>WhatsApp</span>
                </a>
                <Link 
                  href="/crm-selling-panel" 
                  class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-extrabold text-xs rounded-xl shadow-xs flex items-center gap-1.5 cursor-pointer transition-colors"
                >
                  <RocketLaunchIcon class="w-4 h-4" />
                  <span>Launch Workspace</span>
                </Link>
              </div>
            </div>
          </div>
        </div>
      </div>
    </main>
  </div>
</template>
