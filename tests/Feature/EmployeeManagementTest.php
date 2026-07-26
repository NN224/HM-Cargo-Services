<?php

namespace Tests\Feature;

use App\Enums\Capability;
use App\Enums\UserRole;
use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class EmployeeManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_administrator_can_access_users_screen(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Administrator]);

        $warehouse = Warehouse::create(['name' => 'Dubai', 'location' => 'UAE']);
        $employee = User::factory()->create([
            'role' => UserRole::WarehouseEmployee,
            'warehouse_id' => $warehouse->id,
        ]);

        $this->actingAs($employee)
            ->get(UserResource::getUrl('index'))
            ->assertForbidden();

        $this->actingAs($admin)
            ->get(UserResource::getUrl('index'))
            ->assertSuccessful();
    }

    public function test_can_list_users(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Administrator]);
        $user1 = User::factory()->create(['name' => 'Ahmad', 'role' => UserRole::Administrator]);

        $warehouse = Warehouse::create(['name' => 'Dubai', 'location' => 'UAE']);
        $user2 = User::factory()->create([
            'name' => 'Sara',
            'role' => UserRole::WarehouseEmployee,
            'warehouse_id' => $warehouse->id,
        ]);

        Livewire::actingAs($admin)
            ->test(ListUsers::class)
            ->assertCanSeeTableRecords([$user1, $user2])
            ->assertSee('Ahmad')
            ->assertSee('Sara');
    }

    public function test_can_create_warehouse_employee_with_capabilities(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Administrator]);
        $warehouse = Warehouse::create(['name' => 'Dubai', 'location' => 'UAE']);

        Livewire::actingAs($admin)
            ->test(CreateUser::class)
            ->fillForm([
                'name' => 'New Employee',
                'email' => 'emp@example.com',
                'password' => 'secret',
                'role' => UserRole::WarehouseEmployee->value,
                'warehouse_id' => $warehouse->id,
                'capability_'.Capability::RecordPayments->value => true,
                'capability_'.Capability::EditAfterDispatch->value => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('users', [
            'email' => 'emp@example.com',
            'role' => UserRole::WarehouseEmployee->value,
            'warehouse_id' => $warehouse->id,
        ]);

        $user = User::where('email', 'emp@example.com')->first();
        $this->assertTrue($user->hasCapability(Capability::RecordPayments));
        $this->assertTrue($user->hasCapability(Capability::EditAfterDispatch));
        $this->assertFalse($user->hasCapability(Capability::PriceShipments));
    }

    public function test_can_edit_user_and_change_capabilities(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Administrator]);
        $warehouse = Warehouse::create(['name' => 'Dubai', 'location' => 'UAE']);
        $employee = User::factory()->create([
            'role' => UserRole::WarehouseEmployee,
            'warehouse_id' => $warehouse->id,
            'capabilities' => [Capability::RecordPayments->value],
        ]);

        Livewire::actingAs($admin)
            ->test(EditUser::class, ['record' => $employee->id])
            ->assertFormSet([
                'capability_'.Capability::RecordPayments->value => true,
                'capability_'.Capability::PriceShipments->value => false,
            ])
            ->fillForm([
                'capability_'.Capability::RecordPayments->value => false,
                'capability_'.Capability::PriceShipments->value => true,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $employee->refresh();
        $this->assertFalse($employee->hasCapability(Capability::RecordPayments));
        $this->assertTrue($employee->hasCapability(Capability::PriceShipments));
    }
}
