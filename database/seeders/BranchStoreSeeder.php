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
                'name' => 'Level Fit',
                'slug' => 'level-fit',
                'address' => 'Jl. Level Fit No. 1',
                'city' => 'Jakarta',
                'phone' => '081200000000',
                'email' => 'level-fit@example.test',
                'is_payment_strict' => true,
                'type' => 'both',
            ],
            [
                'name' => 'Level Fit 2',
                'slug' => 'level-fit-2',
                'address' => 'Jl. Level Fit No. 2',
                'city' => 'Jakarta',
                'phone' => '081200000002',
                'email' => 'level-fit-2@example.test',
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
