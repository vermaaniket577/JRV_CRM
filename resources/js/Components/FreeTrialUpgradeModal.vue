<script setup>
import { ref, computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { 
  SparklesIcon, 
  XMarkIcon, 
  CheckIcon, 
  RocketLaunchIcon,
  CloudIcon,
  ShieldCheckIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
  isOpen: {
    type: Boolean,
    default: false
  },
  usedGb: {
    type: Number,
    default: 1.25
  },
  limitGb: {
    type: Number,
    default: 5.0
  }
});

const emit = defineEmits(['close']);
const page = usePage();

const selectedPlan = ref('Growth');
const isProcessing = ref(false);
const billingCycle = ref('monthly');

const plans = computed(() => {
  const isAnnual = billingCycle.value === 'annually';

  if (page.props.crm_plans && page.props.crm_plans.length > 0) {
    return page.props.crm_plans.map((p, index) => {
      let parsedFeatures = [];
      try {
        parsedFeatures = typeof p.features === 'string' ? JSON.parse(p.features) : (p.features || []);
      } catch (e) {
        parsedFeatures = [];
      }

      if (!parsedFeatures || parsedFeatures.length === 0) {
        parsedFeatures = [
          `${p.storage_limit_gb >= 999 ? 'Unlimited' : p.storage_limit_gb + ' GB'} Cloud Storage Space`,
          `${p.max_users >= 999 ? 'Unlimited' : 'Up to ' + p.max_users} Team User Seats`,
          'Lead & Contact Pipeline Management',
          'Task Tracking & Email Notifications',
          '24/7 Support Guarantee'
        ];
      }

      const rawMonthlyPrice = Number(p.price_monthly || 0);
      const rawAnnualPrice = Number(p.price_annual || 0);
      const effectiveMonthlyPrice = isAnnual 
        ? (rawAnnualPrice > 0 ? rawAnnualPrice : Math.round(rawMonthlyPrice * 0.8))
        : rawMonthlyPrice;
      const formattedPrice = '₹' + effectiveMonthlyPrice.toLocaleString('en-IN');
      const annualTotal = '₹' + (effectiveMonthlyPrice * 12).toLocaleString('en-IN') + '/yr';

      return {
        id: p.id,
        name: p.name,
        tier: p.name,
        price: formattedPrice,
        period: isAnnual ? '/mo' : '/month',
        annualSubtext: isAnnual ? `Billed as ${annualTotal}` : null,
        storage: (p.storage_limit_gb >= 999 ? 'Unlimited Storage' : `${p.storage_limit_gb} GB Storage`),
        users: (p.max_users >= 999 ? 'Unlimited Users' : `Up to ${p.max_users} Users`),
        features: parsedFeatures,
        popular: p.slug === 'growth' || p.slug === 'pro' || index === 1,
      };
    });
  }

  return [
    {
      name: 'Starter Plan',
      tier: 'Starter',
      price: isAnnual ? '₹3,999' : '₹4,999',
      period: isAnnual ? '/mo' : '/month',
      annualSubtext: isAnnual ? 'Billed as ₹47,988/yr' : null,
      storage: '25 GB Storage',
      users: 'Up to 5 Users',
      features: [
        '25 GB Cloud Storage Space',
        'Up to 5 Team User Seats',
        'Lead & Contact Pipeline Management',
        'Task Tracking & Email Notifications',
        'Standard Support'
      ],
      popular: false,
    },
    {
      name: 'Growth Plan',
      tier: 'Growth',
      price: isAnnual ? '₹11,999' : '₹14,999',
      period: isAnnual ? '/mo' : '/month',
      annualSubtext: isAnnual ? 'Billed as ₹1,43,988/yr' : null,
      storage: '100 GB Storage',
      users: 'Up to 20 Users',
      features: [
        '100 GB Cloud Storage Space',
        'Up to 20 Team User Seats',
        'Custom Industry Sector Workflows',
        'Automated Task & WhatsApp Broadcasts',
        'Revenue Analytics & Export Reports',
        'Priority 24/7 Phone Support'
      ],
      popular: true,
    },
    {
      name: 'Enterprise Plan',
      tier: 'Enterprise',
      price: isAnnual ? '₹31,999' : '₹39,999',
      period: isAnnual ? '/mo' : '/month',
      annualSubtext: isAnnual ? 'Billed as ₹3,83,988/yr' : null,
      storage: 'Unlimited Storage',
      users: 'Unlimited Users',
      features: [
        'Unlimited Cloud Storage Space',
        'Unlimited Team User Seats',
        'Dedicated Instance & SLA Guarantee',
        'Custom API & Webhook Integrations',
        'Multi-Branch & Role Permissions',
        'Dedicated Account Manager'
      ],
      popular: false,
    }
  ];
});

const handleUpgrade = (tier) => {
  selectedPlan.value = tier;
  isProcessing.value = true;
  setTimeout(() => {
    isProcessing.value = false;
    alert(`Thank you! Order logged for ${tier} Plan. Our onboarding team will activate your paid instance immediately.`);
    emit('close');
  }, 1000);
};
</script>

<template>
  <Teleport to="body">
    <div v-if="isOpen" class="fixed inset-0 z-50 overflow-y-auto">
      <!-- Backdrop -->
      <div 
        @click="emit('close')" 
        class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity"
      ></div>
      <div class="flex min-h-full items-center justify-center p-3 text-center">
        <div class="relative w-full max-w-3xl transform overflow-hidden rounded-3xl bg-white text-left align-middle shadow-2xl transition-all border border-slate-100 my-4">
          
          <!-- Modal Header Banner -->
          <div class="bg-gradient-to-r from-red-600 via-red-700 to-slate-900 p-4 sm:p-5 text-white relative">
            <button 
              @click="emit('close')"
              class="absolute top-3.5 right-3.5 text-white/80 hover:text-white p-1.5 rounded-full hover:bg-white/10 transition-colors cursor-pointer"
            >
              <XMarkIcon class="w-5 h-5" />
            </button>

            <div class="flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-white/20 text-white text-[10px] font-extrabold w-fit mb-2 backdrop-blur-xs">
              <SparklesIcon class="w-3.5 h-3.5 text-amber-300" />
              <span>⚡ Free Trial Active (5 GB Space)</span>
            </div>

            <h3 class="text-xl sm:text-2xl font-black tracking-tight text-white">
              Upgrade Your CRM Subscription Plan
            </h3>
            <p class="text-xs text-red-100 mt-0.5 max-w-xl font-medium leading-relaxed">
              5 GB Trial limit. Upgrade anytime to unlock higher cloud storage, team user seats, and sector automation.
            </p>

            <!-- Storage Usage Pill -->
            <div class="mt-3 inline-flex items-center gap-2.5 px-3 py-1.5 rounded-xl bg-white/10 border border-white/20 text-[11px] font-bold backdrop-blur-xs">
              <CloudIcon class="w-3.5 h-3.5 text-red-300 shrink-0" />
              <span>Storage Used: <strong>{{ usedGb }} / {{ limitGb }} GB</strong></span>
              <div class="w-20 bg-white/20 h-1.5 rounded-full overflow-hidden shrink-0">
                <div class="bg-amber-300 h-full rounded-full" :style="{ width: (usedGb / limitGb * 100) + '%' }"></div>
              </div>
            </div>
          </div>

          <!-- Pricing Grid Body -->
          <div class="p-4 sm:p-5 space-y-4">
            <div class="text-center space-y-2">
              <h4 class="text-base font-extrabold text-slate-900">Choose the Right Plan for Your Firm</h4>
              <p class="text-[11px] text-slate-500 font-medium">Simple, transparent pricing. Pay charges monthly or annually with zero hidden fees.</p>

              <!-- Monthly / Annual Toggle Switch -->
              <div class="flex items-center justify-center gap-3 pt-1">
                <div class="bg-slate-100 p-1 rounded-2xl border border-slate-200 inline-flex items-center gap-1 shadow-inner">
                  <button 
                    type="button" 
                    @click="billingCycle = 'monthly'" 
                    :class="[
                      'px-3.5 py-1.5 rounded-xl font-extrabold text-xs transition-all cursor-pointer',
                      billingCycle === 'monthly' ? 'bg-white text-slate-900 shadow-2xs border border-slate-200' : 'text-slate-500 hover:text-slate-900'
                    ]"
                  >
                    Monthly Billing
                  </button>
                  <button 
                    type="button" 
                    @click="billingCycle = 'annually'" 
                    :class="[
                      'px-3.5 py-1.5 rounded-xl font-extrabold text-xs transition-all cursor-pointer flex items-center gap-1.5',
                      billingCycle === 'annually' ? 'bg-red-600 text-white shadow-xs' : 'text-slate-500 hover:text-slate-900'
                    ]"
                  >
                    <span>Annual Billing</span>
                    <span :class="['px-1.5 py-0.2 rounded-full text-[9px] font-black uppercase tracking-wider', billingCycle === 'annually' ? 'bg-white text-red-600' : 'bg-red-100 text-red-700']">
                      Save 20%
                    </span>
                  </button>
                </div>
              </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
              <div 
                v-for="plan in plans" 
                :key="plan.tier"
                :class="[
                  'relative rounded-2xl p-4 border transition-all flex flex-col justify-between',
                  plan.popular 
                    ? 'border-red-500 bg-red-50/20 shadow-md shadow-red-500/10 ring-2 ring-red-500' 
                    : 'border-slate-200 bg-white hover:border-slate-300 shadow-2xs'
                ]"
              >
                <div v-if="plan.popular" class="absolute -top-3 left-1/2 -translate-x-1/2 px-2.5 py-0.5 rounded-full bg-red-600 text-white text-[9px] font-black uppercase tracking-widest shadow-2xs">
                  Most Popular
                </div>

                <div class="space-y-3">
                  <div>
                    <h5 class="text-sm font-extrabold text-slate-900">{{ plan.name }}</h5>
                    <p class="text-[10px] text-slate-500 font-medium mt-0.5">{{ plan.storage }} • {{ plan.users }}</p>
                  </div>

                  <div>
                    <div class="flex items-baseline gap-1">
                      <span class="text-2xl font-black text-slate-900 tracking-tight">{{ plan.price }}</span>
                      <span class="text-[10px] font-bold text-slate-500">{{ plan.period }}</span>
                    </div>
                    <div v-if="plan.annualSubtext" class="text-[10px] text-red-600 font-extrabold mt-0.5">
                      {{ plan.annualSubtext }}
                    </div>
                  </div>

                  <ul class="space-y-1.5 text-[11px] text-slate-600 font-medium pt-2 border-t border-slate-200">
                    <li v-for="feat in plan.features" :key="feat" class="flex items-start gap-1.5 leading-snug">
                      <CheckIcon class="w-3.5 h-3.5 text-emerald-600 shrink-0 mt-0.5" />
                      <span>{{ feat }}</span>
                    </li>
                  </ul>
                </div>

                <div class="pt-4">
                  <button 
                    @click="handleUpgrade(plan.tier)"
                    :disabled="isProcessing"
                    :class="[
                      'w-full py-2 rounded-xl text-xs font-extrabold transition-all flex items-center justify-center gap-1.5 cursor-pointer shadow-2xs',
                      plan.popular 
                        ? 'bg-red-600 hover:bg-red-700 text-white shadow-xs shadow-red-600/20' 
                        : 'bg-slate-900 hover:bg-slate-800 text-white'
                    ]"
                  >
                    <RocketLaunchIcon class="w-3.5 h-3.5" />
                    <span>Select {{ plan.tier }} Plan</span>
                  </button>
                </div>
              </div>
            </div>

            <!-- Footer Notes -->
            <div class="flex flex-col sm:flex-row items-center justify-between gap-2 pt-3 border-t border-slate-200 text-[11px] text-slate-500 font-medium">
              <div class="flex items-center gap-1.5">
                <ShieldCheckIcon class="w-3.5 h-3.5 text-emerald-600" />
                <span>Instant Activation • Cancel Anytime • GST Invoicing Included</span>
              </div>
              <button @click="emit('close')" class="text-slate-400 hover:text-slate-700 font-bold cursor-pointer">Close Window</button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </Teleport>
</template>
