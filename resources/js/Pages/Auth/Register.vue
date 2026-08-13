<script setup>
import { ref } from 'vue';
import { useForm, Head, Link } from '@inertiajs/vue3';
import { 
  SparklesIcon, 
  ArrowRightIcon, 
  LockClosedIcon, 
  EnvelopeIcon, 
  BuildingOfficeIcon, 
  UserIcon, 
  EyeIcon, 
  EyeSlashIcon,
  ShieldCheckIcon,
  CheckCircleIcon
} from '@heroicons/vue/24/outline';

const showPassword = ref(false);
const showConfirmPassword = ref(false);

const form = useForm({
  name: '',
  email: '',
  organization_name: '',
  password: '',
  password_confirmation: '',
});

const submit = () => {
  form.post('/register', {
    onFinish: () => form.reset('password', 'password_confirmation'),
  });
};
</script>

<template>
  <Head title="Create Your Organization - JRV CRM" />

  <div class="min-h-screen bg-slate-100 text-slate-900 font-sans flex items-center justify-center p-4 sm:p-6 relative overflow-hidden">
    <!-- Ambient Soft Glow Background Effects -->
    <div class="absolute top-1/4 left-1/2 -translate-x-1/2 w-[600px] h-[600px] bg-red-500/10 rounded-full blur-3xl pointer-events-none"></div>
    <div class="absolute bottom-10 right-10 w-[350px] h-[350px] bg-red-400/10 rounded-full blur-3xl pointer-events-none"></div>

    <div class="max-w-md w-full relative z-10 space-y-6">
      
      <!-- Logo & Header Badge -->
      <div class="text-center space-y-3">
        <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-red-50 border border-red-200 text-xs font-bold text-red-700 shadow-2xs">
          <SparklesIcon class="w-4 h-4 text-red-600" />
          <span>Multi-Sector SaaS Engine</span>
        </div>

        <div class="flex flex-col items-center justify-center gap-1.5 pt-1">
          <div class="w-12 h-12 bg-red-600 text-white font-black text-xl rounded-2xl flex items-center justify-center shadow-md shadow-red-600/30">
            ⚡
          </div>
          <h1 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight">Create Your Organization</h1>
          <p class="text-slate-500 text-xs sm:text-sm font-medium">Start your 14-day free trial — automatically pre-configured for your business sector.</p>
        </div>
      </div>

      <!-- Main Registration Card -->
      <div class="bg-white border border-slate-200 rounded-3xl p-6 sm:p-8 shadow-xl shadow-slate-200/60 space-y-6">
        <form @submit.prevent="submit" class="space-y-4">
          
          <!-- Full Name -->
          <div class="space-y-1">
            <label class="block text-[11px] font-black uppercase text-slate-700 tracking-wider">Your Full Name</label>
            <div class="relative">
              <UserIcon class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2 shrink-0" />
              <input
                v-model="form.name"
                type="text"
                required
                placeholder="Sarah Connor"
                class="w-full bg-slate-50/80 border border-slate-300 rounded-xl pl-10 pr-4 py-2.5 text-xs font-bold text-slate-900 placeholder-slate-400 focus:outline-none focus:border-red-500 focus:bg-white transition-all shadow-2xs"
              />
            </div>
            <p v-if="form.errors.name" class="text-xs font-bold text-red-600 mt-1">{{ form.errors.name }}</p>
          </div>

          <!-- Organization Name -->
          <div class="space-y-1">
            <label class="block text-[11px] font-black uppercase text-slate-700 tracking-wider">Business / Company Name</label>
            <div class="relative">
              <BuildingOfficeIcon class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2 shrink-0" />
              <input
                v-model="form.organization_name"
                type="text"
                required
                placeholder="Acme Global Solutions"
                class="w-full bg-slate-50/80 border border-slate-300 rounded-xl pl-10 pr-4 py-2.5 text-xs font-bold text-slate-900 placeholder-slate-400 focus:outline-none focus:border-red-500 focus:bg-white transition-all shadow-2xs"
              />
            </div>
            <p v-if="form.errors.organization_name" class="text-xs font-bold text-red-600 mt-1">{{ form.errors.organization_name }}</p>
          </div>

          <!-- Email -->
          <div class="space-y-1">
            <label class="block text-[11px] font-black uppercase text-slate-700 tracking-wider">Work Email Address</label>
            <div class="relative">
              <EnvelopeIcon class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2 shrink-0" />
              <input
                v-model="form.email"
                type="email"
                required
                placeholder="sarah@acme.com"
                class="w-full bg-slate-50/80 border border-slate-300 rounded-xl pl-10 pr-4 py-2.5 text-xs font-bold text-slate-900 placeholder-slate-400 focus:outline-none focus:border-red-500 focus:bg-white transition-all shadow-2xs"
              />
            </div>
            <p v-if="form.errors.email" class="text-xs font-bold text-red-600 mt-1">{{ form.errors.email }}</p>
          </div>

          <!-- Password -->
          <div class="space-y-1">
            <label class="block text-[11px] font-black uppercase text-slate-700 tracking-wider">Password</label>
            <div class="relative">
              <LockClosedIcon class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2 shrink-0" />
              <input
                v-model="form.password"
                :type="showPassword ? 'text' : 'password'"
                required
                placeholder="At least 8 characters"
                class="w-full bg-slate-50/80 border border-slate-300 rounded-xl pl-10 pr-10 py-2.5 text-xs font-bold text-slate-900 placeholder-slate-400 focus:outline-none focus:border-red-500 focus:bg-white transition-all shadow-2xs"
              />
              <button
                type="button"
                @click="showPassword = !showPassword"
                class="absolute right-3.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 p-1 cursor-pointer transition-colors"
                title="Toggle password view"
              >
                <EyeIcon v-if="!showPassword" class="w-4 h-4" />
                <EyeSlashIcon v-else class="w-4 h-4 text-red-600" />
              </button>
            </div>
            <p v-if="form.errors.password" class="text-xs font-bold text-red-600 mt-1">{{ form.errors.password }}</p>
          </div>

          <!-- Confirm Password -->
          <div class="space-y-1">
            <label class="block text-[11px] font-black uppercase text-slate-700 tracking-wider">Confirm Password</label>
            <div class="relative">
              <LockClosedIcon class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2 shrink-0" />
              <input
                v-model="form.password_confirmation"
                :type="showConfirmPassword ? 'text' : 'password'"
                required
                placeholder="Confirm password"
                class="w-full bg-slate-50/80 border border-slate-300 rounded-xl pl-10 pr-10 py-2.5 text-xs font-bold text-slate-900 placeholder-slate-400 focus:outline-none focus:border-red-500 focus:bg-white transition-all shadow-2xs"
              />
              <button
                type="button"
                @click="showConfirmPassword = !showConfirmPassword"
                class="absolute right-3.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 p-1 cursor-pointer transition-colors"
                title="Toggle password view"
              >
                <EyeIcon v-if="!showConfirmPassword" class="w-4 h-4" />
                <EyeSlashIcon v-else class="w-4 h-4 text-red-600" />
              </button>
            </div>
          </div>

          <!-- Submit Button -->
          <button
            type="submit"
            :disabled="form.processing"
            class="w-full py-3 mt-3 bg-red-600 hover:bg-red-700 text-white font-extrabold text-xs rounded-xl shadow-md shadow-red-600/25 flex items-center justify-center gap-2 transition-all cursor-pointer disabled:opacity-50"
          >
            <span>Create Organization & Setup</span>
            <ArrowRightIcon class="w-4 h-4 stroke-[2.5]" />
          </button>
        </form>

        <!-- Footer Link -->
        <div class="pt-3 border-t border-slate-100 text-center text-xs text-slate-500 font-medium flex items-center justify-center gap-1">
          <span>Already registered?</span>
          <Link href="/login" class="font-extrabold text-red-600 hover:text-red-700 hover:underline">
            Sign in here
          </Link>
        </div>
      </div>
    </div>
  </div>
</template>
