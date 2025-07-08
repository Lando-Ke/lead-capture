<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\WebsiteType;
use App\Rules\ActivePlatform;
use App\Rules\SupportsWebsiteType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Request for validating lead submission data.
 * 
 * Validates all lead form fields with proper business rules
 * and provides user-friendly error messages.
 */
final class StoreLeadRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     * 
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                'min:2',
                'regex:/^[a-zA-Z\s\-\.\'\p{L}]+$/u', // Allow letters, spaces, hyphens, dots, apostrophes, and unicode letters
            ],
            'email' => [
                'required',
                'email:rfc',
                'max:255',
                'unique:leads,email',
            ],
            'company' => [
                'nullable',
                'string',
                'max:255',
                'min:2',
            ],
            'website_url' => [
                'nullable',
                'url',
                'max:255',
            ],
            'website_type' => [
                'required',
                'string',
                Rule::enum(WebsiteType::class),
            ],
            'platform_id' => [
                'nullable',
                'required_if:website_type,ecommerce',
                'integer',
                'exists:platforms,id',
                new ActivePlatform(),
                new SupportsWebsiteType(),
            ],
        ];
    }

    /**
     * Get custom error messages for validator errors.
     * 
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Your full name is required.',
            'name.min' => 'Your name must be at least 2 characters long.',
            'name.max' => 'Your name cannot exceed 255 characters.',
            'name.regex' => 'Your name contains invalid characters.',
            'email.required' => 'Your email address is required.',
            'email.email' => 'Please provide a valid email address.',
            'email.unique' => 'This email address has already been submitted.',
            'company.min' => 'Company name must be at least 2 characters long.',
            'website_url.url' => 'Please provide a valid website URL.',
            'website_url.active_url' => 'The website URL must be accessible.',
            'website_type.required' => 'Please select a website type.',
            'website_type.enum' => 'Please select a valid website type.',
            'platform_id.required_if' => 'Please select a platform for your e-commerce site.',
            'platform_id.exists' => 'The selected platform is not valid.',
        ];
    }

    /**
     * Get custom attribute names for validator errors.
     * 
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'full name',
            'email' => 'email address',
            'company' => 'company name',
            'website_url' => 'website URL',
            'website_type' => 'website type',
            'platform_id' => 'platform',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => trim($this->input('name', '')),
            'email' => strtolower(trim($this->input('email', ''))),
            'company' => trim($this->input('company', '')),
            'website_url' => trim($this->input('website_url', '')),
        ]);
    }
} 