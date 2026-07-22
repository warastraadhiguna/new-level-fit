<?php

namespace Database\Seeders;

use App\Models\BranchStore;
use Illuminate\Database\Seeder;

class BranchStoreSeeder extends Seeder
{
    /**
     * Seed the branch stores required by users and other master data.
     *
     * @return void
     */
    public function run()
    {
        $branches = [
            [
                'name' => 'WAn GYM',
                'slug' => 'wan-gy',
                'address' => 'Jl. Wan Gym No. 1',
                'city' => 'Jakarta',
                'phone' => '081200000000',
                'email' => 'wangym@example.test',
                'is_payment_strict' => true,
                'type' => 'both',
            ],
        ];

        foreach ($branches as $branch) {
            // firstOrCreate menjaga data cabang yang sudah pernah disesuaikan admin.
            BranchStore::firstOrCreate(['slug' => $branch['slug']], $branch);
        }
    }
}