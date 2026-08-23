<script setup>
import { useForm } from '@inertiajs/vue3';
import { 
  XMarkIcon, 
  HomeModernIcon, 
  PlusIcon,
  CurrencyRupeeIcon,
  MapPinIcon,
  BuildingOffice2Icon
} from '@heroicons/vue/24/outline';

const props = defineProps({
  isOpen: {
    type: Boolean,
    default: false,
  }
});

const emit = defineEmits(['close', 'success']);

const form = useForm({
  title: '',
  listing_type: 'For Rent',
  property_type: 'Apartment',
  price: '',
  security_deposit: '',
  bedrooms: 2,
  bathrooms: 2,
  carpet_area_sqft: 850,
  furnishing_status: 'Semi-Furnished',
  city: 'Mumbai',
  state: 'Maharashtra',
  locality: '',
  address: '',
  owner_name: '',
  owner_phone: '',
  owner_email: '',
  description: '',
  status: 'Available',
});

const submit = () => {
  form.post('/properties', {
    preserveScroll: true,
    onSuccess: () => {
      form.reset();
      emit('success');
      emit('close');
    }
  });
};
</script>

<template>
  <div v-if="isOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs overflow-y-auto">
    <div class="bg-white border border-slate-200 rounded-3xl max-w-2xl w-full shadow-2xl flex flex-col max-h-[90vh] overflow-y-auto my-auto">
      
      <!-- Modal Header -->
      <div class="p-6 border-b border-slate-100 flex items-center justify-between sticky top-0 bg-white z-10">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center font-black">
            <HomeModernIcon class="w-5 h-5 stroke-[2.5]" />
          </div>
          <div>
            <h3 class="text-base font-black text-slate-900">Add New Property / Rental Listing</h3>
            <p class="text-xs text-slate-500 font-medium">Create a new residential, commercial, or rental unit listing.</p>
          </div>
        </div>

        <button @click="emit('close')" class="text-slate-400 hover:text-slate-600 p-1 rounded-lg cursor-pointer">
          <XMarkIcon class="w-5 h-5 stroke-[2.5]" />
        </button>
      </div>

      <!-- Modal Body -->
      <form @submit.prevent="submit" class="p-6 space-y-5">
        
        <!-- Title & Purpose -->
        <div class="space-y-1">
          <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">Listing Title *</label>
          <input 
            v-model="form.title" 
            type="text" 
            placeholder="e.g. Spacious 2 BHK Sea-View Flat with Balcony"
            required
            class="w-full bg-white border border-slate-300 rounded-xl px-4 py-2.5 text-sm font-bold text-slate-900 focus:outline-none focus:border-amber-500"
          />
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div class="space-y-1">
            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">Listing Purpose *</label>
            <select v-model="form.listing_type" class="w-full bg-white border border-slate-300 rounded-xl px-3.5 py-2.5 text-xs font-bold text-slate-800 focus:outline-none focus:border-amber-500">
              <option value="For Rent">For Rent / Lease</option>
              <option value="For Sale">For Sale</option>
              <option value="Lease">Commercial Lease</option>
              <option value="PG / Co-living">PG / Co-living</option>
            </select>
          </div>

          <div class="space-y-1">
            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">Property Type *</label>
            <select v-model="form.property_type" class="w-full bg-white border border-slate-300 rounded-xl px-3.5 py-2.5 text-xs font-bold text-slate-800 focus:outline-none focus:border-amber-500">
              <option value="Apartment">Apartment / Flat</option>
              <option value="Villa / House">Independent Villa / House</option>
              <option value="Studio Flat">Studio 1 RK</option>
              <option value="Commercial Office">Commercial Office</option>
              <option value="Retail Shop">Retail Shop</option>
              <option value="Penthouse">Luxury Penthouse</option>
            </select>
          </div>
        </div>

        <!-- Pricing -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div class="space-y-1">
            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">
              {{ form.listing_type === 'For Rent' ? 'Monthly Rent (₹) *' : 'Price / Valuation (₹) *' }}
            </label>
            <input 
              v-model="form.price" 
              type="number" 
              placeholder="e.g. 35000"
              required
              class="w-full bg-white border border-slate-300 rounded-xl px-4 py-2.5 text-xs font-bold text-slate-900 focus:outline-none focus:border-amber-500"
            />
          </div>

          <div class="space-y-1">
            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">Security Deposit (₹)</label>
            <input 
              v-model="form.security_deposit" 
              type="number" 
              placeholder="e.g. 100000"
              class="w-full bg-white border border-slate-300 rounded-xl px-4 py-2.5 text-xs font-bold text-slate-900 focus:outline-none focus:border-amber-500"
            />
          </div>
        </div>

        <!-- Specifications -->
        <div class="grid grid-cols-3 gap-4">
          <div class="space-y-1">
            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">Bedrooms (BHK)</label>
            <select v-model="form.bedrooms" class="w-full bg-white border border-slate-300 rounded-xl px-3 py-2 text-xs font-bold text-slate-800 focus:outline-none focus:border-amber-500">
              <option :value="1">1 BHK</option>
              <option :value="2">2 BHK</option>
              <option :value="3">3 BHK</option>
              <option :value="4">4 BHK</option>
              <option :value="5">5+ BHK</option>
            </select>
          </div>

          <div class="space-y-1">
            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">Bathrooms</label>
            <input 
              v-model="form.bathrooms" 
              type="number" 
              class="w-full bg-white border border-slate-300 rounded-xl px-3 py-2 text-xs font-bold text-slate-900 focus:outline-none focus:border-amber-500"
            />
          </div>

          <div class="space-y-1">
            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">Carpet Area (sqft)</label>
            <input 
              v-model="form.carpet_area_sqft" 
              type="number" 
              placeholder="850"
              class="w-full bg-white border border-slate-300 rounded-xl px-3 py-2 text-xs font-bold text-slate-900 focus:outline-none focus:border-amber-500"
            />
          </div>
        </div>

        <!-- Furnishing & Locality -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
          <div class="space-y-1">
            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">Furnishing</label>
            <select v-model="form.furnishing_status" class="w-full bg-white border border-slate-300 rounded-xl px-3 py-2 text-xs font-bold text-slate-800 focus:outline-none focus:border-amber-500">
              <option value="Fully Furnished">Fully Furnished</option>
              <option value="Semi-Furnished">Semi-Furnished</option>
              <option value="Unfurnished">Unfurnished</option>
            </select>
          </div>

          <div class="space-y-1">
            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">City *</label>
            <input 
              v-model="form.city" 
              type="text" 
              required
              class="w-full bg-white border border-slate-300 rounded-xl px-3 py-2 text-xs font-bold text-slate-900 focus:outline-none focus:border-amber-500"
            />
          </div>

          <div class="space-y-1">
            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">Locality / Area</label>
            <input 
              v-model="form.locality" 
              type="text" 
              placeholder="e.g. Bandra West"
              class="w-full bg-white border border-slate-300 rounded-xl px-3 py-2 text-xs font-bold text-slate-900 focus:outline-none focus:border-amber-500"
            />
          </div>
        </div>

        <!-- Owner & Contact Info -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div class="space-y-1">
            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">Owner / Landlord Name</label>
            <input 
              v-model="form.owner_name" 
              type="text" 
              placeholder="e.g. Rajesh Mehra"
              class="w-full bg-white border border-slate-300 rounded-xl px-3.5 py-2 text-xs font-bold text-slate-900 focus:outline-none focus:border-amber-500"
            />
          </div>

          <div class="space-y-1">
            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">Owner Contact Phone</label>
            <input 
              v-model="form.owner_phone" 
              type="text" 
              placeholder="+91 9876543210"
              class="w-full bg-white border border-slate-300 rounded-xl px-3.5 py-2 text-xs font-bold text-slate-900 focus:outline-none focus:border-amber-500"
            />
          </div>
        </div>

        <!-- Submit Buttons -->
        <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-3">
          <button 
            @click="emit('close')" 
            type="button" 
            class="px-4 py-2 text-xs font-bold text-slate-600 hover:bg-slate-100 rounded-xl cursor-pointer"
          >
            Cancel
          </button>
          
          <button 
            type="submit" 
            :disabled="form.processing"
            class="px-6 py-2.5 bg-amber-600 hover:bg-amber-700 text-white text-xs font-extrabold rounded-xl shadow-md disabled:opacity-50 transition cursor-pointer flex items-center gap-2"
          >
            <div v-if="form.processing" class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></div>
            <PlusIcon v-else class="w-4 h-4 stroke-[3]" />
            <span>{{ form.processing ? 'Publishing...' : 'Save & Publish Listing' }}</span>
          </button>
        </div>
      </form>

    </div>
  </div>
</template>
