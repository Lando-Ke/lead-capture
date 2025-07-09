<template>
  <div class="max-w-4xl mx-auto">
    <!-- Header -->
    <div class="text-center mb-8">
      <h1 class="text-3xl font-bold text-gray-900 mb-4">Get Started with Your Project</h1>
      <p class="text-gray-600">Fill out this form to tell us about your project needs. We'll get back to you within 24 hours with a personalized consultation.</p>
    </div>

    <!-- Progress Indicator -->
    <div class="bg-white rounded-lg shadow-sm border p-6 mb-6">
      <div class="flex items-center justify-between">
        <div 
          v-for="(step, index) in steps"
          :key="index"
          class="flex items-center"
          :class="{ 'flex-1': index < steps.length - 1 }"
        >
          <div class="flex items-center">
            <div
              :class="[
                'flex items-center justify-center w-10 h-10 rounded-full border-2 text-sm font-medium',
                index < currentStep
                  ? 'bg-gray-900 border-gray-900 text-white'
                  : index === currentStep
                  ? 'bg-white border-gray-900 text-gray-900'
                  : 'bg-gray-100 border-gray-300 text-gray-400'
              ]"
            >
              <CheckIcon v-if="index < currentStep" class="w-5 h-5" />
              <span v-else>{{ index + 1 }}</span>
            </div>
            <span class="ml-2 text-sm font-medium text-gray-700">{{ step.title }}</span>
          </div>
          <div
            v-if="index < steps.length - 1"
            :class="[
              'flex-1 h-px mx-4',
              index < currentStep ? 'bg-gray-900' : 'bg-gray-200'
            ]"
          />
        </div>
      </div>
    </div>

    <!-- Form Steps -->
    <div class="bg-white rounded-lg shadow-sm border p-8">
      <form @submit.prevent="handleSubmit">
        <!-- Step 1: Basic Information -->
        <div v-show="currentStep === 0">
          <BasicInformationStep
            :errors="leadStore.errors"
            @next="nextStep"
          />
        </div>

        <!-- Step 2: Website Details -->
        <div v-show="currentStep === 1">
          <WebsiteDetailsStep
            :errors="leadStore.errors"
            @next="nextStep"
            @previous="previousStep"
            @website-type-change="handleWebsiteTypeChange"
          />
        </div>

        <!-- Step 3: Platform Selection -->
        <div v-show="currentStep === 2">
          <PlatformSelectionStep
            :errors="leadStore.errors"
            :website-type="leadStore.formData.website_type"
            :available-platforms="platformStore.availablePlatforms"
            :is-loading="platformStore.isLoading"
            @next="nextStep"
            @previous="previousStep"
          />
        </div>

        <!-- Step 4: Review & Submit -->
        <div v-show="currentStep === 3">
          <ReviewStep
            :form-data="leadStore.formData"
            :is-submitting="leadStore.isSubmitting"
            :platform-store="platformStore"
            @edit="goToStep"
            @submit="handleSubmit"
            @previous="previousStep"
          />
        </div>
      </form>
    </div>

    <!-- Support Contact -->
    <div class="text-center mt-6 text-sm text-gray-500">
      Need help? Contact our support team at 
      <a href="mailto:support@example.com" class="text-blue-600 hover:underline">support@example.com</a>
    </div>

    <!-- Success Modal -->
    <SuccessModal
      v-if="showSuccess"
      :lead-data="submittedLead"
      @close="resetForm"
    />

    <!-- Error Alert -->
    <div 
      v-if="leadStore.hasErrors && leadStore.errors.general"
      class="fixed top-4 right-4 bg-red-50 border border-red-200 rounded-lg p-4 shadow-lg z-50"
    >
      <div class="flex items-center">
        <ExclamationTriangleIcon class="h-5 w-5 text-red-400 mr-2" />
        <p class="text-sm text-red-800">{{ leadStore.errors.general[0] }}</p>
        <button @click="leadStore.clearError('general')" class="ml-2 text-red-400 hover:text-red-600">
          <XMarkIcon class="h-4 w-4" />
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { CheckIcon, ExclamationTriangleIcon, XMarkIcon } from '@heroicons/vue/24/solid'
import { useLeadStore } from '@/stores/leadStore'
import { usePlatformStore } from '@/stores/platformStore'
import BasicInformationStep from './FormSteps/BasicInformationStep.vue'
import WebsiteDetailsStep from './FormSteps/WebsiteDetailsStep.vue'
import PlatformSelectionStep from './FormSteps/PlatformSelectionStep.vue'
import ReviewStep from './FormSteps/ReviewStep.vue'
import SuccessModal from './SuccessModal.vue'

// Stores
const leadStore = useLeadStore()
const platformStore = usePlatformStore()

// State
const currentStep = ref(0)
const showSuccess = ref(false)
const submittedLead = ref(null)

const steps = [
  { number: 1, title: 'Basic Info' },
  { number: 2, title: 'Website Details' },
  { number: 3, title: 'Platform' },
  { number: 4, title: 'Review' },
]

// Computed
const canProceedToNextStep = computed(() => {
  return leadStore.validateStep(currentStep.value)
})

const canSubmit = computed(() => {
  return leadStore.validateStep(3) && !leadStore.isSubmitting
})

// Methods
const nextStep = () => {
  if (canProceedToNextStep.value && currentStep.value < steps.length - 1) {
    currentStep.value++
  }
}

const previousStep = () => {
  if (currentStep.value > 0) {
    currentStep.value--
  }
}

const goToStep = (step) => {
  // Validate that we can go to this step
  if (step >= 0 && step < steps.length) {
    currentStep.value = step
  }
}

const handleWebsiteTypeChange = async (websiteType) => {
  leadStore.updateFormField('website_type', websiteType)
  
  if (websiteType === 'ecommerce') {
    await platformStore.fetchPlatformsByWebsiteType(websiteType)
  } else {
    // Clear platform selection for non-ecommerce sites
    leadStore.updateFormField('platform', '')
    platformStore.clearFilteredPlatforms()
  }
}

const handleSubmit = async () => {
  if (!canSubmit.value) return
  
  try {
    const result = await leadStore.submitLead()
    
    if (result.success) {
      submittedLead.value = result.data
      showSuccess.value = true
    }
  } catch (error) {
    console.error('Submission error:', error)
    // Error is already handled in the store
  }
}

const resetForm = () => {
  currentStep.value = 0
  showSuccess.value = false
  submittedLead.value = null
  leadStore.resetForm()
  platformStore.clearFilteredPlatforms()
}

// Lifecycle
onMounted(async () => {
  await platformStore.initializePlatforms()
})
</script> 