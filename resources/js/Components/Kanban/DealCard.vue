<script setup>
import { BuildingOfficeIcon, UserIcon, CalendarIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
  deal: {
    type: Object,
    required: true,
  },
});

const formatINR = (val) => {
  return '₹' + Number(val || 0).toLocaleString('en-IN', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  });
};
</script>

<template>
  <div class="p-4 bg-white border border-slate-200 hover:border-indigo-400 rounded-xl shadow-xs hover:shadow-md transition-all space-y-3 cursor-grab active:cursor-grabbing group">
    <div class="flex items-start justify-between gap-2">
      <h4 class="text-sm font-bold text-slate-800 group-hover:text-indigo-600 transition-colors line-clamp-2">
        {{ deal.title }}
      </h4>
      <span class="text-xs font-black text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-full border border-emerald-200 whitespace-nowrap">
        {{ deal.formatted_value || formatINR(deal.value) }}
      </span>
    </div>

    <div class="space-y-1.5 text-xs text-slate-600">
      <div v-if="deal.company" class="flex items-center gap-1.5">
        <BuildingOfficeIcon class="w-3.5 h-3.5 text-slate-400" />
        <span class="font-medium">{{ deal.company.name }}</span>
      </div>

      <div v-if="deal.contact" class="flex items-center gap-1.5">
        <UserIcon class="w-3.5 h-3.5 text-slate-400" />
        <span>{{ deal.contact.name }}</span>
      </div>
    </div>

    <div class="pt-2 border-t border-slate-100 flex items-center justify-between text-[11px] text-slate-500">
      <div class="flex items-center gap-1">
        <CalendarIcon class="w-3 h-3 text-slate-400" />
        <span>{{ deal.expected_close_date || 'No date set' }}</span>
      </div>

      <div v-if="deal.assignee" class="flex items-center gap-1 font-medium text-slate-600">
        <div class="w-4 h-4 bg-indigo-600 rounded-full flex items-center justify-center text-[9px] text-white uppercase font-bold">
          {{ deal.assignee.name.charAt(0) }}
        </div>
      </div>
    </div>
  </div>
</template>
