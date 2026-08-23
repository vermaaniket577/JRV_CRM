<script setup>
import { ref, computed } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import Navbar from '@/Components/Navbar.vue';
import GlobalSearchModal from '@/Components/GlobalSearchModal.vue';
import { 
  BellIcon, 
  HomeIcon, 
  UserIcon, 
  SignalIcon, 
  DocumentTextIcon, 
  ArrowPathIcon,
  AcademicCapIcon,
  BriefcaseIcon,
  UserGroupIcon,
  GlobeAltIcon,
  AdjustmentsHorizontalIcon,
  CodeBracketIcon,
  CommandLineIcon,
  DocumentDuplicateIcon,
  CheckIcon,
  KeyIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
  apiKey: String,
  embedScriptCode: String,
  iframeCode: String,
  apiDocs: Object,
  appUrl: String,
});

const page = usePage();
const customNav = computed(() => page.props.custom_nav || {});
const businessSettings = computed(() => page.props.business_settings || { business_name: 'JRV CRM', business_icon: '🎓' });

const getNavLabel = (key, fallback) => customNav.value[key]?.label || fallback;
const getNavRoute = (key, fallback) => customNav.value[key]?.route || fallback;

const isSearchOpen = ref(false);
const copiedScript = ref(false);
const copiedIframe = ref(false);
const copiedKey = ref(false);

const copyText = (text, type) => {
  navigator.clipboard.writeText(text);
  if (type === 'script') {
    copiedScript.value = true;
    setTimeout(() => copiedScript.value = false, 2000);
  } else if (type === 'iframe') {
    copiedIframe.value = true;
    setTimeout(() => copiedIframe.value = false, 2000);
  } else if (type === 'key') {
    copiedKey.value = true;
    setTimeout(() => copiedKey.value = false, 2000);
  }
};
</script>

<template>
  <Head title="Website Integration & Embed Portal - JRV CRM" />

  <div class="min-h-screen bg-slate-100 flex font-sans text-slate-900">
    <!-- Fixed Left Vertical Sidebar -->
    <Navbar @open-search="isSearchOpen = true" />

    <!-- Main Content Area -->
    <div class="flex-1 flex flex-col min-w-0">
      <header class="bg-white border-b border-slate-200 px-6 py-3 flex items-center justify-between gap-3 shadow-xs">
        <div class="flex items-center gap-2">
          <Link href="/tenant/settings/navigation" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-extrabold text-xs rounded-xl shadow-xs flex items-center gap-1.5 transition">
            <AdjustmentsHorizontalIcon class="w-4 h-4" />
            <span>Customize Brand & Menu</span>
          </Link>
        </div>

        <div class="flex items-center gap-3">
          <div class="w-9 h-9 bg-red-600 rounded-full flex items-center justify-center text-white font-black text-sm shadow-md">
            {{ businessSettings.business_icon }}
          </div>
        </div>
      </header>

      <div class="p-8 space-y-6 flex-1 overflow-y-auto max-w-7xl mx-auto w-full">
        <div class="border-b border-slate-200 pb-6">
          <div class="flex items-center gap-2 text-xs font-bold text-red-600 uppercase tracking-wider">
            <CodeBracketIcon class="w-4 h-4" />
            <span>Developer Integration Portal</span>
          </div>
          <h1 class="text-3xl font-black tracking-tight text-slate-900 mt-0.5">
            Embed Widgets & REST API Integration
          </h1>
          <p class="text-xs text-slate-600 mt-1">
            Easily connect your community website (WordPress, React, PHP/HTML) to receive bio-data submissions directly into your CRM.
          </p>
        </div>

        <!-- 1. API Secret Token -->
        <div class="bg-white border border-slate-200 rounded-3xl p-6 shadow-xs space-y-3">
          <div class="flex items-center gap-2 text-slate-900 font-extrabold text-sm">
            <KeyIcon class="w-5 h-5 text-red-600" />
            <span>Your Live Website API Key</span>
          </div>
          <div class="flex items-center gap-3">
            <input :value="apiKey" readonly type="text" class="flex-1 bg-slate-50 border border-slate-300 font-mono text-xs font-bold text-slate-800 rounded-xl px-4 py-2.5" />
            <button @click="copyText(apiKey, 'key')" class="px-4 py-2.5 bg-red-600 hover:bg-red-700 text-white font-bold text-xs rounded-xl shadow-xs flex items-center gap-1.5 transition-all">
              <CheckIcon v-if="copiedKey" class="w-4 h-4 text-white" />
              <DocumentDuplicateIcon v-else class="w-4 h-4" />
              <span>{{ copiedKey ? 'Copied Key!' : 'Copy API Key' }}</span>
            </button>
          </div>
        </div>

        <!-- 2. iFrame Registration Form Embed Code -->
        <div class="bg-white border border-slate-200 rounded-3xl p-6 shadow-xs space-y-3">
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-2 text-slate-900 font-extrabold text-sm">
              <CodeBracketIcon class="w-5 h-5 text-red-600" />
              <span>Embeddable iFrame Bio-data Form Code</span>
            </div>
            <a :href="appUrl + '/embed/register'" target="_blank" class="text-xs font-bold text-red-600 underline">Preview Embed Form ↗</a>
          </div>
          <p class="text-xs text-slate-500">Copy and paste this snippet anywhere on your website page to render an embeddable bio-data registration form:</p>

          <div class="relative bg-slate-900 text-emerald-400 font-mono text-xs p-4 rounded-2xl overflow-x-auto">
            <code>{{ iframeCode }}</code>
            <button @click="copyText(iframeCode, 'iframe')" class="absolute top-3 right-3 px-3 py-1 bg-white/20 hover:bg-white/30 text-white font-bold text-[10px] rounded-lg transition-all flex items-center gap-1">
              <CheckIcon v-if="copiedIframe" class="w-3.5 h-3.5 text-emerald-400" />
              <DocumentDuplicateIcon v-else class="w-3.5 h-3.5" />
              <span>{{ copiedIframe ? 'Copied Code!' : 'Copy Snippet' }}</span>
            </button>
          </div>
        </div>

        <!-- 3. REST API Documentation -->
        <div class="bg-white border border-slate-200 rounded-3xl p-6 shadow-xs space-y-4">
          <div class="flex items-center gap-2 text-slate-900 font-extrabold text-sm">
            <CommandLineIcon class="w-5 h-5 text-red-600" />
            <span>REST API Endpoints Documentation</span>
          </div>

          <div class="space-y-3">
            <div v-for="(ep, idx) in apiDocs.endpoints" :key="idx" class="p-4 border border-slate-200 rounded-2xl bg-slate-50 space-y-2">
              <div class="flex items-center gap-3">
                <span :class="['px-2.5 py-0.5 rounded font-mono font-black text-xs', ep.method === 'POST' ? 'bg-emerald-100 text-emerald-800' : 'bg-sky-100 text-sky-800']">
                  {{ ep.method }}
                </span>
                <code class="text-xs font-mono font-bold text-slate-900">{{ apiDocs.base_url }}{{ ep.path }}</code>
              </div>
              <p class="text-xs text-slate-600">{{ ep.description }}</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Modals -->
    <GlobalSearchModal :is-open="isSearchOpen" @close="isSearchOpen = false" />
  </div>
</template>
