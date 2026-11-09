<?php

namespace Tests\Feature;

use App\Models\InventoryIntegredientCategory;
use App\Models\InventoryMeasurementUnit;
use App\Models\InventorySupplier;
use App\Models\Location;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryIngredientApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_can_create_an_inventory_ingredient_with_nested_supplier_and_location_data(): void
    {
        $user = User::factory()->create();
        $measurementUnit = InventoryMeasurementUnit::create(['name' => 'Kg']);
        $category = InventoryIntegredientCategory::create([
            'name' => 'Syrups',
            'description' => 'Sweeteners',
            'is_active' => true,
        ]);
        $supplier = InventorySupplier::create([
            'supplier_code' => 'SUP-001',
            'name' => 'Fresh Foods',
            'email' => 'supplier@example.com',
            'phone' => '123456789',
            'is_active' => true,
        ]);
        $location = Location::create([
            'name' => 'Main Kitchen',
            'code' => 'K1',
            'address' => 'Street 1',
            'phone' => '987654321',
            'status' => true,
        ]);

        $payload = [
            'ingredient_type' => 'ingredient',
            'name' => 'Blueberry Syrup',
            'sku' => '10017',
            'description' => 'House syrup',
            'category_id' => $category->id,
            'measurement_unit_id' => $measurementUnit->id,
            'buying_unit_name' => 'Bag',
            'has_sub_unit' => true,
            'buying_unit_quantity' => 500,
            'net_quantity' => 300,
            'last_buying_price' => 10.0,
            'is_active' => true,
            'suppliers' => [
                [
                    'supplier_id' => $supplier->id,
                    'price' => 10.0,
                    'buying_unit' => 'Box',
                    'has_sub_unit' => true,
                    'sub_unit_name' => 'Bottle',
                    'unit_to_sub_unit_quantity' => 500,
                    'sub_unit_to_base_quantity' => 350,
                    'net_quantity' => 300,
                    'is_default' => true,
                ],
            ],
            'locations' => [
                [
                    'location_id' => $location->id,
                    'opening_balance' => 0,
                    'reorder_level' => 10,
                    'track_inventory' => true,
                ],
            ],
        ];

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/inventory-ingredients', $payload);

        $response->assertCreated();
        $response->assertJsonPath('data.name', 'Blueberry Syrup');
        $response->assertJsonPath('data.sku', '10017');
        $response->assertJsonPath('data.suppliers.0.supplier_id', $supplier->id);
        $response->assertJsonPath('data.locations.0.location_id', $location->id);
    }
}
