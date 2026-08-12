<script setup>
import { useForm } from '@inertiajs/vue3';
import { XMarkIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
  isOpen: Boolean,
  staffList: {
    type: Array,
    default: () => [],
  },
});

const emit = defineEmits(['close']);

const form = useForm({
  title: '',
  description: '',
  assigned_to: '',
  priority: 'medium',
  status: 'pending',
  due_at: '',
});

const submit = () => {
  form.post('/tasks', {
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
        <h3 class="text-base font-bold text-slate-900">Create New Task</h3>
        <button @click="emit('close')" class="text-slate-400 hover:text-slate-600">
          <XMarkIcon class="w-5 h-5" />
        </button>
      </div>

      <!-- Form -->
      <form @submit.prevent="submit" class="p-6 space-y-4">
        <div class="space-y-1">
          <label class="text-xs font-bold text-slate-700 uppercase tracking-wider">Task Title</label>
          <input 
            v-model="form.title" 
            type="text" 
            placeholder="e.g. Verify client document signature"
            required
            class="w-full bg-white border border-slate-300 rounded-xl px-3.5 py-2 text-sm text-slate-900 focus:outline-none focus:border-red-500"
          />
        </div>

        <div class="space-y-1">
          <label class="text-xs font-bold text-slate-700 uppercase tracking-wider">Description</label>
          <textarea 
            v-model="form.description" 
            rows="3"
            placeholder="Task details and action instructions..."
            class="w-full bg-white border border-slate-300 rounded-xl px-3.5 py-2 text-sm text-slate-900 focus:outline-none focus:border-red-500"
          ></textarea>
        </div>

        <div class="grid grid-cols-2 gap-3">
          <div class="space-y-1">
            <label class="text-xs font-bold text-slate-700 uppercase tracking-wider">Assigned Staff</label>
            <select 
              v-model="form.assigned_to"
              class="w-full bg-white border border-slate-300 rounded-xl px-3.5 py-2 text-xs font-semibold text-slate-900 focus:outline-none focus:border-red-500"
            >
              <option value="">Select Staff...</option>
              <option v-for="staff in staffList" :key="staff.id" :value="staff.id">
                {{ staff.name }}
              </option>
            </select>
          </div>

          <div class="space-y-1">
            <label class="text-xs font-bold text-slate-700 uppercase tracking-wider">Priority</label>
            <select 
              v-model="form.priority"
              class="w-full bg-white border border-slate-300 rounded-xl px-3.5 py-2 text-xs font-semibold text-slate-900 focus:outline-none focus:border-red-500"
            >
              <option value="low">Low</option>
              <option value="medium">Medium</option>
              <option value="high">High</option>
              <option value="urgent">Urgent</option>
            </select>
          </div>
        </div>

        <div class="space-y-1">
          <label class="text-xs font-bold text-slate-700 uppercase tracking-wider">Due Date</label>
          <input 
            v-model="form.due_at" 
            type="date"
            class="w-full bg-white border border-slate-300 rounded-xl px-3.5 py-2 text-xs font-semibold text-slate-900 focus:outline-none focus:border-red-500"
          />
        </div>

        <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-3">
          <button type="button" @click="emit('close')" class="px-4 py-2 text-xs font-semibold text-slate-500 hover:text-slate-700">
            Cancel
          </button>
          <button 
            type="submit"
            :disabled="form.processing"
            class="px-5 py-2.5 bg-red-600 hover:bg-red-700 text-white text-xs font-bold rounded-xl shadow-md"
          >
            Create Task
          </button>
        </div>
      </form>
    </div>
  </div>
</template>
