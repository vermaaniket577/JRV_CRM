<script setup>
import { useForm } from '@inertiajs/vue3';
import { XMarkIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
  isOpen: Boolean,
  stages: {
    type: Array,
    default: () => [],
  },
});

const emit = defineEmits(['close']);

const form = useForm({
  title: '',
  value: 10000,
  stage_id: '',
});

const submit = () => {
  if (!form.stage_id && props.stages.length > 0) {
    form.stage_id = props.stages[0].id;
  }

  form.post('/deals', {
    onSuccess: () => {
      form.reset();
      emit('close');
    },
  });
};
</script>

<template>
  <div v-if="isOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-xs">
    <div class="bg-white border border-slate-200 rounded-2xl max-w-md w-full shadow-2xl flex flex-col overflow-hidden">
      <!-- Header -->
      <div class="p-4 border-b border-slate-200 flex items-center justify-between">
        <h3 class="text-base font-bold text-slate-900">Create New Opportunity</h3>
        <button @click="emit('close')" class="text-slate-400 hover:text-slate-600">
          <XMarkIcon class="w-5 h-5" />
        </button>
      </div>

      <!-- Form -->
      <form @submit.prevent="submit" class="p-6 space-y-4">
        <div class="space-y-1">
          <label class="text-xs font-bold text-slate-700 uppercase tracking-wider">Opportunity Title</label>
          <input 
            v-model="form.title" 
            type="text" 
            placeholder="e.g. Enterprise License Deal"
            required
            class="w-full bg-white border border-slate-300 rounded-lg px-3.5 py-2 text-sm text-slate-900 focus:outline-none focus:border-indigo-500"
          />
        </div>

        <div class="space-y-1">
          <label class="text-xs font-bold text-slate-700 uppercase tracking-wider">Deal Value ($)</label>
          <input 
            v-model="form.value" 
            type="number" 
            min="0"
            step="100"
            required
            class="w-full bg-white border border-slate-300 rounded-lg px-3.5 py-2 text-sm text-slate-900 focus:outline-none focus:border-indigo-500"
          />
        </div>

        <div class="space-y-1">
          <label class="text-xs font-bold text-slate-700 uppercase tracking-wider">Pipeline Stage</label>
          <select 
            v-model="form.stage_id"
            required
            class="w-full bg-white border border-slate-300 rounded-lg px-3.5 py-2 text-sm text-slate-900 focus:outline-none focus:border-indigo-500"
          >
            <option value="" disabled>Select initial stage...</option>
            <option v-for="stage in stages" :key="stage.id" :value="stage.id">
              {{ stage.name }}
            </option>
          </select>
        </div>

        <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-3">
          <button type="button" @click="emit('close')" class="px-4 py-2 text-xs font-semibold text-slate-500 hover:text-slate-700">
            Cancel
          </button>
          <button 
            type="submit"
            :disabled="form.processing"
            class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold rounded-lg shadow-md shadow-indigo-600/20"
          >
            Create Opportunity
          </button>
        </div>
      </form>
    </div>
  </div>
</template>
