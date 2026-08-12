<script setup>
import { useForm } from '@inertiajs/vue3';
import { XMarkIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
  isOpen: Boolean,
  counselors: {
    type: Array,
    default: () => [],
  },
});

const emit = defineEmits(['close']);

const form = useForm({
  first_name: '',
  last_name: '',
  gender: 'Female',
  date_of_birth: '1998-05-15',
  height_cm: 165,
  marital_status: 'Never Married',
  religion: 'Jain',
  caste: 'Jain Digambar',
  sub_caste: 'Agarwal',
  gotra: 'Kashyap',
  mother_gotra: 'Vashishtha',
  education_level: 'B.Tech CS',
  occupation_type: 'Software Engineer',
  annual_income: 1200000,
  phone: '+91 9876543210',
  email: '',
  state: 'California',
  city: 'San Francisco',
  assigned_matchmaker_id: '',
  plan_tier: 'Platinum',
});

const submit = () => {
  form.post('/matrimonial/members', {
    onSuccess: () => {
      form.reset();
      emit('close');
    },
  });
};
</script>

<template>
  <div v-if="isOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-xs">
    <div class="bg-white border border-slate-200 rounded-3xl max-w-2xl w-full shadow-2xl flex flex-col overflow-hidden max-h-[90vh]">
      <!-- Header -->
      <div class="p-5 border-b border-slate-200 flex items-center justify-between">
        <h3 class="text-lg font-bold text-slate-900">Add Bio-Data Profile</h3>
        <button @click="emit('close')" class="text-slate-400 hover:text-slate-600">
          <XMarkIcon class="w-5 h-5" />
        </button>
      </div>

      <!-- Form -->
      <form @submit.prevent="submit" class="p-6 space-y-4 overflow-y-auto">
        <div class="grid grid-cols-2 gap-4">
          <div class="space-y-1">
            <label class="text-xs font-bold text-slate-700 uppercase tracking-wider">First Name</label>
            <input v-model="form.first_name" type="text" placeholder="First Name" required class="w-full bg-white border border-slate-300 rounded-xl px-3.5 py-2 text-xs font-bold text-slate-900" />
          </div>
          <div class="space-y-1">
            <label class="text-xs font-bold text-slate-700 uppercase tracking-wider">Last Name</label>
            <input v-model="form.last_name" type="text" placeholder="Last Name" required class="w-full bg-white border border-slate-300 rounded-xl px-3.5 py-2 text-xs font-bold text-slate-900" />
          </div>
        </div>

        <div class="grid grid-cols-3 gap-3">
          <div class="space-y-1">
            <label class="text-xs font-bold text-slate-700 uppercase tracking-wider">Gender</label>
            <select v-model="form.gender" class="w-full bg-white border border-slate-300 rounded-xl px-3 py-2 text-xs font-semibold text-slate-900">
              <option value="Male">Male (Groom)</option>
              <option value="Female">Female (Bride)</option>
            </select>
          </div>
          <div class="space-y-1">
            <label class="text-xs font-bold text-slate-700 uppercase tracking-wider">Date of Birth</label>
            <input v-model="form.date_of_birth" type="date" required class="w-full bg-white border border-slate-300 rounded-xl px-3 py-2 text-xs font-bold text-slate-900" />
          </div>
          <div class="space-y-1">
            <label class="text-xs font-bold text-slate-700 uppercase tracking-wider">Height (cm)</label>
            <input v-model="form.height_cm" type="number" placeholder="165" class="w-full bg-white border border-slate-300 rounded-xl px-3 py-2 text-xs font-bold text-slate-900" />
          </div>
        </div>

        <div class="grid grid-cols-3 gap-3">
          <div class="space-y-1">
            <label class="text-xs font-bold text-slate-700 uppercase tracking-wider">Caste / Community</label>
            <input v-model="form.caste" type="text" required class="w-full bg-white border border-slate-300 rounded-xl px-3 py-2 text-xs font-bold text-slate-900" />
          </div>
          <div class="space-y-1">
            <label class="text-xs font-bold text-slate-700 uppercase tracking-wider">Gotra</label>
            <input v-model="form.gotra" type="text" placeholder="Self Gotra" class="w-full bg-white border border-slate-300 rounded-xl px-3 py-2 text-xs font-bold text-slate-900" />
          </div>
          <div class="space-y-1">
            <label class="text-xs font-bold text-slate-700 uppercase tracking-wider">Mother's Gotra</label>
            <input v-model="form.mother_gotra" type="text" placeholder="Mother Gotra" class="w-full bg-white border border-slate-300 rounded-xl px-3 py-2 text-xs font-bold text-slate-900" />
          </div>
        </div>

        <div class="grid grid-cols-3 gap-3">
          <div class="space-y-1">
            <label class="text-xs font-bold text-slate-700 uppercase tracking-wider">Education</label>
            <input v-model="form.education_level" type="text" placeholder="e.g. B.Tech CS" class="w-full bg-white border border-slate-300 rounded-xl px-3 py-2 text-xs font-bold text-slate-900" />
          </div>
          <div class="space-y-1">
            <label class="text-xs font-bold text-slate-700 uppercase tracking-wider">Occupation</label>
            <input v-model="form.occupation_type" type="text" placeholder="e.g. Engineer" class="w-full bg-white border border-slate-300 rounded-xl px-3 py-2 text-xs font-bold text-slate-900" />
          </div>
          <div class="space-y-1">
            <label class="text-xs font-bold text-slate-700 uppercase tracking-wider">Annual Income (₹)</label>
            <input v-model="form.annual_income" type="number" placeholder="1200000" class="w-full bg-white border border-slate-300 rounded-xl px-3 py-2 text-xs font-bold text-slate-900" />
          </div>
        </div>

        <div class="grid grid-cols-2 gap-4">
          <div class="space-y-1">
            <label class="text-xs font-bold text-slate-700 uppercase tracking-wider">Phone</label>
            <input v-model="form.phone" type="text" required class="w-full bg-white border border-slate-300 rounded-xl px-3.5 py-2 text-xs font-bold text-slate-900" />
          </div>
          <div class="space-y-1">
            <label class="text-xs font-bold text-slate-700 uppercase tracking-wider">Email</label>
            <input v-model="form.email" type="email" required class="w-full bg-white border border-slate-300 rounded-xl px-3.5 py-2 text-xs font-bold text-slate-900" />
          </div>
        </div>

        <div class="grid grid-cols-2 gap-4">
          <div class="space-y-1">
            <label class="text-xs font-bold text-slate-700 uppercase tracking-wider">State</label>
            <input v-model="form.state" type="text" required class="w-full bg-white border border-slate-300 rounded-xl px-3.5 py-2 text-xs font-bold text-slate-900" />
          </div>
          <div class="space-y-1">
            <label class="text-xs font-bold text-slate-700 uppercase tracking-wider">City</label>
            <input v-model="form.city" type="text" required class="w-full bg-white border border-slate-300 rounded-xl px-3.5 py-2 text-xs font-bold text-slate-900" />
          </div>
        </div>

        <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-3">
          <button type="button" @click="emit('close')" class="px-4 py-2 text-xs font-semibold text-slate-500 hover:text-slate-700">Cancel</button>
          <button type="submit" :disabled="form.processing" class="px-5 py-2.5 bg-red-600 hover:bg-red-700 text-white text-xs font-bold rounded-xl shadow-md">Add Profile</button>
        </div>
      </form>
    </div>
  </div>
</template>
