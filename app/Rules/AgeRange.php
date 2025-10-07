<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Carbon\Carbon;

class AgeRange implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // jeconvertis la date de naissance en objet Carbon
        try {
            $birthday = Carbon::parse($value);
        } catch (\Exception $e) {
            $fail('La date de naissance doit être une date valide.');
            return;
        }

        // calcul-age
        $age = $birthday->age;

        
        if ($age < 13) {
            $fail('Vous devez avoir au moins 13 ans pour vous inscrire.');
        } elseif ($age > 35) {
            $fail('Cette plateforme est réservée aux personnes de 35 ans maximum.');
        }
    }
}
