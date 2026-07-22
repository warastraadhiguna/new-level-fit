<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Create or restore the default administrator account.
     *
     * @return void
     */
    public function run()
    {
        User::withTrashed()->updateOrCreate(
            ['email' => 'admin@gmail.com'],
            [
                'branch_store_id' => DB::table('branch_stores')->orderBy('id')->value('id'),
                'full_name' => 'Administrator',
                'role' => 'ADMIN',
                'gender' => 'Male',
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
                'deleted_at' => null,
            ]
        );
    }
}
