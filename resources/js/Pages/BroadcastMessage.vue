<script setup>
import { ref, computed } from 'vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
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
  PlusIcon,
  PaperAirplaneIcon,
  CheckCircleIcon,
  ClockIcon,
  ChatBubbleLeftRightIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
  broadcasts: Object,
  metrics: Object,
  verifiedMembers: Number,
});

const page = usePage();
const customNav = computed(() => page.props.custom_nav || {});
const businessSettings = computed(() => page.props.business_settings || { business_name: 'JRV CRM', business_icon: '♥' });

const getNavLabel = (key, fallback) => customNav.value[key]?.label || fallback;
const getNavRoute = (key, fallback) => customNav.value[key]?.route || fallback;

const isSearchOpen = ref(false);
const isComposeOpen = ref(false);

const form = useForm({
  title: '',
  channel: 'WhatsApp',
  target_audience: 'All Verified Members',
  message_body: 'Jai Jinendra! New bio-data candidates matching your preferences are now available on JRV CRM.',
});

const submit = () => {
  form.post('/broadcast-message', {
    onSuccess: () => {
      form.reset();
      isComposeOpen.value = false;
    },
  });
};
</script>

<template>
  <Head title="BroadCast Message Center - JRV CRM" />

  <div class="min-h-screen bg-slate-100 flex font-sans text-slate-900">
    <!-- Left Vertical Sidebar -->
    <aside class="w-24 bg-white border-r border-slate-200 flex flex-col items-center py-4 space-y-6 shrink-0 shadow-xs">
      <Link href="/" class="flex flex-col items-center gap-1 group">
        <div class="w-12 h-12 bg-red-600 rounded-2xl flex items-center justify-center text-white font-black text-xl shadow-md shadow-red-600/30">
          {{ businessSettings.business_icon }}
        </div>
        <span class="text-[9px] font-black text-red-600 tracking-tighter uppercase text-center px-1 leading-tight line-clamp-1">
          {{ businessSettings.business_name }}
        </span>
      </Link>

      <nav class="flex-1 w-full space-y-3 px-2">
        <Link :href="getNavRoute('app', '/')" class="flex flex-col items-center justify-center p-2 rounded-xl text-slate-500 hover:bg-slate-100 transition-all text-center">
          <HomeIcon class="w-5 h-5 text-slate-400" />
          <span class="text-[9px] font-bold mt-1 leading-tight line-clamp-1">{{ getNavLabel('app', 'App') }}</span>
        </Link>

        <Link href="/matrimonial/directory" class="flex flex-col items-center justify-center p-2 rounded-xl text-slate-500 hover:bg-slate-100 transition-all text-center">
          <DocumentTextIcon class="w-5 h-5 text-slate-400" />
          <span class="text-[9px] font-bold mt-1 leading-tight line-clamp-1">{{ getNavLabel('biodata', 'Biodata') }}</span>
        </Link>

        <!-- Active Item: BroadCast Message -->
        <Link href="/broadcast-message" class="flex flex-col items-center justify-center p-2 rounded-xl bg-red-50 text-red-600 font-bold border border-red-200 text-center shadow-xs">
          <SignalIcon class="w-6 h-6 text-red-600" />
          <span class="text-[9px] font-black mt-1 leading-tight line-clamp-1">{{ getNavLabel('broadcast', 'BroadCast...') }}</span>
        </Link>

        <Link href="/online-users" class="flex flex-col items-center justify-center p-2 rounded-xl text-slate-500 hover:bg-slate-100 transition-all text-center">
          <UserIcon class="w-5 h-5 text-slate-400" />
          <span class="text-[9px] font-bold mt-1 leading-tight line-clamp-1">{{ getNavLabel('online_user', 'Online User') }}</span>
        </Link>

        <Link href="/auto-update" class="flex flex-col items-center justify-center p-2 rounded-xl text-slate-500 hover:bg-slate-100 transition-all text-center">
          <ArrowPathIcon class="w-5 h-5 text-slate-400" />
          <span class="text-[9px] font-bold mt-1 leading-tight line-clamp-1">{{ getNavLabel('auto_update', 'Auto Update') }}</span>
        </Link>

        <Link href="/padhadhikari-directory" class="flex flex-col items-center justify-center p-2 rounded-xl text-slate-500 hover:bg-slate-100 transition-all text-center">
          <AcademicCapIcon class="w-5 h-5 text-slate-400" />
          <span class="text-[9px] font-bold mt-1 leading-tight line-clamp-1">{{ getNavLabel('padhadhikari', 'Padhadhikari') }}</span>
        </Link>

        <Link href="/staff-recruitment" class="flex flex-col items-center justify-center p-2 rounded-xl text-slate-500 hover:bg-slate-100 transition-all text-center">
          <BriefcaseIcon class="w-5 h-5 text-slate-400" />
          <span class="text-[9px] font-bold mt-1 leading-tight line-clamp-1">{{ getNavLabel('staff_recruit', 'Staff Recruit') }}</span>
        </Link>

        <Link href="/employee-management" class="flex flex-col items-center justify-center p-2 rounded-xl text-slate-500 hover:bg-slate-100 transition-all text-center">
          <UserGroupIcon class="w-5 h-5 text-slate-400" />
          <span class="text-[9px] font-bold mt-1 leading-tight line-clamp-1">{{ getNavLabel('staff_management', 'Staff Manag...') }}</span>
        </Link>

        <Link href="/online-users" class="flex flex-col items-center justify-center p-2 rounded-xl text-slate-500 hover:bg-slate-100 transition-all text-center">
          <GlobeAltIcon class="w-5 h-5 text-slate-400" />
          <span class="text-[9px] font-bold mt-1 leading-tight line-clamp-1">{{ getNavLabel('universal', 'Universal') }}</span>
        </Link>
      </nav>
    </aside>

    <!-- Main Content Area -->
    <div class="flex-1 flex flex-col min-w-0">
      <!-- Top Action Bar -->
      <header class="bg-white border-b border-slate-200 px-6 py-3 flex items-center justify-between gap-3 shadow-xs">
        <div class="flex items-center gap-2">
          <button @click="isComposeOpen = true" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-extrabold text-xs rounded-xl shadow-xs transition-all flex items-center gap-1.5 cursor-pointer">
            <PaperAirplaneIcon class="w-4 h-4" />
            <span>+ New BroadCast Message</span>
          </button>
        </div>

        <div class="flex items-center gap-3">
          <div class="w-9 h-9 bg-red-600 rounded-full flex items-center justify-center text-white font-black text-sm shadow-md">
            {{ businessSettings.business_icon }}
          </div>
        </div>
      </header>

      <!-- Page Body Container -->
      <div class="p-8 space-y-6 flex-1 overflow-y-auto max-w-7xl mx-auto w-full">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-200 pb-6">
          <div>
            <div class="flex items-center gap-2 text-xs font-bold text-red-600 uppercase tracking-wider">
              <SignalIcon class="w-4 h-4" />
              <span>Mass Communication Hub</span>
            </div>
            <h1 class="text-3xl font-black tracking-tight text-slate-900 mt-0.5">
              BroadCast Message Center
            </h1>
          </div>

          <button @click="isComposeOpen = true" class="px-6 py-2.5 bg-red-600 hover:bg-red-700 text-white font-bold text-sm rounded-xl shadow-md flex items-center gap-2 transition-all">
            <PaperAirplaneIcon class="w-5 h-5 stroke-[2.5]" />
            <span>Send New Broadcast</span>
          </button>
        </div>

        <!-- 4 Summary Metrics Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
          <div class="p-6 bg-white rounded-2xl border border-slate-200 shadow-xs space-y-2">
            <div class="text-xs font-bold text-slate-500 uppercase">Total Campaigns</div>
            <div class="text-3xl font-black text-slate-900">{{ metrics.total_broadcasts }}</div>
            <p class="text-xs text-indigo-600 font-semibold">Dispatched mass messages</p>
          </div>

          <div class="p-6 bg-white rounded-2xl border border-slate-200 shadow-xs space-y-2">
            <div class="text-xs font-bold text-slate-500 uppercase">WhatsApp Delivery Rate</div>
            <div class="text-3xl font-black text-emerald-600">{{ metrics.whatsapp_delivery_rate }}</div>
            <p class="text-xs text-emerald-600 font-semibold">Verified WhatsApp API</p>
          </div>

          <div class="p-6 bg-white rounded-2xl border border-slate-200 shadow-xs space-y-2">
            <div class="text-xs font-bold text-slate-500 uppercase">Email Open Rate</div>
            <div class="text-3xl font-black text-sky-600">{{ metrics.email_open_rate }}</div>
            <p class="text-xs text-sky-600 font-semibold">Verified email delivery</p>
          </div>

          <div class="p-6 bg-white rounded-2xl border border-slate-200 shadow-xs space-y-2">
            <div class="text-xs font-bold text-slate-500 uppercase">Total Audience Reach</div>
            <div class="text-3xl font-black text-rose-600">{{ metrics.total_audience_reach }}</div>
            <p class="text-xs text-rose-600 font-semibold">Community members</p>
          </div>
        </div>

        <!-- Broadcast Logs Table -->
        <div class="bg-white border border-slate-200 rounded-3xl overflow-hidden shadow-xs space-y-4">
          <div class="p-5 border-b border-slate-200 flex items-center justify-between">
            <h3 class="text-base font-bold text-slate-900">Broadcast Campaign History</h3>
          </div>

          <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
              <thead>
                <tr class="bg-slate-50 border-b border-slate-200 text-slate-500 font-bold uppercase text-[10px]">
                  <th class="py-3 px-4">Title / Campaign</th>
                  <th class="py-3 px-4">Channel</th>
                  <th class="py-3 px-4">Target Segment</th>
                  <th class="py-3 px-4">Sent / Delivered</th>
                  <th class="py-3 px-4">Status</th>
                  <th class="py-3 px-4">Date</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100">
                <tr v-for="b in broadcasts.data" :key="b.id" class="hover:bg-slate-50">
                  <td class="py-3.5 px-4 font-bold text-slate-900">{{ b.title }}</td>
                  <td class="py-3.5 px-4">
                    <span class="px-2.5 py-1 bg-emerald-100 text-emerald-800 font-extrabold text-[10px] rounded-full border border-emerald-300">
                      {{ b.channel }}
                    </span>
                  </td>
                  <td class="py-3.5 px-4 font-semibold text-slate-700">{{ b.target_audience }}</td>
                  <td class="py-3.5 px-4 font-bold text-slate-800">{{ b.sent_count }} / {{ b.delivered_count }}</td>
                  <td class="py-3.5 px-4">
                    <span class="px-2.5 py-0.5 bg-emerald-50 text-emerald-700 border border-emerald-300 rounded-full text-[10px] font-black">
                      {{ b.status }}
                    </span>
                  </td>
                  <td class="py-3.5 px-4 text-slate-500 font-semibold">{{ new Date(b.created_at).toLocaleDateString() }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    <!-- Compose Modal -->
    <div v-if="isComposeOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-xs">
      <div class="bg-white border border-slate-200 rounded-3xl max-w-xl w-full shadow-2xl p-6 space-y-4">
        <h3 class="text-lg font-bold text-slate-900">Send New BroadCast Campaign</h3>
        <form @submit.prevent="submit" class="space-y-4">
          <div>
            <label class="text-xs font-bold text-slate-700">Campaign Title</label>
            <input v-model="form.title" type="text" placeholder="e.g. New Bio-data Candidates Alert" required class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3 py-2 text-xs font-bold" />
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="text-xs font-bold text-slate-700">Channel</label>
              <select v-model="form.channel" class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3 py-2 text-xs font-bold">
                <option value="WhatsApp">WhatsApp</option>
                <option value="SMS">SMS</option>
                <option value="Email">Email</option>
                <option value="All">All Channels</option>
              </select>
            </div>
            <div>
              <label class="text-xs font-bold text-slate-700">Target Segment</label>
              <select v-model="form.target_audience" class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3 py-2 text-xs font-bold">
                <option value="All Verified Members">All Verified Members</option>
                <option value="Premium Subscription Tier">Premium Plan Members</option>
                <option value="Jain Community">Jain Community Only</option>
              </select>
            </div>
          </div>

          <div>
            <label class="text-xs font-bold text-slate-700">Message Body</label>
            <textarea v-model="form.message_body" rows="4" required class="w-full bg-slate-50 border border-slate-300 rounded-xl p-3 text-xs font-medium"></textarea>
          </div>

          <div class="flex justify-end gap-3 pt-2">
            <button type="button" @click="isComposeOpen = false" class="px-4 py-2 text-xs font-bold text-slate-500">Cancel</button>
            <button type="submit" class="px-5 py-2 bg-red-600 hover:bg-red-700 text-white text-xs font-extrabold rounded-xl shadow-md">Dispatch Broadcast</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Modals -->
    <GlobalSearchModal :is-open="isSearchOpen" @close="isSearchOpen = false" />
  </div>
</template>
