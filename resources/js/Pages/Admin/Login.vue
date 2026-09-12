<script setup>
import { ref, onMounted } from 'vue';
import { useForm, Head, Link, usePage } from '@inertiajs/vue3';
import Loader from '@/Components/Loader.vue';
import { 
  EyeIcon, 
  EyeSlashIcon, 
  ShieldCheckIcon, 
  LockClosedIcon, 
  EnvelopeIcon, 
  SparklesIcon,
  KeyIcon,
  ArrowRightIcon,
  ClockIcon
} from '@heroicons/vue/24/outline';

const page = usePage();
const props = defineProps({
  demoCredentials: Object,
  errors: Object,
});

const showPassword = ref(false);
const isInactivityTimeout = ref(false);

const form = useForm({
  email: props.demoCredentials?.email || 'admin@jrvcrm.com',
  password: props.demoCredentials?.password || 'admin123',
  remember: true,
});

onMounted(() => {
  const urlParams = new URLSearchParams(window.location.search);
  const isTimeout = urlParams.get('timeout') === '1' || sessionStorage.getItem('crm_logout_reason') === 'inactivity' || Boolean(page.props.flash?.info);
  if (isTimeout) {
    isInactivityTimeout.value = true;
    sessionStorage.removeItem('crm_logout_reason');
  }
});

const fillDemo = () => {
  form.email = 'admin@jrvcrm.com';
  form.password = 'admin123';
};

const submit = () => {
  form.post('/admin/login', {
    onFinish: () => form.reset('password'),
  });
};
</script>

<template>
  <Head title="Master Admin Authentication" />

  <div class="min-h-screen bg-slate-100 flex items-center justify-center p-4 font-sans text-slate-900">
    <div class="w-full max-w-md space-y-6">
      <!-- Inactivity Timeout Banner -->
      <div 
        v-if="isInactivityTimeout"
        class="p-4 rounded-2xl bg-amber-50 border border-amber-200 shadow-sm flex items-start gap-3 transition-all"
      >
        <div class="p-2 bg-amber-100 text-amber-700 rounded-xl shrink-0">
          <ClockIcon class="w-5 h-5" />
        </div>
        <div class="space-y-0.5 min-w-0">
          <div class="text-sm font-semibold text-amber-900">Admin Session Expired</div>
          <p class="text-xs text-amber-700 leading-relaxed">
            You were automatically signed out from the Master Admin Panel due to inactivity.
          </p>
        </div>
      </div>

      <!-- Brand & Header Icon -->
      <div class="text-center space-y-2">
        <div class="w-16 h-16 bg-red-600 rounded-3xl flex items-center justify-center text-white text-3xl font-black shadow-xl shadow-red-600/30 mx-auto transform hover:scale-105 transition-all">
          ⚡
        </div>
        <div class="inline-flex items-center gap-2 px-3.5 py-1 rounded-full bg-red-50 border border-red-200 text-xs font-black text-red-700">
          <ShieldCheckIcon class="w-4 h-4 text-red-600" />
          <span>Master Admin Portal</span>
        </div>
        <h1 class="text-2xl font-black text-slate-900 tracking-tight">Master Admin Control Panel</h1>
        <p class="text-slate-500 text-xs font-medium">Restricted access. Sign in with Master Admin credentials to proceed.</p>
      </div>

      <!-- Main Login Card (White & Red Light Theme) -->
      <div class="bg-white border border-slate-200 rounded-3xl p-8 shadow-xl space-y-6">
        <!-- Quick Fill Demo Banner -->
        <div class="p-3.5 rounded-2xl bg-amber-50 border border-amber-200 flex items-center justify-between gap-3">
          <div class="flex items-center gap-2 min-w-0">
            <SparklesIcon class="w-4 h-4 text-amber-600 shrink-0" />
            <div class="min-w-0">
              <div class="text-xs font-extrabold text-amber-900 truncate">Default Master Admin</div>
              <div class="text-[10px] text-amber-700 font-mono truncate">admin@jrvcrm.com | admin123</div>
            </div>
          </div>
          <button 
            type="button"
            @click="fillDemo"
            class="px-2.5 py-1 bg-amber-600 hover:bg-amber-700 text-white font-extrabold text-[10px] rounded-lg shadow-xs transition-all shrink-0 cursor-pointer"
          >
            Auto Fill
          </button>
        </div>

        <form @submit.prevent="submit" class="space-y-4">
          <!-- Email Field -->
          <div class="space-y-1">
            <label class="block text-xs font-extrabold uppercase text-slate-500 tracking-wider">Master Admin Email *</label>
            <div class="relative">
              <EnvelopeIcon class="w-4 h-4 text-slate-400 absolute left-3.5 top-3.5" />
              <input 
                v-model="form.email"
                type="email"
                required
                placeholder="admin@jrvcrm.com"
                class="w-full bg-slate-50 border border-slate-200 rounded-2xl pl-10 pr-4 py-3 text-xs font-bold text-slate-900 placeholder-slate-400 focus:outline-none focus:border-red-500 focus:bg-white transition-all"
              />
            </div>
            <p v-if="form.errors.email" class="text-xs font-bold text-rose-500 mt-1">{{ form.errors.email }}</p>
          </div>

          <!-- Password Field with Eye Toggle -->
          <div class="space-y-1">
            <label class="block text-xs font-extrabold uppercase text-slate-500 tracking-wider">Master Password *</label>
            <div class="relative">
              <LockClosedIcon class="w-4 h-4 text-slate-400 absolute left-3.5 top-3.5" />
              <input 
                v-model="form.password"
                :type="showPassword ? 'text' : 'password'"
                required
                placeholder="••••••••"
                class="w-full bg-slate-50 border border-slate-200 rounded-2xl pl-10 pr-10 py-3 text-xs font-bold text-slate-900 placeholder-slate-400 focus:outline-none focus:border-red-500 focus:bg-white transition-all"
              />
              <button 
                type="button"
                @click="showPassword = !showPassword"
                class="absolute right-3.5 top-3.5 text-slate-400 hover:text-slate-600 focus:outline-none cursor-pointer"
                :title="showPassword ? 'Hide Password' : 'Show Password'"
              >
                <EyeSlashIcon v-if="showPassword" class="w-4 h-4" />
                <EyeIcon v-else class="w-4 h-4" />
              </button>
            </div>
            <p v-if="form.errors.password" class="text-xs font-bold text-rose-500 mt-1">{{ form.errors.password }}</p>
          </div>

          <!-- Submit Button -->
          <button 
            type="submit"
            :disabled="form.processing"
            class="w-full py-3.5 bg-red-600 hover:bg-red-700 text-white font-black text-xs rounded-2xl shadow-lg shadow-red-600/30 flex items-center justify-center gap-2 transition-all cursor-pointer disabled:opacity-50 mt-2"
          >
            <Loader v-if="form.processing" size="sm" color="white" text="Authenticating..." />
            <template v-else>
              <span>Sign In to Master Control Center</span>
              <ArrowRightIcon class="w-4 h-4 stroke-[3]" />
            </template>
          </button>
        </form>

        <div class="pt-4 border-t border-slate-200 text-center">
          <Link href="/login" class="text-xs font-bold text-slate-500 hover:text-red-600 transition-colors">
            Switch to Regular Tenant Login →
          </Link>
        </div>
      </div>
    </div>
  </div>
</template>
