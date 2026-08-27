<script setup>
import { ref, computed } from 'vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import Navbar from '@/Components/Navbar.vue';
import GlobalSearchModal from '@/Components/GlobalSearchModal.vue';
import { 
  SignalIcon, 
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
const tenantIndustry = computed(() => page.props.tenant_industry || { name: 'CRM', icon: '⚡', color: 'indigo' });

const isSearchOpen = ref(false);
const isComposeOpen = ref(false);

const form = useForm({
  title: '',
  channel: 'WhatsApp',
  target_audience: 'All Active Contacts',
  message_body: 'Hello! Important updates and communications from your CRM platform.',
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

  <div class="flex h-screen bg-slate-50 overflow-hidden font-sans text-slate-900">
    <!-- Navbar Sidebar -->
    <Navbar />

    <!-- Main Content Area -->
    <div class="flex-1 flex flex-col min-w-0 overflow-hidden">
      <!-- Top Action Bar -->
      <header class="h-16 bg-white border-b border-slate-200 px-6 flex items-center justify-between shrink-0 shadow-2xs">
        <div class="flex items-center gap-3">
          <div class="w-9 h-9 bg-slate-900 rounded-xl flex items-center justify-center text-white font-black text-sm shadow-md">
            <SignalIcon class="w-5 h-5 text-indigo-400" />
          </div>
          <div>
            <h1 class="text-sm font-black text-slate-900 leading-tight">
              BroadCast Message Center
            </h1>
            <p class="text-[11px] text-slate-500 font-medium">Multi-Channel Mass Outreach & Notifications</p>
          </div>
        </div>

        <div class="flex items-center gap-3">
          <button
            @click="isComposeOpen = true"
            class="px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white font-extrabold text-xs rounded-xl shadow-md transition-all flex items-center gap-1.5 cursor-pointer"
          >
            <PaperAirplaneIcon class="w-4 h-4" />
            <span>+ New BroadCast</span>
          </button>
        </div>
      </header>

      <!-- Scrollable Page Body -->
      <div class="p-6 md:p-8 space-y-6 flex-1 overflow-y-auto max-w-7xl mx-auto w-full">
        <!-- Hero Header Banner -->
        <div class="bg-gradient-to-r from-slate-950 via-slate-900 to-slate-950 border border-slate-800 rounded-3xl p-6 md:p-7 text-white shadow-xl flex flex-col md:flex-row md:items-center justify-between gap-6">
          <div class="space-y-1.5 max-w-2xl">
            <div class="inline-flex items-center gap-2 px-2.5 py-0.5 rounded-md bg-white/10 text-slate-200 text-[10px] font-bold uppercase tracking-wider">
              <span>{{ tenantIndustry.icon }}</span>
              <span>{{ tenantIndustry.name }} Communication Hub</span>
            </div>
            <h2 class="text-xl md:text-2xl font-black tracking-tight text-white">
              BroadCast Message Center
            </h2>
            <p class="text-xs text-slate-300">
              Send mass announcements, updates, SMS, and WhatsApp alerts to leads, clients, and active stakeholders.
            </p>
          </div>

          <button
            @click="isComposeOpen = true"
            class="px-5 py-2.5 bg-white text-slate-900 hover:bg-slate-100 font-black text-xs rounded-2xl shadow-lg transition cursor-pointer"
          >
            + Create Campaign
          </button>
        </div>

        <!-- 4 Summary Metrics Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
          <div class="p-5 bg-white rounded-2xl border border-slate-200 shadow-xs space-y-1">
            <div class="text-xs font-bold text-slate-500 uppercase">Total Campaigns</div>
            <div class="text-3xl font-black text-slate-900">{{ metrics.total_broadcasts }}</div>
            <p class="text-xs text-indigo-600 font-semibold">Dispatched mass messages</p>
          </div>

          <div class="p-5 bg-white rounded-2xl border border-slate-200 shadow-xs space-y-1">
            <div class="text-xs font-bold text-slate-500 uppercase">WhatsApp Delivery Rate</div>
            <div class="text-3xl font-black text-emerald-600">{{ metrics.whatsapp_delivery_rate }}</div>
            <p class="text-xs text-emerald-600 font-semibold">Verified WhatsApp API</p>
          </div>

          <div class="p-5 bg-white rounded-2xl border border-slate-200 shadow-xs space-y-1">
            <div class="text-xs font-bold text-slate-500 uppercase">Email Open Rate</div>
            <div class="text-3xl font-black text-sky-600">{{ metrics.email_open_rate }}</div>
            <p class="text-xs text-sky-600 font-semibold">Verified email delivery</p>
          </div>

          <div class="p-5 bg-white rounded-2xl border border-slate-200 shadow-xs space-y-1">
            <div class="text-xs font-bold text-slate-500 uppercase">Total Audience Reach</div>
            <div class="text-3xl font-black text-rose-600">{{ metrics.total_audience_reach }}</div>
            <p class="text-xs text-rose-600 font-semibold">Target contacts</p>
          </div>
        </div>

        <!-- Broadcast Logs Table -->
        <div class="bg-white border border-slate-200 rounded-3xl overflow-hidden shadow-xs space-y-4">
          <div class="p-5 border-b border-slate-200 flex items-center justify-between">
            <h3 class="text-sm font-bold text-slate-900">Broadcast Campaign History</h3>
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
    <div v-if="isComposeOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
      <div class="bg-white border border-slate-200 rounded-3xl max-w-xl w-full shadow-2xl p-6 space-y-4">
        <h3 class="text-base font-black text-slate-900">Send New BroadCast Campaign</h3>
        <form @submit.prevent="submit" class="space-y-4">
          <div>
            <label class="text-xs font-bold text-slate-700 block mb-1">Campaign Title</label>
            <input v-model="form.title" type="text" placeholder="e.g. Policy Renewal Notice / Admissions Open" required class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2 text-xs font-medium" />
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="text-xs font-bold text-slate-700 block mb-1">Channel</label>
              <select v-model="form.channel" class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2 text-xs font-medium">
                <option value="WhatsApp">WhatsApp</option>
                <option value="SMS">SMS</option>
                <option value="Email">Email</option>
                <option value="All">All Channels</option>
              </select>
            </div>
            <div>
              <label class="text-xs font-bold text-slate-700 block mb-1">Target Segment</label>
              <select v-model="form.target_audience" class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2 text-xs font-medium">
                <option value="All Active Contacts">All Active Contacts</option>
                <option value="Qualified Leads">Qualified Leads</option>
                <option value="Enrolled / Active Clients">Enrolled / Active Clients</option>
              </select>
            </div>
          </div>

          <div>
            <label class="text-xs font-bold text-slate-700 block mb-1">Message Body</label>
            <textarea v-model="form.message_body" rows="4" required class="w-full bg-slate-50 border border-slate-300 rounded-xl p-3 text-xs font-medium"></textarea>
          </div>

          <div class="flex justify-end gap-3 pt-2">
            <button type="button" @click="isComposeOpen = false" class="px-4 py-2 text-xs font-bold text-slate-500 hover:bg-slate-100 rounded-xl transition cursor-pointer">Cancel</button>
            <button type="submit" class="px-5 py-2 bg-slate-900 hover:bg-slate-800 text-white text-xs font-black rounded-xl shadow-md cursor-pointer">Dispatch Broadcast</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Modals -->
    <GlobalSearchModal :is-open="isSearchOpen" @close="isSearchOpen = false" />
  </div>
</template>
