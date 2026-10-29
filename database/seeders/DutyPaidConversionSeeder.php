<?php

namespace Database\Seeders;

use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\DutyAssessment;
use App\Models\DutyPaidConversion;
use App\Models\DutyPaidConversionItem;
use App\Models\ImportShipment;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\StockBatch;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseStock;
use App\Services\StockLedgerService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class DutyPaidConversionSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('username', 'admin')->first()
            ?? User::where('email', 'admin@rmsc.om')->first()
            ?? User::first();

        // 1. Bonded Warehouses (Sources)
        $whMctBond = Warehouse::where('warehouse_code', 'WH-MCT-BOND')->first();
        $whSllDepot = Warehouse::where('warehouse_code', 'WH-SLL-DEPOT')->first();
        $whSohBond = Warehouse::where('warehouse_code', 'WH-SOH-BOND')->first();

        // 2. Main Warehouses (Destinations)
        $whMctMain = Warehouse::where('warehouse_code', 'WH-MCT-MAIN')->first();
        $whNzwMain = Warehouse::where('warehouse_code', 'WH-NZW-MAIN')->first();

        if (! $whMctBond || ! $whMctMain) {
            return;
        }

        // Ensure regional main warehouses exist for Salalah & Sohar
        $whSllMain = Warehouse::where('warehouse_code', 'WH-SLL-MAIN')->first()
            ?? Warehouse::firstOrCreate(
                ['warehouse_code' => 'WH-SLL-MAIN'],
                [
                    'company_id' => $whMctMain->company_id,
                    'branch_id' => Branch::where('branch_code', 'BR-SLL')->value('branch_id') ?? $whMctMain->branch_id,
                    'warehouse_name' => 'Salalah Main Warehouse',
                    'warehouse_type' => 'MAIN',
                    'is_active' => true,
                ]
            );

        $whSohMain = Warehouse::where('warehouse_code', 'WH-SOH-MAIN')->first()
            ?? Warehouse::firstOrCreate(
                ['warehouse_code' => 'WH-SOH-MAIN'],
                [
                    'company_id' => $whMctMain->company_id,
                    'branch_id' => Branch::where('branch_code', 'BR-SOH')->value('branch_id') ?? $whMctMain->branch_id,
                    'warehouse_name' => 'Sohar Main Warehouse',
                    'warehouse_type' => 'MAIN',
                    'is_active' => true,
                ]
            );

        $whSllMain = $whSllMain ?? $whMctMain;
        $whSohMain = $whSohMain ?? $whMctMain;
        $whNzwMain = $whNzwMain ?? $whMctMain;

        // 3. Products
        $jwProduct = Product::where('sku_code', 'SKU-JW-BLK-750')->first();
        $heinekenProduct = Product::where('sku_code', 'SKU-HEIN-CAN-330')->first();
        $chivasProduct = Product::where('sku_code', 'SKU-CHIV-12-1L')->first();
        $hennessyProduct = Product::where('sku_code', 'SKU-HEN-VSOP-700')->first();

        $stockLedger = app(StockLedgerService::class);

        // =========================================================================
        // Conversion 1: DPC-2026-0001
        // Muscat Central: Convert 100 bottles JW Black from WH-MCT-BOND to WH-MCT-MAIN
        // Status: POSTED | Customs: BAYAN-2026-88910
        // =========================================================================
        if (! DutyPaidConversion::where('conversion_number', 'DPC-2026-0001')->exists() && $jwProduct && $whMctBond && $whMctMain) {
            $bondedBatch = StockBatch::firstOrCreate([
                'batch_number' => 'BATCH-MCT-JW-BOND',
                'product_id' => $jwProduct->product_id,
            ], [
                'manufacture_date' => '2025-11-01',
                'expiry_date' => '2030-11-01',
                'duty_status' => 'DUTY_FREE_BONDED',
            ]);

            // Ensure bonded stock exists in WH-MCT-BOND
            $stock = WarehouseStock::where('warehouse_id', $whMctBond->warehouse_id)
                ->where('batch_id', $bondedBatch->batch_id)
                ->first();
            if (! $stock || (float) $stock->quantity_available < 100.0) {
                $stockLedger->recordIntake(
                    warehouseId: $whMctBond->warehouse_id,
                    productId: $jwProduct->product_id,
                    batchId: $bondedBatch->batch_id,
                    quantity: 100.0,
                    meta: [
                        'movement_type' => 'INTAKE_GRN',
                        'notes' => 'Opening intake for bonded conversion seeding',
                        'user_id' => $admin?->user_id,
                    ]
                );
            }

            $assessment = DutyAssessment::first();

            $conversion1 = DutyPaidConversion::create([
                'conversion_number' => 'DPC-2026-0001',
                'warehouse_id' => $whMctMain->warehouse_id,
                'conversion_date' => Carbon::parse('2026-02-18'),
                'assessment_id' => $assessment?->assessment_id,
                'status' => 'POSTED',
                'approved_by' => $admin?->user_id,
            ]);

            // Duty-Paid Converted Batch
            $dpBatch1 = StockBatch::firstOrCreate([
                'batch_number' => 'BATCH-MCT-JW-DP',
                'product_id' => $jwProduct->product_id,
            ], [
                'manufacture_date' => '2025-11-01',
                'expiry_date' => '2030-11-01',
                'duty_status' => 'DUTY_PAID',
            ]);

            DutyPaidConversionItem::create([
                'conversion_id' => $conversion1->conversion_id,
                'batch_id' => $bondedBatch->batch_id,
                'quantity_converted' => 100.00,
                'duty_paid_unit_cost' => 6.250,
            ]);

            // Stock movements
            $stockLedger->deductStock(
                warehouseId: $whMctBond->warehouse_id,
                productId: $jwProduct->product_id,
                batchId: $bondedBatch->batch_id,
                quantity: 100.00,
                fromReserved: false,
                saleType: null,
                meta: [
                    'movement_type' => 'TRANSFER_OUT',
                    'reference_type' => 'duty_paid_conversion',
                    'reference_id' => $conversion1->conversion_id,
                    'reference_no' => 'DPC-2026-0001',
                    'notes' => 'Converted to duty-paid stock for Muscat Main DC',
                    'user_id' => $admin?->user_id,
                ]
            );

            $stockLedger->recordIntake(
                warehouseId: $whMctMain->warehouse_id,
                productId: $jwProduct->product_id,
                batchId: $dpBatch1->batch_id,
                quantity: 100.00,
                meta: [
                    'movement_type' => 'TRANSFER_IN',
                    'reference_type' => 'duty_paid_conversion',
                    'reference_id' => $conversion1->conversion_id,
                    'reference_no' => 'DPC-2026-0001',
                    'notes' => 'Duty-paid stock received from Muscat Bonded warehouse',
                    'user_id' => $admin?->user_id,
                ]
            );

            AuditLog::create([
                'user_id' => $admin?->user_id,
                'module_name' => 'Warehouse & Inventory',
                'action' => 'DUTY_PAID_CONVERSION',
                'record_table' => 'duty_paid_conversions',
                'record_id' => $conversion1->conversion_id,
                'details_json' => [
                    'conversion_number' => 'DPC-2026-0001',
                    'source_warehouse' => $whMctBond->warehouse_code,
                    'destination_warehouse' => $whMctMain->warehouse_code,
                    'quantity' => 100.00,
                    'total_duty' => 625.000,
                    'bayan_no' => $assessment?->shipment?->bayan_declaration_no ?? 'BAYAN-2026-88910',
                ],
                'ip_address' => '127.0.0.1',
            ]);
        }

        // =========================================================================
        // Conversion 2: DPC-2026-0002
        // Salalah Regional: Convert 50 bottles JW Black from WH-SLL-DEPOT to WH-SLL-MAIN
        // Status: POSTED | Customs: BAYAN-2026-91204
        // =========================================================================
        if (! DutyPaidConversion::where('conversion_number', 'DPC-2026-0002')->exists() && $whSllDepot && $whSllMain && $jwProduct) {
            $bondedBatchSll = StockBatch::firstOrCreate([
                'batch_number' => 'BATCH-SLL-JW-BOND',
                'product_id' => $jwProduct->product_id,
            ], [
                'manufacture_date' => '2025-12-01',
                'expiry_date' => '2030-12-01',
                'duty_status' => 'DUTY_FREE_BONDED',
            ]);

            // Ensure stock in WH-SLL-DEPOT
            $stockSll = WarehouseStock::where('warehouse_id', $whSllDepot->warehouse_id)
                ->where('batch_id', $bondedBatchSll->batch_id)
                ->first();
            if (! $stockSll || (float) $stockSll->quantity_available < 50.0) {
                $stockLedger->recordIntake(
                    warehouseId: $whSllDepot->warehouse_id,
                    productId: $jwProduct->product_id,
                    batchId: $bondedBatchSll->batch_id,
                    quantity: 50.0,
                    meta: [
                        'movement_type' => 'INTAKE_GRN',
                        'notes' => 'Opening intake for Salalah bonded depot',
                        'user_id' => $admin?->user_id,
                    ]
                );
            }

            // Create Shipment & Duty Assessment for Salalah Port Customs
            $shipment2 = ImportShipment::firstOrCreate([
                'shipment_ref' => 'SHIP-2026-002',
            ], [
                'po_id' => PurchaseOrder::first()?->po_id ?? 1,
                'bl_container_no' => 'CMAU7718291',
                'port_of_entry' => 'Port of Salalah Customs Station',
                'bayan_declaration_no' => 'BAYAN-2026-91204',
                'stage' => 'RECEIVED',
                'eta_date' => '2026-02-15',
                'actual_arrival_date' => '2026-02-17',
            ]);

            $assessment2 = DutyAssessment::firstOrCreate([
                'shipment_id' => $shipment2->shipment_id,
            ], [
                'cif_value' => 312.500,
                'duty_rate_percent' => 100.000,
                'duty_amount' => 312.500,
                'other_charges' => 25.000,
                'total_landed_cost' => 650.000,
                'assessed_by' => $admin?->user_id,
                'assessed_date' => '2026-02-20',
            ]);

            $conversion2 = DutyPaidConversion::create([
                'conversion_number' => 'DPC-2026-0002',
                'warehouse_id' => $whSllMain->warehouse_id,
                'conversion_date' => Carbon::parse('2026-02-24'),
                'assessment_id' => $assessment2->assessment_id,
                'status' => 'POSTED',
                'approved_by' => $admin?->user_id,
            ]);

            $dpBatch2 = StockBatch::firstOrCreate([
                'batch_number' => 'BATCH-SLL-JW-DP',
                'product_id' => $jwProduct->product_id,
            ], [
                'manufacture_date' => '2025-12-01',
                'expiry_date' => '2030-12-01',
                'duty_status' => 'DUTY_PAID',
            ]);

            DutyPaidConversionItem::create([
                'conversion_id' => $conversion2->conversion_id,
                'batch_id' => $bondedBatchSll->batch_id,
                'quantity_converted' => 50.00,
                'duty_paid_unit_cost' => 6.250,
            ]);

            // Stock movements
            $stockLedger->deductStock(
                warehouseId: $whSllDepot->warehouse_id,
                productId: $jwProduct->product_id,
                batchId: $bondedBatchSll->batch_id,
                quantity: 50.00,
                fromReserved: false,
                saleType: null,
                meta: [
                    'movement_type' => 'TRANSFER_OUT',
                    'reference_type' => 'duty_paid_conversion',
                    'reference_id' => $conversion2->conversion_id,
                    'reference_no' => 'DPC-2026-0002',
                    'notes' => 'Duty-paid conversion transfer to Salalah Main Warehouse',
                    'user_id' => $admin?->user_id,
                ]
            );

            $stockLedger->recordIntake(
                warehouseId: $whSllMain->warehouse_id,
                productId: $jwProduct->product_id,
                batchId: $dpBatch2->batch_id,
                quantity: 50.00,
                meta: [
                    'movement_type' => 'TRANSFER_IN',
                    'reference_type' => 'duty_paid_conversion',
                    'reference_id' => $conversion2->conversion_id,
                    'reference_no' => 'DPC-2026-0002',
                    'notes' => 'Duty-paid stock received from Salalah Freezone Depot',
                    'user_id' => $admin?->user_id,
                ]
            );

            AuditLog::create([
                'user_id' => $admin?->user_id,
                'module_name' => 'Warehouse & Inventory',
                'action' => 'DUTY_PAID_CONVERSION',
                'record_table' => 'duty_paid_conversions',
                'record_id' => $conversion2->conversion_id,
                'details_json' => [
                    'conversion_number' => 'DPC-2026-0002',
                    'source_warehouse' => $whSllDepot->warehouse_code,
                    'destination_warehouse' => $whSllMain->warehouse_code,
                    'quantity' => 50.00,
                    'total_duty' => 312.500,
                    'bayan_no' => 'BAYAN-2026-91204',
                ],
                'ip_address' => '127.0.0.1',
            ]);
        }

        // =========================================================================
        // Conversion 3: DPC-2026-0003
        // Sohar Terminal: Convert 100 packs Heineken Lager from WH-SOH-BOND to WH-SOH-MAIN
        // Status: POSTED | Customs: BAYAN-2026-93450
        // =========================================================================
        if (! DutyPaidConversion::where('conversion_number', 'DPC-2026-0003')->exists() && $whSohBond && $whSohMain && $heinekenProduct) {
            $bondedBatchSoh = StockBatch::firstOrCreate([
                'batch_number' => 'BATCH-SOH-HEIN-BOND',
                'product_id' => $heinekenProduct->product_id,
            ], [
                'manufacture_date' => '2026-01-01',
                'expiry_date' => '2028-01-01',
                'duty_status' => 'DUTY_FREE_BONDED',
            ]);

            // Ensure stock in WH-SOH-BOND
            $stockSoh = WarehouseStock::where('warehouse_id', $whSohBond->warehouse_id)
                ->where('batch_id', $bondedBatchSoh->batch_id)
                ->first();
            if (! $stockSoh || (float) $stockSoh->quantity_available < 100.0) {
                $stockLedger->recordIntake(
                    warehouseId: $whSohBond->warehouse_id,
                    productId: $heinekenProduct->product_id,
                    batchId: $bondedBatchSoh->batch_id,
                    quantity: 100.0,
                    meta: [
                        'movement_type' => 'INTAKE_GRN',
                        'notes' => 'Opening intake for Sohar bonded terminal',
                        'user_id' => $admin?->user_id,
                    ]
                );
            }

            $shipment3 = ImportShipment::firstOrCreate([
                'shipment_ref' => 'SHIP-2026-003',
            ], [
                'po_id' => PurchaseOrder::first()?->po_id ?? 1,
                'bl_container_no' => 'HLCU1092834',
                'port_of_entry' => 'Sohar Port & Freezone Customs Station',
                'bayan_declaration_no' => 'BAYAN-2026-93450',
                'stage' => 'RECEIVED',
                'eta_date' => '2026-02-28',
                'actual_arrival_date' => '2026-03-01',
            ]);

            $assessment3 = DutyAssessment::firstOrCreate([
                'shipment_id' => $shipment3->shipment_id,
            ], [
                'cif_value' => 480.000,
                'duty_rate_percent' => 100.000,
                'duty_amount' => 480.000,
                'other_charges' => 30.000,
                'total_landed_cost' => 990.000,
                'assessed_by' => $admin?->user_id,
                'assessed_date' => '2026-03-03',
            ]);

            $conversion3 = DutyPaidConversion::create([
                'conversion_number' => 'DPC-2026-0003',
                'warehouse_id' => $whSohMain->warehouse_id,
                'conversion_date' => Carbon::parse('2026-03-05'),
                'assessment_id' => $assessment3->assessment_id,
                'status' => 'POSTED',
                'approved_by' => $admin?->user_id,
            ]);

            $dpBatch3 = StockBatch::firstOrCreate([
                'batch_number' => 'BATCH-SOH-HEIN-DP',
                'product_id' => $heinekenProduct->product_id,
            ], [
                'manufacture_date' => '2026-01-01',
                'expiry_date' => '2028-01-01',
                'duty_status' => 'DUTY_PAID',
            ]);

            DutyPaidConversionItem::create([
                'conversion_id' => $conversion3->conversion_id,
                'batch_id' => $bondedBatchSoh->batch_id,
                'quantity_converted' => 100.00,
                'duty_paid_unit_cost' => 4.800,
            ]);

            $stockLedger->deductStock(
                warehouseId: $whSohBond->warehouse_id,
                productId: $heinekenProduct->product_id,
                batchId: $bondedBatchSoh->batch_id,
                quantity: 100.00,
                fromReserved: false,
                saleType: null,
                meta: [
                    'movement_type' => 'TRANSFER_OUT',
                    'reference_type' => 'duty_paid_conversion',
                    'reference_id' => $conversion3->conversion_id,
                    'reference_no' => 'DPC-2026-0003',
                    'notes' => 'Duty-paid clearance transfer to Sohar Main Warehouse',
                    'user_id' => $admin?->user_id,
                ]
            );

            $stockLedger->recordIntake(
                warehouseId: $whSohMain->warehouse_id,
                productId: $heinekenProduct->product_id,
                batchId: $dpBatch3->batch_id,
                quantity: 100.00,
                meta: [
                    'movement_type' => 'TRANSFER_IN',
                    'reference_type' => 'duty_paid_conversion',
                    'reference_id' => $conversion3->conversion_id,
                    'reference_no' => 'DPC-2026-0003',
                    'notes' => 'Duty-paid stock received from Sohar Bonded Terminal',
                    'user_id' => $admin?->user_id,
                ]
            );

            AuditLog::create([
                'user_id' => $admin?->user_id,
                'module_name' => 'Warehouse & Inventory',
                'action' => 'DUTY_PAID_CONVERSION',
                'record_table' => 'duty_paid_conversions',
                'record_id' => $conversion3->conversion_id,
                'details_json' => [
                    'conversion_number' => 'DPC-2026-0003',
                    'source_warehouse' => $whSohBond->warehouse_code,
                    'destination_warehouse' => $whSohMain->warehouse_code,
                    'quantity' => 100.00,
                    'total_duty' => 480.000,
                    'bayan_no' => 'BAYAN-2026-93450',
                ],
                'ip_address' => '127.0.0.1',
            ]);
        }

        // =========================================================================
        // Conversion 4: DPC-2026-0004
        // Nizwa Regional: Convert 40 bottles Chivas Regal 12YO from WH-MCT-BOND to WH-NZW-MAIN
        // Status: POSTED | Customs: BAYAN-2026-95620
        // =========================================================================
        if (! DutyPaidConversion::where('conversion_number', 'DPC-2026-0004')->exists() && $whMctBond && $whNzwMain && $chivasProduct) {
            $bondedBatchChivas = StockBatch::firstOrCreate([
                'batch_number' => 'BATCH-MCT-CHIV-BOND',
                'product_id' => $chivasProduct->product_id,
            ], [
                'manufacture_date' => '2025-08-01',
                'expiry_date' => '2030-08-01',
                'duty_status' => 'DUTY_FREE_BONDED',
            ]);

            // Ensure bonded stock exists in WH-MCT-BOND
            $stockChiv = WarehouseStock::where('warehouse_id', $whMctBond->warehouse_id)
                ->where('batch_id', $bondedBatchChivas->batch_id)
                ->first();
            if (! $stockChiv || (float) $stockChiv->quantity_available < 40.0) {
                $stockLedger->recordIntake(
                    warehouseId: $whMctBond->warehouse_id,
                    productId: $chivasProduct->product_id,
                    batchId: $bondedBatchChivas->batch_id,
                    quantity: 40.0,
                    meta: [
                        'movement_type' => 'INTAKE_GRN',
                        'notes' => 'Opening intake for Chivas Regal bonded inventory',
                        'user_id' => $admin?->user_id,
                    ]
                );
            }

            $shipment4 = ImportShipment::firstOrCreate([
                'shipment_ref' => 'SHIP-2026-004',
            ], [
                'po_id' => PurchaseOrder::first()?->po_id ?? 1,
                'bl_container_no' => 'AWB-992-108234',
                'port_of_entry' => 'Muscat International Airport Air Cargo Customs',
                'bayan_declaration_no' => 'BAYAN-2026-95620',
                'stage' => 'RECEIVED',
                'eta_date' => '2026-03-08',
                'actual_arrival_date' => '2026-03-09',
            ]);

            $assessment4 = DutyAssessment::firstOrCreate([
                'shipment_id' => $shipment4->shipment_id,
            ], [
                'cif_value' => 300.000,
                'duty_rate_percent' => 100.000,
                'duty_amount' => 300.000,
                'other_charges' => 20.000,
                'total_landed_cost' => 620.000,
                'assessed_by' => $admin?->user_id,
                'assessed_date' => '2026-03-10',
            ]);

            $conversion4 = DutyPaidConversion::create([
                'conversion_number' => 'DPC-2026-0004',
                'warehouse_id' => $whNzwMain->warehouse_id,
                'conversion_date' => Carbon::parse('2026-03-12'),
                'assessment_id' => $assessment4->assessment_id,
                'status' => 'POSTED',
                'approved_by' => $admin?->user_id,
            ]);

            $dpBatchChivas = StockBatch::firstOrCreate([
                'batch_number' => 'BATCH-NZW-CHIV-DP',
                'product_id' => $chivasProduct->product_id,
            ], [
                'manufacture_date' => '2025-08-01',
                'expiry_date' => '2030-08-01',
                'duty_status' => 'DUTY_PAID',
            ]);

            DutyPaidConversionItem::create([
                'conversion_id' => $conversion4->conversion_id,
                'batch_id' => $bondedBatchChivas->batch_id,
                'quantity_converted' => 40.00,
                'duty_paid_unit_cost' => 7.500,
            ]);

            $stockLedger->deductStock(
                warehouseId: $whMctBond->warehouse_id,
                productId: $chivasProduct->product_id,
                batchId: $bondedBatchChivas->batch_id,
                quantity: 40.0,
                fromReserved: false,
                saleType: null,
                meta: [
                    'movement_type' => 'TRANSFER_OUT',
                    'reference_type' => 'duty_paid_conversion',
                    'reference_id' => $conversion4->conversion_id,
                    'reference_no' => 'DPC-2026-0004',
                    'notes' => 'Duty-paid conversion transfer to Nizwa Main Depot',
                    'user_id' => $admin?->user_id,
                ]
            );

            $stockLedger->recordIntake(
                warehouseId: $whNzwMain->warehouse_id,
                productId: $chivasProduct->product_id,
                batchId: $dpBatchChivas->batch_id,
                quantity: 40.0,
                meta: [
                    'movement_type' => 'TRANSFER_IN',
                    'reference_type' => 'duty_paid_conversion',
                    'reference_id' => $conversion4->conversion_id,
                    'reference_no' => 'DPC-2026-0004',
                    'notes' => 'Duty-paid stock received at Nizwa Regional Depot',
                    'user_id' => $admin?->user_id,
                ]
            );

            AuditLog::create([
                'user_id' => $admin?->user_id,
                'module_name' => 'Warehouse & Inventory',
                'action' => 'DUTY_PAID_CONVERSION',
                'record_table' => 'duty_paid_conversions',
                'record_id' => $conversion4->conversion_id,
                'details_json' => [
                    'conversion_number' => 'DPC-2026-0004',
                    'source_warehouse' => $whMctBond->warehouse_code,
                    'destination_warehouse' => $whNzwMain->warehouse_code,
                    'quantity' => 40.00,
                    'total_duty' => 300.000,
                    'bayan_no' => 'BAYAN-2026-95620',
                ],
                'ip_address' => '127.0.0.1',
            ]);
        }

        // =========================================================================
        // Conversion 5: DPC-2026-0005
        // Muscat Central: Hennessy VSOP Cognac (Clearance Approved, Ready for Dispatch)
        // Status: APPROVED | Customs: BAYAN-2026-97810
        // =========================================================================
        if (! DutyPaidConversion::where('conversion_number', 'DPC-2026-0005')->exists() && $whMctBond && $whMctMain && $hennessyProduct) {
            $bondedBatchHen = StockBatch::firstOrCreate([
                'batch_number' => 'BATCH-MCT-HEN-BOND',
                'product_id' => $hennessyProduct->product_id,
            ], [
                'manufacture_date' => '2025-05-01',
                'expiry_date' => '2030-05-01',
                'duty_status' => 'DUTY_FREE_BONDED',
            ]);

            $stockHen = WarehouseStock::where('warehouse_id', $whMctBond->warehouse_id)
                ->where('batch_id', $bondedBatchHen->batch_id)
                ->first();
            if (! $stockHen || (float) $stockHen->quantity_available < 30.0) {
                $stockLedger->recordIntake(
                    warehouseId: $whMctBond->warehouse_id,
                    productId: $hennessyProduct->product_id,
                    batchId: $bondedBatchHen->batch_id,
                    quantity: 30.0,
                    meta: [
                        'movement_type' => 'INTAKE_GRN',
                        'notes' => 'Opening intake for Hennessy VSOP bonded batch',
                        'user_id' => $admin?->user_id,
                    ]
                );
            }

            $shipment5 = ImportShipment::firstOrCreate([
                'shipment_ref' => 'SHIP-2026-005',
            ], [
                'po_id' => PurchaseOrder::first()?->po_id ?? 1,
                'bl_container_no' => 'MEDU9912048',
                'port_of_entry' => 'Port Sultan Qaboos Customs Inspection Area',
                'bayan_declaration_no' => 'BAYAN-2026-97810',
                'stage' => 'CUSTOMS_CLEARANCE',
                'eta_date' => '2026-03-15',
                'actual_arrival_date' => '2026-03-16',
            ]);

            $assessment5 = DutyAssessment::firstOrCreate([
                'shipment_id' => $shipment5->shipment_id,
            ], [
                'cif_value' => 360.000,
                'duty_rate_percent' => 100.000,
                'duty_amount' => 360.000,
                'other_charges' => 25.000,
                'total_landed_cost' => 745.000,
                'assessed_by' => $admin?->user_id,
                'assessed_date' => '2026-03-18',
            ]);

            $conversion5 = DutyPaidConversion::create([
                'conversion_number' => 'DPC-2026-0005',
                'warehouse_id' => $whMctMain->warehouse_id,
                'conversion_date' => Carbon::parse('2026-03-20'),
                'assessment_id' => $assessment5->assessment_id,
                'status' => 'APPROVED',
                'approved_by' => $admin?->user_id,
            ]);

            DutyPaidConversionItem::create([
                'conversion_id' => $conversion5->conversion_id,
                'batch_id' => $bondedBatchHen->batch_id,
                'quantity_converted' => 30.00,
                'duty_paid_unit_cost' => 12.000,
            ]);

            AuditLog::create([
                'user_id' => $admin?->user_id,
                'module_name' => 'Warehouse & Inventory',
                'action' => 'DUTY_PAID_CONVERSION_APPROVED',
                'record_table' => 'duty_paid_conversions',
                'record_id' => $conversion5->conversion_id,
                'details_json' => [
                    'conversion_number' => 'DPC-2026-0005',
                    'quantity' => 30.00,
                    'total_duty' => 360.000,
                    'bayan_no' => 'BAYAN-2026-97810',
                    'status' => 'APPROVED',
                ],
                'ip_address' => '127.0.0.1',
            ]);
        }

        // =========================================================================
        // Conversion 6: DPC-2026-0006
        // Salalah Freezone: Heineken Lager (Draft Request Awaiting Bayan Assessment)
        // Status: DRAFT | Customs: BAYAN-2026-99150
        // =========================================================================
        if (! DutyPaidConversion::where('conversion_number', 'DPC-2026-0006')->exists() && $whSllDepot && $whSllMain && $heinekenProduct) {
            $bondedBatchHeinSll = StockBatch::firstOrCreate([
                'batch_number' => 'BATCH-SLL-HEIN-BOND',
                'product_id' => $heinekenProduct->product_id,
            ], [
                'manufacture_date' => '2026-02-01',
                'expiry_date' => '2028-02-01',
                'duty_status' => 'DUTY_FREE_BONDED',
            ]);

            $stockHeinSll = WarehouseStock::where('warehouse_id', $whSllDepot->warehouse_id)
                ->where('batch_id', $bondedBatchHeinSll->batch_id)
                ->first();
            if (! $stockHeinSll || (float) $stockHeinSll->quantity_available < 40.0) {
                $stockLedger->recordIntake(
                    warehouseId: $whSllDepot->warehouse_id,
                    productId: $heinekenProduct->product_id,
                    batchId: $bondedBatchHeinSll->batch_id,
                    quantity: 40.0,
                    meta: [
                        'movement_type' => 'INTAKE_GRN',
                        'notes' => 'Opening intake for Heineken in Salalah Freezone',
                        'user_id' => $admin?->user_id,
                    ]
                );
            }

            $shipment6 = ImportShipment::firstOrCreate([
                'shipment_ref' => 'SHIP-2026-006',
            ], [
                'po_id' => PurchaseOrder::first()?->po_id ?? 1,
                'bl_container_no' => 'MSCU8810294',
                'port_of_entry' => 'Salalah Freezone Customs Clearance Station',
                'bayan_declaration_no' => 'BAYAN-2026-99150',
                'stage' => 'CUSTOMS_CLEARANCE',
                'eta_date' => '2026-03-22',
                'actual_arrival_date' => '2026-03-24',
            ]);

            $assessment6 = DutyAssessment::firstOrCreate([
                'shipment_id' => $shipment6->shipment_id,
            ], [
                'cif_value' => 192.000,
                'duty_rate_percent' => 100.000,
                'duty_amount' => 192.000,
                'other_charges' => 15.000,
                'total_landed_cost' => 399.000,
                'assessed_by' => $admin?->user_id,
                'assessed_date' => '2026-03-25',
            ]);

            $conversion6 = DutyPaidConversion::create([
                'conversion_number' => 'DPC-2026-0006',
                'warehouse_id' => $whSllMain->warehouse_id,
                'conversion_date' => Carbon::parse('2026-03-26'),
                'assessment_id' => $assessment6->assessment_id,
                'status' => 'DRAFT',
                'approved_by' => null,
            ]);

            DutyPaidConversionItem::create([
                'conversion_id' => $conversion6->conversion_id,
                'batch_id' => $bondedBatchHeinSll->batch_id,
                'quantity_converted' => 40.00,
                'duty_paid_unit_cost' => 4.800,
            ]);

            AuditLog::create([
                'user_id' => $admin?->user_id,
                'module_name' => 'Warehouse & Inventory',
                'action' => 'DUTY_PAID_CONVERSION_DRAFT',
                'record_table' => 'duty_paid_conversions',
                'record_id' => $conversion6->conversion_id,
                'details_json' => [
                    'conversion_number' => 'DPC-2026-0006',
                    'quantity' => 40.00,
                    'total_duty' => 192.000,
                    'bayan_no' => 'BAYAN-2026-99150',
                    'status' => 'DRAFT',
                ],
                'ip_address' => '127.0.0.1',
            ]);
        }
    }
}
