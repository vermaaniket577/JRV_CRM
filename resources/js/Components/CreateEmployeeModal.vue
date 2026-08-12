<script setup>
import { useForm } from '@inertiajs/vue3';
import { XMarkIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
  isOpen: Boolean,
});

const emit = defineEmits(['close']);

const form = useForm({
  name: '',
  email: '',
  department: 'Sales',
  designation: 'Staff Representative',
  salary: 45000,
  attendance_status: 'Present',
  gender: 'Male',
  phone: '',
  state: 'California',
  city: 'San Francisco',
});

const submit = () => {
  form.post('/employee-management', {
    onSuccess: () => {
      form.reset();
      emit('close');
    },
  });
};
</script>

<template>
  <div v-if="isOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-xs">
    <div class="bg-white border border-slate-200 rounded-3xl max-w-lg w-full shadow-2xl flex flex-col overflow-hidden">
      <!-- Header -->
      <div class="p-5 border-b border-slate-200 flex items-center justify-between">
        <h3 class="text-lg font-bold text-slate-900">Add New Employee</h3>
        <button @click="emit('close')" class="text-slate-400 hover:text-slate-600">
          <XMarkIcon class="w-5 h-5" />
        </button>
      </div>

      <!-- Form -->
      <form @submit.prevent="submit" class="p-6 space-y-4">
        <div class="grid grid-cols-2 gap-4">
          <div class="space-y-1">
            <label class="text-xs font-bold text-slate-700 uppercase tracking-wider">Full Name</label>
            <input 
              v-model="form.name" 
              type="text" 
              placeholder="e.g. John Smith"
              required
              class="w-full bg-white border border-slate-300 rounded-xl px-3.5 py-2 text-xs font-bold text-slate-900 focus:outline-none focus:border-red-500"
            />
          </div>

          <div class="space-y-1">
            <label class="text-xs font-bold text-slate-700 uppercase tracking-wider">Email Address</label>
            <input 
              v-model="form.email" 
              type="email" 
              placeholder="john@company.com"
              required
              class="w-full bg-white border border-slate-300 rounded-xl px-3.5 py-2 text-xs font-bold text-slate-900 focus:outline-none focus:border-red-500"
            />
          </div>
        </div>

        <div class="grid grid-cols-2 gap-4">
          <div class="space-y-1">
            <label class="text-xs font-bold text-slate-700 uppercase tracking-wider">Department</label>
            <select 
              v-model="form.department"
              class="w-full bg-white border border-slate-300 rounded-xl px-3.5 py-2 text-xs font-semibold text-slate-900 focus:outline-none focus:border-red-500"
            >
              <option value="Sales">Sales</option>
              <option value="Engineering">Engineering</option>
              <option value="Support">Support</option>
              <option value="Finance">Finance</option>
              <option value="HR">Human Resources</option>
            </select>
          </div>

          <div class="space-y-1">
            <label class="text-xs font-bold text-slate-700 uppercase tracking-wider">Designation / Role</label>
            <input 
              v-model="form.designation" 
              type="text" 
              placeholder="e.g. Senior Lead"
              required
              class="w-full bg-white border border-slate-300 rounded-xl px-3.5 py-2 text-xs font-bold text-slate-900 focus:outline-none focus:border-red-500"
            />
          </div>
        </div>

        <div class="grid grid-cols-3 gap-3">
          <div class="space-y-1">
            <label class="text-xs font-bold text-slate-700 uppercase tracking-wider">Salary (₹)</label>
            <input 
              v-model="form.salary" 
              type="number" 
              required
              class="w-full bg-white border border-slate-300 rounded-xl px-3 py-2 text-xs font-bold text-slate-900 focus:outline-none focus:border-red-500"
            />
          </div>

          <div class="space-y-1">
            <label class="text-xs font-bold text-slate-700 uppercase tracking-wider">Attendance</label>
            <select 
              v-model="form.attendance_status"
              class="w-full bg-white border border-slate-300 rounded-xl px-2.5 py-2 text-xs font-semibold text-slate-900 focus:outline-none focus:border-red-500"
            >
              <option value="Present">Present</option>
              <option value="Absent">Absent</option>
              <option value="On Leave">On Leave</option>
              <option value="Half Day">Half Day</option>
            </select>
          </div>

          <div class="space-y-1">
            <label class="text-xs font-bold text-slate-700 uppercase tracking-wider">Gender</label>
            <select 
              v-model="form.gender"
              class="w-full bg-white border border-slate-300 rounded-xl px-2.5 py-2 text-xs font-semibold text-slate-900 focus:outline-none focus:border-red-500"
            >
              <option value="Male">Male</option>
              <option value="Female">Female</option>
              <option value="Other">Other</option>
            </select>
          </div>
        </div>

        <div class="grid grid-cols-2 gap-4">
          <div class="space-y-1">
            <label class="text-xs font-bold text-slate-700 uppercase tracking-wider">State</label>
            <input 
              v-model="form.state" 
              type="text" 
              class="w-full bg-white border border-slate-300 rounded-xl px-3.5 py-2 text-xs font-bold text-slate-900 focus:outline-none focus:border-red-500"
            />
          </div>

          <div class="space-y-1">
            <label class="text-xs font-bold text-slate-700 uppercase tracking-wider">City</label>
            <input 
              v-model="form.city" 
              type="text" 
              class="w-full bg-white border border-slate-300 rounded-xl px-3.5 py-2 text-xs font-bold text-slate-900 focus:outline-none focus:border-red-500"
            />
          </div>
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
            Add Employee
          </button>
        </div>
      </form>
    </div>
  </div>
</template>
