<script setup>
import { ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import { HeartIcon, CheckCircleIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
  apiKey: String,
});

const isSubmitted = ref(false);

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
});

const submit = () => {
  form.post('/embed/register', {
    onSuccess: () => {
      isSubmitted.value = true;
    },
  });
};
</script>

<template>
  <Head title="Submit Matrimonial Bio-data" />

  <div class="min-h-screen bg-slate-50 p-4 font-sans text-slate-900 flex items-center justify-center">
    <div class="bg-white border border-slate-200 rounded-3xl p-6 shadow-xl max-w-lg w-full space-y-4">
      <div class="flex items-center gap-2 border-b border-slate-100 pb-3">
        <div class="w-8 h-8 bg-red-600 rounded-xl flex items-center justify-center text-white font-black text-sm">
          ♥
        </div>
        <div>
          <h2 class="text-base font-extrabold text-slate-900">Community Bio-data Registration</h2>
          <p class="text-[11px] text-slate-500">Submit your matrimonial details directly to our portal</p>
        </div>
      </div>

      <div v-if="isSubmitted" class="p-6 text-center space-y-2 bg-emerald-50 rounded-2xl border border-emerald-200">
        <CheckCircleIcon class="w-12 h-12 text-emerald-600 mx-auto" />
        <h3 class="text-base font-extrabold text-emerald-900">Bio-data Submitted Successfully!</h3>
        <p class="text-xs text-emerald-700">Thank you! Your profile has been sent to our community matchmakers for verification review.</p>
      </div>

      <form v-else @submit.prevent="submit" class="space-y-3 text-xs">
        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="font-bold text-slate-700">First Name</label>
            <input v-model="form.first_name" type="text" required class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3 py-1.5 font-bold" />
          </div>
          <div>
            <label class="font-bold text-slate-700">Last Name</label>
            <input v-model="form.last_name" type="text" required class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3 py-1.5 font-bold" />
          </div>
        </div>

        <div class="grid grid-cols-3 gap-3">
          <div>
            <label class="font-bold text-slate-700">Gender</label>
            <select v-model="form.gender" class="w-full bg-slate-50 border border-slate-300 rounded-xl px-2 py-1.5 font-bold">
              <option value="Male">Male (Groom)</option>
              <option value="Female">Female (Bride)</option>
            </select>
          </div>
          <div>
            <label class="font-bold text-slate-700">Date of Birth</label>
            <input v-model="form.date_of_birth" type="date" required class="w-full bg-slate-50 border border-slate-300 rounded-xl px-2 py-1.5 font-bold" />
          </div>
          <div>
            <label class="font-bold text-slate-700">Height (cm)</label>
            <input v-model="form.height_cm" type="number" class="w-full bg-slate-50 border border-slate-300 rounded-xl px-2 py-1.5 font-bold" />
          </div>
        </div>

        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="font-bold text-slate-700">Caste / Community</label>
            <input v-model="form.caste" type="text" required class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3 py-1.5 font-bold" />
          </div>
          <div>
            <label class="font-bold text-slate-700">Gotra</label>
            <input v-model="form.gotra" type="text" class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3 py-1.5 font-bold" />
          </div>
        </div>

        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="font-bold text-slate-700">Phone</label>
            <input v-model="form.phone" type="text" required class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3 py-1.5 font-bold" />
          </div>
          <div>
            <label class="font-bold text-slate-700">Email</label>
            <input v-model="form.email" type="email" required class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3 py-1.5 font-bold" />
          </div>
        </div>

        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="font-bold text-slate-700">State</label>
            <input v-model="form.state" type="text" required class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3 py-1.5 font-bold" />
          </div>
          <div>
            <label class="font-bold text-slate-700">City</label>
            <input v-model="form.city" type="text" required class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3 py-1.5 font-bold" />
          </div>
        </div>

        <button type="submit" :disabled="form.processing" class="w-full py-2.5 bg-red-600 hover:bg-red-700 text-white font-extrabold text-xs rounded-xl shadow-md transition-all mt-2">
          Submit Bio-Data Profile
        </button>
      </form>
    </div>
  </div>
</template>
