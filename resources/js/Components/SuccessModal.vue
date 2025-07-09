<template>
  <div class="fixed inset-0 z-50 overflow-y-auto">
    <div class="flex min-h-full items-center justify-center p-4">
      <!-- Background overlay -->
      <div
        class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"
        @click="$emit('close')"
      />

      <!-- Modal content -->
      <div
        class="relative transform overflow-hidden rounded-lg bg-white px-4 pt-5 pb-4 text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg sm:p-6"
      >
        <div>
          <!-- Success icon -->
          <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-green-100">
            <CheckIcon class="h-6 w-6 text-green-600" />
          </div>

          <!-- Success message -->
          <div class="mt-3 text-center sm:mt-5">
            <h3 class="text-lg font-medium leading-6 text-gray-900">
              Thank you for your submission!
            </h3>
            <div class="mt-2">
              <p class="text-sm text-gray-500">
                We've received your lead information and will get back to you within 24 hours with a
                personalized consultation.
              </p>
            </div>
          </div>
        </div>

        <!-- Submission Summary -->
        <div class="mt-6 bg-gray-50 rounded-lg p-4">
          <h4 class="text-sm font-medium text-gray-900 mb-3">Submission Summary:</h4>
          <div class="space-y-2 text-sm text-gray-600">
            <div class="flex justify-between">
              <span class="font-medium">Name:</span>
              <span>{{ leadData.name }}</span>
            </div>
            <div v-if="leadData.company" class="flex justify-between">
              <span class="font-medium">Company:</span>
              <span>{{ leadData.company }}</span>
            </div>
            <div class="flex justify-between">
              <span class="font-medium">Email:</span>
              <span>{{ leadData.email }}</span>
            </div>
            <div class="flex justify-between">
              <span class="font-medium">Website Type:</span>
              <span class="flex items-center">
                <span class="mr-1">{{ getWebsiteTypeIcon(leadData.website_type) }}</span>
                {{ getWebsiteTypeLabel(leadData.website_type) }}
              </span>
            </div>
            <div v-if="leadData.platform" class="flex justify-between">
              <span class="font-medium">Platform:</span>
              <span>{{ getPlatformLabel(leadData.platform) }}</span>
            </div>
            <div v-if="leadData.website_url" class="flex justify-between">
              <span class="font-medium">Website:</span>
              <a :href="leadData.website_url" target="_blank" class="text-blue-600 hover:underline">
                {{ leadData.website_url }}
              </a>
            </div>
          </div>
        </div>

        <!-- Next Steps -->
        <div class="mt-6 bg-blue-50 rounded-lg p-4">
          <h4 class="text-sm font-medium text-blue-900 mb-2">What happens next?</h4>
          <div class="space-y-2 text-sm text-blue-800">
            <div class="flex items-center">
              <div class="w-2 h-2 bg-blue-400 rounded-full mr-2" />
              <span>Our team will review your submission</span>
            </div>
            <div class="flex items-center">
              <div class="w-2 h-2 bg-blue-400 rounded-full mr-2" />
              <span>We'll prepare a personalized consultation</span>
            </div>
            <div class="flex items-center">
              <div class="w-2 h-2 bg-blue-400 rounded-full mr-2" />
              <span>You'll receive an email within 24 hours</span>
            </div>
          </div>
        </div>

        <!-- Actions -->
        <div class="mt-5 sm:mt-6 flex flex-col sm:flex-row gap-3">
          <button
            type="button"
            class="inline-flex w-full justify-center rounded-md border border-transparent bg-green-600 px-4 py-2 text-base font-medium text-white shadow-sm hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 sm:text-sm"
            @click="$emit('close')"
          >
            Start New Submission
          </button>

          <button
            type="button"
            class="inline-flex w-full justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-base font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 sm:text-sm"
            @click="downloadSubmission"
          >
            <DocumentArrowDownIcon class="w-4 h-4 mr-2" />
            Download Summary
          </button>
        </div>

        <!-- Support contact -->
        <div class="mt-4 text-center text-xs text-gray-500">
          Questions? Contact us at
          <a href="mailto:support@example.com" class="text-blue-600 hover:underline">
            support@example.com
          </a>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { CheckIcon, DocumentArrowDownIcon } from '@heroicons/vue/24/outline'

// Props
const props = defineProps({
  leadData: {
    type: Object,
    required: true,
  },
})

// Emits
defineEmits(['close'])

// Methods
const getWebsiteTypeLabel = websiteType => {
  // Handle both object (from API response) and string formats
  if (typeof websiteType === 'object' && websiteType?.label) {
    return websiteType.label
  }

  const types = {
    ecommerce: 'E-commerce',
    blog: 'Blog/Content Site',
    business: 'Corporate/Business Site',
    portfolio: 'Portfolio',
    other: 'Other',
  }
  return types[websiteType] || 'Not selected'
}

const getWebsiteTypeIcon = websiteType => {
  // Handle both object (from API response) and string formats
  if (typeof websiteType === 'object' && websiteType?.icon) {
    return websiteType.icon
  }

  const icons = {
    ecommerce: '🛒',
    blog: '📝',
    business: '🏢',
    portfolio: '🎨',
    other: '🔍',
  }
  return icons[websiteType] || '🔍'
}

const getPlatformLabel = platform => {
  // Handle both object (from API response) and string formats
  if (typeof platform === 'object' && platform?.name) {
    return platform.name
  }

  const platforms = {
    wordpress: 'WordPress',
    shopify: 'Shopify',
    woocommerce: 'WooCommerce',
    magento: 'Magento',
    custom: 'Custom',
  }
  return platforms[platform] || 'Not selected'
}

const downloadSubmission = () => {
  // Create a simple text summary for download
  const summary = `
Lead Submission Summary
======================

Name: ${props.leadData.name}
${props.leadData.company ? `Company: ${props.leadData.company}` : ''}
Email: ${props.leadData.email}
${props.leadData.website_url ? `Website: ${props.leadData.website_url}` : ''}
Website Type: ${getWebsiteTypeLabel(props.leadData.website_type)}
${props.leadData.platform ? `Platform: ${getPlatformLabel(props.leadData.platform)}` : ''}

Submitted: ${new Date().toLocaleString()}

Thank you for your submission! We'll be in touch within 24 hours.
  `.trim()

  // Create and download file
  const blob = new Blob([summary], { type: 'text/plain' })
  const url = window.URL.createObjectURL(blob)
  const link = document.createElement('a')
  link.href = url
  link.download = `lead-submission-${Date.now()}.txt`
  document.body.appendChild(link)
  link.click()
  document.body.removeChild(link)
  window.URL.revokeObjectURL(url)
}
</script>
