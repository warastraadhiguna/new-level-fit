<?php

namespace Database\Seeders;

use App\Models\Member\MemberPackage;
use App\Models\MethodPayment;
use App\Models\Staff\PersonalTrainer;
use App\Models\Trainer\TrainerPackage;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class MasterDataSeeder extends Seeder
{
    /**
     * Seed two dummy records for each requested master-data category.
     *
     * @return void
     */
    public function run()
    {
        $branchStoreId = DB::table('branch_stores')->orderBy('id')->value('id');
        $userId = DB::table('users')->whereNull('deleted_at')->orderBy('id')->value('id');

        if (!$branchStoreId || !$userId) {
            throw new RuntimeException(
                'MasterDataSeeder requires at least one branch store and one active user.'
            );
        }

        DB::transaction(function () use ($branchStoreId, $userId) {
            $this->seedMemberPackages((int) $branchStoreId, (int) $userId);
            $this->seedTrainerPackages((int) $branchStoreId, (int) $userId);
            $this->seedPaymentMethods();
            $this->seedPersonalTrainers((int) $branchStoreId, (int) $userId);
        });
    }

    private function seedMemberPackages(int $branchStoreId, int $userId): void
    {
        $packages = [
            [
                'package_name' => 'Dummy Member Basic 30 Days',
                'days' => 30,
                'package_price' => 300000,
                'admin_price' => 25000,
                'description' => 'Dummy paket member basic untuk kebutuhan development.',
                'is_all_club' => 0,
            ],
            [
                'package_name' => 'Dummy Member All Club 90 Days',
                'days' => 90,
                'package_price' => 850000,
                'admin_price' => 25000,
                'description' => 'Dummy paket member all club untuk kebutuhan development.',
                'is_all_club' => 1,
            ],
        ];

        foreach ($packages as $package) {
            $model = MemberPackage::withTrashed()->updateOrCreate(
                [
                    'branch_store_id' => $branchStoreId,
                    'package_name' => $package['package_name'],
                ],
                $package + ['user_id' => $userId]
            );

            if ($model->trashed()) {
                $model->restore();
            }
        }
    }

    private function seedTrainerPackages(int $branchStoreId, int $userId): void
    {
        $packages = [
            [
                'package_name' => 'Dummy PT Starter 8 Sessions',
                'number_of_session' => 8,
                'days' => 30,
                'package_price' => 800000,
                'admin_price' => 25000,
                'description' => 'Dummy paket personal trainer pemula.',
                'status' => null,
            ],
            [
                'package_name' => 'Dummy PT Pro 16 Sessions',
                'number_of_session' => 16,
                'days' => 60,
                'package_price' => 1500000,
                'admin_price' => 25000,
                'description' => 'Dummy paket personal trainer lanjutan.',
                'status' => null,
            ],
        ];

        foreach ($packages as $package) {
            $model = TrainerPackage::withTrashed()->updateOrCreate(
                [
                    'branch_store_id' => $branchStoreId,
                    'package_name' => $package['package_name'],
                ],
                $package + ['user_id' => $userId]
            );

            if ($model->trashed()) {
                $model->restore();
            }
        }
    }

    private function seedPaymentMethods(): void
    {
        foreach (['Dummy Cash', 'Dummy Bank Transfer'] as $name) {
            MethodPayment::updateOrCreate(['name' => $name]);
        }
    }

    private function seedPersonalTrainers(int $branchStoreId, int $userId): void
    {
        $trainers = [
            [
                'full_name' => 'Budi Dummy Trainer',
                'phone_number' => '081200000001',
                'gender' => 'Male',
                'role' => 'Personal Trainer',
                'address' => 'Alamat dummy trainer 1',
                'description' => 'Dummy personal trainer untuk kebutuhan development.',
            ],
            [
                'full_name' => 'Sari Dummy Trainer',
                'phone_number' => '081200000002',
                'gender' => 'Female',
                'role' => 'Personal Trainer',
                'address' => 'Alamat dummy trainer 2',
                'description' => 'Dummy personal trainer untuk kebutuhan development.',
            ],
        ];

        foreach ($trainers as $trainer) {
            $model = PersonalTrainer::withTrashed()->updateOrCreate(
                [
                    'branch_store_id' => $branchStoreId,
                    'full_name' => $trainer['full_name'],
                ],
                $trainer + ['user_id' => $userId]
            );

            if ($model->trashed()) {
                $model->restore();
            }
        }
    }
}
