<?php

namespace Tests\Feature;

use App\Livewire\MasterData\SupplierManagement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SupplierManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_currency_is_auto_selected_when_country_changes(): void
    {
        $user = User::where('email', 'admin@rmsc.om')->firstOrFail();

        Livewire::actingAs($user)
            ->test(SupplierManagement::class)
            ->call('openCreateModal')
            ->assertSet('currency_code', 'OMR')
            ->set('country', 'United Kingdom')
            ->assertSet('currency_code', 'GBP')
            ->set('country', 'France')
            ->assertSet('currency_code', 'EUR')
            ->set('country', 'United States')
            ->assertSet('currency_code', 'USD')
            ->set('country', 'Japan')
            ->assertSet('currency_code', 'JPY')
            ->set('country', 'United Arab Emirates')
            ->assertSet('currency_code', 'AED')
            ->set('country', 'Australia')
            ->assertSet('currency_code', 'AUD')
            ->set('country', 'Oman')
            ->assertSet('currency_code', 'OMR');
    }

    public function test_currency_is_auto_selected_for_custom_country(): void
    {
        $user = User::where('email', 'admin@rmsc.om')->firstOrFail();

        Livewire::actingAs($user)
            ->test(SupplierManagement::class)
            ->call('openCreateModal')
            ->set('country', 'ADD_NEW')
            ->assertSet('isCustomCountry', true)
            ->set('custom_country_name', 'Germany')
            ->assertSet('currency_code', 'EUR')
            ->set('custom_country_name', 'Canada')
            ->assertSet('currency_code', 'CAD');
    }

    public function test_supplier_saves_with_auto_selected_currency(): void
    {
        $user = User::where('email', 'admin@rmsc.om')->firstOrFail();

        Livewire::actingAs($user)
            ->test(SupplierManagement::class)
            ->call('openCreateModal')
            ->set('supplier_name', 'Tokyo Spirits Co.')
            ->set('country', 'Japan')
            ->assertSet('currency_code', 'JPY')
            ->call('saveSupplier')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('suppliers', [
            'supplier_name' => 'Tokyo Spirits Co.',
            'country' => 'Japan',
            'currency_code' => 'JPY',
        ]);
    }
}
