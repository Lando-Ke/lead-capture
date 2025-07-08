<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use App\Models\Platform;

class ActivePlatform implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null) {
            return;
        }

        $platform = Platform::find($value);

        if (!$platform) {
            $fail('The selected platform does not exist.');
            return;
        }

        if (!$platform->is_active) {
            $fail('The selected platform is not currently active.');
            return;
        }
    }
}
