<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_ledger', function (Blueprint $table) {
            $table->bigIncrements('ledger_id');
            $table->unsignedInteger('warehouse_id');
            $table->unsignedInteger('branch_id');
            $table->unsignedInteger('product_id');
            $table->unsignedInteger('batch_id');
            $table->string('movement_type', 50); // SALES_RETAIL, SALES_RETAIL_VOID, SALES_WHOLESALE, SALES_WHOLESALE_VOID, OPENING_BALANCE, INTAKE_GRN, ADJUSTMENT, TRANSFER_IN, TRANSFER_OUT
            $table->decimal('quantity', 12, 2); // signed quantity (negative for deductions, positive for additions/reversals)
            $table->decimal('balance_after', 12, 2)->default(0);
            $table->string('reference_type', 50); // retail_sale, sales_order, delivery_note, sales_invoice, stock_adjustment, opening_balance
            $table->unsignedBigInteger('reference_id');
            $table->string('reference_no', 100)->nullable();
            $table->unsignedInteger('sales_order_id')->nullable();
            $table->unsignedInteger('delivery_note_id')->nullable();
            $table->unsignedInteger('user_id')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('warehouse_id')->references('warehouse_id')->on('warehouses')->onDelete('restrict');
            $table->foreign('branch_id')->references('branch_id')->on('branches')->onDelete('restrict');
            $table->foreign('product_id')->references('product_id')->on('products')->onDelete('restrict');
            $table->foreign('batch_id')->references('batch_id')->on('stock_batches')->onDelete('restrict');
            $table->foreign('user_id')->references('user_id')->on('users')->onDelete('set null');

            $table->index(['warehouse_id', 'batch_id']);
            $table->index(['product_id', 'warehouse_id']);
            $table->index(['reference_type', 'reference_id']);
            $table->index('created_at');
        });

        // Initialize opening balance rows for existing warehouse stock records
        if (Schema::hasTable('warehouse_stock') && Schema::hasTable('warehouses')) {
            $existingStocks = DB::table('warehouse_stock')
                ->join('warehouses', 'warehouse_stock.warehouse_id', '=', 'warehouses.warehouse_id')
                ->where('warehouse_stock.quantity_on_hand', '>', 0)
                ->select(
                    'warehouse_stock.stock_id',
                    'warehouse_stock.warehouse_id',
                    'warehouses.branch_id',
                    'warehouse_stock.product_id',
                    'warehouse_stock.batch_id',
                    'warehouse_stock.quantity_on_hand'
                )
                ->get();

            foreach ($existingStocks as $stock) {
                DB::table('stock_ledger')->insert([
                    'warehouse_id' => $stock->warehouse_id,
                    'branch_id' => $stock->branch_id,
                    'product_id' => $stock->product_id,
                    'batch_id' => $stock->batch_id,
                    'movement_type' => 'OPENING_BALANCE',
                    'quantity' => $stock->quantity_on_hand,
                    'balance_after' => $stock->quantity_on_hand,
                    'reference_type' => 'opening_balance',
                    'reference_id' => $stock->stock_id,
                    'reference_no' => 'INIT-STOCK-' . $stock->stock_id,
                    'notes' => 'Opening balance initialization from warehouse_stock',
                    'created_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_ledger');
    }
};

