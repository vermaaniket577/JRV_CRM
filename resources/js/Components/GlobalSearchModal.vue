<script setup>
import { ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import { MagnifyingGlassIcon, XMarkIcon, BanknotesIcon, UserIcon, BuildingOfficeIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
  isOpen: Boolean,
});

const emit = defineEmits(['close']);
const searchQuery = ref('');
const results = ref({ deals: [], contacts: [], companies: [] });
const isLoading = ref(false);

const formatINR = (val) => {
  return '₹' + Number(val || 0).toLocaleString('en-IN', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  });
};

const performSearch = async () => {
  if (!searchQuery.value || searchQuery.value.trim().length < 2) {
    results.value = { deals: [], contacts: [], companies: [] };
    return;
  }

  isLoading.value = true;

  try {
    const res = await fetch(`/global-search?query=${encodeURIComponent(searchQuery.value)}`);
    if (res.ok) {
      results.value = await res.json();
    }
  } catch (e) {
    console.error('Search error:', e);
  } finally {
    isLoading.value = false;
  }
};

watch(searchQuery, () => {
  performSearch();
});

const navigateTo = (path) => {
  emit('close');
  router.visit(path);
};
</script>

<template>
  <div v-if="isOpen" class="fixed inset-0 z-50 flex items-start justify-center pt-20 p-4 bg-slate-900/40 backdrop-blur-xs">
    <div class="bg-white border border-slate-200 rounded-2xl max-w-xl w-full shadow-2xl flex flex-col overflow-hidden">
      <!-- Search Input Header -->
      <div class="p-4 border-b border-slate-200 flex items-center gap-3">
        <MagnifyingGlassIcon class="w-5 h-5 text-red-600 shrink-0" />
        <input 
          v-model="searchQuery" 
          type="text" 
          placeholder="Search deals, contacts, or companies..." 
          class="w-full bg-transparent border-none text-slate-900 text-sm font-medium focus:outline-none focus:ring-0 placeholder:text-slate-400"
          autofocus
        />
        <button @click="emit('close')" class="p-1 text-slate-400 hover:text-slate-600">
          <XMarkIcon class="w-5 h-5" />
        </button>
      </div>

      <!-- Results Body -->
      <div class="p-4 max-h-[60vh] overflow-y-auto space-y-4">
        <div v-if="isLoading" class="text-center py-6 text-xs text-slate-500 font-semibold">
          Searching records...
        </div>

        <div v-else-if="!searchQuery" class="text-center py-8 space-y-1">
          <p class="text-xs font-semibold text-slate-500">Type a query to search across your CRM workspace.</p>
          <p class="text-[11px] text-slate-400">Try searching for "Acme", "Consulting", or "John".</p>
        </div>

        <div v-else-if="results.deals.length === 0 && results.contacts.length === 0 && results.companies.length === 0" class="text-center py-6 text-xs text-slate-500">
          No matching records found.
        </div>

        <!-- Deals -->
        <div v-if="results.deals.length > 0" class="space-y-2">
          <div class="text-[10px] font-bold text-red-600 uppercase tracking-wider">Deals</div>
          <div 
            v-for="deal in results.deals" 
            :key="deal.id"
            @click="navigateTo('/deals')"
            class="p-2.5 bg-slate-50 hover:bg-red-50/50 rounded-lg cursor-pointer flex items-center justify-between border border-slate-200/80 transition-all"
          >
            <div class="flex items-center gap-2.5">
              <BanknotesIcon class="w-4 h-4 text-emerald-600" />
              <span class="text-xs font-bold text-slate-800">{{ deal.title }}</span>
            </div>
            <span class="text-xs font-bold text-emerald-700">{{ deal.formatted_value || formatINR(deal.value) }}</span>
          </div>
        </div>

        <!-- Contacts -->
        <div v-if="results.contacts.length > 0" class="space-y-2">
          <div class="text-[10px] font-bold text-red-600 uppercase tracking-wider">Contacts</div>
          <div 
            v-for="contact in results.contacts" 
            :key="contact.id"
            @click="navigateTo('/deals')"
            class="p-2.5 bg-slate-50 hover:bg-red-50/50 rounded-lg cursor-pointer flex items-center justify-between border border-slate-200/80 transition-all"
          >
            <div class="flex items-center gap-2.5">
              <UserIcon class="w-4 h-4 text-red-600" />
              <span class="text-xs font-bold text-slate-800">{{ contact.first_name }} {{ contact.last_name }}</span>
            </div>
            <span class="text-[11px] text-slate-500">{{ contact.email }}</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
