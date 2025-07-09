<template>
  <div class="space-y-6">
    <div class="text-center mb-8">
      <h2 class="text-2xl font-bold text-gray-900 mb-2">Basic Information</h2>
      <p class="text-gray-600">Let's start with some basic information about you and your company.</p>
    </div>

    <div class="grid grid-cols-1 gap-6">
      <!-- Full Name -->
      <div>
        <label for="name" class="block text-sm font-medium text-gray-700 mb-1">
          Full Name <span class="text-red-500">*</span>
        </label>
        <input
          id="name"
          v-model="formData.name"
          type="text"
          placeholder="Enter your full name"
          class="w-full px-3 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-900 focus:border-gray-900 transition-colors duration-200"
          :class="{ 'border-red-500 focus:ring-red-500 focus:border-red-500': errors.name }"
          @input="handleFieldUpdate('name', $event.target.value)"
          @blur="validateField('name')"
        >
        <p v-if="errors.name" class="mt-1 text-sm text-red-600">{{ errors.name[0] }}</p>
      </div>

      <!-- Email Address -->
      <div>
        <label for="email" class="block text-sm font-medium text-gray-700 mb-1">
          Email Address <span class="text-red-500">*</span>
        </label>
        <input
          id="email"
          v-model="formData.email"
          type="email"
          placeholder="you@example.com"
          class="w-full px-3 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-900 focus:border-gray-900 transition-colors duration-200"
          :class="{ 'border-red-500 focus:ring-red-500 focus:border-red-500': errors.email }"
          @input="handleFieldUpdate('email', $event.target.value)"
          @blur="handleEmailBlur"
        >
        <p v-if="errors.email" class="mt-1 text-sm text-red-600">{{ errors.email[0] }}</p>
        <p v-if="emailWarning" class="mt-1 text-sm text-amber-600 flex items-center">
          <ExclamationTriangleIcon class="h-4 w-4 mr-1" />
          {{ emailWarning }}
        </p>
      </div>

      <!-- Company Name -->
      <div>
        <label for="company" class="block text-sm font-medium text-gray-700 mb-1">
          Company Name <span class="text-red-500">*</span>
        </label>
        <input
          id="company"
          v-model="formData.company"
          type="text"
          placeholder="Enter your company name"
          class="w-full px-3 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-900 focus:border-gray-900 transition-colors duration-200"
          :class="{ 'border-red-500 focus:ring-red-500 focus:border-red-500': errors.company }"
          @input="handleFieldUpdate('company', $event.target.value)"
          @blur="validateField('company')"
        >
        <p v-if="errors.company" class="mt-1 text-sm text-red-600">{{ errors.company[0] }}</p>
      </div>

      <!-- Website URL -->
      <div>
        <label for="website_url" class="block text-sm font-medium text-gray-700 mb-1">
          Website URL <span class="text-gray-400">(optional)</span>
        </label>
        <input
          id="website_url"
          v-model="formData.website_url"
          type="url"
          placeholder="https://example.com"
          class="w-full px-3 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-gray-900 focus:border-gray-900 transition-colors duration-200"
          :class="{ 'border-red-500 focus:ring-red-500 focus:border-red-500': errors.website_url }"
          @input="handleFieldUpdate('website_url', $event.target.value)"
          @blur="validateField('website_url')"
        >
        <p v-if="errors.website_url" class="mt-1 text-sm text-red-600">{{ errors.website_url[0] }}</p>
        <p class="mt-1 text-xs text-gray-500">If you have an existing website, please share the URL so we can review it.</p>
      </div>
    </div>

    <!-- Navigation -->
    <div class="flex justify-end pt-8">
      <button
        type="button"
        :disabled="!canProceed"
        :class="[
          'flex items-center px-6 py-3 rounded-lg text-sm font-medium transition-colors duration-200',
          canProceed
            ? 'bg-gray-900 text-white hover:bg-gray-800 focus:ring-2 focus:ring-gray-900 focus:ring-offset-2'
            : 'bg-gray-300 text-gray-500 cursor-not-allowed'
        ]"
        @click="handleNext"
      >
        Next
        <ChevronRightIcon class="w-4 h-4 ml-2" />
      </button>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import { ExclamationTriangleIcon, ChevronRightIcon } from '@heroicons/vue/24/outline'
import { useLeadStore } from '@/stores/leadStore'

// Props
const props = defineProps({
  errors: {
    type: Object,
    default: () => ({})
  }
})

// Emits
const emit = defineEmits(['next'])

// Store
const leadStore = useLeadStore()

// State
const emailWarning = ref('')
const emailCheckTimeout = ref(null)

// Computed
const formData = computed(() => leadStore.formData)

const canProceed = computed(() => {
  return formData.value.name && 
         formData.value.email && 
         formData.value.company &&
         leadStore.validateEmail(formData.value.email) &&
         !props.errors.name &&
         !props.errors.email &&
         !props.errors.company
})

// Methods
const handleFieldUpdate = (field, value) => {
  leadStore.updateFormField(field, value)
  validateField(field)
}

const validateField = (field) => {
  const value = formData.value[field]
  
  switch (field) {
    case 'name':
      if (!value || value.trim().length < 2) {
        leadStore.setError(field, 'Your full name must be at least 2 characters.')
      }
      break
    case 'email':
      if (!value) {
        leadStore.setError(field, 'Your email address is required.')
      } else if (!leadStore.validateEmail(value)) {
        leadStore.setError(field, 'Please provide a valid email address.')
      }
      break
    case 'company':
      if (!value || value.trim().length < 2) {
        leadStore.setError(field, 'Your company name must be at least 2 characters.')
      }
      break
    case 'website_url':
      if (value && !isValidUrl(value)) {
        leadStore.setError(field, 'Please provide a valid website URL.')
      }
      break
  }
}

const isValidUrl = (url) => {
  try {
    new URL(url)
    return true
  } catch {
    return false
  }
}

const handleEmailBlur = async () => {
  const email = formData.value.email
  
  // Clear previous timeout
  if (emailCheckTimeout.value) {
    clearTimeout(emailCheckTimeout.value)
  }
  
  // Clear previous warning
  emailWarning.value = ''
  
  if (email && leadStore.validateEmail(email)) {
    // Debounce email checking
    emailCheckTimeout.value = setTimeout(async () => {
      try {
        const exists = await leadStore.checkEmailExists(email)
        if (exists) {
          emailWarning.value = 'A submission with this email already exists.'
        }
      } catch (error) {
        console.error('Error checking email:', error)
      }
    }, 500)
  }
  
  validateField('email')
}

const handleNext = () => {
  // Validate all fields before proceeding
  validateField('name')
  validateField('email')
  validateField('company')
  if (formData.value.website_url) {
    validateField('website_url')
  }
  
  if (canProceed.value) {
    emit('next')
  }
}
</script> 