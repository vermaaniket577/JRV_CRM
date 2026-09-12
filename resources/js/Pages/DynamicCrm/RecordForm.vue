<script setup>
import { ref, reactive, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import Navbar from '@/Components/Navbar.vue';
import {
  ArrowLeftIcon, CheckCircleIcon, TableCellsIcon,
  ArrowUpTrayIcon, XMarkIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
  tenant: Object,
  tableMeta: Object,
  formFields: Array,
  mode: String, // 'create' | 'edit'
  record: Object,
  recordId: [Number, String],
  allTables: Array,
});

const isSubmitting = ref(false);
const errors = ref({});

// Build reactive form data from fields
const formData = reactive({});
(props.formFields || []).forEach(field => {
  formData[field.name] = field.value ?? '';
});

const submitForm = async () => {
  isSubmitting.value = true;
  errors.value = {};

  const url = props.mode === 'edit'
    ? `/dynamic-crm/${props.tableMeta.table_name}/${props.recordId}`
    : `/dynamic-crm/${props.tableMeta.table_name}`;

  const method = props.mode === 'edit' ? 'PUT' : 'POST';

  try {
    const res = await fetch(url, {
      method,
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
      },
      body: JSON.stringify(formData),
    });

    const data = await res.json();

    if (res.ok && (data.success || data.id)) {
      if (props.mode === 'edit') {
        router.visit(`/dynamic-crm/${props.tableMeta.table_name}/${props.recordId}`);
      } else {
        router.visit(`/dynamic-crm/${props.tableMeta.table_name}`);
      }
    } else if (data.errors) {
      errors.value = data.errors;
    } else if (data.error) {
      errors.value = { _form: [data.error] };
    }
  } catch (e) {
    errors.value = { _form: ['An error occurred. Please try again.'] };
  }

  isSubmitting.value = false;
};

const getFieldError = (name) => {
  const err = errors.value[name];
  return err ? (Array.isArray(err) ? err[0] : err) : null;
};

const pageTitle = computed(() => {
  return props.mode === 'edit'
    ? `Edit Record #${props.recordId}`
    : `Add ${props.tableMeta?.display_name}`;
});
</script>

<template>
  <Head :title="pageTitle" />
  <div class="min-h-screen bg-[#0f0f23] flex font-sans text-slate-100">
    <Navbar />

    <div class="flex-1 min-w-0 form-page">
      <!-- Sidebar -->
    <aside class="form-sidebar">
      <div class="sidebar-header"><TableCellsIcon class="w-5 h-5 text-indigo-400" /><span>CRM Tables</span></div>
      <Link href="/dynamic-crm/dashboard" class="sidebar-item">📊 Dashboard</Link>
      <Link v-for="t in allTables" :key="t.table_name" :href="`/dynamic-crm/${t.table_name}`" class="sidebar-item" :class="{ 'sidebar-active': t.table_name === tableMeta?.table_name }">
        <span>{{ t.display_name }}</span>
        <span class="sidebar-count">{{ t.record_count }}</span>
      </Link>
    </aside>

    <!-- Main -->
    <main class="form-main">
      <!-- Breadcrumb -->
      <div class="breadcrumb">
        <Link :href="`/dynamic-crm/${tableMeta?.table_name}`" class="bread-link">
          <ArrowLeftIcon class="w-4 h-4" /> {{ tableMeta?.display_name }}
        </Link>
        <span class="bread-sep">›</span>
        <span class="bread-current">{{ mode === 'edit' ? 'Edit' : 'Create' }}</span>
      </div>

      <div class="form-card">
        <h1>{{ pageTitle }}</h1>

        <!-- Global Error -->
        <div v-if="errors._form" class="form-error-banner">
          {{ errors._form[0] }}
        </div>

        <form @submit.prevent="submitForm" class="dynamic-form">
          <div class="form-grid">
            <div v-for="field in formFields" :key="field.name" class="form-field" :class="{ 'field-error': getFieldError(field.name), 'field-full': field.type === 'textarea' }">
              <label :for="field.name" class="field-label">
                {{ field.label }}
                <span v-if="field.required" class="required-star">*</span>
              </label>

              <!-- Text / Email / Tel / URL / Password / Color -->
              <input
                v-if="['text', 'email', 'tel', 'url', 'password', 'color', 'number', 'date', 'datetime-local', 'time'].includes(field.type)"
                :id="field.name"
                v-model="formData[field.name]"
                :type="field.type"
                :placeholder="field.placeholder"
                :required="field.required"
                :step="field.step"
                class="field-input"
              />

              <!-- Textarea -->
              <textarea
                v-else-if="field.type === 'textarea'"
                :id="field.name"
                v-model="formData[field.name]"
                :placeholder="field.placeholder"
                :required="field.required"
                :rows="field.rows || 4"
                class="field-textarea"
              ></textarea>

              <!-- Checkbox -->
              <div v-else-if="field.type === 'checkbox'" class="checkbox-wrap">
                <input :id="field.name" v-model="formData[field.name]" type="checkbox" class="field-checkbox" />
                <span class="checkbox-label">{{ field.label }}</span>
              </div>

              <!-- Select / FK Select -->
              <select
                v-else-if="field.type === 'select' || field.type === 'foreign_key_select'"
                :id="field.name"
                v-model="formData[field.name]"
                :required="field.required"
                class="field-select"
              >
                <option value="">— Select —</option>
                <option v-for="opt in (field.options || [])" :key="opt.value" :value="opt.value">
                  {{ opt.label }}
                </option>
              </select>

              <!-- Fallback: text input -->
              <input v-else :id="field.name" v-model="formData[field.name]" type="text" :placeholder="field.placeholder" class="field-input" />

              <!-- Error -->
              <div v-if="getFieldError(field.name)" class="field-error-msg">{{ getFieldError(field.name) }}</div>
            </div>
          </div>

          <!-- Actions -->
          <div class="form-actions">
            <Link :href="`/dynamic-crm/${tableMeta?.table_name}`" class="btn-cancel">Cancel</Link>
            <button type="submit" :disabled="isSubmitting" class="btn-submit">
              <CheckCircleIcon class="w-5 h-5" />
              {{ isSubmitting ? 'Saving...' : (mode === 'edit' ? 'Update Record' : 'Create Record') }}
            </button>
          </div>
        </form>
      </div>
    </main>
    </div>
  </div>
</template>

<style scoped>
.form-page { min-height: 100vh; background: linear-gradient(135deg, #0f0f23 0%, #1a1a3e 50%, #0f0f23 100%); display: flex; color: #e2e8f0; }
.form-sidebar { width: 240px; min-height: 100vh; padding: 20px 12px; background: rgba(15, 15, 35, 0.95); border-right: 1px solid rgba(99, 102, 241, 0.1); display: flex; flex-direction: column; gap: 4px; position: sticky; top: 0; }
.sidebar-header { display: flex; align-items: center; gap: 8px; padding: 8px 12px; font-size: 0.8rem; color: #64748b; text-transform: uppercase; margin-bottom: 8px; }
.sidebar-item { display: flex; align-items: center; justify-content: space-between; padding: 10px 14px; border-radius: 10px; text-decoration: none; color: #94a3b8; font-size: 0.88rem; transition: all 0.2s; }
.sidebar-item:hover { background: rgba(99, 102, 241, 0.1); color: #c4b5fd; }
.sidebar-active { background: rgba(99, 102, 241, 0.15) !important; color: #c4b5fd !important; font-weight: 600; }
.sidebar-count { font-size: 0.75rem; background: rgba(99, 102, 241, 0.15); padding: 2px 8px; border-radius: 10px; color: #8b5cf6; }

.form-main { flex: 1; padding: 24px 32px; }
.breadcrumb { display: flex; align-items: center; gap: 8px; margin-bottom: 20px; font-size: 0.9rem; }
.bread-link { display: flex; align-items: center; gap: 4px; color: #8b5cf6; text-decoration: none; }
.bread-sep { color: #475569; }
.bread-current { color: #94a3b8; }

.form-card { background: rgba(30, 30, 60, 0.6); border: 1px solid rgba(99, 102, 241, 0.1); border-radius: 20px; padding: 32px; max-width: 900px; }
.form-card h1 { font-size: 1.5rem; font-weight: 800; color: #fff; margin: 0 0 24px; }

.form-error-banner { padding: 12px 16px; background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.3); border-radius: 10px; color: #fca5a5; margin-bottom: 20px; }

.form-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 20px; margin-bottom: 28px; }
.field-full { grid-column: 1 / -1; }

.form-field { display: flex; flex-direction: column; gap: 6px; }
.field-label { font-size: 0.85rem; color: #94a3b8; font-weight: 500; }
.required-star { color: #f87171; }

.field-input, .field-select, .field-textarea {
  padding: 12px 16px; background: rgba(15, 15, 40, 0.6);
  border: 1px solid rgba(99, 102, 241, 0.15); border-radius: 10px;
  color: #e2e8f0; font-size: 0.9rem; outline: none; transition: border-color 0.2s;
  width: 100%; box-sizing: border-box;
}
.field-input:focus, .field-select:focus, .field-textarea:focus { border-color: #6366f1; }
.field-textarea { resize: vertical; min-height: 100px; }
.field-select { appearance: none; cursor: pointer; }
.field-select option { background: #1e1e3e; color: #e2e8f0; }

.checkbox-wrap { display: flex; align-items: center; gap: 10px; padding-top: 8px; }
.field-checkbox { width: 20px; height: 20px; accent-color: #6366f1; }
.checkbox-label { font-size: 0.9rem; color: #94a3b8; }

.field-error .field-input, .field-error .field-select, .field-error .field-textarea { border-color: #f87171; }
.field-error-msg { font-size: 0.8rem; color: #fca5a5; }

.form-actions { display: flex; justify-content: flex-end; gap: 12px; }
.btn-cancel { padding: 12px 24px; background: rgba(100, 116, 139, 0.15); border: 1px solid rgba(100, 116, 139, 0.2); border-radius: 12px; color: #94a3b8; text-decoration: none; font-size: 0.9rem; cursor: pointer; }
.btn-submit { display: inline-flex; align-items: center; gap: 8px; padding: 12px 28px; background: linear-gradient(135deg, #6366f1, #8b5cf6); color: white; border: none; border-radius: 12px; font-size: 0.95rem; font-weight: 700; cursor: pointer; transition: all 0.2s; }
.btn-submit:hover { transform: translateY(-1px); }
.btn-submit:disabled { opacity: 0.6; cursor: not-allowed; }

@media (max-width: 768px) { .form-sidebar { display: none; } .form-main { padding: 16px; } .form-grid { grid-template-columns: 1fr; } }
</style>
