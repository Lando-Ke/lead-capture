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

        <!-- Notification Status -->
        <div v-if="localNotificationStatus" class="mt-6 bg-gray-50 rounded-lg p-4">
          <h4 class="text-sm font-medium text-gray-900 mb-3">Team Notification Status:</h4>
          
          <!-- Notification Enabled - Processing -->
          <div
v-if="localNotificationStatus.enabled && localNotificationStatus.status === 'processing'" 
               class="flex items-center text-sm text-blue-600">
            <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-blue-600 mr-2"></div>
            <span>{{ localNotificationStatus.message }}</span>
          </div>
          
          <!-- Notification Disabled -->
          <div
v-else-if="!localNotificationStatus.enabled" 
               class="flex items-center text-sm text-gray-500">
            <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
            </svg>
            <span>{{ localNotificationStatus.message }}</span>
          </div>
          
          <!-- Notification Completed -->
          <div
v-else-if="localNotificationStatus.status === 'completed'" 
               class="flex items-center text-sm text-green-600">
            <CheckIcon class="w-4 h-4 mr-2" />
            <span>Team notification sent successfully!</span>
          </div>
          
          <!-- Notification Failed -->
          <div
v-else-if="localNotificationStatus.status === 'failed'" 
               class="flex items-center text-sm text-red-600">
            <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
            </svg>
            <span>{{ localNotificationStatus.message || 'Team notification failed, but your submission was received' }}</span>
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
import { ref, onMounted, onUnmounted } from 'vue'
import axios from 'axios'

// Props
const props = defineProps({
  leadData: {
    type: Object,
    required: true,
  },
  notificationStatus: {
    type: Object,
    default: null,
  },
})

// Emits
defineEmits(['close'])

// Local notification status that can be updated
const localNotificationStatus = ref(props.notificationStatus)
let pollInterval = null

// Polling for notification status updates
const pollNotificationStatus = async () => {
  // Only poll if notification is processing
  if (!localNotificationStatus.value?.enabled || localNotificationStatus.value?.status !== 'processing') {
    console.log('📋 Skipping poll - notification not processing:', localNotificationStatus.value)
    return
  }

  console.log('📋 Polling notification status for:', props.leadData.email)

  try {
    const response = await axios.get('/api/v1/notifications/status')
    console.log('📋 Notification status poll response:', response.data)
    
    // Check if there are recent notifications for this lead
    const recentActivity = response.data.data.recent_activity?.recent_leads
    const thisLeadActivity = recentActivity?.find(activity => 
      activity.email === props.leadData.email &&
      new Date(activity.submitted_at) >= new Date(Date.now() - 10 * 60 * 1000) // Within last 10 minutes
    )

    console.log('📋 Found lead activity:', thisLeadActivity)

    if (thisLeadActivity && thisLeadActivity.notification_status === 'processed') {
      // Update status to completed
      console.log('📋 Notification completed! Updating status to completed')
      localNotificationStatus.value = {
        ...localNotificationStatus.value,
        status: 'completed',
        message: 'Team notification sent successfully!'
      }
      stopPolling()
    }
  } catch (error) {
    console.error('📋 Error polling notification status:', error)
    // Don't spam the console, just fail silently for polling
  }
}

const startPolling = () => {
  console.log('📋 Starting notification status polling for:', localNotificationStatus.value)
  
  // Only start polling if notification is processing
  if (localNotificationStatus.value?.enabled && localNotificationStatus.value?.status === 'processing') {
    // Poll every 3 seconds for up to 30 seconds
    let pollCount = 0
    console.log('📋 Initiating poll interval (every 3s, max 30s)')
    
    pollInterval = setInterval(() => {
      pollCount++
      console.log(`📋 Poll attempt ${pollCount}/10`)
      
      if (pollCount >= 10) { // Stop after 30 seconds (10 * 3 seconds)
        console.log('📋 Polling timeout reached (30s), stopping')
        stopPolling()
        // Update status to indicate completion (even if we didn't get confirmation)
        if (localNotificationStatus.value?.status === 'processing') {
          console.log('📋 Marking as completed due to timeout')
          localNotificationStatus.value = {
            ...localNotificationStatus.value,
            status: 'completed',
            message: 'Team notification has been processed'
          }
        }
        return
      }
      
      pollNotificationStatus()
    }, 3000)
  } else {
    console.log('📋 Not starting polling - notification not in processing state')
  }
}

const stopPolling = () => {
  if (pollInterval) {
    console.log('📋 Stopping notification status polling')
    clearInterval(pollInterval)
    pollInterval = null
  }
}

// Lifecycle
onMounted(() => {
  console.log('📋 SuccessModal mounted with notification status:', props.notificationStatus)
  startPolling()
})

onUnmounted(() => {
  console.log('📋 SuccessModal unmounting, cleaning up polling')
  stopPolling()
})

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
