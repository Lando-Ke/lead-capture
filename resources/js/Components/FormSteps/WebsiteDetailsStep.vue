<template>
  <div class="space-y-6">
    <div class="text-center mb-8">
      <h2 class="text-2xl font-bold text-gray-900 mb-2">
        Website Details
      </h2>
      <p class="text-gray-600">
        Tell us about the type of website you're looking to build or improve.
      </p>
    </div>

    <div>
      <label class="block text-sm font-medium text-gray-700 mb-4">
        Website Type <span class="text-red-500">*</span>
      </label>
      
      <div class="space-y-3">
        <div
          v-for="type in websiteTypes"
          :key="type.value"
          class="relative"
        >
          <label 
            class="flex items-start p-4 border rounded-lg cursor-pointer hover:bg-gray-50 transition-colors duration-200"
            :class="{ 
              'border-gray-900 bg-gray-50 ring-2 ring-gray-900': formData.website_type === type.value,
              'border-gray-300': formData.website_type !== type.value 
            }"
          >
            <input
              v-model="formData.website_type"
              :value="type.value"
              type="radio"
              class="mt-1 h-4 w-4 text-gray-900 border-gray-300 focus:ring-gray-900 focus:ring-2"
              @change="handleWebsiteTypeChange(type.value)"
            >
            <div class="ml-3 flex-1">
              <div class="flex items-center">
                <span class="text-lg mr-2">{{ type.icon }}</span>
                <span class="font-medium text-gray-900">{{ type.label }}</span>
              </div>
              <p class="text-sm text-gray-600 mt-1">{{ type.description }}</p>
            </div>
          </label>
        </div>
      </div>
      
      <p v-if="errors.website_type" class="mt-2 text-sm text-red-600">
        {{ errors.website_type[0] }}
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
import { computed } from 'vue'
import { ChevronLeftIcon, ChevronRightIcon } from '@heroicons/vue/24/outline'
import { useLeadStore } from '@/stores/leadStore'
import { usePlatformStore } from '@/stores/platformStore'

// Props
const props = defineProps({
  errors: {
    type: Object,
    default: () => ({})
  }
})

// Emits
const emit = defineEmits(['next', 'previous', 'website-type-change'])

// Stores
const leadStore = useLeadStore()
const platformStore = usePlatformStore()

// Computed
const formData = computed(() => leadStore.formData)
const websiteTypes = computed(() => platformStore.websiteTypes)

const canProceed = computed(() => {
  return formData.value.website_type && !props.errors.website_type
})

// Methods
const handleWebsiteTypeChange = (websiteType) => {
  leadStore.updateFormField('website_type', websiteType)
  emit('website-type-change', websiteType)
}

const handleNext = () => {
  if (!formData.value.website_type) {
    leadStore.setError('website_type', 'Please select a website type.')
    return
  }
  
  if (canProceed.value) {
    emit('next')
  }
}
</script> 