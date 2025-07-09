import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import axios from 'axios'

export const usePlatformStore = defineStore('platform', () => {
  // State
  const platforms = ref([])
  const availablePlatforms = ref([])
  const isLoading = ref(false)
  const errors = ref([])

  // Website type definitions
  const websiteTypes = ref([
    {
      value: 'ecommerce',
      label: 'E-commerce',
      icon: '🛒',
      description: 'Online stores selling products or services',
    },
    {
      value: 'blog',
      label: 'Blog/Content Site',
      icon: '📝',
      description: 'Content-focused websites with articles and posts',
    },
    {
      value: 'business',
      label: 'Corporate/Business Site',
      icon: '🏢',
      description: 'Professional business websites and landing pages',
    },
    {
      value: 'portfolio',
      label: 'Portfolio',
      icon: '🎨',
      description: 'Showcase work, art, or professional portfolio',
    },
    {
      value: 'other',
      label: 'Other',
      icon: '🔍',
      description: 'Custom or specialized website requirements',
    },
  ])

  // Computed
  const hasErrors = computed(() => errors.value.length > 0)

  // Actions
  const clearErrors = () => {
    errors.value = []
  }

  const clearAvailablePlatforms = () => {
    availablePlatforms.value = []
  }

  const fetchAllPlatforms = async () => {
    isLoading.value = true
    clearErrors()

    try {
      const response = await axios.get('/api/v1/platforms')
      platforms.value = response.data.data || []
      return platforms.value
    } catch (error) {
      errors.value = ['Failed to load platforms']
      console.error('Error fetching platforms:', error)
      return []
    } finally {
      isLoading.value = false
    }
  }

  const fetchPlatformsByWebsiteType = async websiteType => {
    if (!websiteType) {
      availablePlatforms.value = []
      return []
    }

    isLoading.value = true
    clearErrors()

    try {
      const response = await axios.get(`/api/v1/platforms?type=${websiteType}`)
      availablePlatforms.value = response.data.data || []
      return availablePlatforms.value
    } catch (error) {
      errors.value = [`Failed to load platforms for ${websiteType}`]
      console.error('Error fetching platforms by type:', error)
      availablePlatforms.value = []
      return []
    } finally {
      isLoading.value = false
    }
  }

  // Initialize platforms data
  const initializePlatforms = async () => {
    await fetchAllPlatforms()
  }

  // Utility methods for website types
  const getWebsiteTypeLabel = websiteType => {
    const type = websiteTypes.value.find(t => t.value === websiteType)
    return type ? type.label : 'Unknown'
  }

  const getWebsiteTypeIcon = websiteType => {
    const type = websiteTypes.value.find(t => t.value === websiteType)
    return type ? type.icon : '🔍'
  }

  const getWebsiteTypeDescription = websiteType => {
    const type = websiteTypes.value.find(t => t.value === websiteType)
    return type ? type.description : 'Custom website type'
  }

  // Utility methods for platforms
  const getPlatformLabel = platformSlug => {
    if (!platformSlug) return 'Not selected'

    // Check in available platforms first
    const availablePlatform = availablePlatforms.value.find(p => p.slug === platformSlug)
    if (availablePlatform) return availablePlatform.name

    // Check in all platforms
    const platform = platforms.value.find(p => p.slug === platformSlug)
    return platform ? platform.name : platformSlug
  }

  const getPlatformById = platformId => {
    if (!platformId) return null

    // Check in available platforms first
    const availablePlatform = availablePlatforms.value.find(p => p.id === platformId)
    if (availablePlatform) return availablePlatform

    // Check in all platforms
    return platforms.value.find(p => p.id === platformId) || null
  }

  const getPlatformLabelById = platformId => {
    if (!platformId) return 'Not selected'

    const platform = getPlatformById(platformId)
    return platform ? platform.name : 'Not selected'
  }

  const getPlatformBySlug = slug => {
    // Check in available platforms first
    const availablePlatform = availablePlatforms.value.find(p => p.slug === slug)
    if (availablePlatform) return availablePlatform

    // Check in all platforms
    return platforms.value.find(p => p.slug === slug) || null
  }

  const getPlatformLogo = platformSlug => {
    if (!platformSlug) return '/images/platforms/default.png'
    return `/images/platforms/${platformSlug}.png`
  }

  const getPlatformLogoById = platformId => {
    const platform = getPlatformById(platformId)
    return platform ? getPlatformLogo(platform.slug) : '/images/platforms/default.png'
  }

  return {
    // State
    platforms,
    availablePlatforms,
    websiteTypes,
    isLoading,
    errors,

    // Computed
    hasErrors,

    // Actions
    clearErrors,
    clearAvailablePlatforms,
    fetchAllPlatforms,
    fetchPlatformsByWebsiteType,
    initializePlatforms,

    // Utility methods
    getWebsiteTypeLabel,
    getWebsiteTypeIcon,
    getWebsiteTypeDescription,
    getPlatformLabel,
    getPlatformById,
    getPlatformLabelById,
    getPlatformBySlug,
    getPlatformLogo,
    getPlatformLogoById,
  }
})
