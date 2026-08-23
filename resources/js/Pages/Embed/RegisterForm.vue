<script setup>
import { ref, computed } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import { 
  AcademicCapIcon,
  HomeModernIcon, 
  HeartIcon,
  BriefcaseIcon,
  CodeBracketIcon,
  UserGroupIcon,
  CheckCircleIcon, 
  MapPinIcon, 
  CurrencyRupeeIcon,
  PhoneIcon,
  EnvelopeIcon,
  UserIcon,
  CalendarIcon,
  SparklesIcon,
  BuildingOfficeIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
  apiKey: String,
  tenantId: [Number, String],
  industry: {
    type: Object,
    default: () => ({ slug: 'education', name: 'Education & Training', icon: '🎓' })
  },
  business: {
    type: Object,
    default: () => ({ name: 'Admissions Dekho', icon: '🎓', brand_color: '#dc2626' })
  }
});

const isSubmitted = ref(false);

const industrySlug = computed(() => {
  const slug = (props.industry?.slug || '').toLowerCase();
  if (slug.includes('edu') || slug.includes('school') || slug.includes('college') || slug.includes('train')) return 'education';
  if (slug.includes('real') || slug.includes('prop') || slug.includes('estate') || slug.includes('rent')) return 'real-estate';
  if (slug.includes('health') || slug.includes('clinic') || slug.includes('hosp') || slug.includes('medic') || slug.includes('doctor')) return 'healthcare';
  if (slug.includes('recruit') || slug.includes('hr') || slug.includes('job') || slug.includes('staff')) return 'recruitment';
  if (slug.includes('tech') || slug.includes('soft') || slug.includes('it-') || slug.includes('saas') || slug.includes('dev')) return 'it-software';
  if (slug.includes('matrimoni') || slug.includes('match') || slug.includes('marriage') || slug.includes('shaadi')) return 'matrimonial';
  return 'education'; // Default to active industry
});

// 1. Education Form
const educationForm = useForm({
  tenant_id: props.tenantId || 7,
  first_name: '',
  last_name: '',
  phone: '',
  email: '',
  course_interested: 'B.Tech / Engineering',
  qualification: '12th Standard (Appearing / Passed)',
  percentage: '',
  admission_year: '2025 - 2026',
  city: 'Mumbai',
  message: '',
});

// 2. Real Estate Form
const realEstateForm = useForm({
  tenant_id: props.tenantId || 7,
  first_name: '',
  last_name: '',
  phone: '',
  email: '',
  inquiry_type: 'Looking to Rent',
  property_type: 'Apartment / Flat',
  bedrooms: '2 BHK',
  budget: '',
  locality: '',
  city: 'Mumbai',
  message: '',
});

// 3. Healthcare Form
const healthcareForm = useForm({
  tenant_id: props.tenantId || 7,
  patient_name: '',
  age_gender: '28 / Male',
  phone: '',
  email: '',
  department: 'General Medicine',
  appointment_type: 'In-Clinic Consultation',
  preferred_date: '',
  city: 'Mumbai',
  symptoms: '',
});

// 4. Recruitment Form
const recruitmentForm = useForm({
  tenant_id: props.tenantId || 7,
  first_name: '',
  last_name: '',
  phone: '',
  email: '',
  job_title: '',
  experience_years: '1 - 2 Years',
  current_company: '',
  notice_period: 'Immediate Joiner',
  expected_salary: '',
  city: 'Mumbai',
  skills_summary: '',
});

// 5. IT & Software Form
const itSoftwareForm = useForm({
  tenant_id: props.tenantId || 7,
  first_name: '',
  company_name: '',
  phone: '',
  email: '',
  service_required: 'Custom Web / Cloud App',
  budget: '₹1 Lakh - ₹5 Lakhs',
  timeline: '1 - 3 Months',
  city: 'Mumbai',
  message: '',
});

// 6. Matrimonial Form
const matrimonialForm = useForm({
  tenant_id: props.tenantId || 7,
  first_name: '',
  last_name: '',
  gender: 'Female',
  date_of_birth: '1998-05-15',
  height_cm: 165,
  marital_status: 'Never Married',
  religion: 'Hindu',
  caste: 'General',
  sub_caste: '',
  gotra: '',
  mother_gotra: '',
  education_level: 'Graduate',
  occupation_type: 'Private Sector',
  annual_income: 800000,
  phone: '',
  email: '',
  state: 'Maharashtra',
  city: 'Mumbai',
});

// Submit Handlers
const submitEducation = () => {
  educationForm.post('/embed/register', {
    onSuccess: () => { isSubmitted.value = true; }
  });
};

const submitRealEstate = () => {
  realEstateForm.post('/embed/register', {
    onSuccess: () => { isSubmitted.value = true; }
  });
};

const submitHealthcare = () => {
  healthcareForm.post('/embed/register', {
    onSuccess: () => { isSubmitted.value = true; }
  });
};

const submitRecruitment = () => {
  recruitmentForm.post('/embed/register', {
    onSuccess: () => { isSubmitted.value = true; }
  });
};

const submitItSoftware = () => {
  itSoftwareForm.post('/embed/register', {
    onSuccess: () => { isSubmitted.value = true; }
  });
};

const submitMatrimonial = () => {
  matrimonialForm.post('/embed/register', {
    onSuccess: () => { isSubmitted.value = true; }
  });
};
</script>

<template>
  <Head :title="`${business.name || 'Portal'} - Online Registration & Inquiry Form`" />

  <div class="min-h-screen bg-slate-50 p-3 sm:p-6 font-sans text-slate-900 flex items-center justify-center">
    <div class="bg-white border border-slate-200/90 rounded-3xl p-6 sm:p-8 shadow-xl max-w-xl w-full space-y-6">
      
      <!-- DYNAMIC SECTOR HEADER -->
      <div class="flex items-center gap-3.5 border-b border-slate-100 pb-5">
        <div class="w-12 h-12 bg-red-50 border border-red-200 rounded-2xl flex items-center justify-center text-red-600 font-black text-2xl shadow-xs shrink-0">
          {{ business.icon || industry.icon || '🎓' }}
        </div>
        <div class="space-y-0.5">
          <h1 class="text-base sm:text-lg font-black text-slate-900 tracking-tight flex items-center gap-2">
            <span>{{ business.name }}</span>
            <span class="text-xs font-semibold text-slate-400">•</span>
            <span class="text-xs font-bold text-red-600">
              <span v-if="industrySlug === 'education'">Admission & Course Inquiry</span>
              <span v-else-if="industrySlug === 'real-estate'">Property & Rental Inquiry</span>
              <span v-else-if="industrySlug === 'healthcare'">Doctor Consultation</span>
              <span v-else-if="industrySlug === 'recruitment'">Job Application</span>
              <span v-else-if="industrySlug === 'it-software'">IT Project Inquiry</span>
              <span v-else-if="industrySlug === 'matrimonial'">Bio-Data Profile</span>
              <span v-else>Direct Inquiry</span>
            </span>
          </h1>
          <p class="text-xs text-slate-500 font-medium">
            <span v-if="industrySlug === 'education'">Submit your academic details and course requirements for instant counseling</span>
            <span v-else-if="industrySlug === 'real-estate'">Submit your rental requirements or property inquiry to our team</span>
            <span v-else-if="industrySlug === 'healthcare'">Book an appointment or send your medical inquiry to our specialists</span>
            <span v-else-if="industrySlug === 'recruitment'">Submit your profile & CV details for top matching job openings</span>
            <span v-else-if="industrySlug === 'it-software'">Tell us about your software development, web app, or cloud requirements</span>
            <span v-else-if="industrySlug === 'matrimonial'">Submit your matrimonial bio-data directly to our community portal</span>
            <span v-else>Fill out the form below to connect directly with our team</span>
          </p>
        </div>
      </div>

      <!-- SUCCESS NOTICE -->
      <div v-if="isSubmitted" class="p-8 text-center space-y-3 bg-emerald-50 rounded-2xl border border-emerald-200 shadow-xs animate-fadeIn">
        <div class="w-14 h-14 bg-emerald-100 text-emerald-600 rounded-full flex items-center justify-center mx-auto text-2xl shadow-xs">
          ✓
        </div>
        <h3 class="text-lg font-black text-emerald-900">
          <span v-if="industrySlug === 'education'">Admission Inquiry Submitted Successfully!</span>
          <span v-else-if="industrySlug === 'real-estate'">Property Inquiry Received!</span>
          <span v-else-if="industrySlug === 'healthcare'">Appointment Request Scheduled!</span>
          <span v-else-if="industrySlug === 'recruitment'">Application Submitted Successfully!</span>
          <span v-else-if="industrySlug === 'it-software'">Project Inquiry Received!</span>
          <span v-else-if="industrySlug === 'matrimonial'">Bio-data Profile Submitted!</span>
          <span v-else>Inquiry Submitted Successfully!</span>
        </h3>
        <p class="text-xs text-emerald-700 font-medium max-w-md mx-auto leading-relaxed">
          Thank you! We have received your submission. A dedicated specialist from <strong>{{ business.name }}</strong> will review your details and contact you shortly.
        </p>
      </div>

      <!-- 1. EDUCATION & TRAINING CRM FORM -->
      <form v-else-if="industrySlug === 'education'" @submit.prevent="submitEducation" class="space-y-4 text-xs">
        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="font-bold text-slate-700 block mb-1">Student First Name *</label>
            <input v-model="educationForm.first_name" type="text" placeholder="e.g. Rahul" required class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2.5 font-bold text-slate-900 focus:bg-white focus:outline-none focus:border-red-500 focus:ring-2 focus:ring-red-100" />
          </div>
          <div>
            <label class="font-bold text-slate-700 block mb-1">Last Name</label>
            <input v-model="educationForm.last_name" type="text" placeholder="e.g. Sharma" class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2.5 font-bold text-slate-900 focus:bg-white focus:outline-none focus:border-red-500 focus:ring-2 focus:ring-red-100" />
          </div>
        </div>

        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="font-bold text-slate-700 block mb-1">Phone / WhatsApp *</label>
            <input v-model="educationForm.phone" type="tel" placeholder="e.g. +91 9876543210" required class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2.5 font-bold text-slate-900 focus:bg-white focus:outline-none focus:border-red-500 focus:ring-2 focus:ring-red-100" />
          </div>
          <div>
            <label class="font-bold text-slate-700 block mb-1">Email Address *</label>
            <input v-model="educationForm.email" type="email" placeholder="e.g. rahul@example.com" required class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2.5 font-bold text-slate-900 focus:bg-white focus:outline-none focus:border-red-500 focus:ring-2 focus:ring-red-100" />
          </div>
        </div>

        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="font-bold text-slate-700 block mb-1">Course Interested *</label>
            <select v-model="educationForm.course_interested" class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2.5 font-bold text-slate-800 focus:bg-white focus:outline-none focus:border-red-500">
              <option value="B.Tech / Engineering">B.Tech / Engineering</option>
              <option value="MBA / PGDM">MBA / Management</option>
              <option value="MBBS / Medical / Dental">MBBS / Medical</option>
              <option value="BCA / MCA">BCA / MCA</option>
              <option value="B.Sc / M.Sc Data Science">B.Sc / M.Sc</option>
              <option value="B.Com / BBA">B.Com / BBA</option>
              <option value="Law (BA LLB / LLM)">Law / LLB</option>
              <option value="Design / Animation">Design / Animation</option>
              <option value="Study Abroad / Overseas">Study Abroad / Overseas</option>
              <option value="Diploma / Vocational">Diploma / Skill Training</option>
              <option value="Other Course">Other Course</option>
            </select>
          </div>
          <div>
            <label class="font-bold text-slate-700 block mb-1">Highest Qualification</label>
            <select v-model="educationForm.qualification" class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2.5 font-bold text-slate-800 focus:bg-white focus:outline-none focus:border-red-500">
              <option value="12th Standard (Appearing / Passed)">12th Standard (Appearing / Passed)</option>
              <option value="10th Standard">10th Standard</option>
              <option value="Undergraduate / Bachelor's Degree">Undergraduate / Bachelor's</option>
              <option value="Postgraduate / Master's Degree">Postgraduate / Master's</option>
              <option value="Diploma Holder">Diploma Holder</option>
            </select>
          </div>
        </div>

        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="font-bold text-slate-700 block mb-1">Target Admission Year</label>
            <select v-model="educationForm.admission_year" class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2.5 font-bold text-slate-800 focus:bg-white focus:outline-none focus:border-red-500">
              <option value="2025 - 2026">2025 - 2026 Session</option>
              <option value="2026 - 2027">2026 - 2027 Session</option>
              <option value="Immediate / Direct Admission">Immediate Admission</option>
            </select>
          </div>
          <div>
            <label class="font-bold text-slate-700 block mb-1">City / Current Location</label>
            <input v-model="educationForm.city" type="text" placeholder="e.g. Delhi, Mumbai, Pune" class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2.5 font-bold text-slate-900 focus:bg-white focus:outline-none focus:border-red-500" />
          </div>
        </div>

        <div>
          <label class="font-bold text-slate-700 block mb-1">Special Requirements / Counselor Inquiries</label>
          <textarea v-model="educationForm.message" rows="2" placeholder="e.g. Interested in Top Colleges, Hostel availability, Scholarship criteria..." class="w-full bg-slate-50 border border-slate-300 rounded-xl p-3 font-medium text-slate-800 focus:bg-white focus:outline-none focus:border-red-500"></textarea>
        </div>

        <button 
          type="submit" 
          :disabled="educationForm.processing"
          class="w-full py-3.5 bg-red-600 hover:bg-red-700 text-white font-black text-xs uppercase tracking-wider rounded-2xl shadow-lg shadow-red-600/20 transition disabled:opacity-50 cursor-pointer flex items-center justify-center gap-2"
        >
          <div v-if="educationForm.processing" class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></div>
          <AcademicCapIcon v-else class="w-4 h-4 stroke-[2.5]" />
          <span>{{ educationForm.processing ? 'Submitting Inquiry...' : '🎓 Submit Admission Inquiry 🚀' }}</span>
        </button>
      </form>

      <!-- 2. REAL ESTATE CRM FORM -->
      <form v-else-if="industrySlug === 'real-estate'" @submit.prevent="submitRealEstate" class="space-y-4 text-xs">
        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="font-bold text-slate-700 block mb-1">First Name *</label>
            <input v-model="realEstateForm.first_name" type="text" placeholder="John" required class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2.5 font-bold text-slate-900 focus:bg-white focus:outline-none focus:border-amber-500" />
          </div>
          <div>
            <label class="font-bold text-slate-700 block mb-1">Last Name</label>
            <input v-model="realEstateForm.last_name" type="text" placeholder="Doe" class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2.5 font-bold text-slate-900 focus:bg-white focus:outline-none focus:border-amber-500" />
          </div>
        </div>

        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="font-bold text-slate-700 block mb-1">Phone Number *</label>
            <input v-model="realEstateForm.phone" type="tel" placeholder="+91 9876543210" required class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2.5 font-bold text-slate-900 focus:bg-white focus:outline-none focus:border-amber-500" />
          </div>
          <div>
            <label class="font-bold text-slate-700 block mb-1">Email Address *</label>
            <input v-model="realEstateForm.email" type="email" placeholder="john@example.com" required class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2.5 font-bold text-slate-900 focus:bg-white focus:outline-none focus:border-amber-500" />
          </div>
        </div>

        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="font-bold text-slate-700 block mb-1">I Am Looking To *</label>
            <select v-model="realEstateForm.inquiry_type" class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2.5 font-bold text-slate-800 focus:bg-white focus:outline-none focus:border-amber-500">
              <option value="Looking to Rent">Rent a Property</option>
              <option value="Looking to Buy">Buy a Property</option>
              <option value="List my Property">List My Property for Rent/Sale</option>
              <option value="Schedule Site Visit">Schedule Site Visit</option>
            </select>
          </div>
          <div>
            <label class="font-bold text-slate-700 block mb-1">Property Type</label>
            <select v-model="realEstateForm.property_type" class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2.5 font-bold text-slate-800 focus:bg-white focus:outline-none focus:border-amber-500">
              <option value="Apartment / Flat">Apartment / Flat</option>
              <option value="Independent Villa">Independent Villa / House</option>
              <option value="Commercial Office">Commercial Office</option>
              <option value="Retail Shop">Retail Shop</option>
              <option value="Plot / Land">Plot / Land</option>
              <option value="PG / Co-living">PG / Co-living</option>
            </select>
          </div>
        </div>

        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="font-bold text-slate-700 block mb-1">Bedrooms (BHK)</label>
            <select v-model="realEstateForm.bedrooms" class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2.5 font-bold text-slate-800 focus:bg-white focus:outline-none focus:border-amber-500">
              <option value="1 BHK">1 BHK</option>
              <option value="2 BHK">2 BHK</option>
              <option value="3 BHK">3 BHK</option>
              <option value="4+ BHK">4+ BHK</option>
              <option value="Studio / 1 RK">Studio / 1 RK</option>
              <option value="Any">Any / Not Decided</option>
            </select>
          </div>
          <div>
            <label class="font-bold text-slate-700 block mb-1">Max Budget / Rent (₹)</label>
            <input v-model="realEstateForm.budget" type="number" placeholder="e.g. 35000" class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2.5 font-bold text-slate-900 focus:bg-white focus:outline-none focus:border-amber-500" />
          </div>
        </div>

        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="font-bold text-slate-700 block mb-1">Preferred Locality</label>
            <input v-model="realEstateForm.locality" type="text" placeholder="e.g. Bandra West" class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2.5 font-bold text-slate-900 focus:bg-white focus:outline-none focus:border-amber-500" />
          </div>
          <div>
            <label class="font-bold text-slate-700 block mb-1">City</label>
            <input v-model="realEstateForm.city" type="text" placeholder="Mumbai" class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2.5 font-bold text-slate-900 focus:bg-white focus:outline-none focus:border-amber-500" />
          </div>
        </div>

        <div>
          <label class="font-bold text-slate-700 block mb-1">Special Requirements / Notes</label>
          <textarea v-model="realEstateForm.message" rows="2" placeholder="e.g. Furnished, Parking needed, Immediate move-in" class="w-full bg-slate-50 border border-slate-300 rounded-xl p-3 font-medium text-slate-800 focus:bg-white focus:outline-none focus:border-amber-500"></textarea>
        </div>

        <button 
          type="submit" 
          :disabled="realEstateForm.processing"
          class="w-full py-3.5 bg-amber-600 hover:bg-amber-700 text-white font-black text-xs uppercase tracking-wider rounded-2xl shadow-lg shadow-amber-600/20 transition disabled:opacity-50 cursor-pointer flex items-center justify-center gap-2"
        >
          <div v-if="realEstateForm.processing" class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></div>
          <HomeModernIcon v-else class="w-4 h-4 stroke-[2.5]" />
          <span>{{ realEstateForm.processing ? 'Submitting...' : '🏠 Submit Property Inquiry 🚀' }}</span>
        </button>
      </form>

      <!-- 3. HEALTHCARE & CLINIC FORM -->
      <form v-else-if="industrySlug === 'healthcare'" @submit.prevent="submitHealthcare" class="space-y-4 text-xs">
        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="font-bold text-slate-700 block mb-1">Patient Full Name *</label>
            <input v-model="healthcareForm.patient_name" type="text" placeholder="e.g. Sunita Verma" required class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2.5 font-bold text-slate-900 focus:bg-white focus:outline-none focus:border-emerald-500" />
          </div>
          <div>
            <label class="font-bold text-slate-700 block mb-1">Age / Gender</label>
            <input v-model="healthcareForm.age_gender" type="text" placeholder="e.g. 32 / Female" class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2.5 font-bold text-slate-900 focus:bg-white focus:outline-none focus:border-emerald-500" />
          </div>
        </div>

        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="font-bold text-slate-700 block mb-1">Phone / WhatsApp *</label>
            <input v-model="healthcareForm.phone" type="tel" placeholder="+91 9876543210" required class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2.5 font-bold text-slate-900 focus:bg-white focus:outline-none focus:border-emerald-500" />
          </div>
          <div>
            <label class="font-bold text-slate-700 block mb-1">Email Address</label>
            <input v-model="healthcareForm.email" type="email" placeholder="patient@example.com" class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2.5 font-bold text-slate-900 focus:bg-white focus:outline-none focus:border-emerald-500" />
          </div>
        </div>

        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="font-bold text-slate-700 block mb-1">Specialty / Department *</label>
            <select v-model="healthcareForm.department" class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2.5 font-bold text-slate-800 focus:bg-white focus:outline-none focus:border-emerald-500">
              <option value="General Medicine">General Medicine</option>
              <option value="Cardiology">Cardiology & Heart</option>
              <option value="Orthopedics">Orthopedics & Joints</option>
              <option value="Pediatrics">Pediatrics & Child</option>
              <option value="Dermatology">Dermatology & Skin</option>
              <option value="Dental Care">Dental Care</option>
              <option value="Neurology">Neurology</option>
              <option value="Gynecology">Gynecology</option>
              <option value="ENT">ENT Care</option>
            </select>
          </div>
          <div>
            <label class="font-bold text-slate-700 block mb-1">Appointment Type</label>
            <select v-model="healthcareForm.appointment_type" class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2.5 font-bold text-slate-800 focus:bg-white focus:outline-none focus:border-emerald-500">
              <option value="In-Clinic Consultation">In-Clinic Consultation</option>
              <option value="Online Video Call">Online Video Call</option>
              <option value="Diagnostic Lab Test">Diagnostic Lab Test</option>
              <option value="Second Medical Opinion">Second Opinion</option>
            </select>
          </div>
        </div>

        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="font-bold text-slate-700 block mb-1">Preferred Date</label>
            <input v-model="healthcareForm.preferred_date" type="date" class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2.5 font-bold text-slate-900 focus:bg-white focus:outline-none focus:border-emerald-500" />
          </div>
          <div>
            <label class="font-bold text-slate-700 block mb-1">City / Branch</label>
            <input v-model="healthcareForm.city" type="text" placeholder="Mumbai" class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2.5 font-bold text-slate-900 focus:bg-white focus:outline-none focus:border-emerald-500" />
          </div>
        </div>

        <div>
          <label class="font-bold text-slate-700 block mb-1">Symptoms / Medical Notes</label>
          <textarea v-model="healthcareForm.symptoms" rows="2" placeholder="Briefly describe health concern or symptoms..." class="w-full bg-slate-50 border border-slate-300 rounded-xl p-3 font-medium text-slate-800 focus:bg-white focus:outline-none focus:border-emerald-500"></textarea>
        </div>

        <button 
          type="submit" 
          :disabled="healthcareForm.processing"
          class="w-full py-3.5 bg-emerald-600 hover:bg-emerald-700 text-white font-black text-xs uppercase tracking-wider rounded-2xl shadow-lg shadow-emerald-600/20 transition disabled:opacity-50 cursor-pointer flex items-center justify-center gap-2"
        >
          <div v-if="healthcareForm.processing" class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></div>
          <HeartIcon v-else class="w-4 h-4 stroke-[2.5]" />
          <span>{{ healthcareForm.processing ? 'Booking Appointment...' : '🏥 Request Doctor Appointment 🚀' }}</span>
        </button>
      </form>

      <!-- 4. RECRUITMENT & HR FORM -->
      <form v-else-if="industrySlug === 'recruitment'" @submit.prevent="submitRecruitment" class="space-y-4 text-xs">
        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="font-bold text-slate-700 block mb-1">Candidate Full Name *</label>
            <input v-model="recruitmentForm.first_name" type="text" placeholder="e.g. Vikram Singh" required class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2.5 font-bold text-slate-900 focus:bg-white focus:outline-none focus:border-purple-500" />
          </div>
          <div>
            <label class="font-bold text-slate-700 block mb-1">Target Job Title / Role *</label>
            <input v-model="recruitmentForm.job_title" type="text" placeholder="e.g. Full Stack Developer" required class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2.5 font-bold text-slate-900 focus:bg-white focus:outline-none focus:border-purple-500" />
          </div>
        </div>

        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="font-bold text-slate-700 block mb-1">Phone / WhatsApp *</label>
            <input v-model="recruitmentForm.phone" type="tel" placeholder="+91 9876543210" required class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2.5 font-bold text-slate-900 focus:bg-white focus:outline-none focus:border-purple-500" />
          </div>
          <div>
            <label class="font-bold text-slate-700 block mb-1">Email Address *</label>
            <input v-model="recruitmentForm.email" type="email" placeholder="vikram@example.com" required class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2.5 font-bold text-slate-900 focus:bg-white focus:outline-none focus:border-purple-500" />
          </div>
        </div>

        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="font-bold text-slate-700 block mb-1">Total Experience</label>
            <select v-model="recruitmentForm.experience_years" class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2.5 font-bold text-slate-800 focus:bg-white focus:outline-none focus:border-purple-500">
              <option value="Fresher / 0 Years">Fresher / Entry Level</option>
              <option value="1 - 2 Years">1 - 2 Years</option>
              <option value="3 - 5 Years">3 - 5 Years</option>
              <option value="6 - 9 Years">6 - 9 Years</option>
              <option value="10+ Years">10+ Years</option>
            </select>
          </div>
          <div>
            <label class="font-bold text-slate-700 block mb-1">Notice Period</label>
            <select v-model="recruitmentForm.notice_period" class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2.5 font-bold text-slate-800 focus:bg-white focus:outline-none focus:border-purple-500">
              <option value="Immediate Joiner">Immediate Joiner</option>
              <option value="15 Days">15 Days</option>
              <option value="30 Days">30 Days</option>
              <option value="60 Days">60 Days</option>
              <option value="90 Days">90 Days</option>
            </select>
          </div>
        </div>

        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="font-bold text-slate-700 block mb-1">Expected CTC (₹ / Year)</label>
            <input v-model="recruitmentForm.expected_salary" type="text" placeholder="e.g. 8,50,000" class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2.5 font-bold text-slate-900 focus:bg-white focus:outline-none focus:border-purple-500" />
          </div>
          <div>
            <label class="font-bold text-slate-700 block mb-1">Current Location / City</label>
            <input v-model="recruitmentForm.city" type="text" placeholder="e.g. Pune / Bangalore" class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2.5 font-bold text-slate-900 focus:bg-white focus:outline-none focus:border-purple-500" />
          </div>
        </div>

        <div>
          <label class="font-bold text-slate-700 block mb-1">Key Skills & Experience Summary</label>
          <textarea v-model="recruitmentForm.skills_summary" rows="2" placeholder="e.g. React, Node.js, AWS, MySQL, 3 years exp in SaaS products..." class="w-full bg-slate-50 border border-slate-300 rounded-xl p-3 font-medium text-slate-800 focus:bg-white focus:outline-none focus:border-purple-500"></textarea>
        </div>

        <button 
          type="submit" 
          :disabled="recruitmentForm.processing"
          class="w-full py-3.5 bg-purple-600 hover:bg-purple-700 text-white font-black text-xs uppercase tracking-wider rounded-2xl shadow-lg shadow-purple-600/20 transition disabled:opacity-50 cursor-pointer flex items-center justify-center gap-2"
        >
          <div v-if="recruitmentForm.processing" class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></div>
          <BriefcaseIcon v-else class="w-4 h-4 stroke-[2.5]" />
          <span>{{ recruitmentForm.processing ? 'Submitting Profile...' : '💼 Submit Candidate Profile 🚀' }}</span>
        </button>
      </form>

      <!-- 5. IT & SOFTWARE CRM FORM -->
      <form v-else-if="industrySlug === 'it-software'" @submit.prevent="submitItSoftware" class="space-y-4 text-xs">
        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="font-bold text-slate-700 block mb-1">Your Name *</label>
            <input v-model="itSoftwareForm.first_name" type="text" placeholder="e.g. Amit Patel" required class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2.5 font-bold text-slate-900 focus:bg-white focus:outline-none focus:border-sky-500" />
          </div>
          <div>
            <label class="font-bold text-slate-700 block mb-1">Company / Startup Name</label>
            <input v-model="itSoftwareForm.company_name" type="text" placeholder="e.g. Apex Innovations" class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2.5 font-bold text-slate-900 focus:bg-white focus:outline-none focus:border-sky-500" />
          </div>
        </div>

        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="font-bold text-slate-700 block mb-1">Work Email Address *</label>
            <input v-model="itSoftwareForm.email" type="email" placeholder="amit@company.com" required class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2.5 font-bold text-slate-900 focus:bg-white focus:outline-none focus:border-sky-500" />
          </div>
          <div>
            <label class="font-bold text-slate-700 block mb-1">Phone / WhatsApp *</label>
            <input v-model="itSoftwareForm.phone" type="tel" placeholder="+91 9876543210" required class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2.5 font-bold text-slate-900 focus:bg-white focus:outline-none focus:border-sky-500" />
          </div>
        </div>

        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="font-bold text-slate-700 block mb-1">Service Required *</label>
            <select v-model="itSoftwareForm.service_required" class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2.5 font-bold text-slate-800 focus:bg-white focus:outline-none focus:border-sky-500">
              <option value="Custom Web / Cloud App">Custom Web / SaaS App</option>
              <option value="Mobile App (iOS & Android)">Mobile App (iOS & Android)</option>
              <option value="UI/UX Product Design">UI/UX Product Design</option>
              <option value="AI & Machine Learning">AI & Machine Learning</option>
              <option value="Cloud & DevOps">Cloud & DevOps Migration</option>
              <option value="Dedicated Tech Team">Dedicated Tech Team</option>
            </select>
          </div>
          <div>
            <label class="font-bold text-slate-700 block mb-1">Estimated Budget</label>
            <select v-model="itSoftwareForm.budget" class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2.5 font-bold text-slate-800 focus:bg-white focus:outline-none focus:border-sky-500">
              <option value="< ₹1 Lakh">< ₹1 Lakh</option>
              <option value="₹1 Lakh - ₹5 Lakhs">₹1 Lakh - ₹5 Lakhs</option>
              <option value="₹5 Lakhs - ₹15 Lakhs">₹5 Lakhs - ₹15 Lakhs</option>
              <option value="₹15 Lakhs - ₹50 Lakhs">₹15 Lakhs - ₹50 Lakhs</option>
              <option value="₹50 Lakhs+">₹50 Lakhs+</option>
            </select>
          </div>
        </div>

        <div>
          <label class="font-bold text-slate-700 block mb-1">Project Scope & Requirements Overview</label>
          <textarea v-model="itSoftwareForm.message" rows="2" placeholder="Tell us what you want to build..." class="w-full bg-slate-50 border border-slate-300 rounded-xl p-3 font-medium text-slate-800 focus:bg-white focus:outline-none focus:border-sky-500"></textarea>
        </div>

        <button 
          type="submit" 
          :disabled="itSoftwareForm.processing"
          class="w-full py-3.5 bg-sky-600 hover:bg-sky-700 text-white font-black text-xs uppercase tracking-wider rounded-2xl shadow-lg shadow-sky-600/20 transition disabled:opacity-50 cursor-pointer flex items-center justify-center gap-2"
        >
          <div v-if="itSoftwareForm.processing" class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></div>
          <CodeBracketIcon v-else class="w-4 h-4 stroke-[2.5]" />
          <span>{{ itSoftwareForm.processing ? 'Requesting Proposal...' : '💻 Request Project Proposal 🚀' }}</span>
        </button>
      </form>

      <!-- 6. MATRIMONIAL FORM -->
      <form v-else-if="industrySlug === 'matrimonial'" @submit.prevent="submitMatrimonial" class="space-y-4 text-xs">
        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="font-bold text-slate-700 block mb-1">First Name *</label>
            <input v-model="matrimonialForm.first_name" type="text" placeholder="Priya" required class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2.5 font-bold text-slate-900 focus:bg-white focus:outline-none focus:border-rose-500" />
          </div>
          <div>
            <label class="font-bold text-slate-700 block mb-1">Last Name *</label>
            <input v-model="matrimonialForm.last_name" type="text" placeholder="Sharma" required class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2.5 font-bold text-slate-900 focus:bg-white focus:outline-none focus:border-rose-500" />
          </div>
        </div>

        <div class="grid grid-cols-3 gap-3">
          <div>
            <label class="font-bold text-slate-700 block mb-1">Gender *</label>
            <select v-model="matrimonialForm.gender" class="w-full bg-slate-50 border border-slate-300 rounded-xl px-2.5 py-2.5 font-bold text-slate-800 focus:bg-white focus:outline-none focus:border-rose-500">
              <option value="Female">Female (Bride)</option>
              <option value="Male">Male (Groom)</option>
            </select>
          </div>
          <div>
            <label class="font-bold text-slate-700 block mb-1">Date of Birth *</label>
            <input v-model="matrimonialForm.date_of_birth" type="date" required class="w-full bg-slate-50 border border-slate-300 rounded-xl px-2.5 py-2.5 font-bold text-slate-900 focus:bg-white focus:outline-none focus:border-rose-500" />
          </div>
          <div>
            <label class="font-bold text-slate-700 block mb-1">Height (cm)</label>
            <input v-model="matrimonialForm.height_cm" type="number" placeholder="165" class="w-full bg-slate-50 border border-slate-300 rounded-xl px-2.5 py-2.5 font-bold text-slate-900 focus:bg-white focus:outline-none focus:border-rose-500" />
          </div>
        </div>

        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="font-bold text-slate-700 block mb-1">Caste / Community *</label>
            <input v-model="matrimonialForm.caste" type="text" placeholder="e.g. Jain / Brahmin" required class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2.5 font-bold text-slate-900 focus:bg-white focus:outline-none focus:border-rose-500" />
          </div>
          <div>
            <label class="font-bold text-slate-700 block mb-1">Gotra</label>
            <input v-model="matrimonialForm.gotra" type="text" placeholder="e.g. Kashyap" class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2.5 font-bold text-slate-900 focus:bg-white focus:outline-none focus:border-rose-500" />
          </div>
        </div>

        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="font-bold text-slate-700 block mb-1">Phone Number *</label>
            <input v-model="matrimonialForm.phone" type="tel" placeholder="+91 9876543210" required class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2.5 font-bold text-slate-900 focus:bg-white focus:outline-none focus:border-rose-500" />
          </div>
          <div>
            <label class="font-bold text-slate-700 block mb-1">Email Address *</label>
            <input v-model="matrimonialForm.email" type="email" placeholder="priya@example.com" required class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2.5 font-bold text-slate-900 focus:bg-white focus:outline-none focus:border-rose-500" />
          </div>
        </div>

        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="font-bold text-slate-700 block mb-1">City *</label>
            <input v-model="matrimonialForm.city" type="text" placeholder="Mumbai" required class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2.5 font-bold text-slate-900 focus:bg-white focus:outline-none focus:border-rose-500" />
          </div>
          <div>
            <label class="font-bold text-slate-700 block mb-1">State *</label>
            <input v-model="matrimonialForm.state" type="text" placeholder="Maharashtra" required class="w-full bg-slate-50 border border-slate-300 rounded-xl px-3.5 py-2.5 font-bold text-slate-900 focus:bg-white focus:outline-none focus:border-rose-500" />
          </div>
        </div>

        <button 
          type="submit" 
          :disabled="matrimonialForm.processing"
          class="w-full py-3.5 bg-rose-600 hover:bg-rose-700 text-white font-black text-xs uppercase tracking-wider rounded-2xl shadow-lg shadow-rose-600/20 transition disabled:opacity-50 cursor-pointer flex items-center justify-center gap-2"
        >
          <div v-if="matrimonialForm.processing" class="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin"></div>
          <HeartIcon v-else class="w-4 h-4 stroke-[2.5]" />
          <span>{{ matrimonialForm.processing ? 'Submitting Profile...' : '💍 Submit Bio-Data Profile 🚀' }}</span>
        </button>
      </form>

      <!-- Footer Info -->
      <div class="text-[11px] text-slate-400 text-center font-medium pt-2 border-t border-slate-100 flex items-center justify-center gap-1.5">
        <SparklesIcon class="w-3.5 h-3.5 text-slate-400" />
        <span>Powered by <strong>{{ business.name }}</strong> CRM Integration Gateway</span>
      </div>

    </div>
  </div>
</template>

