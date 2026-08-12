<script setup>
import { ref } from 'vue';
import { useForm, Head, Link } from '@inertiajs/vue3';
import { SparklesIcon, ArrowRightIcon, LockClosedIcon, EnvelopeIcon, BuildingOfficeIcon, UserIcon, EyeIcon, EyeSlashIcon } from '@heroicons/vue/24/outline';

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

  <div class="min-h-screen bg-slate-100 text-slate-900 font-sans flex items-center justify-center p-6 relative overflow-hidden">
    <!-- Ambient Soft Glow Background -->
    <div class="absolute top-1/3 right-1/3 w-[500px] h-[500px] bg-red-500/10 rounded-full blur-3xl pointer-events-none"></div>
    <div class="absolute bottom-1/3 left-1/4 w-[400px] h-[400px] bg-red-400/10 rounded-full blur-3xl pointer-events-none"></div>

    <div class="max-w-md w-full relative z-10 space-y-8">
      <!-- Title & Badge -->
      <div class="text-center space-y-3">
        <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-red-50 border border-red-200 text-xs font-bold text-red-700">
          <SparklesIcon class="w-4 h-4 text-red-600" />
          <span>Multi-Sector SaaS Engine</span>
        </div>

        <div class="flex flex-col items-center justify-center gap-2 pt-2">
          <div class="w-14 h-14 bg-red-600 rounded-2xl flex items-center justify-center text-white font-black text-2xl shadow-lg shadow-red-600/30">
            ⚡
          </div>
          <h1 class="text-3xl font-black tracking-tight text-slate-900">Create Your Organization</h1>
          <p class="text-slate-500 text-sm font-medium">Start your 14-day trial — automatically configured for your business</p>
        </div>
      </div>

      <!-- Registration Form (White & Red Theme) -->
      <div class="bg-white border border-slate-200 rounded-3xl p-8 shadow-xl shadow-slate-200/50 space-y-6">
        <form @submit.prevent="submit" class="space-y-4">
          <!-- Full Name -->
          <div class="space-y-1">
            <label class="block text-xs font-extrabold text-slate-700 uppercase tracking-wider">Your Full Name</label>
            <div class="relative">
              <UserIcon class="w-5 h-5 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2" />
              <input
                v-model="form.name"
                type="text"
                required
                placeholder="Sarah Connor"
                class="w-full bg-slate-50 border border-slate-300 rounded-xl pl-11 pr-4 py-2.5 text-sm text-slate-900 placeholder-slate-400 font-bold focus:outline-none focus:border-red-500 focus:bg-white transition-colors"
              />
            </div>
            <p v-if="form.errors.name" class="text-xs font-bold text-red-600 mt-1">{{ form.errors.name }}</p>
          </div>

          <!-- Organization Name -->
          <div class="space-y-1">
            <label class="block text-xs font-extrabold text-slate-700 uppercase tracking-wider">Business / Company Name</label>
            <div class="relative">
              <BuildingOfficeIcon class="w-5 h-5 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2" />
              <input
                v-model="form.organization_name"
                type="text"
                required
                placeholder="Acme Global Solutions"
                class="w-full bg-slate-50 border border-slate-300 rounded-xl pl-11 pr-4 py-2.5 text-sm text-slate-900 placeholder-slate-400 font-bold focus:outline-none focus:border-red-500 focus:bg-white transition-colors"
              />
            </div>
            <p v-if="form.errors.organization_name" class="text-xs font-bold text-red-600 mt-1">{{ form.errors.organization_name }}</p>
          </div>

          <!-- Email -->
          <div class="space-y-1">
            <label class="block text-xs font-extrabold text-slate-700 uppercase tracking-wider">Work Email Address</label>
            <div class="relative">
              <EnvelopeIcon class="w-5 h-5 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2" />
              <input
                v-model="form.email"
                type="email"
                required
                placeholder="sarah@acme.com"
                class="w-full bg-slate-50 border border-slate-300 rounded-xl pl-11 pr-4 py-2.5 text-sm text-slate-900 placeholder-slate-400 font-bold focus:outline-none focus:border-red-500 focus:bg-white transition-colors"
              />
            </div>
            <p v-if="form.errors.email" class="text-xs font-bold text-red-600 mt-1">{{ form.errors.email }}</p>
          </div>

          <!-- Password -->
          <div class="space-y-1">
            <label class="block text-xs font-extrabold text-slate-700 uppercase tracking-wider">Password</label>
            <div class="relative">
              <LockClosedIcon class="w-5 h-5 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2" />
              <input
                v-model="form.password"
                :type="showPassword ? 'text' : 'password'"
                required
                placeholder="At least 8 characters"
                class="w-full bg-slate-50 border border-slate-300 rounded-xl pl-11 pr-11 py-2.5 text-sm text-slate-900 placeholder-slate-400 font-bold focus:outline-none focus:border-red-500 focus:bg-white transition-colors"
              />
              <button
                type="button"
                @click="showPassword = !showPassword"
                class="absolute right-3.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 p-1 cursor-pointer transition-colors"
                title="Toggle password view"
              >
                <EyeIcon v-if="!showPassword" class="w-5 h-5" />
                <EyeSlashIcon v-else class="w-5 h-5 text-red-600" />
              </button>
            </div>
            <p v-if="form.errors.password" class="text-xs font-bold text-red-600 mt-1">{{ form.errors.password }}</p>
          </div>

          <!-- Confirm Password -->
          <div class="space-y-1">
            <label class="block text-xs font-extrabold text-slate-700 uppercase tracking-wider">Confirm Password</label>
            <div class="relative">
              <LockClosedIcon class="w-5 h-5 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2" />
              <input
                v-model="form.password_confirmation"
                :type="showConfirmPassword ? 'text' : 'password'"
                required
                placeholder="Confirm password"
                class="w-full bg-slate-50 border border-slate-300 rounded-xl pl-11 pr-11 py-2.5 text-sm text-slate-900 placeholder-slate-400 font-bold focus:outline-none focus:border-red-500 focus:bg-white transition-colors"
              />
              <button
                type="button"
                @click="showConfirmPassword = !showConfirmPassword"
                class="absolute right-3.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 p-1 cursor-pointer transition-colors"
                title="Toggle password view"
              >
                <EyeIcon v-if="!showConfirmPassword" class="w-5 h-5" />
                <EyeSlashIcon v-else class="w-5 h-5 text-red-600" />
              </button>
            </div>
          </div>

          <!-- Submit Button -->
          <button
            type="submit"
            :disabled="form.processing"
            class="w-full py-3.5 mt-2 bg-red-600 hover:bg-red-700 text-white font-extrabold text-sm rounded-xl shadow-md shadow-red-600/30 flex items-center justify-center gap-2 transition-all cursor-pointer disabled:opacity-50"
          >
            <span>Create Organization & Setup</span>
            <ArrowRightIcon class="w-4 h-4 stroke-[3]" />
          </button>
        </form>

        <div class="pt-3 border-t border-slate-200 text-center text-xs text-slate-500 font-medium">
          <span>Already registered? </span>
          <Link href="/login" class="font-extrabold text-red-600 hover:text-red-700 hover:underline">
            Sign in here
          </Link>
        </div>
      </div>
    </div>
  </div>
</template>
