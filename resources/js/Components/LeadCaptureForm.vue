<template>
  <div class="max-w-4xl mx-auto">
    <!-- Header -->
    <div class="text-center mb-8">
      <h1 class="text-3xl font-bold text-gray-900 mb-4">Get Started with Your Project</h1>
      <p class="text-gray-600">
        Fill out this form to tell us about your project needs. We'll get back to you within 24
        hours with a personalized consultation.
      </p>
    </div>

    <!-- Progress Indicator -->
    <div class="bg-white rounded-lg shadow-sm border p-4 md:p-6 mb-6">
      <!-- Mobile Progress Bar -->
      <div class="block md:hidden">
        <div class="flex items-center justify-between mb-2">
          <span class="text-sm font-medium text-gray-700"
            >Step {{ currentStep + 1 }} of {{ steps.length }}</span
          >
          <span class="text-sm text-gray-500"
            >{{ Math.round(((currentStep + 1) / steps.length) * 100) }}%</span
          >
        </div>
        <div class="w-full bg-gray-200 rounded-full h-2">
          <div
            class="bg-gray-900 h-2 rounded-full transition-all duration-300 ease-in-out"
            :style="{ width: `${((currentStep + 1) / steps.length) * 100}%` }"
          />
        </div>
        <div class="mt-2 text-center">
          <span class="text-sm font-medium text-gray-900">{{ steps[currentStep].title }}</span>
        </div>
      </div>

      <!-- Desktop Step Indicators -->
      <div class="hidden md:flex items-center justify-between">
        <div
          v-for="(step, index) in steps"
          :key="index"
          class="flex items-center"
          :class="{ 'flex-1': index < steps.length - 1 }"
        >
          <div class="flex items-center">
            <div
              :class="[
                'flex items-center justify-center w-10 h-10 rounded-full border-2 text-sm font-medium transition-all duration-200',
                index < currentStep
                  ? 'bg-gray-900 border-gray-900 text-white'
                  : index === currentStep
                    ? 'bg-white border-gray-900 text-gray-900'
                    : 'bg-gray-100 border-gray-300 text-gray-400',
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
              'flex-1 h-px mx-4 transition-all duration-300',
              index < currentStep ? 'bg-gray-900' : 'bg-gray-200',
            ]"
          />
        </div>
      </div>
    </div>

    <!-- Form Steps -->
    <div class="bg-white rounded-lg shadow-sm border p-4 md:p-8">
      <form @submit.prevent="handleSubmit">
        <!-- Step 1: Basic Information -->
        <div v-show="currentStep === 0">
          <BasicInformationStep :errors="leadStore.errors" @next="nextStep" />
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
      <a href="mailto:support@example.com" class="text-blue-600 hover:underline"
        >support@example.com</a
      >
    </div>

    <!-- Success Modal -->
    <SuccessModal
      v-if="showSuccess"
      :lead-data="submittedLead"
      :notification-status="leadStore.notificationStatus"
      @close="resetForm"
    />

    <!-- Validation Error Alert -->
    <div
      v-if="
        leadStore.hasErrors &&
        (leadStore.errors.email || leadStore.errors.name || leadStore.errors.company)
      "
      class="fixed top-4 right-4 bg-red-50 border border-red-200 rounded-lg p-4 shadow-lg z-50 max-w-md"
    >
      <div class="flex items-start">
        <ExclamationTriangleIcon class="h-5 w-5 text-red-400 mr-2 flex-shrink-0 mt-0.5" />
        <div class="flex-1">
          <h4 class="text-sm font-medium text-red-800">Please fix the following:</h4>
          <ul class="mt-1 text-sm text-red-700 space-y-1">
            <li v-if="leadStore.errors.email">{{ leadStore.errors.email[0] }}</li>
            <li v-if="leadStore.errors.name">{{ leadStore.errors.name[0] }}</li>
            <li v-if="leadStore.errors.company">{{ leadStore.errors.company[0] }}</li>
          </ul>
          <button
            class="mt-2 text-xs text-red-600 hover:text-red-800 underline"
            @click="leadStore.clearErrors()"
          >
            Dismiss
          </button>
        </div>
      </div>
    </div>

    <!-- General Error Alert -->
    <div
      v-if="leadStore.hasErrors && leadStore.errors.general"
      class="fixed top-4 right-4 bg-red-50 border border-red-200 rounded-lg p-4 shadow-lg z-50"
    >
      <div class="flex items-center">
        <ExclamationTriangleIcon class="h-5 w-5 text-red-400 mr-2" />
        <p class="text-sm text-red-800">
          {{ leadStore.errors.general[0] }}
        </p>
        <button
          class="ml-2 text-red-400 hover:text-red-600"
          @click="leadStore.clearError('general')"
        >
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

const goToStep = step => {
  // Validate that we can go to this step
  if (step >= 0 && step < steps.length) {
    currentStep.value = step
  }
}

const handleWebsiteTypeChange = async websiteType => {
  leadStore.updateFormField('website_type', websiteType)

  // Fetch platforms for the selected website type
  if (websiteType) {
    await platformStore.fetchPlatformsByWebsiteType(websiteType)
    // Clear previous platform selection when website type changes
    leadStore.updateFormField('platform_id', null)
  } else {
    platformStore.clearAvailablePlatforms()
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
  } catch {
    // Log error for debugging

    // Navigate back to the appropriate step if there are field errors
    if (leadStore.hasErrors) {
      // Check which step has errors and navigate to the first one
      if (
        leadStore.errors.name ||
        leadStore.errors.email ||
        leadStore.errors.company ||
        leadStore.errors.website_url
      ) {
        currentStep.value = 0 // Basic Information step
      } else if (leadStore.errors.website_type) {
        currentStep.value = 1 // Website Details step
      } else if (leadStore.errors.platform_id) {
        currentStep.value = 2 // Platform Selection step
      }
    }
  }
}

const resetForm = () => {
  currentStep.value = 0
  showSuccess.value = false
  submittedLead.value = null
  leadStore.resetForm()
  platformStore.clearAvailablePlatforms()
}

// Lifecycle
onMounted(async () => {
  await platformStore.initializePlatforms()
})
</script>
