<?php

namespace Tests\Unit;

use Carbon\Carbon;
use Tests\TestCase;

class PosReceiptLogoTest extends TestCase
{
    public function test_pos_receipt_displays_the_branch_logo(): void
    {
        $sale = (object) [
            'id' => 1,
            'sale_number' => 'POS-20261109-00001',
            'created_at' => Carbon::parse('2026-11-09 16:55:00'),
            'customer_name' => null,
            'subtotal' => 5000,
            'discount_amount' => 0,
            'grand_total' => 5000,
            'paid_amount' => 5000,
            'change_amount' => 0,
            'status' => 'completed',
            'branchStore' => (object) [
                'name' => 'Fitbull Gym & Studio',
                'address' => 'Sragen',
                'phone' => '08159988337',
                'admin_logo_url' => '/storage/assets/branch-store/fitbull.png',
            ],
            'cashier' => (object) ['full_name' => 'Kasir'],
            'items' => collect([(object) [
                'product_name' => 'Air Mineral',
                'quantity' => 1,
                'unit_price' => 5000,
                'subtotal' => 5000,
            ]]),
            'payments' => collect(),
        ];

        $view = $this->view('admin.pos.sales.receipt', compact('sale'));

        $view->assertSee('/storage/assets/branch-store/fitbull.png', false);
        $view->assertSee('class="receipt-logo"', false);
        $view->assertSee('Logo Fitbull Gym &amp; Studio', false);
    }
}
