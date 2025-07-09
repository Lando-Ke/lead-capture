<?php

namespace App\Rules;

use App\Enums\WebsiteType;
use App\Models\Platform;
use Illuminate\Contracts\Validation\ValidationRule;

class SupportsWebsiteType implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString $fail
     */
    public function validate(string $attribute, mixed $value, \Closure $fail): void
    {
        if ($value === null) {
            return;
        }

        $platform = Platform::find($value);

        if (!$platform) {
            // Let the exists rule handle this
            return;
        }

        // Get the website_type from the request
        $websiteType = request()->get('website_type');

        if (!$websiteType) {
            return;
        }

        // Convert string to enum
        try {
            $websiteTypeEnum = WebsiteType::from($websiteType);
        } catch (\ValueError $e) {
            return; // Let the enum validation handle this
        }

        // Check if the platform supports this website type
        if (!in_array($websiteTypeEnum->value, $platform->website_types)) {
            $fail("The selected platform does not support the {$websiteTypeEnum->value} website type.");
        }
    }
}
