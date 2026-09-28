<?php

namespace App\Rules;

use Closure;
use Cron\CronExpression as CronParser;
use Illuminate\Contracts\Validation\ValidationRule;

class CronExpression implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * Validates that the given value is a valid CRON expression
     * using the dragonmantank/cron-expression library.
     *
     * @param  string   $attribute
     * @param  mixed    $value
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ! CronParser::isValidExpression($value)) {
            $fail(__('validation.cron'));
        }
    }
}


