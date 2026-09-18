<?php

namespace App\Services;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class RevenueReportService
{
    public function query(
        int $branchStoreId,
        string $fromDate,
        string $toDate,
        bool $includePos,
        ?string $search = null,
        ?string $category = null
    ): Builder {
        $from = $fromDate . ' 00:00:00';
        $to = $toDate . ' 23:59:59';

        $membership = DB::table('member_registration_payments as payment')
            ->join('member_registrations as registration', 'registration.id', '=', 'payment.member_registration_id')
            ->join('members as member', 'member.id', '=', 'registration.member_id')
            ->leftJoin('member_packages as package', 'package.id', '=', 'registration.member_package_id')
            ->leftJoin('method_payments as method', 'method.id', '=', 'payment.method_payment_id')
            ->leftJoin('users as staff', 'staff.id', '=', 'payment.user_id')
            ->whereBetween('payment.created_at', [$from, $to])
            ->whereRaw(
                'COALESCE(payment.branch_store_id, staff.branch_store_id, member.branch_store_id) = ?',
                [$branchStoreId]
            )
            ->selectRaw("payment.id AS row_id")
            ->selectRaw("'membership' AS source")
            ->selectRaw("CASE WHEN registration.days <= 1 THEN 'One Day Visit' ELSE 'Membership' END AS category")
            ->selectRaw("CONCAT('MEM-', registration.id) AS reference_number")
            ->selectRaw("COALESCE(NULLIF(member.full_name, ''), NULLIF(member.email, ''), '-') AS customer_name")
            ->selectRaw("COALESCE(NULLIF(member.member_code, ''), '-') AS customer_code")
            ->selectRaw("COALESCE(NULLIF(package.package_name, ''), '(deleted)') AS item_name")
            ->selectRaw("COALESCE(NULLIF(method.name, ''), '-') AS payment_method")
            ->selectRaw("COALESCE(NULLIF(staff.full_name, ''), '-') AS staff_name")
            ->selectRaw('payment.value AS amount')
            ->selectRaw('payment.created_at AS transaction_at');

        $trainer = DB::table('trainer_session_payments as payment')
            ->join('trainer_sessions as session', 'session.id', '=', 'payment.trainer_session_id')
            ->join('members as member', 'member.id', '=', 'session.member_id')
            ->leftJoin('trainer_packages as package', 'package.id', '=', 'session.trainer_package_id')
            ->leftJoin('method_payments as method', 'method.id', '=', 'payment.method_payment_id')
            ->leftJoin('users as staff', 'staff.id', '=', 'payment.user_id')
            ->whereBetween('payment.created_at', [$from, $to])
            ->where(function ($query) {
                $query->whereNull('session.is_pt_free')->orWhere('session.is_pt_free', false);
            })
            ->whereRaw(
                'COALESCE(payment.branch_store_id, session.branch_store_id, staff.branch_store_id) = ?',
                [$branchStoreId]
            )
            ->selectRaw('payment.id AS row_id')
            ->selectRaw("'trainer' AS source")
            ->selectRaw("CASE WHEN package.status = 'LGT' THEN 'LGT' ELSE 'PT' END AS category")
            ->selectRaw("CONCAT('PT-', session.id) AS reference_number")
            ->selectRaw("COALESCE(NULLIF(member.full_name, ''), NULLIF(member.email, ''), '-') AS customer_name")
            ->selectRaw("COALESCE(NULLIF(member.member_code, ''), '-') AS customer_code")
            ->selectRaw("COALESCE(NULLIF(package.package_name, ''), '(deleted)') AS item_name")
            ->selectRaw("COALESCE(NULLIF(method.name, ''), '-') AS payment_method")
            ->selectRaw("COALESCE(NULLIF(staff.full_name, ''), '-') AS staff_name")
            ->selectRaw('payment.value AS amount')
            ->selectRaw('payment.created_at AS transaction_at');

        $union = $membership->unionAll($trainer);

        if ($includePos) {
            $posPayment = DB::table('pos_sale_payments')
                ->select('sale_id')
                ->selectRaw('MIN(method_payment_id) AS method_payment_id')
                ->groupBy('sale_id');

            $pos = DB::table('pos_sales as sale')
                ->leftJoinSub($posPayment, 'sale_payment', function ($join) {
                    $join->on('sale_payment.sale_id', '=', 'sale.id');
                })
                ->leftJoin('method_payments as method', 'method.id', '=', 'sale_payment.method_payment_id')
                ->leftJoin('users as staff', 'staff.id', '=', 'sale.cashier_id')
                ->where('sale.branch_store_id', $branchStoreId)
                ->where('sale.status', 'completed')
                ->whereBetween('sale.created_at', [$from, $to])
                ->selectRaw('sale.id AS row_id')
                ->selectRaw("'pos' AS source")
                ->selectRaw("'POS' AS category")
                ->selectRaw('sale.sale_number AS reference_number')
                ->selectRaw("COALESCE(NULLIF(sale.customer_name, ''), '-') AS customer_name")
                ->selectRaw("'-' AS customer_code")
                ->selectRaw("'Penjualan POS' AS item_name")
                ->selectRaw("COALESCE(NULLIF(method.name, ''), '-') AS payment_method")
                ->selectRaw("COALESCE(NULLIF(staff.full_name, ''), '-') AS staff_name")
                ->selectRaw('sale.grand_total AS amount')
                ->selectRaw('sale.created_at AS transaction_at');

            $union->unionAll($pos);
        }

        $query = DB::query()->fromSub($union, 'revenue_transactions');

        if ($search !== null && trim($search) !== '') {
            $keyword = '%' . trim($search) . '%';
            $query->where(function ($searchQuery) use ($keyword) {
                $searchQuery
                    ->where('reference_number', 'like', $keyword)
                    ->orWhere('customer_name', 'like', $keyword)
                    ->orWhere('customer_code', 'like', $keyword)
                    ->orWhere('item_name', 'like', $keyword)
                    ->orWhere('staff_name', 'like', $keyword);
            });
        }

        if ($category !== null && $category !== '') {
            $categories = [
                'membership' => 'Membership',
                'one_day' => 'One Day Visit',
                'pt' => 'PT',
                'lgt' => 'LGT',
                'pos' => 'POS',
            ];

            if (isset($categories[$category])) {
                $query->where('category', $categories[$category]);
            }
        }

        return $query;
    }
}
