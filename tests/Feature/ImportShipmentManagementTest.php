<?php

namespace Tests\Feature;

use App\Livewire\PurchaseImport\ImportShipmentManagement;
use App\Models\ImportShipment;
use App\Models\PurchaseOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ImportShipmentManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_import_shipments_screen_renders_with_metrics_and_table(): void
    {
        $user = User::where('email', 'admin@rmsc.om')->firstOrFail();

        Livewire::actingAs($user)
            ->test(ImportShipmentManagement::class)
            ->assertStatus(200)
            ->assertSee('Total Shipments')
            ->assertSee('SHIP-2026-001');
    }

    public function test_can_create_import_shipment_with_po_association(): void
    {
        $user = User::where('email', 'admin@rmsc.om')->firstOrFail();
        $po = PurchaseOrder::firstOrFail();

        Livewire::actingAs($user)
            ->test(ImportShipmentManagement::class)
            ->call('openCreateModal')
            ->set('po_id', (string)$po->po_id)
            ->set('bl_container_no', 'MEDU7788990')
            ->set('bayan_declaration_no', 'BAYAN-2026-99001')
            ->set('port_of_entry', 'Sohar Port Customs Station')
            ->set('eta_date', '2026-04-10')
            ->set('actual_arrival_date', '2026-04-12')
            ->set('stage', 'IN_TRANSIT')
            ->call('saveShipment')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('import_shipments', [
            'po_id' => $po->po_id,
            'bl_container_no' => 'MEDU7788990',
            'bayan_declaration_no' => 'BAYAN-2026-99001',
            'port_of_entry' => 'Sohar Port Customs Station',
            'stage' => 'IN_TRANSIT',
        ]);
    }

    public function test_actual_arrival_date_must_be_on_or_after_eta_date(): void
    {
        $user = User::where('email', 'admin@rmsc.om')->firstOrFail();
        $po = PurchaseOrder::firstOrFail();

        // Invalid: actual arrival before ETA
        Livewire::actingAs($user)
            ->test(ImportShipmentManagement::class)
            ->call('openCreateModal')
            ->set('po_id', (string)$po->po_id)
            ->set('eta_date', '2026-04-15')
            ->set('actual_arrival_date', '2026-04-10')
            ->call('saveShipment')
            ->assertHasErrors(['actual_arrival_date']);

        // Valid: actual arrival on or after ETA
        Livewire::actingAs($user)
            ->test(ImportShipmentManagement::class)
            ->call('openCreateModal')
            ->set('po_id', (string)$po->po_id)
            ->set('eta_date', '2026-04-15')
            ->set('actual_arrival_date', '2026-04-20')
            ->call('saveShipment')
            ->assertHasNoErrors();
    }

    public function test_can_advance_shipment_customs_stage(): void
    {
        $user = User::where('email', 'admin@rmsc.om')->firstOrFail();
        $po = PurchaseOrder::firstOrFail();

        $shipment = ImportShipment::create([
            'shipment_ref' => 'SHIP-2026-TEST-01',
            'po_id' => $po->po_id,
            'port_of_entry' => 'Sohar Port Customs Station',
            'stage' => 'ORDERED',
        ]);

        Livewire::actingAs($user)
            ->test(ImportShipmentManagement::class)
            ->call('advanceStage', $shipment->shipment_id);

        $this->assertEquals('IN_TRANSIT', $shipment->fresh()->stage);
    }

    public function test_can_view_shipment_details(): void
    {
        $user = User::where('email', 'admin@rmsc.om')->firstOrFail();
        $shipment = ImportShipment::firstOrFail();

        Livewire::actingAs($user)
            ->test(ImportShipmentManagement::class)
            ->call('viewShipment', $shipment->shipment_id)
            ->assertSet('showViewModal', true)
            ->assertSet('view_shipment_id', $shipment->shipment_id)
            ->assertSee($shipment->shipment_ref)
            ->assertSee('Oman Customs Bayan Declaration');
    }

    public function test_can_delete_unlinked_import_shipment(): void
    {
        $user = User::where('email', 'admin@rmsc.om')->firstOrFail();
        $po = PurchaseOrder::firstOrFail();

        $shipment = ImportShipment::create([
            'shipment_ref' => 'SHIP-2026-TEST-DEL',
            'po_id' => $po->po_id,
            'port_of_entry' => 'Sohar Port Customs Station',
            'stage' => 'ORDERED',
        ]);

        Livewire::actingAs($user)
            ->test(ImportShipmentManagement::class)
            ->call('confirmDeleteShipment', $shipment->shipment_id, $shipment->shipment_ref)
            ->call('executeDeleteItem');

        $this->assertDatabaseMissing('import_shipments', [
            'shipment_id' => $shipment->shipment_id,
        ]);
    }
}
