<script setup>
import { ref, computed } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import Navbar from '@/Components/Navbar.vue';
import GlobalSearchModal from '@/Components/GlobalSearchModal.vue';
import CreatePropertyModal from '@/Components/CreatePropertyModal.vue';
import DataImportModal from '@/Components/DataImportModal.vue';
import LoginHistoryModal from '@/Components/LoginHistoryModal.vue';
import { 
  BellIcon, 
  HomeModernIcon, 
  BuildingOffice2Icon,
  MapPinIcon, 
  PlusIcon,
  MagnifyingGlassIcon,
  FunnelIcon,
  AdjustmentsHorizontalIcon,
  ChevronLeftIcon,
  ChevronRightIcon,
  ArrowUpTrayIcon,
  ArrowDownTrayIcon,
  CurrencyRupeeIcon,
  CheckBadgeIcon,
  ClockIcon,
  PhoneIcon,
  EnvelopeIcon,
  Squares2X2Icon,
  ListBulletIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
  properties: Object,
  metrics: Object,
  filters: Object,
});

const page = usePage();
const customNav = computed(() => page.props.custom_nav || {});
const businessSettings = computed(() => page.props.business_settings || { business_name: 'Real Estate CRM', business_icon: '🏠' });

const getNavLabel = (key, fallback) => customNav.value[key]?.label || fallback;

const isSearchOpen = ref(false);
const isCreatePropertyOpen = ref(false);
const isImportModalOpen = ref(false);
const isLoginHistoryOpen = ref(false);
const viewMode = ref('grid'); // 'grid' or 'table'

// Filters
const searchQuery = ref(props.filters.query || '');
const listingType = ref(props.filters.listing_type || '');
const propertyType = ref(props.filters.property_type || '');
const bedrooms = ref(props.filters.bedrooms || '');
const furnishingStatus = ref(props.filters.furnishing_status || '');
const status = ref(props.filters.status || '');
const city = ref(props.filters.city || '');

const applyFilter = () => {
  router.get('/properties', {
    query: searchQuery.value,
    listing_type: listingType.value,
    property_type: propertyType.value,
    bedrooms: bedrooms.value,
    furnishing_status: furnishingStatus.value,
    status: status.value,
    city: city.value,
  }, {
    preserveState: true,
    replace: true,
  });
};

const resetFilter = () => {
  searchQuery.value = '';
  listingType.value = '';
  propertyType.value = '';
  bedrooms.value = '';
  furnishingStatus.value = '';
  status.value = '';
  city.value = '';
  applyFilter();
};

const updateStatus = (property, newStatus) => {
  router.patch(`/properties/${property.id}/status`, {
    status: newStatus,
  }, {
    preserveScroll: true,
  });
};
</script>

<template>
  <Head title="Properties & Rental Listings - JRV Real Estate CRM" />

  <div class="min-h-screen bg-slate-100 flex font-sans text-slate-900">
    <!-- Navbar (Fixed Left Vertical Sidebar) -->
    <Navbar @open-search="isSearchOpen = true" />

    <!-- Main Content Area -->
    <div class="flex-1 flex flex-col min-w-0">
      
      <!-- Top Action Bar -->
      <header class="bg-white border-b border-slate-200 px-6 py-3 flex items-center justify-between gap-3 shadow-xs">
        <div class="flex items-center gap-2">
          <!-- Add Property Button -->
          <button @click="isCreatePropertyOpen = true" type="button" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-extrabold text-xs rounded-xl shadow-xs transition-all flex items-center gap-1.5 cursor-pointer">
            <PlusIcon class="w-4 h-4 stroke-[3]" />
            <span>+ Add Property / Rental</span>
          </button>

          <!-- Customize Brand & Menu Button -->
          <Link href="/tenant/settings/navigation" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-extrabold text-xs rounded-xl shadow-xs transition-all flex items-center gap-1.5">
            <AdjustmentsHorizontalIcon class="w-4 h-4" />
            <span>Customize Brand & Menu</span>
          </Link>
        </div>

        <div class="flex items-center gap-3">
          <div class="relative">
            <button @click="isSearchOpen = true" class="p-2 bg-slate-100 rounded-full text-slate-600 hover:bg-slate-200 relative cursor-pointer">
              <BellIcon class="w-5 h-5" />
              <span class="absolute -top-1 -right-1 bg-amber-600 text-white text-[10px] font-black w-4 h-4 rounded-full flex items-center justify-center">
                3
              </span>
            </button>
          </div>

          <div class="w-9 h-9 bg-amber-600 rounded-full flex items-center justify-center text-white font-black text-sm shadow-md">
            {{ businessSettings.business_icon }}
          </div>
        </div>
      </header>

      <!-- Page Content Area -->
      <div class="p-6 space-y-6 flex-1 overflow-y-auto w-full">
        
        <!-- Header Bar with Import/Export Actions -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-5 rounded-3xl border border-slate-200 shadow-2xs">
          <div>
            <div class="flex items-center gap-2.5">
              <h1 class="text-xl font-black text-slate-900 tracking-tight">Properties & Rental Listings Directory</h1>
              <span class="px-3 py-1 bg-amber-50 text-amber-800 text-xs font-black rounded-full border border-amber-200">
                🏠 Real Estate CRM
              </span>
            </div>
            <p class="text-xs text-slate-500 font-medium mt-0.5">Manage property inventory, tenant rental units, valuations, and site visit schedules.</p>
          </div>

          <div class="flex items-center gap-2">
            <!-- View Mode Switcher -->
            <div class="bg-slate-100 p-1 rounded-xl flex items-center gap-1 border border-slate-200 mr-2">
              <button 
                @click="viewMode = 'grid'" 
                :class="['p-1.5 rounded-lg text-xs font-bold transition', viewMode === 'grid' ? 'bg-white shadow-2xs text-amber-600' : 'text-slate-500 hover:text-slate-900']"
                title="Grid View"
              >
                <Squares2X2Icon class="w-4 h-4" />
              </button>
              <button 
                @click="viewMode = 'table'" 
                :class="['p-1.5 rounded-lg text-xs font-bold transition', viewMode === 'table' ? 'bg-white shadow-2xs text-amber-600' : 'text-slate-500 hover:text-slate-900']"
                title="Table View"
              >
                <ListBulletIcon class="w-4 h-4" />
              </button>
            </div>

            <!-- Import CSV Button -->
            <button 
              @click="isImportModalOpen = true"
              class="px-3.5 py-2 bg-white hover:bg-slate-50 text-slate-700 font-bold text-xs rounded-xl border border-slate-300 shadow-2xs flex items-center gap-1.5 transition cursor-pointer"
            >
              <ArrowUpTrayIcon class="w-4 h-4 text-slate-500 stroke-[2.5]" />
              <span>Import Data</span>
            </button>

            <!-- Export CSV Button -->
            <a 
              href="/properties/export"
              class="px-3.5 py-2 bg-white hover:bg-slate-50 text-slate-700 font-bold text-xs rounded-xl border border-slate-300 shadow-2xs flex items-center gap-1.5 transition"
            >
              <ArrowDownTrayIcon class="w-4 h-4 text-slate-500 stroke-[2.5]" />
              <span>Export Data</span>
            </a>

            <!-- Add Property Modal Trigger -->
            <button 
              @click="isCreatePropertyOpen = true"
              class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white font-extrabold text-xs rounded-xl shadow-md flex items-center gap-1.5 transition cursor-pointer"
            >
              <PlusIcon class="w-4 h-4 stroke-[3]" />
              <span>+ Add Listing</span>
            </button>
          </div>
        </div>

        <!-- 5 Real Estate Metric Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
          <div class="p-5 rounded-2xl bg-gradient-to-r from-amber-500 to-amber-600 text-white shadow-md space-y-1">
            <div class="text-2xl font-black">{{ metrics.total_properties }}</div>
            <div class="text-xs font-bold tracking-wide opacity-90">Total Properties</div>
          </div>

          <div class="p-5 rounded-2xl bg-gradient-to-r from-emerald-500 to-teal-600 text-white shadow-md space-y-1">
            <div class="text-2xl font-black">{{ metrics.for_rent }}</div>
            <div class="text-xs font-bold tracking-wide opacity-90">For Rent / Lease</div>
          </div>

          <div class="p-5 rounded-2xl bg-gradient-to-r from-indigo-500 to-blue-600 text-white shadow-md space-y-1">
            <div class="text-2xl font-black">{{ metrics.for_sale }}</div>
            <div class="text-xs font-bold tracking-wide opacity-90">For Sale Units</div>
          </div>

          <div class="p-5 rounded-2xl bg-gradient-to-r from-cyan-500 to-sky-600 text-white shadow-md space-y-1">
            <div class="text-2xl font-black">{{ metrics.available_units }}</div>
            <div class="text-xs font-bold tracking-wide opacity-90">Available Right Now</div>
          </div>

          <div class="p-5 rounded-2xl bg-gradient-to-r from-rose-500 to-pink-600 text-white shadow-md space-y-1">
            <div class="text-2xl font-black">{{ metrics.rented_or_sold }}</div>
            <div class="text-xs font-bold tracking-wide opacity-90">Leased / Sold Out</div>
          </div>
        </div>

        <!-- Multi-Criteria Filter Bar -->
        <div class="bg-white border border-slate-200 rounded-3xl p-5 shadow-2xs space-y-4">
          <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 lg:grid-cols-7 gap-3">
            
            <!-- Search Text -->
            <div class="lg:col-span-2 space-y-1">
              <label class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Search Keyword</label>
              <div class="relative">
                <input 
                  v-model="searchQuery" 
                  @keydown.enter="applyFilter"
                  type="text" 
                  placeholder="Title, Locality, Code, Owner..." 
                  class="w-full bg-slate-50 border border-slate-300 rounded-xl pl-8 pr-3 py-1.5 text-xs font-bold text-slate-800 focus:outline-none focus:border-amber-500"
                />
                <MagnifyingGlassIcon class="w-4 h-4 text-slate-400 absolute left-2.5 top-2" />
              </div>
            </div>

            <!-- Listing Purpose -->
            <div class="space-y-1">
              <label class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Purpose</label>
              <select v-model="listingType" @change="applyFilter" class="w-full bg-slate-50 border border-slate-300 rounded-xl px-2.5 py-1.5 text-xs font-bold text-slate-700">
                <option value="">All Purpose</option>
                <option value="For Rent">For Rent</option>
                <option value="For Sale">For Sale</option>
                <option value="Lease">Lease</option>
                <option value="PG / Co-living">PG / Co-living</option>
              </select>
            </div>

            <!-- Property Type -->
            <div class="space-y-1">
              <label class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Property Type</label>
              <select v-model="propertyType" @change="applyFilter" class="w-full bg-slate-50 border border-slate-300 rounded-xl px-2.5 py-1.5 text-xs font-bold text-slate-700">
                <option value="">All Types</option>
                <option value="Apartment">Apartment</option>
                <option value="Villa / House">Villa / House</option>
                <option value="Studio Flat">Studio</option>
                <option value="Commercial Office">Office</option>
                <option value="Retail Shop">Shop</option>
                <option value="Penthouse">Penthouse</option>
              </select>
            </div>

            <!-- Bedrooms -->
            <div class="space-y-1">
              <label class="text-[10px] font-bold uppercase tracking-wider text-slate-500">BHK / Bedrooms</label>
              <select v-model="bedrooms" @change="applyFilter" class="w-full bg-slate-50 border border-slate-300 rounded-xl px-2.5 py-1.5 text-xs font-bold text-slate-700">
                <option value="">All BHK</option>
                <option value="1">1 BHK</option>
                <option value="2">2 BHK</option>
                <option value="3">3 BHK</option>
                <option value="4+">4+ BHK</option>
              </select>
            </div>

            <!-- Furnishing -->
            <div class="space-y-1">
              <label class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Furnishing</label>
              <select v-model="furnishingStatus" @change="applyFilter" class="w-full bg-slate-50 border border-slate-300 rounded-xl px-2.5 py-1.5 text-xs font-bold text-slate-700">
                <option value="">All Furnishing</option>
                <option value="Fully Furnished">Fully Furnished</option>
                <option value="Semi-Furnished">Semi-Furnished</option>
                <option value="Unfurnished">Unfurnished</option>
              </select>
            </div>

            <!-- Status & Action -->
            <div class="flex items-end gap-2">
              <button 
                @click="applyFilter" 
                class="w-full px-3 py-1.5 bg-amber-600 hover:bg-amber-700 text-white text-xs font-black rounded-xl shadow-xs transition flex items-center justify-center gap-1 cursor-pointer"
              >
                <FunnelIcon class="w-3.5 h-3.5" />
                <span>Filter</span>
              </button>
              <button 
                @click="resetFilter" 
                class="px-2.5 py-1.5 bg-slate-200 hover:bg-slate-300 text-slate-700 text-xs font-bold rounded-xl transition cursor-pointer"
                title="Reset All Filters"
              >
                Reset
              </button>
            </div>

          </div>
        </div>

        <!-- Grid View Mode -->
        <div v-if="viewMode === 'grid'" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
          <div 
            v-for="prop in properties.data" 
            :key="prop.id" 
            class="bg-white border border-slate-200 hover:border-amber-400 rounded-3xl p-5 shadow-xs hover:shadow-md transition-all flex flex-col justify-between space-y-4 group"
          >
            <!-- Card Top Header -->
            <div class="space-y-3">
              <div class="flex items-start justify-between gap-2">
                <div>
                  <span class="px-2.5 py-0.5 bg-amber-50 text-amber-800 text-[10px] font-black rounded-full border border-amber-200 uppercase tracking-wider">
                    {{ prop.property_code }}
                  </span>
                  <span :class="[
                    'ml-2 px-2.5 py-0.5 text-[10px] font-black rounded-full uppercase tracking-wider',
                    prop.listing_type === 'For Rent' ? 'bg-emerald-100 text-emerald-800' : 'bg-indigo-100 text-indigo-800'
                  ]">
                    {{ prop.listing_type }}
                  </span>
                </div>

                <!-- Status Badge -->
                <span :class="[
                  'px-2.5 py-0.5 text-[10px] font-black rounded-full',
                  prop.status === 'Available' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-slate-100 text-slate-600'
                ]">
                  {{ prop.status }}
                </span>
              </div>

              <div>
                <h3 class="text-sm font-black text-slate-900 group-hover:text-amber-600 transition leading-snug">
                  {{ prop.title }}
                </h3>
                <p class="text-xs text-slate-500 font-medium flex items-center gap-1 mt-1">
                  <MapPinIcon class="w-3.5 h-3.5 text-slate-400 shrink-0" />
                  <span>{{ prop.locality ? `${prop.locality}, ${prop.city}` : prop.city }}</span>
                </p>
              </div>

              <!-- Key Specs (BHK, Baths, Area) -->
              <div class="grid grid-cols-3 gap-2 bg-slate-50 p-2.5 rounded-2xl border border-slate-100 text-center">
                <div>
                  <div class="text-[10px] font-bold text-slate-400 uppercase">Bedrooms</div>
                  <div class="text-xs font-black text-slate-800">{{ prop.bedrooms }} BHK</div>
                </div>
                <div>
                  <div class="text-[10px] font-bold text-slate-400 uppercase">Baths</div>
                  <div class="text-xs font-black text-slate-800">{{ prop.bathrooms }}</div>
                </div>
                <div>
                  <div class="text-[10px] font-bold text-slate-400 uppercase">Carpet Area</div>
                  <div class="text-xs font-black text-slate-800">{{ prop.carpet_area_sqft || 850 }} sqft</div>
                </div>
              </div>

              <!-- Owner Info & Furnishing -->
              <div class="flex items-center justify-between text-xs font-medium text-slate-600 pt-1 border-t border-slate-100">
                <span class="text-[11px] text-slate-500 font-bold">🛋️ {{ prop.furnishing_status }}</span>
                <span v-if="prop.owner_name" class="text-[11px] text-slate-700 font-bold">👤 {{ prop.owner_name }}</span>
              </div>
            </div>

            <!-- Card Bottom Price & Status Toggle -->
            <div class="pt-3 border-t border-slate-100 flex items-center justify-between gap-3">
              <div>
                <div class="text-[10px] font-bold text-slate-400 uppercase">
                  {{ prop.listing_type === 'For Rent' ? 'Rent Price / Month' : 'Total Valuation' }}
                </div>
                <div class="text-base font-black text-slate-900">
                  ₹{{ Number(prop.price).toLocaleString('en-IN') }}
                  <span v-if="prop.listing_type === 'For Rent'" class="text-[10px] text-slate-400 font-normal">/mo</span>
                </div>
              </div>

              <!-- Quick Status Selector -->
              <select 
                :value="prop.status" 
                @change="updateStatus(prop, $event.target.value)"
                class="bg-slate-50 border border-slate-200 text-slate-700 text-[11px] font-bold rounded-xl px-2.5 py-1.5 focus:outline-none focus:border-amber-500 cursor-pointer"
              >
                <option value="Available">Available</option>
                <option value="Under Offer">Under Offer</option>
                <option value="Rented Out">Rented Out</option>
                <option value="Sold">Sold</option>
              </select>
            </div>

          </div>
        </div>

        <!-- Table View Mode -->
        <div v-else class="bg-white rounded-3xl border border-slate-200 shadow-2xs overflow-hidden">
          <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
              <thead>
                <tr class="bg-slate-50/80 border-b border-slate-200 text-[11px] font-black uppercase tracking-wider text-slate-500">
                  <th class="py-3.5 px-4">Code & Title</th>
                  <th class="py-3.5 px-4">Type</th>
                  <th class="py-3.5 px-4">Location</th>
                  <th class="py-3.5 px-4">Specs</th>
                  <th class="py-3.5 px-4">Furnishing</th>
                  <th class="py-3.5 px-4">Price</th>
                  <th class="py-3.5 px-4">Owner / Contact</th>
                  <th class="py-3.5 px-4 text-right">Status</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100 text-xs font-semibold text-slate-700">
                <tr v-for="prop in properties.data" :key="prop.id" class="hover:bg-slate-50/60 transition">
                  <td class="py-3.5 px-4">
                    <div class="font-black text-slate-900">{{ prop.title }}</div>
                    <div class="text-[10px] text-amber-700 font-mono font-bold">{{ prop.property_code }}</div>
                  </td>
                  <td class="py-3.5 px-4">
                    <span class="px-2 py-0.5 bg-slate-100 rounded-lg text-[10px] font-bold text-slate-700">{{ prop.property_type }}</span>
                  </td>
                  <td class="py-3.5 px-4">
                    <div>{{ prop.locality || prop.city }}</div>
                    <div class="text-[10px] text-slate-400">{{ prop.state }}</div>
                  </td>
                  <td class="py-3.5 px-4">
                    <div class="font-bold text-slate-900">{{ prop.bedrooms }} BHK ({{ prop.bathrooms }} Bath)</div>
                    <div class="text-[10px] text-slate-400">{{ prop.carpet_area_sqft || 850 }} sqft</div>
                  </td>
                  <td class="py-3.5 px-4 text-[11px]">
                    {{ prop.furnishing_status }}
                  </td>
                  <td class="py-3.5 px-4 font-black text-slate-900">
                    ₹{{ Number(prop.price).toLocaleString('en-IN') }}
                  </td>
                  <td class="py-3.5 px-4 text-[11px]">
                    <div>{{ prop.owner_name || 'Direct Listing' }}</div>
                    <div class="text-[10px] text-slate-400">{{ prop.owner_phone || '—' }}</div>
                  </td>
                  <td class="py-3.5 px-4 text-right">
                    <select 
                      :value="prop.status" 
                      @change="updateStatus(prop, $event.target.value)"
                      class="bg-slate-50 border border-slate-200 text-slate-700 text-[11px] font-bold rounded-xl px-2 py-1 focus:outline-none focus:border-amber-500 cursor-pointer"
                    >
                      <option value="Available">Available</option>
                      <option value="Under Offer">Under Offer</option>
                      <option value="Rented Out">Rented Out</option>
                      <option value="Sold">Sold</option>
                    </select>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Empty State Notice -->
        <div v-if="properties.data.length === 0" class="bg-white rounded-3xl border border-slate-200 p-12 text-center space-y-4">
          <div class="w-16 h-16 rounded-full bg-amber-50 text-amber-600 flex items-center justify-center mx-auto shadow-xs">
            <HomeModernIcon class="w-8 h-8" />
          </div>
          <div class="space-y-1">
            <h3 class="text-base font-black text-slate-900">No Real Estate Properties Listed Yet</h3>
            <p class="text-xs text-slate-500 max-w-sm mx-auto">Click below or upload your CSV spreadsheet to add residential and rental listings to your CRM.</p>
          </div>
          <div class="flex items-center justify-center gap-3 pt-2">
            <button 
              @click="isCreatePropertyOpen = true" 
              class="px-5 py-2.5 bg-amber-600 hover:bg-amber-700 text-white text-xs font-black rounded-xl shadow-md flex items-center gap-1.5 transition cursor-pointer"
            >
              <PlusIcon class="w-4 h-4 stroke-[3]" />
              <span>+ Add First Property</span>
            </button>
            <button 
              @click="isImportModalOpen = true" 
              class="px-5 py-2.5 bg-white hover:bg-slate-50 text-slate-700 text-xs font-bold rounded-xl border border-slate-300 shadow-2xs flex items-center gap-1.5 transition cursor-pointer"
            >
              <ArrowUpTrayIcon class="w-4 h-4 stroke-[2.5]" />
              <span>Import CSV</span>
            </button>
          </div>
        </div>

        <!-- Pagination -->
        <div v-if="properties.links && properties.links.length > 3" class="flex items-center justify-between pt-4 border-t border-slate-200">
          <p class="text-xs text-slate-500 font-medium">
            Showing <span class="font-bold text-slate-800">{{ properties.from || 0 }}</span> to <span class="font-bold text-slate-800">{{ properties.to || 0 }}</span> of <span class="font-bold text-slate-800">{{ properties.total || 0 }}</span> properties
          </p>

          <div class="flex items-center gap-1">
            <template v-for="(link, idx) in properties.links" :key="idx">
              <Link
                v-if="link.url"
                :href="link.url"
                :class="[
                  'px-3.5 py-1.5 text-xs font-bold rounded-xl transition',
                  link.active ? 'bg-amber-600 text-white shadow-xs' : 'bg-white text-slate-700 border border-slate-200 hover:bg-slate-50'
                ]"
                v-html="link.label"
              />
            </template>
          </div>
        </div>

      </div>
    </div>

    <!-- Modals -->
    <GlobalSearchModal :is-open="isSearchOpen" @close="isSearchOpen = false" />
    <CreatePropertyModal :is-open="isCreatePropertyOpen" @close="isCreatePropertyOpen = false" />
    <LoginHistoryModal :is-open="isLoginHistoryOpen" @close="isLoginHistoryOpen = false" />
    <DataImportModal 
      :is-open="isImportModalOpen" 
      title="Import Real Estate Properties (CSV)"
      import-url="/properties/import"
      sample-url="/properties/sample-csv"
      description="Upload a CSV spreadsheet with Title, Purpose (Rent/Sale), Type, Price, BHK, Area, Locality, and City."
      @close="isImportModalOpen = false" 
    />
  </div>
</template>
