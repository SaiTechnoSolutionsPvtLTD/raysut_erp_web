<?php

namespace Tests\Feature;

use App\Livewire\PurchaseImport\DutyAssessmentManagement;
use App\Models\DutyAssessment;
use App\Models\ImportShipment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DutyAssessmentManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_duty_assessment_screen_renders_with_metrics_and_table(): void
    {
        $user = User::where('email', 'admin@rmsc.om')->firstOrFail();

        Livewire::actingAs($user)
            ->test(DutyAssessmentManagement::class)
            ->assertStatus(200)
            ->assertSee('Duty Assessment Register')
            ->assertSee('Total CIF Valuation')
            ->assertSee('Total Customs Duty');
    }

    public function test_can_create_new_duty_assessment_with_components(): void
    {
        $user = User::where('email', 'admin@rmsc.om')->firstOrFail();
        $shipment = ImportShipment::firstOrFail();

        Livewire::actingAs($user)
            ->test(DutyAssessmentManagement::class)
            ->call('openCreateModal')
            ->set('shipment_id', (string) $shipment->shipment_id)
            ->set('cif_value', '5000.000')
            ->set('duty_rate_percent', '100.0')
            ->set('cost_components', [
                ['charge_type' => 'Bayan Port Handling & Demurrage', 'amount' => '250.000'],
                ['charge_type' => 'Customs Clearance & Brokerage Fee', 'amount' => '100.000'],
            ])
            ->call('saveAssessment')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('duty_assessments', [
            'shipment_id' => $shipment->shipment_id,
            'cif_value' => 5000.000,
            'duty_rate_percent' => 100.000,
            'duty_amount' => 5000.000,
            'other_charges' => 350.000,
            'total_landed_cost' => 10350.000,
        ]);
    }

    public function test_can_view_duty_assessment_details(): void
    {
        $user = User::where('email', 'admin@rmsc.om')->firstOrFail();
        $assessment = DutyAssessment::firstOrFail();

        Livewire::actingAs($user)
            ->test(DutyAssessmentManagement::class)
            ->call('openViewModal', $assessment->assessment_id)
            ->assertSet('showViewModal', true)
            ->assertSet('view_assessment_id', $assessment->assessment_id)
            ->assertSee('#ASSESS-' . str_pad((string) $assessment->assessment_id, 4, '0', STR_PAD_LEFT));
    }

    public function test_can_edit_duty_assessment(): void
    {
        $user = User::where('email', 'admin@rmsc.om')->firstOrFail();
        $assessment = DutyAssessment::firstOrFail();

        Livewire::actingAs($user)
            ->test(DutyAssessmentManagement::class)
            ->call('openEditModal', $assessment->assessment_id)
            ->assertSet('isEdit', true)
            ->set('cif_value', '8000.000')
            ->set('duty_rate_percent', '50.0')
            ->call('saveAssessment')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('duty_assessments', [
            'assessment_id' => $assessment->assessment_id,
            'cif_value' => 8000.000,
            'duty_rate_percent' => 50.000,
            'duty_amount' => 4000.000,
        ]);
    }

    public function test_can_delete_duty_assessment(): void
    {
        $user = User::where('email', 'admin@rmsc.om')->firstOrFail();
        $assessment = DutyAssessment::firstOrFail();
        $id = $assessment->assessment_id;

        Livewire::actingAs($user)
            ->test(DutyAssessmentManagement::class)
            ->call('confirmDelete', $id)
            ->assertSet('showItemDeleteModal', true)
            ->call('executeDeleteItem')
            ->assertSet('showItemDeleteModal', false);

        $this->assertDatabaseMissing('duty_assessments', [
            'assessment_id' => $id,
        ]);
    }

    public function test_live_recalculation_on_adding_and_updating_cost_components(): void
    {
        $user = User::where('email', 'admin@rmsc.om')->firstOrFail();

        Livewire::actingAs($user)
            ->test(DutyAssessmentManagement::class)
            ->call('openCreateModal')
            ->set('cif_value', '1000.000')
            ->set('duty_rate_percent', '10.0')
            ->assertSet('duty_amount', '100.000')
            ->call('addCostComponent', 'Freight')
            ->set('cost_components.1.amount', '150.000')
            ->assertSet('other_charges', '300.000')
            ->assertSet('total_landed_cost', '1400.000');
    }

    public function test_clear_filters_resets_search_and_dates(): void
    {
        $user = User::where('email', 'admin@rmsc.om')->firstOrFail();

        Livewire::actingAs($user)
            ->test(DutyAssessmentManagement::class)
            ->set('search', 'BAYAN-88910')
            ->set('date_from', '2026-01-01')
            ->set('date_to', '2026-12-31')
            ->call('clearFilters')
            ->assertSet('search', '')
            ->assertSet('date_from', '')
            ->assertSet('date_to', '');
    }
}
