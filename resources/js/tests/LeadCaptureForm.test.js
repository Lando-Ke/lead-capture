import { describe, it, expect, beforeEach } from 'vitest'
import { mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import LeadCaptureForm from '@/Components/LeadCaptureForm.vue'

describe('LeadCaptureForm', () => {
  beforeEach(() => {
    // Create a fresh Pinia instance for each test
    setActivePinia(createPinia())
  })

  it('renders the component', () => {
    const wrapper = mount(LeadCaptureForm)
    expect(wrapper.exists()).toBe(true)
  })

  it('displays the first step (Basic Information) by default', () => {
    const wrapper = mount(LeadCaptureForm)
    
    // Check that we're on step 0 (first step)
    expect(wrapper.vm.currentStep).toBe(0)
    
    // Check that the Basic Information step is visible
    expect(wrapper.text()).toContain('Basic Information')
    expect(wrapper.text()).toContain('Let\'s start with some basic information')
  })

  it('allows user to fill out basic information fields', async () => {
    const wrapper = mount(LeadCaptureForm)
    
    // Find the input fields
    const nameInput = wrapper.find('input[id="name"]')
    const emailInput = wrapper.find('input[id="email"]')
    const companyInput = wrapper.find('input[id="company"]')
    
    // Check that inputs exist
    expect(nameInput.exists()).toBe(true)
    expect(emailInput.exists()).toBe(true)
    expect(companyInput.exists()).toBe(true)
    
    // Fill out the form
    await nameInput.setValue('John Doe')
    await emailInput.setValue('john@example.com')
    await companyInput.setValue('Test Company')
    
    // Check that the form data is updated in the store
    const leadStore = wrapper.vm.leadStore
    expect(leadStore.formData.name).toBe('John Doe')
    expect(leadStore.formData.email).toBe('john@example.com')
    expect(leadStore.formData.company).toBe('Test Company')
  })

  it('allows navigation between steps using next and previous buttons', async () => {
    const wrapper = mount(LeadCaptureForm)
    
    // Start at step 0
    expect(wrapper.vm.currentStep).toBe(0)
    
    // Fill out basic info to enable next step
    const nameInput = wrapper.find('input[id="name"]')
    const emailInput = wrapper.find('input[id="email"]')
    const companyInput = wrapper.find('input[id="company"]')
    
    await nameInput.setValue('Test User')
    await emailInput.setValue('test@example.com')
    await companyInput.setValue('Test Co')
    
    // Click next to go to step 1
    wrapper.vm.nextStep()
    expect(wrapper.vm.currentStep).toBe(1)
    
    // Click previous to go back to step 0
    wrapper.vm.previousStep()
    expect(wrapper.vm.currentStep).toBe(0)
  })
}) 