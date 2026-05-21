<?php

namespace App\Domains\Beneficiaries\Support;

use App\Domains\Beneficiaries\Models\Beneficiary;

class BeneficiaryIdentityMatcher
{
    public function findMatch(array $attributes, ?int $ignoreId = null): ?Beneficiary
    {
        $idNumber = $this->normalizeString($attributes['id_number'] ?? null);
        $name = $this->normalizeString($attributes['name'] ?? null);
        $surname = $this->normalizeString($attributes['surname'] ?? null);
        $dob = $this->normalizeString($attributes['dob'] ?? null);
        $email = $this->normalizeEmail($attributes['email'] ?? null);

        $query = Beneficiary::withTrashed();

        if ($ignoreId !== null) {
            $query->whereKeyNot($ignoreId);
        }

        if ($idNumber !== null) {
            return (clone $query)
                ->where('id_number', $idNumber)
                ->first();
        }

        if ($name !== null && $surname !== null && $dob !== null) {
            $matchingPerson = (clone $query)
                ->whereRaw('LOWER(name) = ?', [strtolower($name)])
                ->whereRaw('LOWER(surname) = ?', [strtolower($surname)])
                ->whereDate('dob', $dob)
                ->first();

            if ($matchingPerson) {
                return $matchingPerson;
            }
        }

        if ($email !== null && $name !== null && $surname !== null) {
            return (clone $query)
                ->whereRaw('LOWER(name) = ?', [strtolower($name)])
                ->whereRaw('LOWER(surname) = ?', [strtolower($surname)])
                ->whereRaw('LOWER(email) = ?', [strtolower($email)])
                ->first();
        }

        return null;
    }

    protected function normalizeString(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }

    protected function normalizeEmail(mixed $value): ?string
    {
        $normalized = $this->normalizeString($value);

        return $normalized === null ? null : strtolower($normalized);
    }
}
