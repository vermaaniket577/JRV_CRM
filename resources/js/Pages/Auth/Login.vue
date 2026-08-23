<script setup>
import { ref } from 'vue';
import { useForm, Head, Link } from '@inertiajs/vue3';
import { SparklesIcon, ArrowRightIcon, LockClosedIcon, EnvelopeIcon, EyeIcon, EyeSlashIcon } from '@heroicons/vue/24/outline';

const showPassword = ref(false);

const form = useForm({
  email: '',
  password: '',
  remember: false,
});

const submit = () => {
  form.post('/login', {
    onFinish: () => form.reset('password'),
  });
};
</script>

<template>
  <Head title="Sign In - JRV CRM" />

  <div class="min-h-screen bg-slate-50 text-slate-900 font-sans flex items-center justify-center p-4 sm:p-6 relative overflow-hidden">
    <!-- Ambient Soft Glow Background -->
    <div class="absolute top-1/4 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[500px] h-[500px] bg-red-500/5 rounded-full blur-3xl pointer-events-none"></div>
    <div class="absolute bottom-1/4 right-1/4 w-[350px] h-[350px] bg-red-400/5 rounded-full blur-3xl pointer-events-none"></div>

    <div class="max-w-md w-full relative z-10 space-y-6">
      <!-- Logo & Title -->
      <div class="text-center space-y-2">
        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-red-50 border border-red-100 text-xs font-semibold text-red-700">
          <SparklesIcon class="w-4 h-4 text-red-600" />
          <span>Multi-Sector CRM Platform</span>
        </div>

        <div class="flex flex-col items-center justify-center gap-1 pt-1">
          <div class="w-12 h-12 bg-red-600 rounded-2xl flex items-center justify-center text-white font-bold text-xl shadow-md shadow-red-600/20">
            ⚡
          </div>
          <h1 class="text-2xl sm:text-3xl font-bold tracking-tight text-slate-900 pt-1">Welcome back</h1>
          <p class="text-slate-500 text-sm font-normal">Sign in to your CRM workspace</p>
        </div>
      </div>

      <!-- Login Form Card -->
      <div class="bg-white border border-slate-200/90 rounded-2xl p-6 sm:p-8 shadow-sm space-y-5">
        <form @submit.prevent="submit" class="space-y-4">
          <!-- Email Input -->
          <div class="space-y-1.5">
            <label class="block text-sm font-medium text-slate-700">Email address</label>
            <div class="relative">
              <EnvelopeIcon class="w-5 h-5 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2 shrink-0 pointer-events-none" />
              <input
                v-model="form.email"
                type="email"
                required
                placeholder="name@company.com"
                class="w-full bg-white border border-slate-300 rounded-xl pl-10 pr-4 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 placeholder:font-normal focus:outline-none focus:ring-2 focus:ring-red-500/20 focus:border-red-500 transition shadow-2xs"
              />
            </div>
            <p v-if="form.errors.email" class="text-xs font-medium text-red-600 mt-1">{{ form.errors.email }}</p>
          </div>

          <!-- Password Input -->
          <div class="space-y-1.5">
            <div class="flex items-center justify-between">
              <label class="block text-sm font-medium text-slate-700">Password</label>
            </div>
            <div class="relative">
              <LockClosedIcon class="w-5 h-5 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2 shrink-0 pointer-events-none" />
              <input
                v-model="form.password"
                :type="showPassword ? 'text' : 'password'"
                required
                placeholder="••••••••"
                class="w-full bg-white border border-slate-300 rounded-xl pl-10 pr-10 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 placeholder:font-normal focus:outline-none focus:ring-2 focus:ring-red-500/20 focus:border-red-500 transition shadow-2xs"
              />
              <button
                type="button"
                @click="showPassword = !showPassword"
                class="absolute right-3.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 p-1 cursor-pointer transition"
                tabindex="-1"
              >
                <EyeIcon v-if="!showPassword" class="w-4 h-4" />
                <EyeSlashIcon v-else class="w-4 h-4 text-red-600" />
              </button>
            </div>
            <p v-if="form.errors.password" class="text-xs font-medium text-red-600 mt-1">{{ form.errors.password }}</p>
          </div>

          <!-- Remember Me -->
          <div class="flex items-center justify-between text-sm pt-1">
            <label class="flex items-center gap-2 cursor-pointer text-slate-600 font-normal">
              <input v-model="form.remember" type="checkbox" class="w-4 h-4 rounded border-slate-300 text-red-600 focus:ring-red-500" />
              <span>Remember me</span>
            </label>
          </div>

          <!-- Submit Button -->
          <div class="pt-2">
            <button
              type="submit"
              :disabled="form.processing"
              class="w-full py-3 px-4 bg-red-600 hover:bg-red-700 text-white font-semibold text-sm rounded-xl shadow-md shadow-red-600/20 flex items-center justify-center gap-2 transition cursor-pointer disabled:opacity-50"
            >
              <span>Sign in to CRM</span>
              <ArrowRightIcon class="w-4 h-4 stroke-[2.5]" />
            </button>
          </div>
        </form>

        <!-- Divider & Signup Link -->
        <div class="pt-4 border-t border-slate-100 text-center text-sm text-slate-500 font-normal">
          <span>Don't have an account? </span>
          <Link href="/register" class="font-semibold text-red-600 hover:text-red-700 hover:underline">
            Register your business
          </Link>
        </div>
      </div>
    </div>
  </div>
</template>
