<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePosInventoryModule extends Migration
{
    public function up()
    {
        Schema::table('branch_stores', function (Blueprint $table) {
            $table->boolean('pos_inventory_enabled')->default(false)->after('trainer_discount_enabled');
        });

        Schema::create('pos_product_categories', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name', 100)->unique();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('pos_products', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('category_id')->nullable();
            $table->string('sku', 50)->unique();
            $table->string('barcode', 100)->nullable()->unique();
            $table->string('name', 150);
            $table->string('unit', 30)->default('pcs');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('category_id')->references('id')->on('pos_product_categories')->onDelete('set null');
        });

        Schema::create('pos_suppliers', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name', 150);
            $table->string('phone', 50)->nullable();
            $table->string('email', 150)->nullable();
            $table->text('address')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('pos_branch_products', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedSmallInteger('branch_store_id');
            $table->unsignedInteger('product_id');
            $table->decimal('stock_qty', 14, 3)->default(0);
            $table->decimal('average_cost', 15, 2)->default(0);
            $table->decimal('selling_price', 15, 2)->default(0);
            $table->decimal('minimum_stock', 14, 3)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['branch_store_id', 'product_id'], 'pos_branch_product_unique');
            $table->foreign('branch_store_id')->references('id')->on('branch_stores');
            $table->foreign('product_id')->references('id')->on('pos_products');
        });

        Schema::create('pos_purchases', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedSmallInteger('branch_store_id');
            $table->unsignedInteger('supplier_id')->nullable();
            $table->unsignedInteger('created_by');
            $table->unsignedInteger('received_by')->nullable();
            $table->string('purchase_number', 50)->unique();
            $table->string('supplier_invoice_number', 100)->nullable();
            $table->date('purchase_date');
            $table->dateTime('received_at')->nullable();
            $table->enum('status', ['draft', 'received', 'cancelled'])->default('draft');
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->text('notes')->nullable();
            $table->string('idempotency_key', 64)->unique();
            $table->timestamps();

            $table->foreign('branch_store_id')->references('id')->on('branch_stores');
            $table->foreign('supplier_id')->references('id')->on('pos_suppliers')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('received_by')->references('id')->on('users')->onDelete('set null');
        });

        Schema::create('pos_purchase_items', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('purchase_id');
            $table->unsignedInteger('product_id');
            $table->decimal('quantity', 14, 3);
            $table->decimal('unit_cost', 15, 2);
            $table->decimal('subtotal', 15, 2);
            $table->timestamps();

            $table->foreign('purchase_id')->references('id')->on('pos_purchases')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('pos_products');
        });

        Schema::create('pos_stock_adjustments', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedSmallInteger('branch_store_id');
            $table->unsignedInteger('created_by');
            $table->string('adjustment_number', 50)->unique();
            $table->text('reason');
            $table->string('idempotency_key', 64)->unique();
            $table->timestamps();

            $table->foreign('branch_store_id')->references('id')->on('branch_stores');
            $table->foreign('created_by')->references('id')->on('users');
        });

        Schema::create('pos_stock_adjustment_items', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('adjustment_id');
            $table->unsignedInteger('product_id');
            $table->decimal('quantity_change', 14, 3);
            $table->timestamps();

            $table->foreign('adjustment_id')->references('id')->on('pos_stock_adjustments')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('pos_products');
        });

        Schema::create('pos_sales', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedSmallInteger('branch_store_id');
            $table->unsignedInteger('cashier_id');
            $table->string('sale_number', 50)->unique();
            $table->string('customer_name', 150)->nullable();
            $table->decimal('subtotal', 15, 2);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('grand_total', 15, 2);
            $table->decimal('paid_amount', 15, 2);
            $table->decimal('change_amount', 15, 2)->default(0);
            $table->enum('status', ['completed', 'void'])->default('completed');
            $table->text('notes')->nullable();
            $table->string('idempotency_key', 64)->unique();
            $table->dateTime('voided_at')->nullable();
            $table->unsignedInteger('voided_by')->nullable();
            $table->text('void_reason')->nullable();
            $table->timestamps();

            $table->foreign('branch_store_id')->references('id')->on('branch_stores');
            $table->foreign('cashier_id')->references('id')->on('users');
            $table->foreign('voided_by')->references('id')->on('users')->onDelete('set null');
        });

        Schema::create('pos_sale_items', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('sale_id');
            $table->unsignedInteger('product_id');
            $table->string('product_name', 150);
            $table->string('sku', 50);
            $table->decimal('quantity', 14, 3);
            $table->decimal('unit_price', 15, 2);
            $table->decimal('unit_cost', 15, 2);
            $table->decimal('subtotal', 15, 2);
            $table->timestamps();

            $table->foreign('sale_id')->references('id')->on('pos_sales')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('pos_products');
        });

        Schema::create('pos_sale_payments', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('sale_id');
            $table->integer('method_payment_id');
            $table->decimal('amount', 15, 2);
            $table->string('reference_number', 100)->nullable();
            $table->timestamps();

            $table->foreign('sale_id')->references('id')->on('pos_sales')->onDelete('cascade');
            $table->foreign('method_payment_id')->references('id')->on('method_payments');
        });

        Schema::create('pos_inventory_movements', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedSmallInteger('branch_store_id');
            $table->unsignedInteger('product_id');
            $table->unsignedInteger('created_by');
            $table->enum('movement_type', ['purchase', 'sale', 'adjustment', 'sale_void']);
            $table->decimal('quantity_before', 14, 3);
            $table->decimal('quantity_change', 14, 3);
            $table->decimal('quantity_after', 14, 3);
            $table->decimal('unit_cost', 15, 2);
            $table->string('reference_type', 50);
            $table->unsignedBigInteger('reference_id');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['branch_store_id', 'product_id', 'created_at'], 'pos_movement_lookup');
            $table->index(['reference_type', 'reference_id'], 'pos_movement_reference');
            $table->foreign('branch_store_id')->references('id')->on('branch_stores');
            $table->foreign('product_id')->references('id')->on('pos_products');
            $table->foreign('created_by')->references('id')->on('users');
        });
    }

    public function down()
    {
        Schema::dropIfExists('pos_inventory_movements');
        Schema::dropIfExists('pos_sale_payments');
        Schema::dropIfExists('pos_sale_items');
        Schema::dropIfExists('pos_sales');
        Schema::dropIfExists('pos_stock_adjustment_items');
        Schema::dropIfExists('pos_stock_adjustments');
        Schema::dropIfExists('pos_purchase_items');
        Schema::dropIfExists('pos_purchases');
        Schema::dropIfExists('pos_branch_products');
        Schema::dropIfExists('pos_suppliers');
        Schema::dropIfExists('pos_products');
        Schema::dropIfExists('pos_product_categories');

        Schema::table('branch_stores', function (Blueprint $table) {
            $table->dropColumn('pos_inventory_enabled');
        });
    }
}
