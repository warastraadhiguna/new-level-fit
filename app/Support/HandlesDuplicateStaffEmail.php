<?php

namespace App\Support;

use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

trait HandlesDuplicateStaffEmail
{
    protected function normalizeStaffEmail(Request $request): void
    {
        $request->merge([
            'email' => strtolower(trim((string) $request->input('email'))),
        ]);
    }

    protected function staffEmailRules(?int $ignoreUserId = null): array
    {
        return [
            'required',
            'email',
            'max:255',
            Rule::unique('users', 'email')->ignore($ignoreUserId),
        ];
    }

    protected function staffEmailValidationMessages(): array
    {
        return [
            'email.required' => 'Email staff wajib diisi.',
            'email.email' => 'Format email staff tidak valid.',
            'email.unique' => 'Email tersebut sudah digunakan oleh staff lain. Satu email hanya boleh digunakan oleh satu akun staff, meskipun role atau cabangnya berbeda.',
        ];
    }

    protected function isDuplicateStaffEmailException(QueryException $exception): bool
    {
        $message = strtolower($exception->getMessage());

        return ((int) ($exception->errorInfo[1] ?? 0) === 1062)
            && (
                strpos($message, 'users_email_unique') !== false
                || strpos($message, "key 'email'") !== false
                || strpos($message, 'for key \'users.email\'') !== false
            );
    }

    protected function duplicateStaffEmailMessage(): string
    {
        return 'Email tersebut baru saja digunakan oleh staff lain. Satu email hanya boleh digunakan oleh satu akun staff, meskipun role atau cabangnya berbeda.';
    }
}
