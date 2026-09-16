<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Customer;
use App\Models\CustomerCreditTerm;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductPricingTier;
use App\Models\Supplier;
use App\Models\SupplierProduct;
use App\Models\TaxVatRate;
use App\Models\Uom;
use App\Models\UomConversion;
use Illuminate\Database\Seeder;

class MasterDataSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Product Categories
        $spirits = ProductCategory::create(['category_name' => 'Spirits']);
        $wine = ProductCategory::create(['category_name' => 'Wine']);
        $beer = ProductCategory::create(['category_name' => 'Beer']);
        $liqueurs = ProductCategory::create(['category_name' => 'Liqueurs']);

        $whisky = ProductCategory::create(['category_name' => 'Scotch Whisky', 'parent_category_id' => $spirits->category_id]);
        $vodka = ProductCategory::create(['category_name' => 'Vodka', 'parent_category_id' => $spirits->category_id]);
        $cognac = ProductCategory::create(['category_name' => 'Cognac & Brandy', 'parent_category_id' => $spirits->category_id]);

        // 2. Brands
        $jw = Brand::create(['brand_name' => 'Johnnie Walker', 'country_of_origin' => 'Scotland']);
        $chivas = Brand::create(['brand_name' => 'Chivas Regal', 'country_of_origin' => 'Scotland']);
        $heineken = Brand::create(['brand_name' => 'Heineken', 'country_of_origin' => 'Netherlands']);
        $moet = Brand::create(['brand_name' => 'Moët & Chandon', 'country_of_origin' => 'France']);
        $smirnoff = Brand::create(['brand_name' => 'Smirnoff', 'country_of_origin' => 'United Kingdom']);
        $hennessy = Brand::create(['brand_name' => 'Hennessy', 'country_of_origin' => 'France']);

        // 3. UOM
        $btl = Uom::create(['uom_code' => 'BTL', 'uom_name' => 'Bottle']);
        $cse = Uom::create(['uom_code' => 'CSE', 'uom_name' => 'Case']);
        $can = Uom::create(['uom_code' => 'CAN', 'uom_name' => 'Can']);
        $keg = Uom::create(['uom_code' => 'KEG', 'uom_name' => 'Keg']);
        $ltr = Uom::create(['uom_code' => 'LTR', 'uom_name' => 'Litre']);

        // 4. Tax VAT Rates
        $vat5 = TaxVatRate::create([
            'tax_name' => 'Standard VAT 5%',
            'tax_type' => 'VAT',
            'rate_percent' => 5.000,
            'effective_from' => '2021-04-16',
            'is_active' => true,
        ]);
        $excise100 = TaxVatRate::create([
            'tax_name' => 'Alcohol Excise Tax 100%',
            'tax_type' => 'EXCISE',
            'rate_percent' => 100.000,
            'effective_from' => '2019-06-15',
            'is_active' => true,
        ]);
        $customs100 = TaxVatRate::create([
            'tax_name' => 'Liquor Customs Duty 100%',
            'tax_type' => 'CUSTOMS_DUTY',
            'rate_percent' => 100.000,
            'effective_from' => '2020-01-01',
            'is_active' => true,
        ]);

        // 5. Products
        $p1 = Product::create([
            'sku_code' => 'SKU-JW-BLK-750',
            'barcode' => '5000267014005',
            'product_name' => 'Johnnie Walker Black Label 12YO 750ml',
            'category_id' => $whisky->category_id,
            'brand_id' => $jw->brand_id,
            'base_uom_id' => $btl->uom_id,
            'tax_rate_id' => $vat5->tax_rate_id,
            'hs_code' => '2208.30.00',
            'pack_size' => '12 x 750ml',
            'reorder_level' => 100.00,
            'is_duty_free_eligible' => true,
            'image_path' => null,
            'is_active' => true,
        ]);

        $p2 = Product::create([
            'sku_code' => 'SKU-HEIN-CAN-330',
            'barcode' => '8712000023456',
            'product_name' => 'Heineken Lager Can 330ml 24-Pack',
            'category_id' => $beer->category_id,
            'brand_id' => $heineken->brand_id,
            'base_uom_id' => $cse->uom_id,
            'tax_rate_id' => $vat5->tax_rate_id,
            'hs_code' => '2203.00.00',
            'pack_size' => '24 x 330ml',
            'reorder_level' => 200.00,
            'is_duty_free_eligible' => true,
            'image_path' => null,
            'is_active' => true,
        ]);

        $p3 = Product::create([
            'sku_code' => 'SKU-CHIV-12-1L',
            'barcode' => '5000299000123',
            'product_name' => 'Chivas Regal 12YO Blended Scotch 1L',
            'category_id' => $whisky->category_id,
            'brand_id' => $chivas->brand_id,
            'base_uom_id' => $btl->uom_id,
            'tax_rate_id' => $vat5->tax_rate_id,
            'hs_code' => '2208.30.00',
            'pack_size' => '12 x 1L',
            'reorder_level' => 50.00,
            'is_duty_free_eligible' => true,
            'image_path' => null,
            'is_active' => true,
        ]);

        $p4 = Product::create([
            'sku_code' => 'SKU-HEN-VSOP-700',
            'barcode' => '3245900001122',
            'product_name' => 'Hennessy VSOP Cognac 700ml',
            'category_id' => $cognac->category_id,
            'brand_id' => $hennessy->brand_id,
            'base_uom_id' => $btl->uom_id,
            'tax_rate_id' => $vat5->tax_rate_id,
            'hs_code' => '2208.20.00',
            'pack_size' => '6 x 700ml',
            'reorder_level' => 30.00,
            'is_duty_free_eligible' => true,
            'image_path' => null,
            'is_active' => true,
        ]);

        // 6. UOM Conversions
        UomConversion::create([
            'product_id' => $p1->product_id,
            'from_uom_id' => $cse->uom_id,
            'to_uom_id' => $btl->uom_id,
            'conversion_factor' => 12.0000,
        ]);
        UomConversion::create([
            'product_id' => $p3->product_id,
            'from_uom_id' => $cse->uom_id,
            'to_uom_id' => $btl->uom_id,
            'conversion_factor' => 12.0000,
        ]);

        // 7. Product Pricing Tiers
        ProductPricingTier::create([
            'product_id' => $p1->product_id,
            'price_tier' => 'RETAIL',
            'min_quantity' => 1,
            'unit_price' => 18.500,
            'effective_from' => '2026-01-01',
            'effective_to' => null,
        ]);
        ProductPricingTier::create([
            'product_id' => $p1->product_id,
            'price_tier' => 'WHOLESALE',
            'min_quantity' => 12,
            'unit_price' => 15.200,
            'effective_from' => '2026-01-01',
            'effective_to' => null,
        ]);
        ProductPricingTier::create([
            'product_id' => $p1->product_id,
            'price_tier' => 'HARBOR',
            'min_quantity' => 1,
            'unit_price' => 9.800,
            'effective_from' => '2026-01-01',
            'effective_to' => null,
        ]);

        ProductPricingTier::create([
            'product_id' => $p2->product_id,
            'price_tier' => 'WHOLESALE',
            'min_quantity' => 5,
            'unit_price' => 14.500,
            'effective_from' => '2026-01-01',
            'effective_to' => null,
        ]);

        // 8. Suppliers
        $sup1 = Supplier::create([
            'supplier_code' => 'SUP-DIAGEO-UK',
            'supplier_name' => 'Diageo Brands Trading International Ltd',
            'country' => 'United Kingdom',
            'currency_code' => 'GBP',
            'payment_terms_days' => 60,
            'import_license_no' => 'LIC-UK-DIAGEO-99',
            'is_active' => true,
        ]);

        $sup2 = Supplier::create([
            'supplier_code' => 'SUP-PERNOD-FR',
            'supplier_name' => 'Pernod Ricard Europe SA',
            'country' => 'France',
            'currency_code' => 'EUR',
            'payment_terms_days' => 45,
            'import_license_no' => 'LIC-FR-PERNOD-44',
            'is_active' => true,
        ]);

        $sup3 = Supplier::create([
            'supplier_code' => 'SUP-HEINEKEN-NL',
            'supplier_name' => 'Heineken Supply Chain B.V.',
            'country' => 'Netherlands',
            'currency_code' => 'EUR',
            'payment_terms_days' => 30,
            'import_license_no' => 'LIC-NL-HEIN-12',
            'is_active' => true,
        ]);

        // 9. Supplier Products
        SupplierProduct::create([
            'supplier_id' => $sup1->supplier_id,
            'product_id' => $p1->product_id,
            'supplier_sku' => 'DIAGEO-JW-BLK-75',
            'last_purchase_cost' => 6.250,
        ]);
        SupplierProduct::create([
            'supplier_id' => $sup2->supplier_id,
            'product_id' => $p3->product_id,
            'supplier_sku' => 'PERNOD-CHIV-12-1L',
            'last_purchase_cost' => 7.100,
        ]);
        SupplierProduct::create([
            'supplier_id' => $sup3->supplier_id,
            'product_id' => $p2->product_id,
            'supplier_sku' => 'HEIN-CAN-330-24',
            'last_purchase_cost' => 8.500,
        ]);

        // 10. Customers & Credit Terms
        $c1 = Customer::create([
            'customer_code' => 'CUST-HYATT-MCT',
            'customer_name' => 'Grand Hyatt Muscat',
            'customer_segment' => 'HORECA',
            'phone' => '+968 2464 1234',
            'email' => 'purchase@grandhyattmuscat.com',
            'oman_card_no' => 'CR-1092834',
            'is_active' => true,
        ]);
        CustomerCreditTerm::create([
            'customer_id' => $c1->customer_id,
            'credit_limit' => 50000.000,
            'payment_terms_days' => 30,
            'outstanding_balance' => 12450.000,
        ]);

        $c2 = Customer::create([
            'customer_code' => 'CUST-DUTYFREE-MCT',
            'customer_name' => 'Oman Duty Free Company SAOC',
            'customer_segment' => 'HARBOR',
            'phone' => '+968 2435 6000',
            'email' => 'imports@omandutyfree.com',
            'oman_card_no' => 'CR-2049182',
            'is_active' => true,
        ]);
        CustomerCreditTerm::create([
            'customer_id' => $c2->customer_id,
            'credit_limit' => 120000.000,
            'payment_terms_days' => 60,
            'outstanding_balance' => 34200.000,
        ]);

        $c3 = Customer::create([
            'customer_code' => 'CUST-INDIV-0082',
            'customer_name' => 'Alexander Wright (Liquor Permit #9821)',
            'customer_segment' => 'RETAIL',
            'phone' => '+968 9912 3456',
            'email' => 'alex.wright@omantel.net.om',
            'oman_card_no' => 'CIV-78192039',
            'is_active' => true,
        ]);
        CustomerCreditTerm::create([
            'customer_id' => $c3->customer_id,
            'credit_limit' => 0.000,
            'payment_terms_days' => 0,
            'outstanding_balance' => 0.000,
        ]);
    }
}
