<script setup>
import { computed } from 'vue';

const props = defineProps({
  show: {
    type: Boolean,
    default: true
  },
  overlay: {
    type: Boolean,
    default: false
  },
  fullScreen: {
    type: Boolean,
    default: false
  },
  size: {
    type: String,
    default: 'md' // 'sm', 'md', 'lg', 'xl'
  },
  text: {
    type: String,
    default: ''
  },
  color: {
    type: String,
    default: 'indigo' // 'indigo', 'emerald', 'white', 'slate'
  }
});

const spinnerSizeClasses = computed(() => {
  switch (props.size) {
    case 'sm': return 'w-4 h-4 border-2';
    case 'lg': return 'w-10 h-10 border-3';
    case 'xl': return 'w-14 h-14 border-4';
    case 'md':
    default: return 'w-6 h-6 border-3';
  }
});

const colorClasses = computed(() => {
  switch (props.color) {
    case 'emerald': return 'border-emerald-200 border-t-emerald-600 text-emerald-600';
    case 'white': return 'border-white/30 border-t-white text-white';
    case 'slate': return 'border-slate-200 border-t-slate-700 text-slate-700';
    case 'indigo':
    default: return 'border-indigo-200 border-t-indigo-600 text-indigo-600';
  }
});

const containerClasses = computed(() => {
  if (props.fullScreen) {
    return 'fixed inset-0 z-50 flex flex-col items-center justify-center bg-slate-900/40 backdrop-blur-xs transition-all';
  }
  if (props.overlay) {
    return 'absolute inset-0 z-20 flex flex-col items-center justify-center bg-white/75 backdrop-blur-[2px] rounded-inherit transition-all';
  }
  return 'inline-flex items-center gap-2.5';
});
</script>

<template>
  <div v-if="show" :class="containerClasses">
    <div class="relative flex items-center justify-center">
      <!-- Animated Spinning Ring -->
      <div 
        :class="[
          'rounded-full animate-spin',
          spinnerSizeClasses,
          colorClasses
        ]"
      ></div>
    </div>
    
    <!-- Optional Loading Text -->
    <span 
      v-if="text" 
      :class="[
        'font-bold text-xs tracking-wide animate-pulse',
        color === 'white' ? 'text-white' : 'text-slate-700'
      ]"
    >
      {{ text }}
    </span>
  </div>
</template>
