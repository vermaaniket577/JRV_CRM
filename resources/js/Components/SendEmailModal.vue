<script setup>
import { ref, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { XMarkIcon, PaperAirplaneIcon, EnvelopeIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
  isOpen: Boolean,
  recipientEmail: String,
  recipientName: String,
});

const emit = defineEmits(['close']);

const templates = [
  {
    name: 'General Update',
    subject: 'Important Notification from JRV CRM',
    body: 'Hello,\n\nWe have an update regarding your bio-data profile on JRV CRM. Please review your account for details.\n\nWarm regards,\nJRV Matrimonial Team',
  },
  {
    name: 'Verification Approved',
    subject: 'Profile Identity Verification Approved!',
    body: 'Hello,\n\nWe are pleased to inform you that your bio-data profile identity verification documents have been APPROVED by our counselor team.\n\nYour profile now carries the official Verified Identity Badge.\n\nWarm regards,\nJRV Matrimonial Team',
  },
  {
    name: 'Match Suggestion',
    subject: 'New Bio-Data Match Found for You',
    body: 'Hello,\n\nOur matchmaker counselors have shortlisted a new bio-data candidate matching your partner preferences!\n\nPlease log in to view candidate details.\n\nWarm regards,\nJRV Matrimonial Team',
  },
  {
    name: 'Membership Receipt',
    subject: 'Payment Receipt - Membership Plan Active',
    body: 'Hello,\n\nThank you for renewing your JRV Matrimonial Premium Membership plan.\n\nYour receipt of ₹2,100.00 is attached to your account.\n\nWarm regards,\nJRV Matrimonial Team',
  },
];

const form = useForm({
  recipient_email: '',
  recipient_name: '',
  subject: templates[0].subject,
  body: templates[0].body,
  template_name: templates[0].name,
});

watch(() => props.isOpen, (newVal) => {
  if (newVal) {
    form.recipient_email = props.recipientEmail || '';
    form.recipient_name = props.recipientName || '';
  }
});

const applyTemplate = (template) => {
  form.template_name = template.name;
  form.subject = template.subject;
  form.body = template.body;
};

const submit = () => {
  form.post('/tenant/settings/mail/send-direct', {
    onSuccess: () => {
      form.reset();
      emit('close');
    },
  });
};
</script>

<template>
  <div v-if="isOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-xs">
    <div class="bg-white border border-slate-200 rounded-3xl max-w-lg w-full shadow-2xl flex flex-col overflow-hidden">
      <!-- Header -->
      <div class="p-5 border-b border-slate-200 flex items-center justify-between bg-gradient-to-r from-red-600 to-rose-600 text-white">
        <div class="flex items-center gap-2">
          <EnvelopeIcon class="w-5 h-5" />
          <h3 class="text-base font-extrabold">Send Official Email</h3>
        </div>
        <button @click="emit('close')" class="text-white/80 hover:text-white">
          <XMarkIcon class="w-5 h-5" />
        </button>
      </div>

      <!-- Body Form -->
      <form @submit.prevent="submit" class="p-6 space-y-4 text-xs">
        <!-- Quick Template Selector -->
        <div>
          <label class="font-extrabold text-slate-700 uppercase text-[10px] tracking-wider">Quick Template Selector</label>
          <div class="grid grid-cols-2 gap-2 mt-1">
            <button 
              v-for="(t, idx) in templates" 
              :key="idx"
              type="button"
              @click="applyTemplate(t)"
              :class="[
                'p-2 rounded-xl border text-left text-[11px] font-bold transition-all',
                form.template_name === t.name 
                  ? 'bg-red-50 text-red-700 border-red-300 shadow-xs' 
                  : 'bg-slate-50 text-slate-700 border-slate-200 hover:bg-slate-100'
              ]"
            >
              {{ t.name }}
            </button>
          </div>
        </div>

        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="font-extrabold text-slate-700 uppercase text-[10px] tracking-wider">Recipient Name</label>
            <input v-model="form.recipient_name" type="text" placeholder="Member Name" required class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3 py-2 font-bold" />
          </div>
          <div>
            <label class="font-extrabold text-slate-700 uppercase text-[10px] tracking-wider">Recipient Email</label>
            <input v-model="form.recipient_email" type="email" placeholder="user@example.com" required class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3 py-2 font-bold" />
          </div>
        </div>

        <div>
          <label class="font-extrabold text-slate-700 uppercase text-[10px] tracking-wider">Subject Line</label>
          <input v-model="form.subject" type="text" required class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3 py-2 font-bold text-slate-900" />
        </div>

        <div>
          <label class="font-extrabold text-slate-700 uppercase text-[10px] tracking-wider">Email Body</label>
          <textarea v-model="form.body" rows="5" required class="w-full bg-slate-50 border border-slate-300 rounded-xl p-3 font-medium leading-relaxed"></textarea>
        </div>

        <div class="pt-2 flex items-center justify-between border-t border-slate-100">
          <span class="text-[10px] text-slate-500 font-semibold">From: <strong class="text-slate-800">info@jainshadimilan.com</strong></span>
          <div class="flex items-center gap-3">
            <button type="button" @click="emit('close')" class="px-4 py-2 font-bold text-slate-500 hover:text-slate-700">Cancel</button>
            <button type="submit" :disabled="form.processing" class="px-5 py-2.5 bg-red-600 hover:bg-red-700 text-white font-extrabold rounded-xl shadow-md flex items-center gap-1.5 transition-all">
              <PaperAirplaneIcon class="w-4 h-4" />
              <span>Send Email</span>
            </button>
          </div>
        </div>
      </form>
    </div>
  </div>
</template>
