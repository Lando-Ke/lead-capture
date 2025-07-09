<template>
  <div class="space-y-6">
    <div class="text-center mb-8">
      <h2 class="text-2xl font-bold text-gray-900 mb-2">Platform Selection</h2>
      <p v-if="websiteType === 'ecommerce'" class="text-gray-600">
        Select the e-commerce platform you're using or planning to use.
      </p>
      <p v-else-if="websiteType === 'blog'" class="text-gray-600">
        Select the blogging platform you're using or planning to use.
      </p>
      <p v-else-if="websiteType === 'business'" class="text-gray-600">
        Select the platform you're using or planning to use for your business website.
      </p>
      <p v-else-if="websiteType === 'portfolio'" class="text-gray-600">
        Select the platform you're using or planning to use for your portfolio.
      </p>
      <p v-else class="text-gray-600">
        Select the platform you're using or planning to use for your
        {{ getWebsiteTypeLabel(websiteType) }} website.
      </p>
    </div>

    <!-- Platform Selection for All Website Types -->
    <div v-if="websiteType">
      <label class="block text-sm font-medium text-gray-700 mb-4">
        Platform <span class="text-red-500">*</span>
      </label>

      <!-- Loading State -->
      <div v-if="isLoading" class="flex items-center justify-center py-8">
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-gray-900" />
        <span class="ml-2 text-gray-600">Loading platforms...</span>
      </div>

      <!-- Platform Options -->
      <div v-else-if="availablePlatforms.length > 0" class="space-y-3">
        <div v-for="platform in availablePlatforms" :key="platform.id" class="relative">
          <label
            class="flex items-start p-4 border rounded-lg cursor-pointer hover:bg-gray-50 transition-colors duration-200"
            :class="{
              'border-gray-900 bg-gray-50 ring-2 ring-gray-900':
                formData.platform_id === platform.id,
              'border-gray-300': formData.platform_id !== platform.id,
            }"
          >
            <input
              v-model="formData.platform_id"
              :value="platform.id"
              type="radio"
              class="mt-1 h-4 w-4 text-gray-900 border-gray-300 focus:ring-gray-900 focus:ring-2"
              @change="handlePlatformChange(platform.id)"
            />
            <div class="ml-3 flex-1">
              <div class="flex items-center">
                <img
                  :src="platformStore.getPlatformLogoById(platform.id)"
                  :alt="platform.name"
                  class="w-6 h-6 mr-2 object-contain"
                  @error="handleImageError"
                />
                <span class="font-medium text-gray-900">{{ platform.name }}</span>
              </div>
              <p class="text-sm text-gray-600 mt-1">{{ platform.description }}</p>
            </div>
          </label>
        </div>
      </div>

      <!-- No Platforms Found -->
      <div v-else class="text-center py-8">
        <div class="text-gray-400 mb-4">
          <ExclamationTriangleIcon class="w-16 h-16 mx-auto" />
        </div>
        <h3 class="text-lg font-medium text-gray-900 mb-2">No Platforms Available</h3>
        <p class="text-gray-600">We couldn't load the available platforms. Please try again.</p>
        <button
          type="button"
          class="mt-4 px-4 py-2 bg-gray-900 text-white rounded-lg hover:bg-gray-800 transition-colors duration-200"
          @click="retryLoadPlatforms"
        >
          Retry
        </button>
      </div>

      <p v-if="errors.platform_id" class="mt-2 text-sm text-red-600">
        {{ errors.platform_id[0] }}
      </p>
    </div>

    <!-- No Website Type Selected -->
    <div v-else class="text-center py-8">
      <div class="text-gray-400 mb-4">
        <ExclamationTriangleIcon class="w-16 h-16 mx-auto" />
      </div>
      <h3 class="text-lg font-medium text-gray-900 mb-2">Website Type Required</h3>
      <p class="text-gray-600">
        Please go back and select a website type to see available platforms.
      </p>
    </div>

    <!-- Navigation -->
    <div class="flex justify-between pt-8">
      <button
        type="button"
        class="flex items-center px-6 py-3 rounded-lg text-sm font-medium text-gray-600 bg-gray-100 hover:bg-gray-200 transition-colors duration-200"
        @click="$emit('previous')"
      >
        <ChevronLeftIcon class="w-4 h-4 mr-2" />
        Previous
      </button>

      <button
        type="button"
        :disabled="!canProceed"
        :class="[
          'flex items-center px-6 py-3 rounded-lg text-sm font-medium transition-colors duration-200',
          canProceed
            ? 'bg-gray-900 text-white hover:bg-gray-800 focus:ring-2 focus:ring-gray-900 focus:ring-offset-2'
            : 'bg-gray-300 text-gray-500 cursor-not-allowed',
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
import { computed } from 'vue'
import {
  ChevronLeftIcon,
  ChevronRightIcon,
  ExclamationTriangleIcon,
} from '@heroicons/vue/24/outline'
import { useLeadStore } from '@/stores/leadStore'
import { usePlatformStore } from '@/stores/platformStore'

// Props
const props = defineProps({
  errors: {
    type: Object,
    default: () => ({}),
  },
  websiteType: {
    type: String,
    default: '',
  },
  availablePlatforms: {
    type: Array,
    default: () => [],
  },
  isLoading: {
    type: Boolean,
    default: false,
  },
})

// Emits
const emit = defineEmits(['next', 'previous'])

// Stores
const leadStore = useLeadStore()
const platformStore = usePlatformStore()

// Computed
const formData = computed(() => leadStore.formData)

const canProceed = computed(() => {
  // Platform selection is required for all website types
  return props.websiteType && formData.value.platform_id && !props.errors.platform_id
})

// Methods
const getWebsiteTypeLabel = websiteType => {
  return platformStore.getWebsiteTypeLabel(websiteType)
}

const handlePlatformChange = platformId => {
  leadStore.updateFormField('platform_id', platformId)
}

const handleImageError = event => {
  // Hide broken images
  event.target.style.display = 'none'
}

const retryLoadPlatforms = async () => {
  if (props.websiteType) {
    await platformStore.fetchPlatformsByWebsiteType(props.websiteType)
  }
}

const handleNext = () => {
  if (!formData.value.platform_id) {
    leadStore.setError(
      'platform_id',
      `Please select a platform for your ${getWebsiteTypeLabel(props.websiteType)} website.`
    )
    return
  }

  if (canProceed.value) {
    emit('next')
  }
}
</script>
