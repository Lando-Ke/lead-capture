import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import axios from 'axios'

export const useLeadStore = defineStore('lead', () => {
  // State
  const errors = ref({})
  const isLoading = ref(false)
  const isSubmitting = ref(false)
  const notificationStatus = ref(null)

  // Form data
  const formData = ref({
    name: '',
    email: '',
    company: '',
    website_url: '',
    website_type: '',
    platform_id: null,
  })

  // Computed
  const hasErrors = computed(() => Object.keys(errors.value).length > 0)
  const hasNotificationStatus = computed(() => notificationStatus.value !== null)

  // Actions
  const clearErrors = () => {
    errors.value = {}
  }

  const setError = (field, message) => {
    errors.value[field] = Array.isArray(message) ? message : [message]
  }

  const clearError = field => {
    if (errors.value[field]) {
      delete errors.value[field]
    }
  }

  const updateFormField = (field, value) => {
    formData.value[field] = value
    // Clear error when field is updated
    clearError(field)
  }

  const setNotificationStatus = status => {
    console.log('📋 Setting notification status:', status)
    notificationStatus.value = status
  }

  const clearNotificationStatus = () => {
    console.log('📋 Clearing notification status')
    notificationStatus.value = null
  }

  const validateEmail = email => {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
    return emailRegex.test(email)
  }

  const validateStep = step => {
    switch (step) {
      case 0: // Basic Information
        return (
          formData.value.name &&
          formData.value.email &&
          formData.value.company &&
          validateEmail(formData.value.email)
        )
      case 1: // Website Details
        return formData.value.website_type
      case 2: // Platform Selection
        return formData.value.website_type && formData.value.platform_id
      case 3: {
        // Review & Submit
        const basicValid =
          formData.value.name &&
          formData.value.email &&
          formData.value.company &&
          formData.value.website_type
        const platformValid = formData.value.platform_id
        return basicValid && platformValid
      }
      default:
        return false
    }
  }

  const resetForm = () => {
    formData.value = {
      name: '',
      email: '',
      company: '',
      website_url: '',
      website_type: '',
      platform_id: null,
    }
    clearErrors()
    clearNotificationStatus()
  }

  const checkEmailExists = async email => {
    try {
      const response = await axios.get(`/api/v1/leads/${encodeURIComponent(email)}/check`)
      return response.data.exists
    } catch (error) {
      console.error('Error checking email:', error)
      return false
    }
  }

  const submitLead = async () => {
    isSubmitting.value = true
    clearErrors()
    clearNotificationStatus()

    try {
      const response = await axios.post('/api/v1/leads', formData.value)

      console.log('📋 Lead submission response received:', response.data)

      // Set notification status from API response
      if (response.data.notification) {
        console.log('📋 Notification status from API:', response.data.notification)
        setNotificationStatus(response.data.notification)
      }

      return {
        success: true,
        data: response.data.data,
        message: response.data.message,
        notification: response.data.notification,
      }
    } catch (error) {
      console.error('📋 Lead submission error:', error)

      if (error.response?.status === 422) {
        // Validation errors - extract and set them
        const responseErrors = error.response.data.errors || {}
        Object.keys(responseErrors).forEach(field => {
          setError(field, responseErrors[field])
        })
      } else if (error.response?.status === 409) {
        // Lead already exists
        setError('email', [error.response.data.message])
      } else {
        // General error
        setError('general', ['An error occurred while submitting the form. Please try again.'])
      }

      throw error
    } finally {
      isSubmitting.value = false
    }
  }

  return {
    // State
    formData,
    errors,
    isLoading,
    isSubmitting,
    notificationStatus,

    // Computed
    hasErrors,
    hasNotificationStatus,

    // Actions
    clearErrors,
    setError,
    clearError,
    updateFormField,
    setNotificationStatus,
    clearNotificationStatus,
    validateEmail,
    validateStep,
    resetForm,
    checkEmailExists,
    submitLead,
  }
})
