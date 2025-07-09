import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import axios from 'axios'

export const usePlatformStore = defineStore('platform', () => {
  // State
  const platforms = ref([])
  const ecommercePlatforms = ref([])
  const isLoading = ref(false)
  const errors = ref([])
  
  // Website type definitions
  const websiteTypes = ref([
    {
      value: 'ecommerce',
      label: 'E-commerce',
      icon: '🛒',
      description: 'Online stores selling products or services'
    },
    {
      value: 'blog',
      label: 'Blog/Content Site',
      icon: '📝',
      description: 'Content-focused websites with articles and posts'
    },
    {
      value: 'business',
      label: 'Corporate/Business Site',
      icon: '🏢',
      description: 'Professional business websites and landing pages'
    },
    {
      value: 'portfolio',
      label: 'Portfolio',
      icon: '🎨',
      description: 'Showcase work, art, or professional portfolio'
    },
    {
      value: 'other',
      label: 'Other',
      icon: '🔍',
      description: 'Custom or specialized website requirements'
    }
  ])
  
  // Computed
  const hasErrors = computed(() => errors.value.length > 0)
  
  // Actions
  const clearErrors = () => {
    errors.value = []
  }
  
  const fetchAllPlatforms = async () => {
    isLoading.value = true
    clearErrors()
    
    try {
      const response = await axios.get('/api/v1/platforms')
      platforms.value = response.data.data
    } catch (error) {
      errors.value = ['Failed to load platforms']
      console.error('Error fetching platforms:', error)
    } finally {
      isLoading.value = false
    }
  }
  
  const fetchEcommercePlatforms = async () => {
    isLoading.value = true
    clearErrors()
    
    try {
      const response = await axios.get('/api/v1/platforms?type=ecommerce')
      ecommercePlatforms.value = response.data.data
    } catch (error) {
      errors.value = ['Failed to load e-commerce platforms']
      console.error('Error fetching e-commerce platforms:', error)
    } finally {
      isLoading.value = false
    }
  }
  
  const fetchPlatformsByType = async (websiteType) => {
    isLoading.value = true
    clearErrors()
    
    try {
      const response = await axios.get(`/api/v1/platforms?type=${websiteType}`)
      return response.data.data
    } catch (error) {
      errors.value = [`Failed to load platforms for ${websiteType}`]
      console.error('Error fetching platforms by type:', error)
      return []
    } finally {
      isLoading.value = false
    }
  }
  
  // Utility methods for website types
  const getWebsiteTypeLabel = (websiteType) => {
    const type = websiteTypes.value.find(t => t.value === websiteType)
    return type ? type.label : 'Unknown'
  }
  
  const getWebsiteTypeIcon = (websiteType) => {
    const type = websiteTypes.value.find(t => t.value === websiteType)
    return type ? type.icon : '🔍'
  }
  
  const getWebsiteTypeDescription = (websiteType) => {
    const type = websiteTypes.value.find(t => t.value === websiteType)
    return type ? type.description : 'Custom website type'
  }
  
  // Utility methods for platforms
  const getPlatformLabel = (platformSlug) => {
    if (!platformSlug) return 'Not selected'
    
    // Check in e-commerce platforms first
    const ecommercePlatform = ecommercePlatforms.value.find(p => p.slug === platformSlug)
    if (ecommercePlatform) return ecommercePlatform.name
    
    // Check in all platforms
    const platform = platforms.value.find(p => p.slug === platformSlug)
    return platform ? platform.name : platformSlug
  }
  
  const getPlatformBySlug = (slug) => {
    // Check in e-commerce platforms first
    const ecommercePlatform = ecommercePlatforms.value.find(p => p.slug === slug)
    if (ecommercePlatform) return ecommercePlatform
    
    // Check in all platforms
    return platforms.value.find(p => p.slug === slug) || null
  }
  
  // Initialize data on store creation
  const initialize = async () => {
    await Promise.all([
      fetchAllPlatforms(),
      fetchEcommercePlatforms()
    ])
  }
  
  return {
    // State
    platforms,
    ecommercePlatforms,
    websiteTypes,
    isLoading,
    errors,
    
    // Computed
    hasErrors,
    
    // Actions
    clearErrors,
    fetchAllPlatforms,
    fetchEcommercePlatforms,
    fetchPlatformsByType,
    initialize,
    
    // Utility methods
    getWebsiteTypeLabel,
    getWebsiteTypeIcon,
    getWebsiteTypeDescription,
    getPlatformLabel,
    getPlatformBySlug
  }
}) 