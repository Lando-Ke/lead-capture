<template>
  <div class="space-y-6">
    <div class="text-center mb-8">
      <h2 class="text-2xl font-bold text-gray-900 mb-2">Review & Submit</h2>
      <p class="text-gray-600">Please review your information before submitting.</p>
    </div>

    <div class="space-y-6">
      <!-- Basic Information -->
      <div class="bg-gray-50 rounded-lg p-6">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-lg font-medium text-gray-900">Basic Information</h3>
          <button
            type="button"
            class="px-3 py-1 text-sm text-gray-600 hover:text-gray-900 font-medium"
            @click="emit('edit', 0)"
          >
            Edit
          </button>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
          <div>
            <span class="text-gray-500">Name</span>
            <p class="font-medium">
              {{ formData.name || 'Not provided' }}
            </p>
          </div>
          <div>
            <span class="text-gray-500">Email</span>
            <p class="font-medium">
              {{ formData.email || 'Not provided' }}
            </p>
          </div>
          <div>
            <span class="text-gray-500">Company</span>
            <p class="font-medium">
              {{ formData.company || 'Not provided' }}
            </p>
          </div>
          <div>
            <span class="text-gray-500">Website URL</span>
            <p class="font-medium">
              {{ formData.website_url || 'Not provided' }}
            </p>
          </div>
        </div>
      </div>

      <!-- Website Details -->
      <div class="bg-gray-50 rounded-lg p-6">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-lg font-medium text-gray-900">Website Details</h3>
          <button
            type="button"
            class="px-3 py-1 text-sm text-gray-600 hover:text-gray-900 font-medium"
            @click="emit('edit', 1)"
          >
            Edit
          </button>
        </div>

        <div class="text-sm">
          <span class="text-gray-500">Website Type</span>
          <div class="flex items-center mt-1">
            <span class="text-lg mr-2">{{ getWebsiteTypeIcon(formData.website_type) }}</span>
            <p class="font-medium">
              {{ getWebsiteTypeLabel(formData.website_type) }}
            </p>
          </div>
          <p class="text-gray-600 mt-1">
            {{ getWebsiteTypeDescription(formData.website_type) }}
          </p>
        </div>
      </div>

      <!-- Platform -->
      <div v-if="formData.website_type && formData.platform_id" class="bg-gray-50 rounded-lg p-6">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-lg font-medium text-gray-900">Platform</h3>
          <button
            type="button"
            class="px-3 py-1 text-sm text-gray-600 hover:text-gray-900 font-medium"
            @click="emit('edit', 2)"
          >
            Edit
          </button>
        </div>

        <div class="text-sm">
          <span class="text-gray-500">Selected Platform</span>
          <div class="flex items-center mt-1">
            <img
              :src="props.platformStore.getPlatformLogoById(formData.platform_id)"
              :alt="getPlatformLabel(formData.platform_id)"
              class="w-5 h-5 mr-2 object-contain"
              @error="handleImageError"
            />
            <p class="font-medium">
              {{ getPlatformLabel(formData.platform_id) }}
            </p>
          </div>
        </div>
      </div>
    </div>

    <!-- Terms and Privacy -->
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
      <div class="flex items-start">
        <InformationCircleIcon class="h-5 w-5 text-blue-400 mt-0.5 mr-2 flex-shrink-0" />
        <div class="text-sm">
          <p class="text-blue-800">
            By submitting this form, you agree to our
            <a href="#" class="text-blue-600 hover:underline font-medium">Terms of Service</a>
            and
            <a href="#" class="text-blue-600 hover:underline font-medium">Privacy Policy</a>. We'll
            use this information to provide you with a personalized consultation.
          </p>
        </div>
      </div>
    </div>

    <!-- Navigation -->
    <div class="flex justify-between pt-8">
      <button
        type="button"
        class="flex items-center px-6 py-3 rounded-lg text-sm font-medium text-gray-600 bg-gray-100 hover:bg-gray-200 transition-colors duration-200"
        @click="emit('previous')"
      >
        <ChevronLeftIcon class="w-4 h-4 mr-2" />
        Previous
      </button>

      <button
        type="button"
        :disabled="!canSubmit || isSubmitting"
        :class="[
          'flex items-center px-8 py-3 rounded-lg text-sm font-medium transition-colors duration-200',
          canSubmit && !isSubmitting
            ? 'bg-green-600 text-white hover:bg-green-700 focus:ring-2 focus:ring-green-500 focus:ring-offset-2'
            : 'bg-gray-300 text-gray-500 cursor-not-allowed',
        ]"
        @click="emit('submit')"
      >
        <div v-if="isSubmitting" class="flex items-center">
          <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-white mr-2" />
          Submitting...
        </div>
        <div v-else class="flex items-center">
          <CheckIcon class="w-4 h-4 mr-2" />
          Submit
        </div>
      </button>
    </div>

    <!-- Form Validation Summary -->
    <div v-if="!canSubmit && !isSubmitting" class="bg-red-50 border border-red-200 rounded-lg p-4">
      <div class="flex items-start">
        <ExclamationTriangleIcon class="h-5 w-5 text-red-400 mt-0.5 mr-2 flex-shrink-0" />
        <div class="text-sm">
          <h4 class="text-red-800 font-medium">Please complete the following:</h4>
          <ul class="mt-2 text-red-700 list-disc list-inside space-y-1">
            <li v-if="!formData.name">Full name is required</li>
            <li v-if="!formData.email">Email address is required</li>
            <li v-if="!formData.company">Company name is required</li>
            <li v-if="!formData.website_type">Website type selection is required</li>
            <li v-if="formData.website_type && !formData.platform_id">
              Platform selection is required
            </li>
          </ul>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import {
  ChevronLeftIcon,
  CheckIcon,
  InformationCircleIcon,
  ExclamationTriangleIcon,
} from '@heroicons/vue/24/outline'

// Props
const props = defineProps({
  formData: {
    type: Object,
    required: true,
  },
  isSubmitting: {
    type: Boolean,
    default: false,
  },
  platformStore: {
    type: Object,
    required: true,
  },
})

// Emits
const emit = defineEmits(['edit', 'previous', 'submit'])

// Computed
const canSubmit = computed(() => {
  const data = props.formData
  const basicRequirements = data.name && data.email && data.company && data.website_type
  const platformRequirement = data.platform_id

  return basicRequirements && platformRequirement
})

// Methods
const getWebsiteTypeLabel = websiteType => {
  return props.platformStore.getWebsiteTypeLabel(websiteType)
}

const getWebsiteTypeDescription = websiteType => {
  return props.platformStore.getWebsiteTypeDescription(websiteType)
}

const getWebsiteTypeIcon = websiteType => {
  return props.platformStore.getWebsiteTypeIcon(websiteType)
}

const getPlatformLabel = platformId => {
  return props.platformStore.getPlatformLabelById(platformId)
}

const handleImageError = event => {
  // Hide broken images and show fallback
  event.target.style.display = 'none'
}
</script>
