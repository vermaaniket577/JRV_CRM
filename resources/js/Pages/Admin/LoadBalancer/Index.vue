<script setup>
import { ref, computed } from 'vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import Navbar from '@/Components/Navbar.vue';
import GlobalSearchModal from '@/Components/GlobalSearchModal.vue';
import {
  ServerStackIcon,
  CpuChipIcon,
  ArrowPathIcon,
  PlusIcon,
  CheckCircleIcon,
  XCircleIcon,
  ExclamationTriangleIcon,
  ShieldCheckIcon,
  SignalIcon,
  AdjustmentsHorizontalIcon,
  ArrowDownTrayIcon,
  DocumentDuplicateIcon,
  PlayIcon,
  TrashIcon,
  PencilSquareIcon,
  ArrowLeftIcon,
  ClockIcon,
  CircleStackIcon,
  SparklesIcon,
  CommandLineIcon,
  CubeTransparentIcon,
  CheckIcon,
  WrenchScrewdriverIcon,
  EyeIcon,
  ArrowsRightLeftIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
  nodes: Array,
  stats: Object,
  algorithm: String,
  algorithms: Array,
  initialSimulation: Object,
  nginxConfig: String,
  haproxyConfig: String,
});

const isSearchOpen = ref(false);
const activeTab = ref('nodes'); // 'nodes', 'algorithm', 'simulator', 'gateway', 'configs'
const isAddModalOpen = ref(false);
const editingNode = ref(null);
const isCopyingNginx = ref(false);
const isCopyingHaproxy = ref(false);
const pingingNodeId = ref(null);

// Form for Adding / Editing Node
const nodeForm = useForm({
  name: '',
  host: '127.0.0.1',
  port: 8000,
  protocol: 'http',
  weight: 1,
  health_check_url: '/up',
  is_backup: false,
});

// Form for Algorithm Switch
const algorithmForm = useForm({
  algorithm: props.algorithm || 'round_robin',
});

// Simulation State
const simulationRequests = ref(100);
const simulationAlgo = ref(props.algorithm || 'round_robin');
const simulationResult = ref(props.initialSimulation || null);
const isSimulating = ref(false);

const goBack = () => {
  if (window.history.length > 1) {
    window.history.back();
  } else {
    router.visit('/admin');
  }
};

const openAddModal = () => {
  editingNode.value = null;
  nodeForm.reset();
  nodeForm.name = '';
  nodeForm.host = '127.0.0.1';
  nodeForm.port = 8000;
  nodeForm.protocol = 'http';
  nodeForm.weight = 1;
  nodeForm.health_check_url = '/up';
  nodeForm.is_backup = false;
  isAddModalOpen.value = true;
};

const openEditModal = (node) => {
  editingNode.value = node;
  nodeForm.name = node.name;
  nodeForm.host = node.host;
  nodeForm.port = node.port;
  nodeForm.protocol = node.protocol || 'http';
  nodeForm.weight = node.weight;
  nodeForm.health_check_url = node.health_check_url || '/up';
  nodeForm.is_backup = Boolean(node.is_backup);
  isAddModalOpen.value = true;
};

const submitNodeForm = () => {
  if (editingNode.value) {
    nodeForm.put(`/admin/load-balancer/nodes/${editingNode.value.id}`, {
      onSuccess: () => {
        isAddModalOpen.value = false;
        editingNode.value = null;
      },
    });
  } else {
    nodeForm.post('/admin/load-balancer/nodes', {
      onSuccess: () => {
        isAddModalOpen.value = false;
        nodeForm.reset();
      },
    });
  }
};

const deleteNode = (node) => {
  if (confirm(`Are you sure you want to remove upstream node '${node.name}' from the cluster pool?`)) {
    router.delete(`/admin/load-balancer/nodes/${node.id}`);
  }
};

const toggleDrain = (node) => {
  router.post(`/admin/load-balancer/nodes/${node.id}/drain`);
};

const toggleEnabled = (node) => {
  router.post(`/admin/load-balancer/nodes/${node.id}/toggle`);
};

const pingNode = (node) => {
  pingingNodeId.value = node.id;
  router.post(`/admin/load-balancer/nodes/${node.id}/ping`, {}, {
    onFinish: () => {
      pingingNodeId.value = null;
    }
  });
};

const probeAllNodes = () => {
  router.post('/admin/load-balancer/ping-all');
};

const selectAlgorithm = (algoId) => {
  algorithmForm.algorithm = algoId;
  algorithmForm.post('/admin/load-balancer/algorithm', {
    preserveScroll: true,
  });
};

const runSimulation = async () => {
  isSimulating.value = true;
  try {
    const response = await fetch('/admin/load-balancer/simulate', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
        'Accept': 'application/json',
      },
      body: JSON.stringify({
        requests: simulationRequests.value,
        algorithm: simulationAlgo.value,
      }),
    });
    if (response.ok) {
      simulationResult.value = await response.json();
    }
  } catch (err) {
    console.error('Simulation error:', err);
  } finally {
    isSimulating.value = false;
  }
};

const copyToClipboard = (text, type) => {
  navigator.clipboard.writeText(text);
  if (type === 'nginx') {
    isCopyingNginx.value = true;
    setTimeout(() => { isCopyingNginx.value = false; }, 2000);
  } else {
    isCopyingHaproxy.value = true;
    setTimeout(() => { isCopyingHaproxy.value = false; }, 2000);
  }
};

const getStatusBadge = (status, isEnabled) => {
  if (!isEnabled) {
    return { label: 'Disabled', bg: 'bg-slate-100 text-slate-600 border-slate-200', dot: 'bg-slate-400' };
  }
  switch (status) {
    case 'healthy':
      return { label: 'Healthy', bg: 'bg-emerald-50 text-emerald-700 border-emerald-200', dot: 'bg-emerald-500' };
    case 'degraded':
      return { label: 'Degraded', bg: 'bg-amber-50 text-amber-700 border-amber-200', dot: 'bg-amber-500' };
    case 'maintenance':
      return { label: 'Drain Mode', bg: 'bg-purple-50 text-purple-700 border-purple-200', dot: 'bg-purple-500' };
    case 'offline':
    default:
      return { label: 'Offline', bg: 'bg-rose-50 text-rose-700 border-rose-200', dot: 'bg-rose-500' };
  }
};
</script>

<template>
  <Head title="Software Load Balancer & Cluster Controller" />

  <div class="min-h-screen bg-slate-50/80 flex font-sans text-slate-900">
    <!-- Navbar (Fixed Left Vertical Sidebar) -->
    <Navbar @open-search="isSearchOpen = true" />

    <!-- Main Content Area -->
    <main class="flex-1 p-6 md:p-8 space-y-6 overflow-y-auto max-w-7xl mx-auto w-full">
      <!-- Top Title & Controls Header -->
      <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-slate-200/90 pb-6">
        <div class="space-y-1.5">
          <div class="flex items-center gap-2">
            <button 
              @click="goBack"
              class="px-3 py-1 bg-white hover:bg-slate-100 border border-slate-200 text-slate-700 font-bold text-xs rounded-xl shadow-2xs flex items-center gap-1.5 cursor-pointer transition-all whitespace-nowrap"
            >
              <ArrowLeftIcon class="w-3.5 h-3.5 text-slate-500" />
              <span>Back</span>
            </button>
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-50 border border-emerald-200 text-xs font-bold text-emerald-700">
              <span class="w-2 h-2 rounded-full bg-emerald-500 animate-ping"></span>
              <span>Layer 7 Software Load Balancer Online</span>
            </div>
          </div>
          <h1 class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight flex items-center gap-3">
            <span>Cluster Load Balancer & Traffic Controller</span>
          </h1>
          <p class="text-slate-500 text-sm font-medium">
            Dynamic request distribution, real-time node health monitoring, active connection tracking, and instant multi-node scaling.
          </p>
        </div>

        <!-- Header Actions -->
        <div class="flex flex-wrap items-center gap-2.5">
          <button 
            @click="probeAllNodes"
            class="px-3.5 py-2 bg-white hover:bg-slate-50 border border-slate-200 text-slate-700 font-extrabold text-xs rounded-xl shadow-2xs flex items-center gap-1.5 transition-all cursor-pointer whitespace-nowrap"
            title="Probe health across all upstream nodes"
          >
            <ArrowPathIcon class="w-4 h-4 text-slate-500" />
            <span>Probe All Nodes</span>
          </button>

          <button 
            @click="openAddModal"
            class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-extrabold text-xs rounded-xl shadow-xs shadow-red-600/20 flex items-center gap-1.5 transition-all cursor-pointer whitespace-nowrap"
          >
            <PlusIcon class="w-4 h-4 stroke-[3]" />
            <span>Add Upstream Node</span>
          </button>
        </div>
      </div>

      <!-- Cluster Key Metric Cards -->
      <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3.5">
        <!-- Total Nodes -->
        <div class="p-4 rounded-2xl bg-white border border-slate-200/90 shadow-2xs space-y-1">
          <div class="flex items-center justify-between">
            <span class="text-[10px] font-black uppercase tracking-wider text-slate-400">Total Nodes</span>
            <ServerStackIcon class="w-4 h-4 text-red-600" />
          </div>
          <div class="text-2xl font-black text-slate-900 tracking-tight">{{ stats.total_nodes }}</div>
          <div class="text-[11px] font-bold text-slate-500 flex items-center gap-1.5">
            <span class="text-emerald-600">{{ stats.healthy_nodes }} Healthy</span>
            <span>•</span>
            <span class="text-slate-400">{{ stats.maintenance_nodes }} Drain</span>
          </div>
        </div>

        <!-- Healthy Nodes Ratio -->
        <div class="p-4 rounded-2xl bg-white border border-slate-200/90 shadow-2xs space-y-1">
          <div class="flex items-center justify-between">
            <span class="text-[10px] font-black uppercase tracking-wider text-slate-400">Cluster Health</span>
            <ShieldCheckIcon class="w-4 h-4 text-emerald-600" />
          </div>
          <div class="text-2xl font-black text-emerald-600 tracking-tight">
            {{ stats.total_nodes > 0 ? Math.round((stats.healthy_nodes / stats.total_nodes) * 100) : 0 }}%
          </div>
          <div class="text-[11px] font-bold text-slate-500">
            {{ stats.healthy_nodes }}/{{ stats.total_nodes }} Online
          </div>
        </div>

        <!-- Active Connections -->
        <div class="p-4 rounded-2xl bg-white border border-slate-200/90 shadow-2xs space-y-1">
          <div class="flex items-center justify-between">
            <span class="text-[10px] font-black uppercase tracking-wider text-slate-400">Active Conns</span>
            <SignalIcon class="w-4 h-4 text-sky-600" />
          </div>
          <div class="text-2xl font-black text-slate-900 tracking-tight">{{ stats.active_connections }}</div>
          <div class="text-[11px] font-bold text-slate-500">Live Web Requests</div>
        </div>

        <!-- Average Latency -->
        <div class="p-4 rounded-2xl bg-white border border-slate-200/90 shadow-2xs space-y-1">
          <div class="flex items-center justify-between">
            <span class="text-[10px] font-black uppercase tracking-wider text-slate-400">Avg Latency</span>
            <ClockIcon class="w-4 h-4 text-indigo-600" />
          </div>
          <div class="text-2xl font-black text-slate-900 tracking-tight">{{ stats.avg_latency_ms }}<span class="text-xs text-slate-400 font-bold ml-0.5">ms</span></div>
          <div class="text-[11px] font-bold text-emerald-600">Sub-millisecond Edge</div>
        </div>

        <!-- Total Routed Requests -->
        <div class="p-4 rounded-2xl bg-white border border-slate-200/90 shadow-2xs space-y-1">
          <div class="flex items-center justify-between">
            <span class="text-[10px] font-black uppercase tracking-wider text-slate-400">Routed Requests</span>
            <CubeTransparentIcon class="w-4 h-4 text-amber-600" />
          </div>
          <div class="text-2xl font-black text-slate-900 tracking-tight">{{ Number(stats.total_requests).toLocaleString() }}</div>
          <div class="text-[11px] font-bold text-emerald-600">{{ stats.success_rate }}% Success</div>
        </div>

        <!-- Active Strategy -->
        <div class="p-4 rounded-2xl bg-white border border-slate-200/90 shadow-2xs space-y-1">
          <div class="flex items-center justify-between">
            <span class="text-[10px] font-black uppercase tracking-wider text-slate-400">Strategy</span>
            <AdjustmentsHorizontalIcon class="w-4 h-4 text-purple-600" />
          </div>
          <div class="text-sm font-black text-purple-700 tracking-tight capitalize truncate pt-1">
            {{ (algorithm || 'round_robin').replace(/_/g, ' ') }}
          </div>
          <div class="text-[10px] font-bold text-slate-500">Traffic Balancer</div>
        </div>
      </div>

      <!-- Navigation Tabs -->
      <div class="flex items-center gap-1.5 border-b border-slate-200 overflow-x-auto pb-px">
        <button
          @click="activeTab = 'nodes'"
          :class="[
            'px-4 py-2.5 text-xs font-bold rounded-t-xl transition-all border-b-2 flex items-center gap-2 cursor-pointer whitespace-nowrap',
            activeTab === 'nodes'
              ? 'border-red-600 text-red-600 bg-white font-extrabold'
              : 'border-transparent text-slate-600 hover:text-slate-900 hover:bg-slate-100/60'
          ]"
        >
          <ServerStackIcon class="w-4 h-4" />
          <span>Upstream Nodes Pool ({{ nodes.length }})</span>
        </button>

        <button
          @click="activeTab = 'algorithm'"
          :class="[
            'px-4 py-2.5 text-xs font-bold rounded-t-xl transition-all border-b-2 flex items-center gap-2 cursor-pointer whitespace-nowrap',
            activeTab === 'algorithm'
              ? 'border-red-600 text-red-600 bg-white font-extrabold'
              : 'border-transparent text-slate-600 hover:text-slate-900 hover:bg-slate-100/60'
          ]"
        >
          <AdjustmentsHorizontalIcon class="w-4 h-4" />
          <span>Balancing Strategy</span>
        </button>

        <button
          @click="activeTab = 'simulator'"
          :class="[
            'px-4 py-2.5 text-xs font-bold rounded-t-xl transition-all border-b-2 flex items-center gap-2 cursor-pointer whitespace-nowrap',
            activeTab === 'simulator'
              ? 'border-red-600 text-red-600 bg-white font-extrabold'
              : 'border-transparent text-slate-600 hover:text-slate-900 hover:bg-slate-100/60'
          ]"
        >
          <PlayIcon class="w-4 h-4" />
          <span>Live Traffic Simulator</span>
        </button>

        <button
          @click="activeTab = 'gateway'"
          :class="[
            'px-4 py-2.5 text-xs font-bold rounded-t-xl transition-all border-b-2 flex items-center gap-2 cursor-pointer whitespace-nowrap',
            activeTab === 'gateway'
              ? 'border-red-600 text-red-600 bg-white font-extrabold'
              : 'border-transparent text-slate-600 hover:text-slate-900 hover:bg-slate-100/60'
          ]"
        >
          <ArrowsRightLeftIcon class="w-4 h-4" />
          <span>Reverse Proxy Gateway</span>
        </button>

        <button
          @click="activeTab = 'configs'"
          :class="[
            'px-4 py-2.5 text-xs font-bold rounded-t-xl transition-all border-b-2 flex items-center gap-2 cursor-pointer whitespace-nowrap',
            activeTab === 'configs'
              ? 'border-red-600 text-red-600 bg-white font-extrabold'
              : 'border-transparent text-slate-600 hover:text-slate-900 hover:bg-slate-100/60'
          ]"
        >
          <CommandLineIcon class="w-4 h-4" />
          <span>Export NGINX & HAProxy Config</span>
        </button>
      </div>

      <!-- TAB 1: UPSTREAM NODES POOL -->
      <div v-if="activeTab === 'nodes'" class="space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
          <div 
            v-for="node in nodes" 
            :key="node.id"
            class="bg-white rounded-2xl border border-slate-200/90 shadow-2xs hover:shadow-xs transition-all p-5 space-y-4 relative overflow-hidden"
          >
            <!-- Top Status Indicator Strip -->
            <div 
              class="absolute top-0 left-0 right-0 h-1"
              :class="node.status === 'healthy' ? 'bg-emerald-500' : (node.status === 'degraded' ? 'bg-amber-500' : (node.status === 'maintenance' ? 'bg-purple-500' : 'bg-rose-500'))"
            ></div>

            <div class="flex items-start justify-between gap-2">
              <div class="space-y-1 min-w-0">
                <div class="flex items-center gap-2 flex-wrap">
                  <h3 class="text-base font-black text-slate-900 tracking-tight truncate">{{ node.name }}</h3>
                  <span v-if="node.is_backup" class="px-2 py-0.5 rounded-full bg-slate-100 text-slate-600 text-[10px] font-bold border border-slate-200">
                    Standby Backup
                  </span>
                </div>
                <div class="flex items-center gap-1.5 text-xs font-mono text-slate-500">
                  <span class="font-bold text-red-600">{{ node.protocol }}://</span>
                  <span>{{ node.host }}:{{ node.port }}</span>
                </div>
              </div>

              <!-- Status Badge -->
              <span 
                :class="[
                  'px-2.5 py-1 rounded-full text-xs font-extrabold border flex items-center gap-1.5 shrink-0',
                  getStatusBadge(node.status, node.is_enabled).bg
                ]"
              >
                <span :class="['w-2 h-2 rounded-full', getStatusBadge(node.status, node.is_enabled).dot]"></span>
                <span>{{ getStatusBadge(node.status, node.is_enabled).label }}</span>
              </span>
            </div>

            <!-- Health & Connection Metrics Grid -->
            <div class="grid grid-cols-3 gap-2 p-3 rounded-xl bg-slate-50 border border-slate-100 text-xs">
              <div>
                <div class="text-[10px] font-bold text-slate-400 uppercase">Weight</div>
                <div class="font-black text-slate-800 text-sm mt-0.5">{{ node.weight }}x</div>
              </div>
              <div>
                <div class="text-[10px] font-bold text-slate-400 uppercase">Live Conns</div>
                <div class="font-black text-slate-800 text-sm mt-0.5">{{ node.active_connections }}</div>
              </div>
              <div>
                <div class="text-[10px] font-bold text-slate-400 uppercase">Latency</div>
                <div class="font-black text-slate-800 text-sm mt-0.5">{{ node.avg_latency_ms }} ms</div>
              </div>
            </div>

            <!-- Traffic Stats Row -->
            <div class="flex items-center justify-between text-xs text-slate-500 pt-1 border-t border-slate-100">
              <span>Total Requests: <strong class="text-slate-800">{{ Number(node.total_requests).toLocaleString() }}</strong></span>
              <span>Success: <strong class="text-emerald-700">{{ node.success_rate }}%</strong></span>
            </div>

            <!-- Error Notice if Any -->
            <div v-if="node.last_error" class="p-2 rounded-lg bg-rose-50 border border-rose-200 text-rose-700 text-[11px] font-mono line-clamp-1">
              {{ node.last_error }}
            </div>

            <!-- Action Buttons Footer -->
            <div class="flex items-center justify-between gap-1.5 pt-2 border-t border-slate-100">
              <button 
                @click="pingNode(node)"
                :disabled="pingingNodeId === node.id"
                class="px-2.5 py-1.5 bg-slate-50 hover:bg-slate-100 border border-slate-200 rounded-lg text-xs font-bold text-slate-700 flex items-center gap-1 transition cursor-pointer"
                title="Send instant HTTP/TCP health probe"
              >
                <ArrowPathIcon :class="['w-3.5 h-3.5 text-slate-500', pingingNodeId === node.id ? 'animate-spin text-red-600' : '']" />
                <span>{{ pingingNodeId === node.id ? 'Probing...' : 'Ping' }}</span>
              </button>

              <button 
                @click="toggleDrain(node)"
                :class="[
                  'px-2.5 py-1.5 rounded-lg text-xs font-bold border transition cursor-pointer flex items-center gap-1',
                  node.status === 'maintenance'
                    ? 'bg-emerald-50 text-emerald-700 border-emerald-200 hover:bg-emerald-100'
                    : 'bg-purple-50 text-purple-700 border-purple-200 hover:bg-purple-100'
                ]"
                :title="node.status === 'maintenance' ? 'Exit Drain Mode & Resume Traffic' : 'Drain Mode: Stop sending new requests'"
              >
                <WrenchScrewdriverIcon class="w-3.5 h-3.5" />
                <span>{{ node.status === 'maintenance' ? 'Resume' : 'Drain' }}</span>
              </button>

              <div class="flex items-center gap-1 ml-auto">
                <button 
                  @click="openEditModal(node)"
                  class="p-1.5 hover:bg-slate-100 rounded-lg text-slate-500 hover:text-slate-800 transition cursor-pointer"
                  title="Edit Node Settings"
                >
                  <PencilSquareIcon class="w-4 h-4" />
                </button>
                <button 
                  @click="deleteNode(node)"
                  class="p-1.5 hover:bg-red-50 rounded-lg text-slate-400 hover:text-red-600 transition cursor-pointer"
                  title="Delete Node from Cluster"
                >
                  <TrashIcon class="w-4 h-4" />
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- TAB 2: BALANCING STRATEGY -->
      <div v-if="activeTab === 'algorithm'" class="space-y-6">
        <div class="bg-white rounded-2xl border border-slate-200/90 shadow-2xs p-6 space-y-4">
          <div>
            <h2 class="text-xl font-black text-slate-900 tracking-tight">Active Load Balancing Strategy</h2>
            <p class="text-slate-500 text-xs font-medium mt-0.5">
              Select how the software load balancer schedules and distributes incoming user requests across healthy backend nodes.
            </p>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 pt-2">
            <div 
              v-for="algo in algorithms" 
              :key="algo.id"
              @click="selectAlgorithm(algo.id)"
              :class="[
                'p-5 rounded-2xl border-2 transition-all cursor-pointer space-y-3 relative',
                algorithm === algo.id
                  ? 'border-red-600 bg-red-50/40 shadow-xs'
                  : 'border-slate-200 hover:border-slate-300 bg-white hover:bg-slate-50/50'
              ]"
            >
              <div class="flex items-center justify-between">
                <span class="text-sm font-black text-slate-900">{{ algo.name }}</span>
                <span 
                  v-if="algorithm === algo.id"
                  class="w-5 h-5 rounded-full bg-red-600 text-white flex items-center justify-center"
                >
                  <CheckIcon class="w-3.5 h-3.5 stroke-[3]" />
                </span>
              </div>

              <p class="text-xs text-slate-600 font-medium leading-relaxed">
                {{ algo.description }}
              </p>

              <div class="pt-2 border-t border-slate-100 text-[11px] text-slate-500">
                <span class="font-bold text-slate-700">Best for:</span> {{ algo.ideal_for }}
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- TAB 3: LIVE TRAFFIC SIMULATOR -->
      <div v-if="activeTab === 'simulator'" class="space-y-6">
        <div class="bg-white rounded-2xl border border-slate-200/90 shadow-2xs p-6 space-y-6">
          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-100 pb-4">
            <div>
              <h2 class="text-xl font-black text-slate-900 tracking-tight">Real-Time Traffic Distribution Simulator</h2>
              <p class="text-slate-500 text-xs font-medium mt-0.5">
                Stress-test your load balancing algorithm by dispatching simulated requests and watching real-time node allocation percentages.
              </p>
            </div>

            <!-- Simulator Controls -->
            <div class="flex items-center gap-3">
              <select 
                v-model="simulationAlgo"
                class="px-3 py-1.5 text-xs font-bold bg-white border border-slate-200 rounded-xl text-slate-700 focus:ring-2 focus:ring-red-500 outline-hidden"
              >
                <option v-for="a in algorithms" :key="a.id" :value="a.id">{{ a.name }}</option>
              </select>

              <select 
                v-model.number="simulationRequests"
                class="px-3 py-1.5 text-xs font-bold bg-white border border-slate-200 rounded-xl text-slate-700 focus:ring-2 focus:ring-red-500 outline-hidden"
              >
                <option :value="50">50 Requests</option>
                <option :value="100">100 Requests</option>
                <option :value="250">250 Requests</option>
                <option :value="500">500 Requests</option>
                <option :value="1000">1,000 Requests</option>
              </select>

              <button 
                @click="runSimulation"
                :disabled="isSimulating"
                class="px-4 py-1.5 bg-red-600 hover:bg-red-700 text-white font-extrabold text-xs rounded-xl shadow-xs flex items-center gap-1.5 transition cursor-pointer"
              >
                <PlayIcon class="w-3.5 h-3.5 fill-current" />
                <span>{{ isSimulating ? 'Simulating...' : 'Run Simulation' }}</span>
              </button>
            </div>
          </div>

          <!-- Simulation Visual Progress Bars -->
          <div v-if="simulationResult && simulationResult.distribution" class="space-y-4">
            <div 
              v-for="item in simulationResult.distribution" 
              :key="item.id"
              class="p-4 rounded-xl bg-slate-50 border border-slate-200/80 space-y-2"
            >
              <div class="flex items-center justify-between text-xs">
                <div class="flex items-center gap-2">
                  <span class="font-bold text-slate-900">{{ item.name }}</span>
                  <span class="text-slate-400 font-mono text-[11px]">({{ item.host }}:{{ item.port }})</span>
                  <span class="px-1.5 py-0.5 rounded-md bg-white border border-slate-200 text-[10px] font-bold text-slate-600">Weight: {{ item.weight }}x</span>
                </div>
                <div class="font-extrabold text-slate-900">
                  <span class="text-red-600 text-sm font-black">{{ item.allocated_requests }}</span> / {{ simulationResult.total_requests }} reqs
                  <span class="text-slate-400 ml-1">({{ item.percentage }}%)</span>
                </div>
              </div>

              <!-- Animated Load Bar -->
              <div class="w-full bg-slate-200 h-3 rounded-full overflow-hidden">
                <div 
                  class="h-full bg-gradient-to-r from-red-500 to-red-600 transition-all duration-500 rounded-full"
                  :style="{ width: item.percentage + '%' }"
                ></div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- TAB 4: REVERSE PROXY GATEWAY -->
      <div v-if="activeTab === 'gateway'" class="space-y-6">
        <div class="bg-white rounded-2xl border border-slate-200/90 shadow-2xs p-6 space-y-4">
          <div>
            <h2 class="text-xl font-black text-slate-900 tracking-tight">Built-in Software Layer 7 Reverse Proxy Gateway</h2>
            <p class="text-slate-500 text-xs font-medium mt-0.5">
              This JRV CRM instance itself can receive external HTTP traffic and forward requests across your upstream server cluster.
            </p>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-2">
            <div class="p-4 rounded-xl bg-slate-900 text-slate-100 font-mono text-xs space-y-3">
              <div class="text-slate-400 uppercase text-[10px] tracking-wider font-bold">Proxy Gateway Endpoints</div>
              <div class="space-y-1">
                <div class="text-emerald-400">ANY /lb/gateway</div>
                <p class="text-slate-400 text-[11px]">Directs incoming root traffic to chosen backend node preserving all HTTP headers.</p>
              </div>
              <div class="space-y-1 pt-2 border-t border-slate-800">
                <div class="text-emerald-400">ANY /lb/proxy/{any_path}</div>
                <p class="text-slate-400 text-[11px]">Proxies full URI path e.g. <code class="text-amber-400">/lb/proxy/api/v1/leads</code>.</p>
              </div>
              <div class="space-y-1 pt-2 border-t border-slate-800">
                <div class="text-emerald-400">GET /lb/status</div>
                <p class="text-slate-400 text-[11px]">Real-time JSON monitoring endpoint for external uptime probes.</p>
              </div>
            </div>

            <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 space-y-3 text-xs">
              <div class="text-slate-500 uppercase text-[10px] tracking-wider font-bold">Sample cURL Test Request</div>
              <pre class="p-3 bg-white rounded-lg border border-slate-200 font-mono text-[11px] text-slate-800 overflow-x-auto">curl -i http://localhost:8080/lb/gateway</pre>
              <div class="text-slate-500 text-[11px] space-y-1">
                <p class="font-bold text-slate-700">Injected Response Headers:</p>
                <div class="font-mono text-[10px] space-y-0.5 text-slate-600">
                  <div>X-Load-Balancer: JRV-Cluster-Engine</div>
                  <div>X-Upstream-Node: Scale Worker Node (Node-2)</div>
                  <div>X-Upstream-Latency: 12.4ms</div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- TAB 5: PRODUCTION CONFIGS -->
      <div v-if="activeTab === 'configs'" class="space-y-6">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
          <!-- NGINX Config Card -->
          <div class="bg-white rounded-2xl border border-slate-200/90 shadow-2xs p-6 space-y-4">
            <div class="flex items-center justify-between">
              <div>
                <h3 class="text-lg font-black text-slate-900 tracking-tight">Production NGINX Configuration</h3>
                <p class="text-slate-500 text-xs font-medium">Auto-generated upstream & server block for NGINX edge proxy</p>
              </div>
              <div class="flex items-center gap-2">
                <button 
                  @click="copyToClipboard(nginxConfig, 'nginx')"
                  class="px-3 py-1.5 bg-slate-50 hover:bg-slate-100 border border-slate-200 rounded-xl text-xs font-bold text-slate-700 flex items-center gap-1.5 transition cursor-pointer"
                >
                  <DocumentDuplicateIcon class="w-3.5 h-3.5 text-slate-500" />
                  <span>{{ isCopyingNginx ? 'Copied!' : 'Copy' }}</span>
                </button>
                <a 
                  href="/admin/load-balancer/export?type=nginx" 
                  class="px-3 py-1.5 bg-red-50 hover:bg-red-100 border border-red-200 rounded-xl text-xs font-bold text-red-700 flex items-center gap-1.5 transition"
                >
                  <ArrowDownTrayIcon class="w-3.5 h-3.5" />
                  <span>Download</span>
                </a>
              </div>
            </div>
            <pre class="p-4 bg-slate-900 text-slate-100 rounded-xl font-mono text-[11px] overflow-x-auto max-h-96">{{ nginxConfig }}</pre>
          </div>

          <!-- HAProxy Config Card -->
          <div class="bg-white rounded-2xl border border-slate-200/90 shadow-2xs p-6 space-y-4">
            <div class="flex items-center justify-between">
              <div>
                <h3 class="text-lg font-black text-slate-900 tracking-tight">Production HAProxy Configuration</h3>
                <p class="text-slate-500 text-xs font-medium">Auto-generated frontend & backend config for HAProxy</p>
              </div>
              <div class="flex items-center gap-2">
                <button 
                  @click="copyToClipboard(haproxyConfig, 'haproxy')"
                  class="px-3 py-1.5 bg-slate-50 hover:bg-slate-100 border border-slate-200 rounded-xl text-xs font-bold text-slate-700 flex items-center gap-1.5 transition cursor-pointer"
                >
                  <DocumentDuplicateIcon class="w-3.5 h-3.5 text-slate-500" />
                  <span>{{ isCopyingHaproxy ? 'Copied!' : 'Copy' }}</span>
                </button>
                <a 
                  href="/admin/load-balancer/export?type=haproxy" 
                  class="px-3 py-1.5 bg-red-50 hover:bg-red-100 border border-red-200 rounded-xl text-xs font-bold text-red-700 flex items-center gap-1.5 transition"
                >
                  <ArrowDownTrayIcon class="w-3.5 h-3.5" />
                  <span>Download</span>
                </a>
              </div>
            </div>
            <pre class="p-4 bg-slate-900 text-slate-100 rounded-xl font-mono text-[11px] overflow-x-auto max-h-96">{{ haproxyConfig }}</pre>
          </div>
        </div>
      </div>
    </main>

    <!-- Modal: Add / Edit Node -->
    <div 
      v-if="isAddModalOpen" 
      class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs"
    >
      <div class="bg-white rounded-2xl border border-slate-200 shadow-xl max-w-lg w-full p-6 space-y-5">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
          <h3 class="text-lg font-black text-slate-900 tracking-tight">
            {{ editingNode ? 'Edit Upstream Node' : 'Add Upstream Cluster Node' }}
          </h3>
          <button 
            @click="isAddModalOpen = false" 
            class="w-7 h-7 rounded-lg hover:bg-slate-100 flex items-center justify-center text-slate-400 hover:text-slate-700 cursor-pointer"
          >
            ✕
          </button>
        </div>

        <form @submit.prevent="submitNodeForm" class="space-y-4 text-xs">
          <!-- Node Name -->
          <div class="space-y-1">
            <label class="font-bold text-slate-700">Node Friendly Name</label>
            <input 
              v-model="nodeForm.name"
              type="text" 
              required 
              placeholder="e.g. EU Worker Node 3"
              class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium focus:ring-2 focus:ring-red-500 outline-hidden"
            />
          </div>

          <!-- Protocol, Host, Port -->
          <div class="grid grid-cols-3 gap-2">
            <div class="space-y-1">
              <label class="font-bold text-slate-700">Protocol</label>
              <select 
                v-model="nodeForm.protocol"
                class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium focus:ring-2 focus:ring-red-500 outline-hidden"
              >
                <option value="http">http</option>
                <option value="https">https</option>
              </select>
            </div>
            <div class="space-y-1">
              <label class="font-bold text-slate-700">Host / IP</label>
              <input 
                v-model="nodeForm.host"
                type="text" 
                required 
                placeholder="127.0.0.1"
                class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium focus:ring-2 focus:ring-red-500 outline-hidden"
              />
            </div>
            <div class="space-y-1">
              <label class="font-bold text-slate-700">Port</label>
              <input 
                v-model.number="nodeForm.port"
                type="number" 
                required 
                min="1" 
                max="65535" 
                placeholder="8000"
                class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium focus:ring-2 focus:ring-red-500 outline-hidden"
              />
            </div>
          </div>

          <!-- Weight & Health Check URL -->
          <div class="grid grid-cols-2 gap-3">
            <div class="space-y-1">
              <label class="font-bold text-slate-700">Capacity Weight (1-100)</label>
              <input 
                v-model.number="nodeForm.weight"
                type="number" 
                required 
                min="1" 
                max="100" 
                class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium focus:ring-2 focus:ring-red-500 outline-hidden"
              />
            </div>
            <div class="space-y-1">
              <label class="font-bold text-slate-700">Health Check URL</label>
              <input 
                v-model="nodeForm.health_check_url"
                type="text" 
                placeholder="/up or /api/health"
                class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-medium focus:ring-2 focus:ring-red-500 outline-hidden"
              />
            </div>
          </div>

          <!-- Standby Checkbox -->
          <div class="flex items-center gap-2 pt-1">
            <input 
              v-model="nodeForm.is_backup"
              id="is_backup_cb"
              type="checkbox"
              class="w-4 h-4 text-red-600 rounded border-slate-300 focus:ring-red-500"
            />
            <label for="is_backup_cb" class="text-xs font-bold text-slate-700 cursor-pointer">
              Mark as Standby Failover Node (only receives traffic if all primary nodes fail)
            </label>
          </div>

          <!-- Footer Buttons -->
          <div class="flex items-center justify-end gap-2 pt-4 border-t border-slate-100">
            <button 
              type="button" 
              @click="isAddModalOpen = false" 
              class="px-4 py-2 border border-slate-200 rounded-xl font-bold text-slate-600 hover:bg-slate-50 transition cursor-pointer"
            >
              Cancel
            </button>
            <button 
              type="submit" 
              :disabled="nodeForm.processing"
              class="px-5 py-2 bg-red-600 hover:bg-red-700 text-white font-extrabold rounded-xl shadow-xs transition cursor-pointer"
            >
              {{ editingNode ? 'Update Node' : 'Save Node' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Global Search Modal -->
    <GlobalSearchModal 
      :is-open="isSearchOpen" 
      @close="isSearchOpen = false" 
    />
  </div>
</template>
