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

  <div class="min-h-screen bg-slate-100 text-slate-900 font-sans flex items-center justify-center p-6 relative overflow-hidden">
    <!-- Ambient Soft Glow Background -->
    <div class="absolute top-1/4 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[500px] h-[500px] bg-red-500/10 rounded-full blur-3xl pointer-events-none"></div>
    <div class="absolute bottom-1/4 right-1/4 w-[350px] h-[350px] bg-red-400/10 rounded-full blur-3xl pointer-events-none"></div>

    <div class="max-w-md w-full relative z-10 space-y-8">
      <!-- Logo & Title -->
      <div class="text-center space-y-3">
        <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full bg-red-50 border border-red-200 text-xs font-bold text-red-700">
          <SparklesIcon class="w-4 h-4 text-red-600" />
          <span>Multi-Sector CRM Platform</span>
        </div>

        <div class="flex flex-col items-center justify-center gap-2 pt-2">
          <div class="w-14 h-14 bg-red-600 rounded-2xl flex items-center justify-center text-white font-black text-2xl shadow-lg shadow-red-600/30">
            ⚡
          </div>
          <h1 class="text-3xl font-black tracking-tight text-slate-900">Welcome Back</h1>
          <p class="text-slate-500 text-sm font-medium">Sign in to your industry-tailored CRM workspace</p>
        </div>
      </div>

      <!-- Login Form Card (White & Red Theme) -->
      <div class="bg-white border border-slate-200 rounded-3xl p-8 shadow-xl shadow-slate-200/50 space-y-6">
        <form @submit.prevent="submit" class="space-y-5">
          <!-- Email Input -->
          <div class="space-y-1.5">
            <label class="block text-xs font-extrabold text-slate-700 uppercase tracking-wider">Email Address</label>
            <div class="relative">
              <EnvelopeIcon class="w-5 h-5 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2" />
              <input
                v-model="form.email"
                type="email"
                required
                placeholder="name@company.com"
                class="w-full bg-slate-50 border border-slate-300 rounded-xl pl-11 pr-4 py-3 text-sm text-slate-900 placeholder-slate-400 font-bold focus:outline-none focus:border-red-500 focus:bg-white transition-colors"
              />
            </div>
            <p v-if="form.errors.email" class="text-xs font-bold text-red-600 mt-1">{{ form.errors.email }}</p>
          </div>

          <!-- Password Input -->
          <div class="space-y-1.5">
            <div class="flex items-center justify-between">
              <label class="block text-xs font-extrabold text-slate-700 uppercase tracking-wider">Password</label>
            </div>
            <div class="relative">
              <LockClosedIcon class="w-5 h-5 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2" />
              <input
                v-model="form.password"
                :type="showPassword ? 'text' : 'password'"
                required
                placeholder="••••••••"
                class="w-full bg-slate-50 border border-slate-300 rounded-xl pl-11 pr-11 py-3 text-sm text-slate-900 placeholder-slate-400 font-bold focus:outline-none focus:border-red-500 focus:bg-white transition-colors"
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

          <!-- Remember Me -->
          <div class="flex items-center justify-between text-xs">
            <label class="flex items-center gap-2 cursor-pointer text-slate-600 font-bold">
              <input v-model="form.remember" type="checkbox" class="w-4 h-4 rounded border-slate-300 text-red-600 focus:ring-red-500" />
              <span>Remember me</span>
            </label>
          </div>

          <!-- Submit Button (Red Theme) -->
          <button
            type="submit"
            :disabled="form.processing"
            class="w-full py-3.5 bg-red-600 hover:bg-red-700 text-white font-extrabold text-sm rounded-xl shadow-md shadow-red-600/30 flex items-center justify-center gap-2 transition-all cursor-pointer disabled:opacity-50"
          >
            <span>Sign In to CRM</span>
            <ArrowRightIcon class="w-4 h-4 stroke-[3]" />
          </button>
        </form>

        <!-- Divider & Signup Link -->
        <div class="pt-4 border-t border-slate-200 text-center text-xs text-slate-500 font-medium">
          <span>Don't have an account? </span>
          <Link href="/register" class="font-extrabold text-red-600 hover:text-red-700 hover:underline">
            Register your business
          </Link>
        </div>
      </div>
    </div>
  </div>
</template>
