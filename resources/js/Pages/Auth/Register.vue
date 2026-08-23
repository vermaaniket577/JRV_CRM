<script setup>
import { ref, watch } from 'vue';
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
  GlobeAltIcon,
  ServerStackIcon
} from '@heroicons/vue/24/outline';

const showPassword = ref(false);
const showConfirmPassword = ref(false);
const manualSubdomain = ref(false);

const form = useForm({
  name: '',
  email: '',
  organization_name: '',
  subdomain: '',
  password: '',
  password_confirmation: '',
});

// Auto-generate clean subdomain as user types organization name
watch(() => form.organization_name, (newVal) => {
  if (!manualSubdomain.value && newVal) {
    form.subdomain = newVal
      .toLowerCase()
      .replace(/[^a-z0-9]/g, '')
      .slice(0, 30);
  }
});

const onSubdomainInput = () => {
  manualSubdomain.value = true;
  form.subdomain = form.subdomain.toLowerCase().replace(/[^a-z0-9]/g, '').slice(0, 30);
};

const submit = () => {
  form.post('/register', {
    onFinish: () => form.reset('password', 'password_confirmation'),
  });
};
</script>

<template>
  <Head title="Create Your Organization - JRV CRM" />

  <div class="min-h-screen bg-slate-50 text-slate-900 font-sans flex items-center justify-center p-4 sm:p-6 relative overflow-hidden">
    <!-- Ambient Soft Glow Background Effects -->
    <div class="absolute top-1/4 left-1/2 -translate-x-1/2 w-[600px] h-[600px] bg-red-500/5 rounded-full blur-3xl pointer-events-none"></div>
    <div class="absolute bottom-10 right-10 w-[350px] h-[350px] bg-red-400/5 rounded-full blur-3xl pointer-events-none"></div>

    <div class="max-w-lg w-full relative z-10 space-y-6 my-6">
      
      <!-- Logo & Header Badge -->
      <div class="text-center space-y-2">
        <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-red-50 border border-red-100 text-xs font-semibold text-red-700">
          <SparklesIcon class="w-4 h-4 text-red-600" />
          <span>Multi-Tenant Cloud CRM SaaS</span>
        </div>

        <div class="flex flex-col items-center justify-center gap-1 pt-1">
          <div class="w-12 h-12 bg-red-600 text-white font-bold text-xl rounded-2xl flex items-center justify-center shadow-md shadow-red-600/20">
            ⚡
          </div>
          <h1 class="text-2xl sm:text-3xl font-bold text-slate-900 tracking-tight pt-1">Create Your Company CRM</h1>
          <p class="text-slate-500 text-sm font-normal">Dedicated company subdomain & separate MySQL database provisioned instantly.</p>
        </div>
      </div>

      <!-- Main Registration Card -->
      <div class="bg-white border border-slate-200/90 rounded-3xl p-6 sm:p-8 shadow-sm space-y-5">
        <form @submit.prevent="submit" class="space-y-4">
          
          <!-- Full Name -->
          <div class="space-y-1.5">
            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">Full Name *</label>
            <div class="relative">
              <UserIcon class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2 shrink-0 pointer-events-none" />
              <input
                v-model="form.name"
                type="text"
                required
                placeholder="Enter your full name"
                class="w-full bg-white border border-slate-300 rounded-xl pl-10 pr-4 py-2.5 text-xs font-semibold text-slate-900 placeholder:text-slate-400 placeholder:font-normal focus:outline-none focus:ring-2 focus:ring-red-500/20 focus:border-red-500 transition shadow-2xs"
              />
            </div>
            <p v-if="form.errors.name" class="text-xs font-medium text-red-600 mt-1">{{ form.errors.name }}</p>
          </div>

          <!-- Organization Name -->
          <div class="space-y-1.5">
            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">Company / Business Name *</label>
            <div class="relative">
              <BuildingOfficeIcon class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2 shrink-0 pointer-events-none" />
              <input
                v-model="form.organization_name"
                type="text"
                required
                placeholder="e.g. Admissions Dekho"
                class="w-full bg-white border border-slate-300 rounded-xl pl-10 pr-4 py-2.5 text-xs font-semibold text-slate-900 placeholder:text-slate-400 placeholder:font-normal focus:outline-none focus:ring-2 focus:ring-red-500/20 focus:border-red-500 transition shadow-2xs"
              />
            </div>
            <p v-if="form.errors.organization_name" class="text-xs font-medium text-red-600 mt-1">{{ form.errors.organization_name }}</p>
          </div>

          <!-- Dedicated Company Subdomain Field with Live URL Preview -->
          <div class="space-y-1.5 bg-red-50/50 border border-red-100 rounded-2xl p-4">
            <div class="flex items-center justify-between">
              <label class="block text-xs font-bold text-red-950 uppercase tracking-wider flex items-center gap-1.5">
                <GlobeAltIcon class="w-4 h-4 text-red-600" />
                <span>Dedicated Company Subdomain *</span>
              </label>
              <span class="text-[10px] text-red-600 font-bold">Unique URL</span>
            </div>

            <div class="flex items-center rounded-xl bg-white border border-slate-300 focus-within:border-red-500 focus-within:ring-2 focus-within:ring-red-500/20 overflow-hidden shadow-2xs">
              <span class="pl-3.5 pr-1 text-slate-400 font-mono text-xs select-none">https://</span>
              <input
                v-model="form.subdomain"
                @input="onSubdomainInput"
                type="text"
                required
                placeholder="companyname"
                class="w-full py-2.5 text-xs font-mono font-bold text-red-600 focus:outline-none placeholder:text-slate-300"
              />
              <span class="pr-3.5 pl-1 text-slate-500 font-mono text-xs font-medium select-none bg-slate-50 py-2.5 border-l border-slate-200">.jrvcrm.com</span>
            </div>

            <!-- Live Subdomain & Separate DB Explainer -->
            <div class="pt-1 flex flex-col gap-1 text-[11px] text-slate-600">
              <div class="flex items-center gap-1 font-mono text-slate-800">
                <span class="text-emerald-600 font-bold">● URL:</span>
                <span class="bg-white px-2 py-0.5 rounded border border-slate-200 font-bold text-red-600">
                  https://{{ form.subdomain || 'yourcompany' }}.jrvcrm.com
                </span>
              </div>
              <div class="flex items-center gap-1 text-[10px] text-slate-500">
                <ServerStackIcon class="w-3.5 h-3.5 text-slate-400" />
                <span>A separate, isolated MySQL database <code class="bg-white px-1 py-0.5 rounded text-slate-700">crm_tenant_{{ form.subdomain || 'company' }}</code> is created automatically.</span>
              </div>
            </div>
            <p v-if="form.errors.subdomain" class="text-xs font-medium text-red-600 mt-1">{{ form.errors.subdomain }}</p>
          </div>

          <!-- Email -->
          <div class="space-y-1.5">
            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">Work Email Address *</label>
            <div class="relative">
              <EnvelopeIcon class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2 shrink-0 pointer-events-none" />
              <input
                v-model="form.email"
                type="email"
                required
                placeholder="name@company.com"
                class="w-full bg-white border border-slate-300 rounded-xl pl-10 pr-4 py-2.5 text-xs font-semibold text-slate-900 placeholder:text-slate-400 placeholder:font-normal focus:outline-none focus:ring-2 focus:ring-red-500/20 focus:border-red-500 transition shadow-2xs"
              />
            </div>
            <p v-if="form.errors.email" class="text-xs font-medium text-red-600 mt-1">{{ form.errors.email }}</p>
          </div>

          <!-- Password Grid -->
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div class="space-y-1.5">
              <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">Password *</label>
              <div class="relative">
                <LockClosedIcon class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2 shrink-0 pointer-events-none" />
                <input
                  v-model="form.password"
                  :type="showPassword ? 'text' : 'password'"
                  required
                  placeholder="Min. 8 chars"
                  class="w-full bg-white border border-slate-300 rounded-xl pl-9 pr-8 py-2.5 text-xs font-semibold text-slate-900 focus:outline-none focus:ring-2 focus:ring-red-500/20 focus:border-red-500 transition shadow-2xs"
                />
                <button
                  type="button"
                  @click="showPassword = !showPassword"
                  class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 p-1 cursor-pointer transition"
                  tabindex="-1"
                >
                  <EyeIcon v-if="!showPassword" class="w-3.5 h-3.5" />
                  <EyeSlashIcon v-else class="w-3.5 h-3.5 text-red-600" />
                </button>
              </div>
              <p v-if="form.errors.password" class="text-xs font-medium text-red-600 mt-1">{{ form.errors.password }}</p>
            </div>

            <div class="space-y-1.5">
              <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">Confirm Password *</label>
              <div class="relative">
                <LockClosedIcon class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2 shrink-0 pointer-events-none" />
                <input
                  v-model="form.password_confirmation"
                  :type="showConfirmPassword ? 'text' : 'password'"
                  required
                  placeholder="Re-enter password"
                  class="w-full bg-white border border-slate-300 rounded-xl pl-9 pr-8 py-2.5 text-xs font-semibold text-slate-900 focus:outline-none focus:ring-2 focus:ring-red-500/20 focus:border-red-500 transition shadow-2xs"
                />
                <button
                  type="button"
                  @click="showConfirmPassword = !showConfirmPassword"
                  class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 p-1 cursor-pointer transition"
                  tabindex="-1"
                >
                  <EyeIcon v-if="!showConfirmPassword" class="w-3.5 h-3.5" />
                  <EyeSlashIcon v-else class="w-3.5 h-3.5 text-red-600" />
                </button>
              </div>
            </div>
          </div>

          <!-- Submit Button -->
          <div class="pt-2">
            <button
              type="submit"
              :disabled="form.processing"
              class="w-full py-3.5 px-4 bg-red-600 hover:bg-red-700 text-white font-black text-xs uppercase tracking-wider rounded-2xl shadow-lg shadow-red-600/20 flex items-center justify-center gap-2 transition cursor-pointer disabled:opacity-50"
            >
              <span>{{ form.processing ? 'Provisioning Subdomain & Database...' : '🚀 Create Subdomain & Launch CRM' }}</span>
              <ArrowRightIcon class="w-4 h-4 stroke-[3]" />
            </button>
          </div>
        </form>

        <!-- Divider & Sign in Link -->
        <div class="pt-4 border-t border-slate-100 text-center text-xs text-slate-500 font-normal">
          <span>Already registered? </span>
          <Link href="/login" class="font-bold text-red-600 hover:text-red-700 hover:underline">
            Sign in to your CRM
          </Link>
        </div>
      </div>
    </div>
  </div>
</template>
