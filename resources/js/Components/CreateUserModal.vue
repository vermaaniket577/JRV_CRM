<script setup>
import { useForm } from '@inertiajs/vue3';
import { XMarkIcon, UserPlusIcon, KeyIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
  isOpen: Boolean,
});

const emit = defineEmits(['close']);

const form = useForm({
  name: '',
  email: '',
  phone: '',
  role: 'Staff',
  status: 'active',
  password: 'password123',
});

const generateRandomPassword = () => {
  const chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%';
  let pass = '';
  for (let i = 0; i < 10; i++) {
    pass += chars.charAt(Math.floor(Math.random() * chars.length));
  }
  form.password = pass;
};

const closeModal = () => {
  form.reset();
  form.clearErrors();
  emit('close');
};

const submit = () => {
  form.post('/online-users', {
    onSuccess: () => {
      closeModal();
    },
  });
};
</script>

<template>
  <div v-if="isOpen" class="fixed inset-0 z-50 overflow-y-auto font-sans">
    <!-- Backdrop -->
    <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" @click="closeModal"></div>

    <!-- Modal Box -->
    <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
      <div class="relative transform overflow-hidden rounded-3xl bg-white text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-lg border border-slate-100">
        <!-- Header -->
        <div class="bg-gradient-to-r from-red-600 to-indigo-600 px-6 py-5 flex items-center justify-between text-white">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-white/20 backdrop-blur-md flex items-center justify-center text-white font-bold">
              <UserPlusIcon class="w-6 h-6 stroke-[2.5]" />
            </div>
            <div>
              <h3 class="text-lg font-extrabold tracking-tight">Create New User</h3>
              <p class="text-xs text-white/80 font-medium">Add a new user to your CRM organization</p>
            </div>
          </div>
          <button @click="closeModal" class="rounded-xl p-1.5 text-white/80 hover:bg-white/20 transition-colors">
            <XMarkIcon class="w-5 h-5 stroke-[2.5]" />
          </button>
        </div>

        <!-- Form Body -->
        <form @submit.prevent="submit" class="p-6 space-y-4">
          <!-- Full Name -->
          <div class="space-y-1">
            <label class="block text-xs font-extrabold uppercase text-slate-700 tracking-wider">Full Name *</label>
            <input 
              v-model="form.name" 
              type="text" 
              required 
              placeholder="e.g. Alex Mercer"
              class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm text-slate-900 font-medium focus:outline-none focus:border-red-500 focus:bg-white transition-all"
            />
            <p v-if="form.errors.name" class="text-xs font-bold text-rose-600 mt-1">{{ form.errors.name }}</p>
          </div>

          <!-- Email & Phone Grid -->
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="space-y-1">
              <label class="block text-xs font-extrabold uppercase text-slate-700 tracking-wider">Email Address *</label>
              <input 
                v-model="form.email" 
                type="email" 
                required 
                placeholder="alex@company.com"
                class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm text-slate-900 font-medium focus:outline-none focus:border-red-500 focus:bg-white transition-all"
              />
              <p v-if="form.errors.email" class="text-xs font-bold text-rose-600 mt-1">{{ form.errors.email }}</p>
            </div>

            <div class="space-y-1">
              <label class="block text-xs font-extrabold uppercase text-slate-700 tracking-wider">Phone Number</label>
              <input 
                v-model="form.phone" 
                type="text" 
                placeholder="+1 (555) 000-0000"
                class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm text-slate-900 font-medium focus:outline-none focus:border-red-500 focus:bg-white transition-all"
              />
            </div>
          </div>

          <!-- Role & Status Grid -->
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="space-y-1">
              <label class="block text-xs font-extrabold uppercase text-slate-700 tracking-wider">User Role</label>
              <select 
                v-model="form.role"
                class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm text-slate-900 font-medium focus:outline-none focus:border-red-500 focus:bg-white transition-all"
              >
                <option value="Staff">Staff</option>
                <option value="Manager">Manager</option>
                <option value="Admin">Tenant Admin</option>
                <option value="Counselor">Counselor</option>
                <option value="Member">Member</option>
              </select>
            </div>

            <div class="space-y-1">
              <label class="block text-xs font-extrabold uppercase text-slate-700 tracking-wider">Account Status</label>
              <select 
                v-model="form.status"
                class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm text-slate-900 font-medium focus:outline-none focus:border-red-500 focus:bg-white transition-all"
              >
                <option value="active">Active</option>
                <option value="pending">Pending</option>
                <option value="suspended">Suspended</option>
              </select>
            </div>
          </div>

          <!-- Password Field -->
          <div class="space-y-1">
            <div class="flex items-center justify-between">
              <label class="block text-xs font-extrabold uppercase text-slate-700 tracking-wider">Password *</label>
              <button 
                type="button" 
                @click="generateRandomPassword" 
                class="text-xs font-bold text-red-600 hover:text-red-700 flex items-center gap-1"
              >
                <KeyIcon class="w-3.5 h-3.5" />
                <span>Auto Generate</span>
              </button>
            </div>
            <input 
              v-model="form.password" 
              type="text" 
              required 
              class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm text-slate-900 font-mono font-bold focus:outline-none focus:border-red-500 focus:bg-white transition-all"
            />
            <p v-if="form.errors.password" class="text-xs font-bold text-rose-600 mt-1">{{ form.errors.password }}</p>
          </div>

          <!-- Footer Action Buttons -->
          <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
            <button 
              type="button" 
              @click="closeModal" 
              class="px-5 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs rounded-xl transition-all"
            >
              Cancel
            </button>

            <button 
              type="submit" 
              :disabled="form.processing"
              class="px-6 py-2.5 bg-red-600 hover:bg-red-700 text-white font-extrabold text-xs rounded-xl shadow-md shadow-red-600/25 transition-all flex items-center gap-2 disabled:opacity-50"
            >
              <UserPlusIcon class="w-4 h-4 stroke-[2.5]" />
              <span>Create User</span>
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>
