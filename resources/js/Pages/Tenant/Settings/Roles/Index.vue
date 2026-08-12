<script setup>
import { ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import Navbar from '@/Components/Navbar.vue';
import GlobalSearchModal from '@/Components/GlobalSearchModal.vue';
import { ShieldCheckIcon, PlusIcon, CheckIcon, TrashIcon } from '@heroicons/vue/24/outline';

const props = defineProps({
  roles: {
    type: Array,
    required: true,
  },
  permissionSections: {
    type: Array,
    required: true,
  },
});

const isSearchOpen = ref(false);
const isModalOpen = ref(false);
const editingRole = ref(null);

const form = useForm({
  name: '',
  permissions: [],
});

const openCreateModal = () => {
  editingRole.value = null;
  form.reset();
  isModalOpen.value = true;
};

const openEditModal = (role) => {
  editingRole.value = role;
  form.name = role.name;
  form.permissions = role.permissions ? [...role.permissions] : [];
  isModalOpen.value = true;
};

const togglePermission = (id) => {
  const index = form.permissions.indexOf(id);
  if (index > -1) {
    form.permissions.splice(index, 1);
  } else {
    form.permissions.push(id);
  }
};

const saveRole = () => {
  if (editingRole.value) {
    form.put(`/tenant/settings/roles/${editingRole.value.id}`, {
      onSuccess: () => { isModalOpen.value = false; form.reset(); },
    });
  } else {
    form.post('/tenant/settings/roles', {
      onSuccess: () => { isModalOpen.value = false; form.reset(); },
    });
  }
};
</script>

<template>
  <Head title="Role & Permission Settings - SaaS CRM" />

  <div class="min-h-screen bg-slate-100 flex font-sans text-slate-900">
    <Navbar @open-search="isSearchOpen = true" />

    <main class="flex-1 p-8 space-y-8 overflow-y-auto min-w-0 max-w-7xl mx-auto w-full">
      <!-- Header -->
      <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-slate-200 pb-6">
        <div>
          <h1 class="text-3xl font-extrabold tracking-tight text-slate-900 flex items-center gap-3">
            <ShieldCheckIcon class="w-8 h-8 text-indigo-600" />
            <span>Role & Section Permissions</span>
          </h1>
          <p class="text-slate-500 text-sm mt-1">Configure staff access controls by enabling/disabling specific UI modules.</p>
        </div>

        <button 
          @click="openCreateModal"
          class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-semibold shadow-md shadow-indigo-600/20 flex items-center gap-2"
        >
          <PlusIcon class="w-4 h-4" />
          <span>Create Custom Role</span>
        </button>
      </div>

      <!-- Roles Grid -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <div 
          v-for="role in roles" 
          :key="role.id"
          class="bg-white border border-slate-200 rounded-xl p-6 space-y-4 hover:border-slate-300 transition-all flex flex-col justify-between shadow-xs"
        >
          <div class="space-y-2">
            <div class="flex items-center justify-between">
              <h3 class="text-lg font-bold text-slate-900">{{ role.name }}</h3>
              <span class="text-xs px-2.5 py-0.5 rounded-full bg-indigo-50 text-indigo-700 border border-indigo-200 font-semibold">
                {{ role.users_count || 0 }} Staff Members
              </span>
            </div>
            <p class="text-xs text-slate-500 font-medium">
              Grants access to {{ role.permissions_count }} section permissions.
            </p>
          </div>

          <div class="pt-4 border-t border-slate-100 flex items-center justify-between">
            <button 
              @click="openEditModal(role)"
              class="text-xs font-bold text-indigo-600 hover:text-indigo-700"
            >
              Edit Permissions
            </button>
            <span class="text-[11px] text-slate-500">Created {{ role.created_at }}</span>
          </div>
        </div>
      </div>
    </main>

    <!-- Modals -->
    <GlobalSearchModal :is-open="isSearchOpen" @close="isSearchOpen = false" />

    <div v-if="isModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-xs">
      <div class="bg-white border border-slate-200 rounded-2xl max-w-2xl w-full max-h-[90vh] flex flex-col overflow-hidden shadow-2xl">
        <div class="p-6 border-b border-slate-200 flex items-center justify-between">
          <h3 class="text-lg font-bold text-slate-900">
            {{ editingRole ? 'Edit Custom Role' : 'Create Custom Role' }}
          </h3>
          <button @click="isModalOpen = false" class="text-slate-400 hover:text-slate-600">✕</button>
        </div>

        <div class="p-6 space-y-6 overflow-y-auto flex-1">
          <!-- Role Name Input -->
          <div class="space-y-1">
            <label class="text-xs font-bold text-slate-700 uppercase tracking-wider">Role Title</label>
            <input 
              v-model="form.name" 
              type="text" 
              placeholder="e.g. Sales Specialist, Support Representative"
              class="w-full bg-white border border-slate-300 rounded-lg px-3.5 py-2 text-sm text-slate-900 focus:outline-none focus:border-indigo-500"
            />
          </div>

          <!-- Section Permissions Matrix -->
          <div class="space-y-6">
            <h4 class="text-xs font-bold text-slate-500 uppercase tracking-wider">Assign Module & Section Permissions</h4>

            <div v-for="sectionGroup in permissionSections" :key="sectionGroup.section" class="space-y-3 bg-slate-50 p-4 border border-slate-200 rounded-xl">
              <div class="text-xs font-extrabold text-indigo-700 tracking-wider uppercase">
                Section: {{ sectionGroup.section }}
              </div>

              <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <label 
                  v-for="perm in sectionGroup.items" 
                  :key="perm.id"
                  class="flex items-center gap-3 p-2.5 bg-white border border-slate-200 rounded-lg cursor-pointer hover:border-slate-300 transition-all shadow-xs"
                >
                  <input 
                    type="checkbox"
                    :checked="form.permissions.includes(perm.id)"
                    @change="togglePermission(perm.id)"
                    class="w-4 h-4 rounded bg-white border-slate-300 text-indigo-600 focus:ring-0 focus:ring-offset-0"
                  />
                  <span class="text-xs font-semibold text-slate-800">{{ perm.name }}</span>
                </label>
              </div>
            </div>
          </div>
        </div>

        <!-- Footer -->
        <div class="p-4 border-t border-slate-200 bg-slate-50 flex items-center justify-end gap-3">
          <button @click="isModalOpen = false" class="px-4 py-2 text-xs font-semibold text-slate-500 hover:text-slate-700">
            Cancel
          </button>
          <button 
            @click="saveRole"
            :disabled="form.processing"
            class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold rounded-lg shadow-md shadow-indigo-600/20"
          >
            Save Role Permissions
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
