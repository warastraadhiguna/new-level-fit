<?php

namespace App\Console\Commands;

use App\Models\BranchStore;
use App\Models\User;
use App\Support\ApplicationAccess;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;

class CreateOwner extends Command
{
    protected $signature = 'owner:create
        {--name= : Nama lengkap owner}
        {--email= : Email login owner}
        {--branch= : ID branch store}
        {--gender= : Gender (Male/Female)}
        {--password= : Password; kosongkan agar diminta secara tersembunyi}';

    protected $description = 'Create an Owner user with access to all applications';

    public function handle(): int
    {
        if (!Schema::hasTable('users') || !Schema::hasTable('branch_stores')) {
            $this->error('Tabel users atau branch_stores belum tersedia. Jalankan migration terlebih dahulu.');

            return self::FAILURE;
        }

        $branches = BranchStore::query()->orderBy('name')->get(['id', 'name']);
        if ($branches->isEmpty()) {
            $this->error('Branch store belum tersedia. Buat branch store terlebih dahulu.');

            return self::FAILURE;
        }

        if (!$this->option('branch')) {
            $this->table(['ID', 'Branch Store'], $branches->map(function ($branch) {
                return [$branch->id, $branch->name];
            }));
        }

        $data = [
            'full_name' => $this->validatedValue(
                'name',
                'Nama lengkap owner',
                ['required', 'string', 'max:255']
            ),
            'email' => strtolower($this->validatedValue(
                'email',
                'Email owner',
                ['required', 'email', 'max:255', 'unique:users,email']
            )),
            'branch_store_id' => (int) $this->validatedValue(
                'branch',
                'Branch Store ID',
                ['required', 'integer', 'exists:branch_stores,id'],
                (string) $branches->first()->id
            ),
            'gender' => $this->resolveGender(),
            'role' => 'OWNER',
            'password' => Hash::make($this->resolvePassword()),
        ];

        $owner = DB::transaction(function () use ($data) {
            $owner = User::create($data);

            if (Schema::hasTable('user_application_access')) {
                foreach (ApplicationAccess::ADMIN_APPLICATIONS as $applicationCode) {
                    $owner->applicationAccesses()->updateOrCreate(
                        ['application_code' => $applicationCode],
                        ['is_active' => true]
                    );
                }
            }

            return $owner;
        });

        $this->newLine();
        $this->info('Owner berhasil dibuat.');
        $this->table(['ID', 'Nama', 'Email', 'Branch', 'Role'], [[
            $owner->id,
            $owner->full_name,
            $owner->email,
            optional($owner->branchStore)->name,
            $owner->role,
        ]]);

        return self::SUCCESS;
    }

    private function validatedValue(
        string $option,
        string $question,
        array $rules,
        ?string $default = null
    ): string {
        $value = $this->option($option);

        do {
            if ($value === null || $value === '') {
                $value = $this->ask($question, $default);
            }

            $validator = Validator::make([$option => $value], [$option => $rules]);
            if (!$validator->fails()) {
                return trim((string) $value);
            }

            $this->error($validator->errors()->first($option));
            $value = null;
        } while (true);
    }

    private function resolveGender(): string
    {
        $gender = ucfirst(strtolower((string) $this->option('gender')));

        if (in_array($gender, ['Male', 'Female'], true)) {
            return $gender;
        }

        if ($this->option('gender')) {
            $this->warn('Gender harus Male atau Female.');
        }

        return $this->choice('Gender', ['Male', 'Female'], 0);
    }

    private function resolvePassword(): string
    {
        $password = (string) $this->option('password');

        if ($password !== '') {
            if (strlen($password) < 6) {
                $this->error('Password minimal 6 karakter.');

                return $this->askForPassword();
            }

            return $password;
        }

        return $this->askForPassword();
    }

    private function askForPassword(): string
    {
        do {
            $password = (string) $this->secret('Password (minimal 6 karakter)');

            if (strlen($password) < 6) {
                $this->error('Password minimal 6 karakter.');
                continue;
            }

            if ($password !== (string) $this->secret('Ulangi password')) {
                $this->error('Konfirmasi password tidak sama. Silakan ulangi.');
                continue;
            }

            return $password;
        } while (true);
    }
}
