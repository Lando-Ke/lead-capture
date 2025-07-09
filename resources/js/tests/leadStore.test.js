import { describe, it, expect, beforeEach } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'
import { useLeadStore } from '@/stores/leadStore'

describe('Lead Store', () => {
  beforeEach(() => {
    // Create a fresh Pinia instance for each test
    setActivePinia(createPinia())
  })

  it('initializes with empty form data', () => {
    const store = useLeadStore()
    
    expect(store.formData.name).toBe('')
    expect(store.formData.email).toBe('')
    expect(store.formData.company).toBe('')
    expect(store.formData.website_url).toBe('')
    expect(store.formData.website_type).toBe('')
    expect(store.formData.platform_id).toBe(null)
  })

  it('updates form field correctly', () => {
    const store = useLeadStore()
    
    store.updateFormField('name', 'John Doe')
    store.updateFormField('email', 'john@example.com')
    store.updateFormField('company', 'Test Company')
    
    expect(store.formData.name).toBe('John Doe')
    expect(store.formData.email).toBe('john@example.com')
    expect(store.formData.company).toBe('Test Company')
  })

  it('validates required fields correctly', () => {
    const store = useLeadStore()
    
    // Initially validation should fail (return falsy value)
    expect(store.validateStep(0)).toBeFalsy()
    
    // Fill required fields
    store.updateFormField('name', 'John Doe')
    store.updateFormField('email', 'john@example.com')
    store.updateFormField('company', 'Test Company')
    
    // Now step 0 validation should pass (return truthy value)
    expect(store.validateStep(0)).toBeTruthy()
  })
}) 